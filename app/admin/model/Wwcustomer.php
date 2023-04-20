<?php

namespace app\admin\model;
use app\common\model\TimeModel;

class Wwcustomer extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'ww_customer';

    protected $visible = ['云仓', '商品负责人'];

}