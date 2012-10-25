<?php

namespace CuteControllers\Base;

class RestCrud extends Rest
{
    public function route()
    {
        // If no record is specified, try to use the __search and __list magic methods
        if ($this->action === NULL && $this->request->method === 'GET') {
            if ($this->request->request('search') !== NULL &&
                $this->check_method('__search')) {
                $this->action = '__search';
            } else if ($this->check_method('__list')) {
                $this->action = '__list';
            } else {
                $this->action = '__' . strtolower($this->request->method);
            }
        } else {
            $id = $this->action;
            $this->action = '__' . strtolower($this->request->method);
        }

        if ($this->check_method($this->action)) {
            if ($this->action === '__list') {
                $this->generate_response($this->{$this->action}());
            } else if ($this->action === '__search') {
                $this->generate_response($this->{$this->action}($this->request->request('search')));
            } else {
                $this->generate_response($this->{$this->action}($id));
            }
        } else {
            print $this->action;
            throw new \CuteControllers\HttpError(404);
        }
    }
}
