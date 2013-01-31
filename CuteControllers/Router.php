<?php

namespace CuteControllers;

// For the .001% of people who are on PHP 5.4+ and not using an SPL Class Loader, load the files ourselves:
require_once('Request.php');
require_once('HttpError.php');
require_once('ControllerFileTrie.php');
require_once('Base/Controller.php');
require_once('Base/Web.php');
require_once('Base/Rest.php');
require_once('Base/RestCrud.php');

class Router
{
    protected static $instance = NULL;
    /**
     * Begins routing
     * @param  string  $controllers_directory The directory to load controllers from
     * @param  Request $request               The request to route for
     */
    public static function start($controllers_directory = NULL, $request = NULL)
    {
        if (static::$instance === NULL) {
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
        if (static::$instance === NULL) {
            static::$instance = new static();
        }

        static::$instance->add_rewrite($from, $to);
    }


    protected $rewrites = [];
    protected $controllers_directory = NULL;

    public function __construct($controllers_directory = NULL)
    {
        if ($controllers_directory !== NULL) {
            $this->controllers_directory = $controllers_directory;
        }
    }

    /**
     * Begins routing
     * @param  Request $request The request to route for
     */
    public function route($request = NULL)
    {
        // If the path to the controllers directory wasn't set, throw an exception
        if ($this->controllers_directory === NULL) {
            throw new \BadFunctionCallException('Must set a controllers directory before calling start.');
        }

        if ($request === NULL) {
            $request = Request::current();
        }
        $responsible_controller = $this->get_responsible_controller($request);
        $responsible_controller->__cc_route();
    }

    /**
     * Gets the controller responsible for a request
     * @param  Request $request The request to find a controller for
     * @return object           Controller
     */
    public function get_responsible_controller($request = NULL)
    {
        if ($request === NULL) {
            $request = Request::current();
        }

        // Find the controller which should handle this
        $trie = new ControllerFileTrie($request->path);
        $best_match = $trie->find_closest_filesystem_match($this->controllers_directory);

        if ($best_match === NULL) {
            throw new HttpError(404);
        }

        $controller = static::get_class_from_path($best_match->path);
        $controller->routing_information = $best_match;
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

            $request->uri = preg_replace('/^' + str_replace('/', '\\/', $from) + '$/i', $to, $request->uri);
        }

        return $request;
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
        $class = $namespace = $buffer = NULL;
        $i = 0;

        while ($class === NULL) { // The namespace of the class can only be changed before the class is declared.

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
