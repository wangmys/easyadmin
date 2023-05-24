<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 库存调整单商品详情 model
 */
class ErpStockAdjustGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Stockadjustgoodsdetail';
    protected $schema = [
        'StockAdjustGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}