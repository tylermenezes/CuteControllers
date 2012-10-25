<?php

namespace CuteControllers\Base;

class Web extends Controller
{
    public function route()
    {
        if ($this->action === NULL) {
            $this->action = 'index';
        }

        $this->action = '__' . $this->action;

        if ($this->check_method($this->action, count($this->positional_args))) {
            echo call_user_func_array(array($this, $this->action), $this->positional_args);
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }
}
