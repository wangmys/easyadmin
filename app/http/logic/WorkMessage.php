<?php

namespace app\http\logic;

use app\admin\model\SystemAdmin;

/**
 * 工作流逻辑层
 * Class WorkMessage
 * @package app\http\logic
 */
class WorkMessage
{
    protected $worker;
    protected $list;
    protected $redis;

    public function __construct($worker,$redis,$list)
    {
        $this->worker = $worker;
        $this->redis = $redis;
        $this->list = $list;
    }

    /**
     * 获取客户列表
     */
    public function getUserList()
    {
        $list = SystemAdmin::field('id,username')->select();
        echo '<pre>';
        print_r(555);
//        print_r($list->toArray());
//        die;
    }

    /**
     * 获取uid列表
     */
    public function getUidList()
    {
        echo '<pre>';
//        print_r(array_keys($this->list));
        print_r(666);
//        die;
    }
}