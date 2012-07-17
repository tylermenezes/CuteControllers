<?php

namespace CuteControllers\Base;

abstract class Controller
{
    protected $request;

    public function __construct(\CuteControllers\Request $request)
    {
        $this->request = $request;
    }

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

    abstract public function route();
}