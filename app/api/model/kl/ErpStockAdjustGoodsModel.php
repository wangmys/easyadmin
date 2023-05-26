<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 库存调整单商品 model
 */
class ErpStockAdjustGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Stockadjustgoods';

    protected $schema = [
        'StockAdjustGoodsID' => 'nvarchar',
        'StockAdjustID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Quantity' => 'decimal',
        'Remark' => 'varchar',
        'CostPrice' => 'decimal',
    ];
}