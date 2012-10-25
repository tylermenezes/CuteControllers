<?php

namespace CuteControllers;

// Load these in case there's no SPL class loader
require_once('Router.php');
require_once('HttpError.php');

class Request
{
    public $ip;
    public $username;
    public $password;
    public $method;
    public $scheme;
    public $host;
    public $port;
    public $uri;
    public $full_uri;
    public $query;
    public $body;
    protected $_get;
    protected $_post;

    public function __construct($ip, $username, $password, $method, $scheme, $host, $port, $path, $full_uri, $query, $post, $body)
    {
        $this->ip = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->method = strtoupper($method);

        $this->scheme =$scheme;
        $this->host = $host;
        $this->port = $port;
        $this->uri = $path;
        $this->full_uri = $full_uri;
        $this->query = $query;

        parse_str($this->query, $this->_get);
        $this->_post = $post;

        $this->body = $body;
    }

    private static $current = null;

    /**
     * Gets the request which represents the current HTTP session
     */
    public static function current()
    {
        if (!isset(static::$current)) {

            // Get information about the current request
            $ip = $_SERVER['REMOTE_ADDR'];
            $username = isset($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER'] : FALSE;
            $password = isset($_SERVER['PHP_AUTH_PW'])? $_SERVER['PHP_AUTH_PW'] : FALSE;
            $method = $_SERVER['REQUEST_METHOD'];

            // Figure out if we're on https:
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] === 'on')) {
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }


            $host = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            $path = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] :
                                                  (isset($_SERVER['ORIG_PATH_INFO'])? $_SERVER['ORIG_PATH_INFO'] : '');

            $full_uri = $_SERVER['REQUEST_URI'];
            $full_uri = strpos($full_uri, '?') !== FALSE? substr($full_uri, 0, strrpos($full_uri, '?')) : $full_uri;

            $query = $_SERVER['QUERY_STRING'];
            $post = $_POST;
            $body = file_get_contents('php://input');

            static::$current = new Request($ip, $username, $password, $method, $scheme, $host, $port, $path,
                                           $full_uri, $query, $post, $body);
        }

        return static::$current;
    }

    /**
     * Gets a GET paramater (from the query string)
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function get($name)
    {
        return isset($this->_get[$name])? $this->_get[$name] : NULL;
    }

    /**
     * Gets a POST paramater
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function post($name)
    {
        return isset($this->_post[$name])? $this->_post[$name] : NULL;
    }

    /**
     * Gets a paramater from the request - first checking GET and then POST
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function request($name)
    {
        return ($this->get($name) !== NULL)? $this->get($name) : $this->post($name);
    }

    /**
     * Magic getter method
     * @param  string $key Name of the object to get
     * @return mixed       Value of the object
     */
    public function __get($key)
    {
        switch ($key) {
            case 'file':
                $pathparts = explode('/', $this->uri);
                return array_pop($pathparts);
                break;
            case 'file_name':
                return strpos($this->file, '.') !== FALSE? substr($this->file, 0, strrpos($this->file, '.')) : $this->file;
                break;
            case 'file_ext':
                return strrpos($this->file, '.') !== FALSE? substr($this->file, strrpos($this->file, '.') + 1) : FALSE;
                break;
            case 'path':
                $pathparts = explode('/', $this->uri);
                array_pop($pathparts);
                return implode('/', $pathparts);
                break;
            case 'segments':
                return explode('/', $this->uri);
                break;
        }
    }
}
