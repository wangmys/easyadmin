<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调出单商品 model
 */
class ErpCustOutboundGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custoutboundgoods';

    protected $schema = [
        'CustOutboundGoodsId' => 'nvarchar',
        'CustOutboundId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'Remark' => 'nvarchar',
        'InstructionId' => 'nvarchar',
        'CostPrice' => 'decimal',
    ];
}