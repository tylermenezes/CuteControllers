<?php

namespace CuteControllers;

class Request
{
    public $ip;
    public $username;
    public $password;
    public $method;
    public $scheme;
    public $hostname;
    public $port;
    public $uri;
    public $full_uri;
    public $query;
    public $body;
    protected $_get;
    protected $_post;

    public function __construct($ip, $username, $password, $method, $scheme, $hostname, $port, $path, $full_path, $query, $post, $body)
    {
        $this->ip = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->method = strtoupper($method);

        $this->scheme =$scheme;
        $this->hostname = $hostname;
        $this->port = $port;
        $this->uri = $path;
        $this->full_uri = $full_path;
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
            // First, get the URI
            $full_uri = $_SERVER['REQUEST_URI'];
            $full_uri = strpos($full_uri, '?') !== FALSE? substr($full_uri, 0, strrpos($full_uri, '?')) : $full_uri;

            static::$current = new Request($_SERVER['REMOTE_ADDR'],
                                           isset($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER'] : FALSE,
                                           isset($_SERVER['PHP_AUTH_PW'])? $_SERVER['PHP_AUTH_PW'] : FALSE,
                                           $_SERVER['REQUEST_METHOD'],
                                           isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'? 'https' : 'http',
                                           $_SERVER['SERVER_NAME'],
                                           $_SERVER['SERVER_PORT'],
                                           isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : '',
                                           $full_uri,
                                           $_SERVER['QUERY_STRING'],
                                           $_POST,
                                           file_get_contents('php://input'));
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