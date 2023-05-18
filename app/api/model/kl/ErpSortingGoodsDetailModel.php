<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单支付表 model
 */
class ErpSortingGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Sortinggoodsdetail';
    protected $schema = [
        'SortingGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}