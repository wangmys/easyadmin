<?php

namespace app\api\model\store;


use app\common\model\TimeModel;

/**
 * 尺码库存
 * Class Zy
 * @package app\api\model\store
 */
class SpCustomerStockSaleSizeModel extends TimeModel
{
    protected $connection = 'mysql';
    // 表名
    protected $table = 'sp_customer_stock_sale_threeyear';
}