<?php

namespace CuteControllers\Base;

trait Rest
{
    use Controller;
    public function __route()
    {
        $url_params = explode('/', $this->routing_information->unmatched_path);
        $action = array_shift($url_params);

        if (!$action) {
            $action = 'index';
        }

        $action = '__' . strtolower($this->request->method) . '_' . $action;

        if ($this->__cc_check_method($action, count($url_params))) {
            $this->generate_response(call_user_func_array(array($this, $action), $url_params));
        } else {
            throw new \CuteControllers\HttpError(404);
        }
    }

    protected $__enable_xdomain = FALSE;
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
                if ($this->__enable_xdomain) {
                    echo $this->request->param('callback') . '(' . json_encode($response) . ');';
                } else {
                    echo 'alert("Cross-domain access is not allowed.");';
                }
                break;
            case 'jsonh':
                header("Content-type: text/html");
                $json = json_encode($response);
                echo '<!DOCTYPE html><html><head><title>Cross-Domain Proxy</title></head><body><script type="text/javascript">';

                if ($this->__enable_xdomain) {
                    echo 'parent.postMessage($json, location.protocol + "//" + parent.document.domain);';
                } else {
                    echo 'alert("Cross-domain access is not allowed.");';
                }

                echo '</script></body></html>';
                break;
            case 'html':
            default:
                header("Content-type: text/html");
                echo $response;
                break;
            case 'serialized':
                header("Content-type: application/vnd.php.serialized");
                echo serialize($response);
            case 'php':
                header("Content-type: application/vnd.php.serialized");
                echo var_export($response, TRUE);
                break;
        }
    }
}
