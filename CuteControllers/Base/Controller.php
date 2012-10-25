<?php

namespace CuteControllers\Base;

abstract class Controller
{
    protected $request;
    protected $action;
    protected $positional_args;

    public function __construct(\CuteControllers\Request $request, $action, $positional_args)
    {
        $this->request = $request;
        $this->action = $action;
        $this->positional_args = $positional_args;
        $this->before();
    }

    public function check_method($method_name, $num_params = NULL)
    {
        if (!method_exists($this, $method_name)) {
            return false;
        }

        $reflection = new \ReflectionMethod($this, $method_name);
        if ($num_params === NULL) {
            return $reflection->isPublic();
        } else {
            return $reflection->getNumberOfRequiredParameters() === $num_params && $reflection->isPublic();
        }
    }

    public function before(){}

    protected function require_get()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->get($require) === NULL) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    protected function require_post()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->post($require) === NULL) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    protected function require_request()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->request($require) === NULL) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    protected function redirect($to)
    {
        \CuteControllers\Router::redirect($to);
    }

    abstract public function route();
}
