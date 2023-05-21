<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺退货单商品详情 model
 */
class ErpReturnGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Returngoodsdetail';
    protected $schema = [
        'ReturnGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}