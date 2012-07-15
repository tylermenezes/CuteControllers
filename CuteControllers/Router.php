<?php

namespace CuteControllers;

class Router
{
    protected static $filters = array();
    protected static $rewrites = array();

    /**
     * Starts routing for a controller folder
     * @param  string $path Path to the controllers folder
     */
    public static function start($path)
    {
        $request = static::apply_filters(Request::current());
        $request = static::apply_rewrites($request);

        // Now we do the actual routing
        // First off, if there is no path, there will have to be a controller.
        if ($request->path === '') {
            if ($request->file) {
                $request->uri = $request->path . '/' . $request->file_name . '/' . ($request->file_ext? '.' . $request->file_ext : '');
                $controller = static::get_controller($path, $request);
            } else {
                $request->path = '/index';
                $controller = static::get_controller($path, $request);
            }
        } else {
            $controller = static::get_controller($path, $request);
            if ($controller === FALSE) {
                // Maybe they want to call the index function
                $request->uri = $request->path . '/' . $request->file_name . '/' . ($request->file_ext? '.' . $request->file_ext : '');
                $controller = static::get_controller($path, $request);
            }
        }

        if ($controller !== FALSE) {
            $controller->route();
        } else {
            throw new HttpError(404);
        }
    }

    /**
     * Gets a controller associated with a request object
     * @param  string  $path    Path to controllers folder
     * @param  Request $request Request object to load the controller for
     * @return mixed            False if the controller doesn't exist, otherwise the controller
     */
    protected static function get_controller($path, Request $request)
    {
        $path = $path . $request->path . '.php';
        if (file_exists($path))
        {
            include_once($path);
            $controller_name = static::get_class_name_from_file($path);
            return new $controller_name($request);
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