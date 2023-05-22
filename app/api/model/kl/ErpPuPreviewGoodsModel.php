<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购预收单商品 model
 */
class ErpPuPreviewGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Pupreviewgoods';

    protected $schema = [
        'PuPreviewGoodsID' => 'nvarchar',
        'PuPreviewID' => 'nvarchar',
        'SupplyId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'Quantity' => 'decimal',
        'ViewOrder' => 'int',
    ];
}