<?php

namespace CuteControllers\Base;

class Rest extends Controller
{
    public function route()
    {
        $method = strtolower($this->request->method) . '_';
        if ($this->request->file_name === '') {
            $method .= 'index';
        } else {
            $method .= $this->request->file_name;
        }

        if (method_exists($this, $method)) {
            $reflection = new \ReflectionMethod($this, $method);
            if (!$reflection->isPublic()) {
                throw new \CuteControllers\HttpError(403);
            }
            $this->generate_response($this->$method());
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }

    public function generate_response($response)
    {
        if (!isset($response)) {
            return;
        }

        switch($this->request->file_ext || 'html') {
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