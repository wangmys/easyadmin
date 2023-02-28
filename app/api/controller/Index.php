<?php
declare (strict_types = 1);

namespace app\api\controller;

class Index
{
    public function index()
    {
        return '您好！这是一个[api]示例应用';
    }

    /**
     * 这里是列表
     */
    public function list(){
        echo '<pre>';
        print_r('这里是列表');
        die;
    }
}
