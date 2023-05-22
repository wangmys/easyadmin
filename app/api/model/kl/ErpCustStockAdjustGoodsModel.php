<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调整单商品 model
 */
class ErpCustStockAdjustGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custstockadjustgoods';

    protected $schema = [
        'StockAdjustGoodsID' => 'nvarchar',
        'StockAdjustID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Quantity' => 'decimal',
        'Remark' => 'nvarchar',
        'CostPrice' => 'decimal',
    ];
}