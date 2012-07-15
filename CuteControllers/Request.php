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
    public static current()
    {
        if (!isset(self::current)) {
            $current = new Request($_SERVER['REMOTE_ADDR'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REQUEST_METHOD'],
                                   full_url(), $_POST, file_get_contents('php://input'));
        }

        return self::current;
    }

    private static function full_url()
    {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    public function get($name)
    {
        return $this->_get[$name];
    }

    public function post($name)
    {
        if (!$this->method !== 'POST') {
            throw new \Exception('Cannot read postdata on a non-POST request.');
        }

        return $this->_post[$name];
    }

    public function __get($key)
    {
        switch ($key) {
            case 'file':
                $pathparts = explode('/', $this->uri);
                return array_pop($pathparts);
                break;
            case 'path':
                $pathparts = explode('/', $this->uri);
                array_pop($pathparts);
                return implode('/', $pathparts);
                break;
        }
    }
}