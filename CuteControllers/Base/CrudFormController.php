<?php

namespace CuteControllers\Base;

abstract class CrudFormController extends \CuteControllers\Base\Rest
{
    protected $static_values = array();

    public function before()
    {
        $model_class = static::$model;
        $this->form = new \FastForms\Form($model_class);

        $this->supporess = array();
        $this->form->static_values = $this->static_values;
    }

    public function get_create()
    {
        $form = $this->form;
        include_once(static::$template);
    }

    public function get_update()
    {
        $model_class = static::$model;
        $instance = new $model_class($this->request->request($model_class::$primary_key));
        $form = $this->form;
        include_once(static::$template);
    }

    public function post_update()
    {
        $model_class = static::$model;
        $instance = new $model_class($this->request->request($model_class::$primary_key));
        $form = $this->form;

        try {
            $form->update($instance);
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            include_once(static::$template);
            return;
        }

        $this->redirect('');
    }

    public function post_create()
    {
        $model_class = static::$model;
        $instance = NULL;
        $form = $this->form;

        try {
            $new_obj = $form->create();
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            include_once(static::$template);
            return;
        }

        if (\CuteControllers\Request::current()->query === '') {
            $this->redirect('update?' . $model_class::$primary_key . '=' . $new_obj->{$model_class::$primary_key});
        } else {
            $this->redirect('update?' . \CuteControllers\Request::current()->query . '&' .
                            $model_class::$primary_key . '=' . $new_obj->{$model_class::$primary_key});
        }
    }
}
