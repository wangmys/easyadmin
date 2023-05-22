<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨出库单商品详情 model
 */
class ErpOutboundGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Outboundgoodsdetail';
    protected $schema = [
        'OutboundGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}