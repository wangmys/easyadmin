<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨出库单商品 model
 */
class ErpOutboundGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Outboundgoods';

    protected $schema = [
        'OutboundGoodsId' => 'nvarchar',
        'OutboundId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'InstructionId' => 'nvarchar',
    ];
}