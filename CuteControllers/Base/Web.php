<?php

namespace CuteControllers\Base;

trait Web
{
    use Controller;

    public function __route()
    {
        $url_params = explode('/', $this->routing_information->unmatched_path);
        $action = array_shift($url_params);

        if (!$action) {
            $action = 'index';
        }

        $action = '__' . $action;

        // First, check if the action exists, taking count($url_params) or less paramaters
        if ($this->__cc_check_method($action, count($url_params))) {
            echo call_user_func_array(array($this, $action), $url_params);

        // Check if the __index method exists, taking the URL params, plus the action param
        } else if ($this->__cc_check_method('__index', count($url_params) + 1)) {
            echo call_user_func_array(array($this, '__index'), array_merge([$action_orig], $url_params));

        // Method not found!
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}
