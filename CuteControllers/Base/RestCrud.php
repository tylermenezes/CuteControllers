<?php

namespace CuteControllers\Base;

trait RestCrud
{
    use Rest;

    public function __route()
    {
        $url_params = explode('/', $this->routing_information->unmatched_path);
        $action = array_shift($url_params);

        if (!$action && $this->request->method === 'GET') {
            if ($this->request->param('search') !== NULL &&
                $this->__cc_check_method('__search')) {
                $action = '__search';
            } else if ($this->__cc_check_method('__list')) {
                $action = '__list';
            } else {
                $action = '__' . strtolower($this->request->method);
            }
        } else {
            $id = $action;
            switch ($this->request->method) {
                case 'GET':
                    $action = '__read';
                    break;
                case 'POST':
                    $action = '__create';
                    break;
                case 'PUT':
                case 'PATCH':
                    $action = '__update';
                    break;
                case 'DELETE';
                    $action = '__delete';
                    break;
                default:
                    $action = '__' . strtolower($this->request->method);
                    break;
            }
        }

        if ($this->__cc_check_method($action)) {
            if ($action === '__list') {
                $this->generate_response($this->{$action}());
            } else if ($action === '__search') {
                $this->generate_response($this->{$action}($this->request->param('search')));
            } else {
                $this->generate_response($this->{$action}($id));
            }
        } else {
            print $action;
            throw new \CuteControllers\HttpError(404);
        }
    }
}
