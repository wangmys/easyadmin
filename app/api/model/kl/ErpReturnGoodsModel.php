<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺退货单商品 model
 */
class ErpReturnGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Returngoods';

    protected $schema = [
        'ReturnGoodsID' => 'nvarchar',
        'ReturnID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Discount' => 'decimal',
        'Quantity' => 'decimal',
        'Remark' => 'nvarchar',
        'ReturnNoticeID' => 'nvarchar',
        'InstructionId' => 'nvarchar',
        'CostPrice' => 'decimal',
    ];
}