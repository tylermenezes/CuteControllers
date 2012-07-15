<?php

namespace CuteControllers\Base;

class Web extends Controller
{
    public function route()
    {
        if ($this->request->file_name === '') {
            $method = 'index';
        } else {
            $method = $this->request->file_name;
        }

        if (method_exists($this, $method)) {
            $reflection = new \ReflectionMethod($this, $method);
            if (!$reflection->isPublic()) {
                throw new \CuteControllers\HttpError(403);
            }
            $this->$method();
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}