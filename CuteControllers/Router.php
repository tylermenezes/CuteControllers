<?php

namespace CuteControllers;

class Router
{
    protected static $filters = array();
    protected static $rewrites = array();

    public static function start($path)
    {
        $request = static::apply_filters(Request::current());
        $request = static::apply_rewrites($request);

        // Now we do the actual routing
        $controller =
                        static::get_controller($path . '/' . $request->path . '/' $request->filename '.php', $request) ||
                        static::get_controller($path . '/' . $request->path . '/' $request->filename . '/index.php', $request);

        if ($controller === FALSE) {
            // Throw 404
            // TODO: Recurse up the path, looking for a 404 handler.
        } else {
            $controller->route();
        }
    }

    protected static function get_controller($path, $request)
    {
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
        $this->filters[] = $lambda;
    }

    /**
     * Applies all registered filters
     * @param  Request $request Request to which to apply filters
     * @return Request          Result Request
     */
    protected static function apply_filters($request)
    {
        foreach ($this->filters as $filter)
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
        $this->rewrites[] = array($from, $to);
    }

    /**
     * Applies all registered rewrites
     * @param  Request $request Request to which to apply rewrites
     * @return Request          Result Request
     */
    protected static function apply_rewrites($request)
    {
        foreach ($this->rewrites as $rewrite)
        {
            $from = $rewrite[0];
            $to = $rewrite[1];

            $request->uri = preg_replace('/^' + str_replace('/', '\\/', $from) + '$/i', $to, $request->uri);
        }

        return $request;
    }
}