<?php

namespace app\admin\model;
use app\common\model\TimeModel;

class Customorstocksale7 extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_stock_sale_7day';

}