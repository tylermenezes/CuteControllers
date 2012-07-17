<?php

namespace CuteControllers\Base;

class RestCrud extends Rest
{
    public function route()
    {
        if ($this->request->file_name === '' && $this->request->method === 'GET') {
            if ($this->request->request('search') !== NULL && method_exists($this, 'get_search')) {
                $method = 'get_search';
            } else if (method_exists($this, 'get_list')) {
                $method = 'get_list';
            } else {
                throw new \CuteControllers\HttpError(404);
            }
        } else {
            $method = strtolower($this->request->method);
        }

        if (method_exists($this, $method)) {
            $reflection = new \ReflectionMethod($this, $method);
            if (!$reflection->isPublic()) {
                throw new \CuteControllers\HttpError(403);
            }
            if ($method == 'get_list') {
                $this->generate_response($this->$method());
            } else if ($method == 'get_search') {
                $this->generate_response($this->$method($this->request->request('search')));
            } else {
                $this->generate_response($this->$method($this->request->file_name));
            }
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}