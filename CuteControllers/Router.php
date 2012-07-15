<?php

namespace CuteControllers;

class Router
{
    protected static $filters = array();
    protected static $aliases = array();

    public static function start($path)
    {
        $request = static::apply_filters(Request::current());
        $request = static::apply_aliases($request);
    }

    protected static function apply_aliases($request)
    {
        foreach ($aliases as $alias)
        {
            $from = $alias[0];
            $to = $alias[1];

            $request->uri = preg_replace('/' + str_replace('/', '\\/', $from) + '/i', $to, $request->uri);
        }

        return $request;
    }

    protected static function apply_filters($request)
    {
        foreach ($this->filters as $filter)
        {
            $request = $filter($request);
        }

        return $request;
    }

    public static function register_filter($lambda)
    {
        $this->filters[] = $lambda;
    }

    public static function rewrite($from, $to)
    {
        $this->aliases[] = array($from, $to);
    }
}