<?php

namespace CuteControllers;

// Load these in case there's no SPL class loader
require_once('Request.php');
require_once('HttpError.php');
require_once('Base/Controller.php');
require_once('Base/Web.php');
require_once('Base/Rest.php');
require_once('Base/RestCrud.php');

class Router
{
    protected static $filters = array();
    protected static $rewrites = array();

    protected static $path = '';

    /**
     * Starts routing for a controller folder
     * @param  string $path Path to the controllers folder
     */
    public static function start($path)
    {
        static::$path = $path;
        $controller = static::get_responsible_controller();
        $controller->route();
    }


    public static function get_responsible_controller(Request $request = NULL)
    {
        // If no request is specified, assume we're looking at the current one
        if ($request === NULL) {
            $request = Request::current();

            // Do the rewrites
            $request = static::apply_filters(Request::current());
            $request = static::apply_rewrites($request);
        }

        $uri_parts = explode('/', $request->uri);

        // Remove empty segments
        $uri_parts = array_filter($uri_parts, function($part) {
            return $part !== '';
        });

        $uri_parts = array_values($uri_parts); //Renumber

        $best_match = NULL;
        $chain = '';

        // Try to find the best match for a controller
        $i = 0;
        foreach ($uri_parts as $part) {
            $chain .= '/' . $part;

            $best_path = NULL;
            if (file_exists(static::get_controller_path_from_uri($chain . '/index'))) {
                $best_path = static::get_controller_path_from_uri($chain . '/index');
            } else if (file_exists(static::get_controller_path_from_uri($chain))) {
                $best_path = static::get_controller_path_from_uri($chain);
            }

            if ($best_path !== NULL) {
                if (isset($uri_parts[$i+1])) {
                    $action = $uri_parts[$i+1];
                } else {
                    $action = NULL;
                }

                if (isset($uri_parts[$i+2])) {
                    $positional_args = array_slice($uri_parts, $i+2);
                } else {
                    $positional_args = array();
                }

                $best_match = static::get_controller($best_path, $request, $action, $positional_args);
            }

            $i++;
        }

        if ($best_match === NULL) {
            throw new HttpError(404);
        } else {
            return $best_match;
        }
    }

    protected static function get_controller_path_from_uri($uri)
    {
        return self::$path . $uri . '.php';
    }

    /**
     * Gets the URI of the application front-controller. This would be the location of the front-controller relative
     * to the webroot, unless you're using htaccess redirects.
     * @return string URI of the application front-controller
     */
    public static function get_app_uri()
    {
        $current_request = Request::current();
        $uri = $current_request->uri;
        if (substr($uri, -1) === '/' && substr($current_request->full_uri, -1) !== '/') {
            $uri = substr($uri, 0, strlen($uri) - 1);
        }
        $url = substr($current_request->full_uri, 0, strlen($current_request->full_uri) - strlen($uri));
        if (substr($url, -1) === '/') {
            $url = substr($url, 0, strlen($url) - 1);
        }
        return $url;
    }

    /**
     * Gets a link to a resource using the current router configuration.
     * @param  string  $to       The resource URI to get a link to, relative to the application router
     * @param  boolean $absolute True to include the protocol, False otherwise
     * @return string            URL to resource
     */
    public static function link($to, $absolute = FALSE)
    {
        if (strlen($to) == 0) { // Current page
            $to = Request::current()->full_uri;
            if (Request::current()->query) {
                $to .= '?' . Request::current()->query;
            }
        } else if (substr($to, 0, 1) == '?') { // Current page + query string
            $to = Request::current()->full_uri . $to;
        } else if (substr($to, 0, 1) === '/') { // Relative to the app root
            $to = self::get_app_url() . $to;
        } else if (strpos($to, '://') === FALSE) { // Relative to the current page
                $url_parts = explode('/', Request::current()->full_uri);
                array_pop($url_parts);

                $to = implode('/', $url_parts) . '/' . $to;
        } else { // Fully qualified URL
            return $to;
        }

        return Request::current()->scheme . '://' . Request::current()->host . $to;
    }

    public static function redirect($to)
    {
        header('Location: ' . self::get_link($to));
        exit;
    }


    protected static function get_controller($path, $request, $action, $positional_args)
    {
        if (file_exists($path))
        {
            include_once($path);
            $controller_name = static::get_class_name_from_file($path);
            return new $controller_name($request, $action, $positional_args);
        } else {
            return FALSE;
        }
    }

    /**
     * Gets the fully-namespaced name of the first class in the file
     * @param  string $file File path
     * @return string       Fully-namespaced class name (e.g. Namespace\ClassName)
     */
    protected static function get_class_name_from_file($file)
    {
        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) break;

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) continue;

            for (;$i<count($tokens);$i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j=$i+1;$j<count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                             $namespace .= '\\'.$tokens[$j][1];
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                             break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $namespace . '\\' . $class;
    }


    /**
     * Registers a filter
     * @param  function($request) $lambda Anonymous function to call, taking and returning a Request object
     */
    public static function filter($lambda)
    {
        static::$filters[] = $lambda;
    }

    /**
     * Applies all registered filters
     * @param  Request $request Request to which to apply filters
     * @return Request          Result Request
     */
    protected static function apply_filters(Request $request)
    {
        foreach (static::$filters as $filter)
        {
            $request = $filter($request);
        }

        return $request;
    }

    /**
     * Registers a rewrite rule
     * @param  string $from Regex to match on
     * @param  string $to   Replacement (can include capture group references)
     */
    public static function rewrite($from, $to)
    {
        static::$rewrites[] = array($from, $to);
    }

    /**
     * Applies all registered rewrites
     * @param  Request $request Request to which to apply rewrites
     * @return Request          Result Request
     */
    protected static function apply_rewrites(Request $request)
    {
        foreach (static::$rewrites as $rewrite)
        {
            $from = $rewrite[0];
            $to = $rewrite[1];

            $request->uri = preg_replace('/^' + str_replace('/', '\\/', $from) + '$/i', $to, $request->uri);
        }

        return $request;
    }
}
