<?php

require_once(dirname(__FILE__) . '/../CuteControllers/Router.php');

class ControllerTest extends PHPUnit_Framework_TestCase
{
    public function test_index()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => '');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $controller->cc_route();
        $this->assertEquals(1, $last_result);
    }

    public function test_fallback()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => '');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'post');
        $controller->cc_route();
        $this->assertEquals(2, $last_result);
    }

    public function test_named()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => 'test');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $controller->cc_route();
        $this->assertEquals(3, $last_result);
    }

    public function test_named_fallback()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => 'foo');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $controller->cc_route();
        $this->assertEquals(4, $last_result);
    }

    public function test_params_success()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => 'bar/foo');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $controller->cc_route();
        $this->assertEquals(5, $last_result);
    }

    public function test_nomethod()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => 'xyz');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $this->setExpectedException('\CuteControllers\HttpError');
        $controller->cc_route();
    }

    public function test_params_fail()
    {
        global $last_result;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => 'bar');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $this->setExpectedException('\CuteControllers\HttpError');
        $controller->cc_route();
    }

    public function test_before_after()
    {
        global $last_result, $foo_called, $bar_called;
        $controller = new ControllerTestObject();
        $controller->routing_information = (object)array('unmatched_path' => '');
        $controller->request = (object)array('file_ext' => 'txt', 'method' => 'get');
        $controller->cc_route();

        $this->assertEquals(true, $foo_called);
        $this->assertEquals(true, $bar_called);
    }
}

$last_result = 0;
$foo_called = false;
$bar_called = false;
class ControllerTestObject
{
    use \CuteControllers\Controller;

    public function get_index()
    {
        global $last_result;
        $last_result = 1;
    }

    public function action_index()
    {
        global $last_result;
        $last_result = 2;
    }

    public function get_test()
    {
        global $last_result;
        $last_result = 3;
    }

    public function action_foo()
    {
        global $last_result;
        $last_result = 4;
    }

    public function action_bar($p1)
    {
        global $last_result;
        $last_result = 5;
    }

    public function before_foo()
    {
        global $foo_called;
        $foo_called = true;
    }

    public function after_bar()
    {
        global $bar_called;
        $bar_called = true;
    }
}
