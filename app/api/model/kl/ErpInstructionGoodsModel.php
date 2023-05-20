<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨指令单/店铺调拨指令单 商品 model
 */
class ErpInstructionGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Instructiongoods';

    protected $schema = [
        'InstructionGoodsId' => 'nvarchar',
        'InstructionId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'OutPrice' => 'decimal',
        'OutDiscount' => 'decimal',
        'InPrice' => 'decimal',
        'InDiscount' => 'decimal',
        'Quantity' => 'decimal',
        'Remark' => 'nvarchar',
        'ReturnApplyID' => 'nvarchar',
        'JUnitPrice' => 'decimal',
        'JDiscount' => 'decimal',
        'InJUnitPrice' => 'decimal',
        'InJDiscount' => 'decimal',
        'InstructionApplyId' => 'nvarchar',
    ];
}