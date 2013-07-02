<?php

namespace CuteControllers;

require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Internal', 'require.php']));

/**
 * Performs routing for a web request.
 *
 * @author      Tyler Menezes <tylermenezes@gmail.com>
 * @copyright   Copyright (c) Tyler Menezes. Released under the BSD license.
 *
 * @package     CuteControllers
 */
class Router
{
    const default_file_name = 'index';

    protected static $instance = null;
    /**
     * Begins routing
     * @param  string  $controllers_directory The directory to load controllers from
     * @param  Request $request               The request to route for
     */
    public static function start($controllers_directory = null, $request = null)
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        static::$instance->controllers_directory = $controllers_directory;
        static::$instance->route($request);
    }

    /**
     * Registers a rewrite rule
     * @param  string $from Regex to match on
     * @param  string $to   Replacement (can include capture group references)
     */
    public static function rewrite($from, $to)
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        static::$instance->add_rewrite($from, $to);
    }

    /**
     * Gets an absolute URL given a HTML-style link
     * @param  string $uri HTML-style link
     * @return string      Absolute URL
     */
    public static function link($uri)
    {
        $request = Request::current();
        if (substr($uri, 0, 2) === '//') { // Is it a protocol-relative URL?
            return $request->scheme . ':' . $uri;
        } else if (strpos($uri, '://') !== false) { // Is it a fully-qualified URL already?
            return $uri;
        } else if (substr($uri, 0, 1) === '/') { // Is it relative to the domain?
            return $request->scheme . '://' . $request->host . $uri;
        } else if (substr($uri, 0, 1) === '?') { // Is it a query-string?
            return $request->scheme . '://' . $request->host . $request->path . $uri;
        } else { // It's relative to the current page

            // Remove the file component of the path
            $path_parts = explode('/', $request->path);
            array_pop($path_parts);
            $path = implode('/', $request->path);

            return $request->scheme . '://' . $request->host . $path . $uri;
        }
    }

    /**
     * Redirects the user to a new page and terminates execution
     * @param  string  $uri         Page to redirect to
     * @param  [int]   $status_code Status code, defaults to 302
     */
    public static function redirect($uri, $status_code = 302)
    {
        $codes = [
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            307 => "Temporary Redirect"
        ];

        if (!array_key_exists($status_code, $codes)) {
            throw new \InvalidArgumentException('Status code must be a 3xx code for redirects.');
        }

        header("HTTP/1.1 " . $status_code . ' ' . $codes[$status_code]);
        header("Location: " . self::link($uri));
        exit;
    }


    protected $rewrites = [];
    protected $controllers_directory = null;

    public function __construct($controllers_directory = null)
    {
        if ($controllers_directory !== null) {
            $this->controllers_directory = $controllers_directory;
        }
    }

    /**
     * Begins routing
     * @param  Request $request The request to route for
     */
    public function route($request = null)
    {
        // If the path to the controllers directory wasn't set, throw an exception
        if ($this->controllers_directory === null) {
            throw new \BadFunctionCallException('Must set a controllers directory before calling start.');
        }

        if ($request === null) {
            $request = Request::current();
        }
        $responsible_controller = $this->get_responsible_controller($request);
        $responsible_controller->cc_route();
    }

    /**
     * Gets the controller responsible for a request
     * @param  Request $request The request to find a controller for
     * @return object           Controller
     */
    public function get_responsible_controller($request = null)
    {
        if ($request === null) {
            $request = Request::current();
        }

        // Find the controller which should handle this
        // Remove the extension from the path, if one exists
        $args = array_merge(array_filter(explode('/', $request->path)));
        if (strpos($args[count($args)-1], '.') !== false) {
            $args[count($args)-1] = substr($args[count($args)-1], 0, strpos($args[count($args)-1], '.'));
        }

        // Find the best match
        $dir = $this->controllers_directory;
        $best_match = self::get_best_match($args, function($potential_match) use ($dir) {
            return file_exists($dir.DIRECTORY_SEPARATOR.$potential_match.'.php');
        });

        if ($best_match === null) {
            throw new HttpError(404);
        }

        $controller = static::get_class_from_path($dir.DIRECTORY_SEPARATOR.$best_match['path'].'.php');
        $controller->routing_information = (object)[
            'path' => $best_match['path'],
            'unmatched_path' => implode('/', array_slice($args, $best_match['count']))
        ];

        $controller->request = $request;

        return $controller;
    }

    /**
     * Registers a rewrite rule
     * @param  string $from Regex to match on
     * @param  string $to   Replacement (can include capture group references)
     */
    public function add_rewrite($from, $to)
    {
        $this->rewrites[] = ['from' => $from, 'to' => $to];
    }

    /**
     * Applies all registered rewrites
     * @param  Request $request Request to which to apply rewrites
     * @return Request          Result Request
     */
    protected function apply_rewrites(Request $request)
    {
        foreach ($this->rewrites as $rewrite)
        {
            $from = $rewrite['from'];
            $to = $rewrite['to'];

            $request->uri = preg_replace('/^'.str_replace('/', '\\/', $from).'$/i', $to, $request->uri);
        }

        return $request;
    }

    /**
     * Gets the best-matching controller match, given a match function
     *
     * @param $arguments        array       List of position-wise command-line arguments
     * @param $match_function   callable    Match function, taking a match and returning true if it's valid
     * @return mixed                        Best matching string, or null if no match was found
     */
    private static function get_best_match($arguments, $match_function)
    {
        foreach (self::get_potential_matches($arguments) as $match) {
            if ($match_function($match['path'])) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Gets a list of potential locations for the controllers.
     *
     * @param $arguments    array   The list of position-wise command-line arguments
     * @return array                List of potential locations from most to least specific, relative to nothing, without any extensions
     */
    private static function get_potential_matches($arguments)
    {
        if (count($arguments) === 0) {
            return [['path' => self::default_file_name, 'count' => 0]];
        }

        $potential_matches = [['path' => implode(DIRECTORY_SEPARATOR, $arguments) . DIRECTORY_SEPARATOR . self::default_file_name,
                               'count' => count($arguments)]];
        do {
            $potential_matches[] = ['path' => implode(DIRECTORY_SEPARATOR, $arguments), 'count' => count($arguments)];
            array_pop($arguments);
            $potential_matches[] = ['path' => implode(DIRECTORY_SEPARATOR, array_merge($arguments, [self::default_file_name])),
                                    'count' => count($arguments)];
        } while (count($arguments) > 0);

        return $potential_matches;
    }

    /**
     * Gets an instance of a class from its path
     * @param  string $path Path to the class
     * @return object       Instance of the object
     */
    protected static function get_class_from_path($path)
    {
        if (file_exists($path)) {
            try {
                include_once($path);
            } catch (\Exception $ex) {
                 throw new \InvalidArgumentException($path . ' did not exist.');
            }

                $controller_info = static::get_class_info_from_path($path);
                $controller_name = implode('\\', [$controller_info->namespace, $controller_info->class]);

                return new $controller_name();
        } else {
            throw new \InvalidArgumentException($path . ' did not exist.');
        }
    }

    /**
     * Gets the namespace and class name of a class from its file
     * @param  string $path Path to the file
     * @return object       Object containing details of the object in "namespace" and "class" properties.
     */
    protected static function get_class_info_from_path($path)
    {
        // This function reads in a file, uses PHP to lex it, and then processes those tokens in a very naive way. As
        // soon as it finds a class start definition, it closes the file, so there's not much unnecessary reading going
        // on, which is probably a silly optimization.
        //
        // Because it only parses up until the first class token, it's not suited for parsing anything after the first
        // class token. (A side effect of this is that it only returns the FIRST class in the file, not the last as one
        // might expect.) This method returns everything you need to know to create a reflector to get additional
        // information, though.
        //
        // Because namespaces cascade down into the class, we can just concat them until we hit a class token. This
        // doesn't deal with files of the form:
        //
        // <?php
        //     namespace red\herring { }
        //     namespace xyzzy {
        //         class plugh {}
        //     }
        //
        // -- the function would return namespace => red\herring\xyz
        //
        // But this really shouldn't be an issue for this sort of thing. The only non-hacky way to fix this is to build
        // an AST for the file, which is fairly overkill. (You might be thinking: "why not just pop off a stack on }?".
        // I did it this way at first, but realized it didn't actually solve anything, since anyone who put a function
        // in the namespace would cause the namespace stack to get double-popped. You could count the number of { not
        // associated with a T_NAMESPACE, and subtract to 0 before popping, but that gets pretty messy.)
        //
        // tl;dr: This function is magic.

        $fp = fopen($path, 'r');
        $class = $namespace = $buffer = null;
        $i = 0;

        while ($class === null) { // The namespace of the class can only be changed before the class is declared.

            // If our file pointer is at EOF, this file just isn't classy. Throw an exception, because what kind of
            // person asks for class information about a file without a class?!
            if (feof($fp)) {
                throw new \BadFunctionCallException('File ' . $path . ' did not contain a class');
            }


            $buffer .= fread($fp, 512); // Read some bytes into the buffer
            $tokens = token_get_all($buffer); // Turn the buffer into tokens


            // If we don't see a begin-bracket, we definitely haven't gotten to the class, and we probably haven't
            // gotten to the end of the namespace declaration. Go forward and read some more bytes into the buffer.
            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (/* $i is global */; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) { // If this is a namespace token, keep going
                    for ($j = $i+1; $j < count($tokens); $j++) { // If we're out of tokens, nothing to see here.
                        if ($tokens[$j][0] === T_STRING) { // If the token is a string, we're looking at a namespace!
                             $namespace .= '\\' . $tokens[$j][1]; // Add on to the namespace
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') { // Namespace change over!
                             break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) { // We've finally reached the beginning of a class def
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return (object)[
            'namespace' => $namespace,
            'class' => $class
        ];
    }
}
