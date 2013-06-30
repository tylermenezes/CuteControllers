<?php

namespace CuteControllers;

require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Internal', 'require.php']));

/**
 * Makes a class into a CuteControllers router.
 *
 * @author      Tyler Menezes <tylermenezes@gmail.com>
 * @copyright   Copyright (c) Tyler Menezes. Released under the BSD license.
 *
 * @package     CuteControllers
 */
trait Controller
{
    public $routing_information;
    public $request;

    /**
     * Handles the incoming route request from the CuteControllers Router
     */
    public function cc_route()
    {
        $url_params = explode('/', $this->routing_information->unmatched_path);
        $action = str_replace('-', '_', array_shift($url_params));
        $action_orig = $action;
        $method = strtolower($this->request->method);

        $full_url_params = $url_params;
        if ($action_orig) {
            $full_url_params = array_merge(array($action_orig), $url_params);
        }

        $ext = $this->request->file_ext;

        $this->cc_call_prefixed_methods('before_');

        $to_try_methods = [];

        // If an action was specified, add that first.
        if ($action) {
            $to_try_methods = array_merge($to_try_methods, [
                $method . '_' . $action . '_' . $ext => $url_params,                     // get_foo_html
                $method . '_' . $action => $url_params,                                  // get_foo
                'action_' . $action . '_' . $ext => $url_params,                         // action_foo_html
                'action_' . $action => $url_params                                       // action_foo
            ]);
        }

        $to_try_methods = array_merge($to_try_methods, [
            $method . '_index' => $full_url_params,                                  // get_index
            $method . '_index_' . $ext => $full_url_params,                          // get_index_html
            'action_index_' . $ext => $full_url_params,                              // action_index_html
            'action_index' => $full_url_params                                       // action_index
        ]);

        $result = $this->cc_try_methods($to_try_methods);
        $this->cc_generate_response($result);
        $this->cc_call_prefixed_methods('after_');
    }

    /* # Helpers */

    /* ## Method Calls */

    /**
     * Calls all methods prefixed with the specified string (e.g. `before_` => [`before_foo()`, `before_bar()`])
     * @param  string $prefix Prefix to search for
     */
    private function cc_call_prefixed_methods($prefix)
    {
        $all_methods = get_class_methods($this);
        foreach ($all_methods as $method)
        {
            if (strpos($method, $prefix) === 0) {
                $this->$method();
            }
        }
    }

    /**
     * Calls cc_call_method on the provided list of methods until one exists. If none exist, throws a 404 exception.
     * @param  array  $to_try Key-value pair consisting of method name and args
     * @return mixed  Result of calling the method, if one existed
     */
    private function cc_try_methods($to_try)
    {
        foreach ($to_try as $name => $args)
        {
            try {
                return $this->cc_call_method($name, $args);
            } catch (NoArgumentMatchException $ex) {
                throw new \CuteControllers\HttpError(404);
            } catch (NoMethodMatchException $ex) { }
        }


        throw new \CuteControllers\HttpError(404);
    }

    /**
     * Checks if a method accepting the given number of arguments or less is found and, if so, calls it with the arguments.
     * @param  string $name       Method name to check for
     * @param  array  $args       Params to call the method with
     * @return mixed              Method result
     */
    private function cc_call_method($name, $args)
    {
        if (!method_exists($this, $name)) {
            throw new NoMethodMatchException();
        }


        $reflection = new \ReflectionMethod($this, $name);
        if (
                $reflection->isPublic() &&
                $reflection->getNumberOfParameters() === count($args)
        ) {
            return call_user_func_array(array($this, $name), $args);
        } else {
            throw new NoArgumentMatchException();
        }
    }

    /* ## Responses */

    /**
     * Prints text to the browser, taking into account the requested page extension and the provided response from the controller method
     * @param  mixed  $response Result of calling a controller method (e.g. a string, or an object)
     */
    private function cc_generate_response($response)
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
                if (isset($this->enable_xdomain) && $this->enable_xdomain) {
                    echo $this->request->param('callback') . '(' . json_encode($response) . ');';
                } else {
                    echo 'alert("Cross-domain access is not allowed.");';
                }
                break;
            case 'jsonh':
                header("Content-type: text/html");
                $json = json_encode(json_encode($response));
                echo '<!DOCTYPE html><html><head><title>Cross-Domain Proxy</title></head><body><script type="text/javascript">';

                if (isset($this->enable_xdomain) && $this->enable_xdomain) {
                    echo 'parent.postMessage(' . $json . ', "' . $this->request->param('domain') . '");';
                } else {
                    echo 'alert("Cross-domain access is not allowed.");';
                }

                echo '</script></body></html>';
                break;
            case 'html':
                header("Content-type: text/html");
                echo $response;
                break;
            case 'serialized':
                header("Content-type: application/vnd.php.serialized");
                echo serialize($response);
            case 'php':
                header("Content-type: application/vnd.php.serialized");
                echo var_export($response, true);
                break;
            default:
                if (is_array($response) || is_object($response)) {
                    header("Content-type: application/json");
                    echo json_encode($response);
                } else {
                    header("Content-type: text/html");
                    echo $response;
                }
        }
    }

    /* ## Controller Helpers */

    /**
     * Throws an exception if the specified GET parameter doesn't exist
     * @return string Parameter name
     */
    protected function require_get()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->get($require) === null) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    /**
     * Throws an exception if the specified POST parameter doesn't exist
     * @return string Parameter name
     */
    protected function require_post()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->post($require) === null) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    /**
     * Throws an exception if the specified parameter doesn't exist
     * @return string Parameter name
     */
    protected function require_param()
    {
        $required = func_get_args();
        foreach ($required as $require) {
            if ($this->request->param($require) === null) {
                throw new \CuteControllers\HttpError(400);
            }
        }
    }

    /**
     * Redirects the user to a new page
     * @param  string $to      New location
     * @param  [int]  $status  HTTP status code to send
     */
    protected function redirect($to, $status = 302)
    {
        \CuteControllers\Router::redirect($to, $status);
    }
}
