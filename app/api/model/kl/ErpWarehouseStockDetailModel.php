<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库库存流水详情表 model
 */
class ErpWarehouseStockDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Warehousestockdetail';

    protected $schema = [
        'StockId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];

}