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

        if ($this->__cc_check_method($action, count($url_params))) {
            echo call_user_func_array(array($this, $action), $url_params);
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}
