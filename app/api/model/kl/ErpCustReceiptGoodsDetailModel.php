<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 收仓库收货单商品详情 model
 */
class ErpCustReceiptGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custreceiptgoodsdetail';
    protected $schema = [
        'ReceiptGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}