<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购单商品详情 model
 */
class ErpPurchaseGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchasegoodsdetail';
    protected $schema = [
        'PurchaseGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}