<?php

namespace app\api\model\store;


use app\common\model\TimeModel;

/**
 * 店铺库存尺码表
 * Class StockSaleSize
 * @package app\api\model\store
 */
class StockSaleSize extends TimeModel
{
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_stock_sale_size';
}