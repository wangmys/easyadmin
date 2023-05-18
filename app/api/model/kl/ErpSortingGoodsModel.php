<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单支付表 model
 */
class ErpSortingGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Sortinggoods';

    protected $schema = [
        'SortingGoodsID' => 'nvarchar',
        'SortingID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
    ];
}