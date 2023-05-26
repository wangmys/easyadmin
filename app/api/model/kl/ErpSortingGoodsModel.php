<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 出货指令单商品 model
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