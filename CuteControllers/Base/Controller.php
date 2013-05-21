<?php

namespace CuteControllers\Base;

trait Controller
{
    public $routing_information;
    public $request;

    private function __cc_check_method($method_name, $num_params = NULL, $check_public = TRUE)
    {
        if (!method_exists($this, $method_name)) {
            return false;
        }

        $reflection = new \ReflectionMethod($this, $method_name);
        if ($num_params === NULL) {
            return $reflection->isPublic() || !$check_public;
        } else {
            return $reflection->getNumberOfRequiredParameters() <= $num_params &&
                    ($reflection->isPublic() || !$check_public);
        }
    }

    public function __cc_route()
    {
        if ($this->__cc_check_method('__before', NULL, FALSE)) {
            $this->__before();
        }

        $this->__route();

        if ($this->__cc_check_method('__after', NULL, FALSE)) {
            $this->__after();
        }
    }

    abstract protected function __route();

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
}
