<?php

namespace CuteControllers;

// Load these in case there's no SPL class loader
require_once('Router.php');
require_once('HttpError.php');

class Request
{
    public $method;
    public $scheme;
    public $username;
    public $password;
    public $host;
    public $port;
    public $path;
    public $query;
    public $body;
    public $user_ip;

    protected $_get;
    protected $_post;

    public function __construct($method, $scheme, $username, $password, $host, $port, $path, $query, $user_ip, $post,
                                $body)
    {
        $this->method = $method;
        $this->scheme = $scheme;
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;

        $this->user_ip = $user_ip;

        parse_str($this->query, $this->_get);
        $this->_post = $post;

        $this->body = $body;
    }

    /**
     * Gets the request which represents the current HTTP session
     */
    public static function Current()
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            throw new \BadFunctionCallException('Cannot get request information in non-server invocation.');
        }

        // Get information about the current request
        $user_ip = $_SERVER['REMOTE_ADDR'];

        $method = isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD'] : 'GET';
        // Figure out if we're on https:
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] === 'on')) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }

        $username = isset($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER'] : FALSE;
        $password = isset($_SERVER['PHP_AUTH_PW'])? $_SERVER['PHP_AUTH_PW'] : FALSE;
        $host = $_SERVER['SERVER_NAME'];
        $port = isset($_SERVER['SERVER_PORT'])? $_SERVER['SERVER_PORT'] : ($scheme === 'https'? 443 : 80);
        $path = $_SERVER['REQUEST_URI'];
        $query = (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0)? $_SERVER['QUERY_STRING'] :
                                                                                            NULL;

        if ($query !== NULL) {
            $path = substr($path, 0, strlen($path) - (strlen($query) + 1));
        }

        $post = $_POST;
        $body = file_get_contents('php://input');

        return new self($method, $scheme, $username, $password, $host, $port, $path, $query, $user_ip,
                                    $post, $body);
    }

    /**
     * Creates a request instance from a URL
     * @param string $url       The URL to create the request instance from
     * @param string $method    Optional, the HTTP verb to create the request as, defaults to GET
     * @param string $user_ip   Optional, the user's IP
     * @param array  $post      Optional, POST data
     * @param string $body      Optional, POST body
     */
    public static function FromUrl($url, $method = 'GET', $user_ip = NULL, $post = NULL, $body = NULL)
    {
        $parts = parse_url($url);

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? $parts['port'] : ($scheme === 'https' ? 443 : 80);
        $username = isset($parts['user']) ? $parts['user'] : NULL;
        $password = isset($parts['pass']) ? $parts['pass'] : NULL;
        $path = isset($parts['path']) ? $parts['path'] : '/';
        $query = isset($parts['query']) ? $parts['query'] : NULL;

        return new self($method, $scheme, $username, $password, $host, $port, $path, $query, $user_ip, $post, $body);
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
    public function param($name)
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
                $pathparts = explode('/', $this->path);
                return array_pop($pathparts);
                break;
            case 'file_name':
                return strpos($this->file, '.') !== FALSE? substr($this->file, 0, strrpos($this->file, '.')) : $this->file;
                break;
            case 'file_ext':
                return strrpos($this->file, '.') !== FALSE? substr($this->file, strrpos($this->file, '.') + 1) : FALSE;
                break;
            case 'segments':
                return explode('/', $this->path);
                break;
        }
    }
}
