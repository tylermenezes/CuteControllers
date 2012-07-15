<?php

namespace CuteControllers\Base;

abstract class Controller
{
    protected $request;

    public function __construct(\CuteControllers\Request $request)
    {
        $this->request = $request;
    }

    abstract public function route();
}