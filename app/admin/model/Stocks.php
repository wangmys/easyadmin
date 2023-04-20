<?php

namespace app\admin\model;


use app\common\model\TimeModel;

class Stocks extends TimeModel
{
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_stock_sale_year';
}