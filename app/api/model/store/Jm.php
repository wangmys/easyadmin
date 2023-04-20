<?php

namespace app\api\model\store;


use app\common\model\TimeModel;

/**
 * 加盟店铺冬季预计库存
 * Class Zy
 * @package app\api\model\store
 */
class Jm extends TimeModel
{
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_winter_yuji_stock_jm';
}