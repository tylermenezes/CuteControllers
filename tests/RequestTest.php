<?php

require_once(dirname(__FILE__) . '/../CuteControllers/Router.php');
require_once(dirname(__FILE__) . '/../CuteControllers/Request.php');

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function test_from_url()
    {
        $request = \CuteControllers\Request::FromUrl('https://tyler.menez.es/test?abc=123&def=456');

        $this->assertEquals('GET', $request->method);
        $this->assertEquals('https', $request->scheme);
        $this->assertEquals(NULL, $request->username);
        $this->assertEquals(NULL, $request->password);
        $this->assertEquals('tyler.menez.es', $request->host);
        $this->assertEquals('/test', $request->path);
        $this->assertEquals('abc=123&def=456', $request->query);
        $this->assertEquals('123', $request->get('abc'));
        $this->assertEquals(NULL, $request->post('abc'));
        $this->assertEquals('123', $request->param('abc'));
        $this->assertEquals('456', $request->get('def'));
        $this->assertEquals(NULL, $request->post('def'));
        $this->assertEquals('456', $request->param('def'));

        $request = \CuteControllers\Request::FromUrl('http://foo');

        $this->assertEquals($request->method, 'GET');
        $this->assertEquals($request->scheme, 'http');
        $this->assertEquals($request->username, NULL);
        $this->assertEquals($request->password, NULL);
        $this->assertEquals($request->host, 'foo');
        $this->assertEquals($request->path, '/');
        $this->assertEquals($request->query, NULL);
        $this->assertEquals($request->get('abc'), NULL);
        $this->assertEquals($request->post('abc'), NULL);
        $this->assertEquals(NULL, $request->param('abc'));

        $request = \CuteControllers\Request::FromUrl('http://u:p@foo.bar:81/?get_test=1&priority=x', 'POST', '1.2.3.4',
                                                     ['post_test' => '456', 'priority' => 'y']);

        $this->assertEquals('POST', $request->method);
        $this->assertEquals('http', $request->scheme);
        $this->assertEquals('u', $request->username);
        $this->assertEquals('p', $request->password);
        $this->assertEquals('foo.bar', $request->host);
        $this->assertEquals(81, $request->port);
        $this->assertEquals('/', $request->path);
        $this->assertEquals('get_test=1&priority=x', $request->query);
        $this->assertEquals('1', $request->get('get_test'));
        $this->assertEquals(NULL, $request->post('get_test'));
        $this->assertEquals('1', $request->param('get_test'));
        $this->assertEquals(NULL, $request->get('post_test'));
        $this->assertEquals('456', $request->post('post_test'));
        $this->assertEquals('456', $request->param('post_test'));
        $this->assertEquals('x', $request->get('priority'));
        $this->assertEquals('y', $request->post('priority'));
        $this->assertEquals('x', $request->param('priority'));
    }

    /**
     * @expectedException BadFunctionCallException
     */
    public function test_no_server_invocation()
    {
        $_SERVER = [
            'USER' => 'tylermenezes',
            'LOGNAME' => 'tylermenezes',
            'HOME' => '/home/tylermenezes',
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games',
            'MAIL' => '/var/mail/tylermenezes',
            'SHELL' => '/usr/bin/zsh',
            'SSH_TTY' => '/dev/pts/2',
            'PWD' => '/var/www/tests',
            'OLDPWD' => '/var/www',
            'PLATFORM' => 'Linux',
            'VIRTUAL_ENV_DISABLE_PROMPT' => 'definitely',
            '_' => '/usr/bin/php',
            'PHP_SELF' => '../index.php',
            'SCRIPT_NAME' => '../index.php',
            'SCRIPT_FILENAME' => '../index.php',
            'PATH_TRANSLATED' => '../index.php',
            'DOCUMENT_ROOT' => '',
            'REQUEST_TIME_FLOAT' => 1234567890.123,
            'REQUEST_TIME' => 1234567890,
            'argv' =>
            array (
            0 => 'index.php',
            ),
            'argc' => 1,
        ];

        \CuteControllers\Router::start('');
    }

    public function test_current_no_rewrite()
    {
        $_SERVER = [
          'HTTP_HOST' => 'foo.bar',
          'HTTP_CONNECTION' => 'keep-alive',
          'HTTP_CACHE_CONTROL' => 'no-cache',
          'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          'HTTP_PRAGMA' => 'no-cache',
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.27 (KHTML, like Gecko) Chrome/26.0.1386.0 Safari/537.27',
          'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
          'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
          'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
          'PATH' => '/usr/local/bin:/usr/bin:/bin',
          'SERVER_SIGNATURE' => '<address>Apache/2.2.22 (Ubuntu) Server at foo.bar Port 80</address>
        ',
          'SERVER_SOFTWARE' => 'Apache/2.2.22 (Ubuntu)',
          'SERVER_NAME' => 'foo.bar',
          'SERVER_ADDR' => 'foo.bar',
          'SERVER_PORT' => '80',
          'REMOTE_ADDR' => '127.0.0.1',
          'DOCUMENT_ROOT' => '/var/www',
          'SERVER_ADMIN' => 'webmaster@localhost',
          'SCRIPT_FILENAME' => '/var/www/index.php',
          'REMOTE_PORT' => '41162',
          'GATEWAY_INTERFACE' => 'CGI/1.1',
          'SERVER_PROTOCOL' => 'HTTP/1.1',
          'REQUEST_METHOD' => 'GET',
          'QUERY_STRING' => '',
          'REQUEST_URI' => '/index.php/xyz/a/b/c',
          'SCRIPT_NAME' => '/index.php',
          'PATH_INFO' => '/xyz/a/b/c',
          'PATH_TRANSLATED' => 'redirect:/index.php/a/b/c',
          'PHP_SELF' => '/index.php/xyz/a/b/c',
          'REQUEST_TIME_FLOAT' => 1234567890.123,
          'REQUEST_TIME' => 1234567890,
        ];

        $request = \CuteControllers\Request::Current();
        $this->assertEquals('GET', $request->method);
        $this->assertEquals('http', $request->scheme);
        $this->assertEquals(NULL, $request->username);
        $this->assertEquals(NULL, $request->password);
        $this->assertEquals('foo.bar', $request->host);
        $this->assertEquals('/index.php/xyz/a/b/c', $request->path);
        $this->assertEquals(NULL, $request->query);
        $this->assertEquals(NULL, $request->get('abc'));
        $this->assertEquals(NULL, $request->post('abc'));
        $this->assertEquals(NULL, $request->param('abc'));
    }

    public function test_current_rewrite()
    {
        $_SERVER = [
          'REDIRECT_STATUS' => '200',
          'HTTP_HOST' => 'foo.bar',
          'HTTP_CONNECTION' => 'keep-alive',
          'HTTP_CACHE_CONTROL' => 'no-cache',
          'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          'HTTP_PRAGMA' => 'no-cache',
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.27 (KHTML, like Gecko) Chrome/26.0.1386.0 Safari/537.27',
          'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
          'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
          'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
          'PATH' => '/usr/local/bin:/usr/bin:/bin',
          'SERVER_SIGNATURE' => '<address>Apache/2.2.22 (Ubuntu) Server at foo.bar Port 80</address>
        ',
          'SERVER_SOFTWARE' => 'Apache/2.2.22 (Ubuntu)',
          'SERVER_NAME' => 'foo.bar',
          'SERVER_ADDR' => 'foo.bar',
          'SERVER_PORT' => '80',
          'REMOTE_ADDR' => '127.0.0.1',
          'DOCUMENT_ROOT' => '/var/www',
          'SERVER_ADMIN' => 'webmaster@localhost',
          'SCRIPT_FILENAME' => '/var/www/index.php',
          'REMOTE_PORT' => '41156',
          'REDIRECT_URL' => '/xyz/a/b/c',
          'GATEWAY_INTERFACE' => 'CGI/1.1',
          'SERVER_PROTOCOL' => 'HTTP/1.1',
          'REQUEST_METHOD' => 'GET',
          'QUERY_STRING' => '',
          'REQUEST_URI' => '/xyz/a/b/c',
          'SCRIPT_NAME' => '/index.php',
          'PHP_SELF' => '/index.php',
          'REQUEST_TIME_FLOAT' => 1234567890.123,
          'REQUEST_TIME' => 1234567890,
        ];

        $request = \CuteControllers\Request::Current();
        $this->assertEquals('GET', $request->method);
        $this->assertEquals('http', $request->scheme);
        $this->assertEquals(NULL, $request->username);
        $this->assertEquals(NULL, $request->password);
        $this->assertEquals('foo.bar', $request->host);
        $this->assertEquals('/xyz/a/b/c', $request->path);
        $this->assertEquals(NULL, $request->query);
        $this->assertEquals(NULL, $request->get('abc'));
        $this->assertEquals(NULL, $request->post('abc'));
        $this->assertEquals(NULL, $request->param('abc'));
    }
}
