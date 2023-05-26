<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨指令单/店铺调拨指令单 商品详情 model
 */
class ErpInstructionGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Instructiongoodsdetail';
    protected $schema = [
        'InstructionGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}