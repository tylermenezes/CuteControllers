<?php

require_once(dirname(__FILE__) . '/../CuteControllers/Router.php');
require_once(dirname(__FILE__) . '/RequestTest.php');

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function test_get_instance()
    {
        $this->assertInstanceOf('\CuteControllers\Router', new \CuteControllers\Router());
    }

    /**
     * @expectedException \CuteControllers\HttpError
     * @expectedExceptionCode 404
     */
    public function test_start_route()
    {
        $_SERVER = [
          'REDIRECT_STATUS' => '200',
          'HTTP_HOST' => 'foo.bar',
          'HTTP_CONNECTION' => 'keep-alive',
          'HTTP_CACHE_CONTROL' => 'no-cache',
          'HTTP_PRAGMA' => 'no-cache',
          'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
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
          'REMOTE_ADDR' => '1.2.3.4',
          'DOCUMENT_ROOT' => '/var/www',
          'SERVER_ADMIN' => 'webmaster@localhost',
          'SCRIPT_FILENAME' => '/var/www/index.php',
          'REMOTE_PORT' => '56673',
          'REDIRECT_URL' => '/folder/file.html',
          'GATEWAY_INTERFACE' => 'CGI/1.1',
          'SERVER_PROTOCOL' => 'HTTP/1.1',
          'REQUEST_METHOD' => 'GET',
          'QUERY_STRING' => '',
          'REQUEST_URI' => '/folder/file.html',
          'SCRIPT_NAME' => '/index.php',
          'PHP_SELF' => '/index.php',
          'REQUEST_TIME_FLOAT' => 1234567890.123,
          'REQUEST_TIME' => 1234567890,
        ];
        \CuteControllers\Router::start('');
    }

    /**
     * @depends test_get_instance
     */
    public function test_get_route_to_index()
    {
        $_SERVER = [
          'REDIRECT_STATUS' => '200',
          'HTTP_HOST' => 'foo.bar',
          'HTTP_CONNECTION' => 'keep-alive',
          'HTTP_CACHE_CONTROL' => 'no-cache',
          'HTTP_PRAGMA' => 'no-cache',
          'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
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
          'REMOTE_ADDR' => '1.2.3.4',
          'DOCUMENT_ROOT' => '/var/www',
          'SERVER_ADMIN' => 'webmaster@localhost',
          'SCRIPT_FILENAME' => '/var/www/index.php',
          'REMOTE_PORT' => '56673',
          'REDIRECT_URL' => '/folder/file.html',
          'GATEWAY_INTERFACE' => 'CGI/1.1',
          'SERVER_PROTOCOL' => 'HTTP/1.1',
          'REQUEST_METHOD' => 'GET',
          'QUERY_STRING' => '',
          'REQUEST_URI' => '/folder/file.html',
          'SCRIPT_NAME' => '/index.php',
          'PHP_SELF' => '/index.php',
          'REQUEST_TIME_FLOAT' => 1234567890.123,
          'REQUEST_TIME' => 1234567890,
        ];

        try {
            unlink('running_test/folder/file/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder/file');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/file.php');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test');
        } catch (\Exception $ex) {}

        mkdir('running_test');
        mkdir('running_test/folder');
        mkdir('running_test/folder/file');
        file_put_contents('running_test/folder/file/index.php', '<?php class w { public $whoami = "w"; }');
        file_put_contents('running_test/folder/file.php', '<?php class x { public $whoami = "x"; }');
        file_put_contents('running_test/folder/index.php', '<?php class y { public $whoami = "y"; }');
        file_put_contents('running_test/folder.php', '<?php class z { public $whoami = "z"; }');
        $router = new \CuteControllers\Router('running_test');

        $this->assertEquals('w', $router->get_responsible_controller()->whoami, 'Failed routing to x/y/index.php');
        unlink('running_test/folder/file/index.php');
        $this->assertEquals('x', $router->get_responsible_controller()->whoami, 'Failed routing to x/y.php');
        rmdir('running_test/folder/file');
        $this->assertEquals('x', $router->get_responsible_controller()->whoami, 'Failed routing to x/y.php');
        unlink('running_test/folder/file.php');
        $this->assertEquals('y', $router->get_responsible_controller()->whoami, 'Failed routing to x/index.php');
        unlink('running_test/folder/index.php');
        $this->assertEquals('z', $router->get_responsible_controller()->whoami, 'Failed routing to x.php');
        rmdir('running_test/folder');
        $this->assertEquals('z', $router->get_responsible_controller()->whoami, 'Failed routing to x.php');
        unlink('running_test/folder.php');
        rmdir('running_test');
    }

    /**
     * @depends test_get_instance
     * @expectedException BadFunctionCallException
     */
    public function test_no_path_throws_exception()
    {
        $router_instance = new \CuteControllers\Router();
        $router_instance->route();
    }


    /**
     * @depends test_get_instance
     */
    public function test_rewrite_array_populates()
    {
        $router_instance = new \CuteControllers\Router();

        $from = 'abc123!@#';
        $to = ')(*098zxy';

        $router_instance->add_rewrite($from, $to);

        $this->assertAttributeEquals(
            [[
                'from' => $from,
                'to'   => $to
            ]],
            'rewrites',
            $router_instance
        );
    }
}
