<?php

namespace CuteControllers\Base;

class RestCrud extends Rest
{
    public function route()
    {
        if ($this->request->file_name === '') {
            if ($this->request->request('search') !== NULL && method_exists($this, 'search')) {
                $method = 'search';
            } else {
                $method = 'list';
            }
        } else {
            $method = strtolower($this->request->method);
        }

        if (method_exists($this, $method)) {
            $reflection = new \ReflectionMethod($this, $method);
            if (!$reflection->isPublic()) {
                throw new \CuteControllers\HttpError(403);
            }
            if ($method == 'list') {
                $this->generate_response($this->$method());
            } else if ($method == 'search') {
                $this->generate_response($this->$method($this->request->request('search')));
            } else {
                $this->generate_response($this->$method($this->request->file_name));
            }
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}