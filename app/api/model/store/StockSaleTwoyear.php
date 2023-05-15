<?php

namespace app\api\model\store;


use app\common\model\TimeModel;

/**
 * 店铺库存twoyear表
 * Class Zy
 * @package app\api\model\store
 */
class StockSaleTwoyear extends TimeModel
{
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_stock_sale_twoyear';
}