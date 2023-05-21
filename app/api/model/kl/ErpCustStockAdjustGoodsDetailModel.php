<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调整单商品详情 model
 */
class ErpCustStockAdjustGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custstockadjustgoodsdetail';
    protected $schema = [
        'StockAdjustGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}