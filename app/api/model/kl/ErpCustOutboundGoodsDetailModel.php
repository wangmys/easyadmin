<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调出单商品详情 model
 */
class ErpCustOutboundGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custoutboundgoodsdetail';
    protected $schema = [
        'CustOutboundGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}