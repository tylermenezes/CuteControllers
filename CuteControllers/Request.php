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
    public $extenstion;
    public $extension;
    public $query;
    public $fragment;
    public $stdin;
    protected $_get;
    protected $_post;

    public __construct($ip, $username, $password, $method, $url, $post, $stdin)
    {
        $this->ip = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->method = strtoupper($method);

        $parts = parse_url($url);
        $this->scheme = $parts['scheme'];
        $this->host = $parts['host'];
        $this->uri = $parts['path'];
        $this->query = $parts['query'];
        $this->fragment = $parts['fragment'];

        $this->extension = substr($this->file, strrpos($this->file, '.') + 1);

        parse_str($this->query, $this->get);
        $this->post = $post;

        $this->stdin = $stdin;
    }

    private static $current = null;

    /**
     * Gets the request which represents the current HTTP session
     */
    public static current()
    {
        if (!isset(self::current)) {
            $current = new Request($_SERVER['REMOTE_ADDR'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REQUEST_METHOD'],
                                   full_url(), $_POST, file_get_contents('php://input'));
        }

        return self::current;
    }

    /**
     * Gets the fully qualified URL associated with the request
     * @return string Fully-qualified URL
     */
    private static function full_url()
    {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     * Gets a GET paramater (from the query string)
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function get($name)
    {
        return $this->_get[$name];
    }

    /**
     * Gets a POST paramater
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function post($name)
    {
        if (!$this->method !== 'POST') {
            throw new \Exception('Cannot read postdata on a non-POST request.');
        }

        return $this->_post[$name];
    }

    /**
     * Gets a paramater from the request - first checking GET and then POST
     * @param  string $name Name of the paramater to get
     * @return mixed        Value of the paramater
     */
    public function request($name)
    {
        return $this->get($name) || $this->post($name);
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
            case 'filename':
                return substr($this->file, 0, strrpos($this->file, '.'));
                break;
            case 'path':
                $pathparts = explode('/', $this->uri);
                array_pop($pathparts);
                return implode('/', $pathparts);
                break;
        }
    }
}