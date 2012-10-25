<?php

namespace CuteControllers\Base;

class Rest extends Controller
{
    public function route()
    {
        if ($this->action === NULL) {
            $this->action = 'index';
        }

        $this->action = '__' . strtolower($this->request->method) . '_' . $this->action;

        if ($this->check_method($this->action, count($this->positional_args))) {
            $this->generate_response(call_user_func_array(array($this, $this->action), $this->positional_args));
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }

    public function generate_response($response)
    {
        if (!isset($response)) {
            return;
        }

        switch($this->request->file_ext) {
            case 'txt':
                header("Content-type: text/plain");
                echo $response;
                break;
            case 'json':
                header("Content-type: application/json");
                echo json_encode($response);
                break;
            case 'jsonp':
                header("Content-type: text/javascript");
                echo $this->request->request('callback') . '(' . json_encode($response) . ');';
                break;
            case 'html':
            default:
                header("Content-type: text/html");
                echo $response;
                break;
            case 'serialized':
            case 'php':
                header("Content-type: application/vnd.php.serialized");
                echo serialize($response);
                break;
        }
    }
}
