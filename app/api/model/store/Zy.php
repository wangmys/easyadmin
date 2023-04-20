<?php

namespace app\api\model\store;


use app\common\model\TimeModel;

/**
 * 直营店铺冬季预计库存数据
 * Class Zy
 * @package app\api\model\store
 */
class Zy extends TimeModel
{
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_winter_yuji_stock_zy';
}