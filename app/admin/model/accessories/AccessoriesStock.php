<?php

namespace app\admin\model\accessories;


use app\common\model\TimeModel;

class AccessoriesStock extends TimeModel
{
    // 数据库配置
    protected $connection = 'mysql2';
    // 表名
    protected $name = 'customer_yinliu_stock';
}