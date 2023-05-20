<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库库存流水表 model
 */
class ErpWarehouseStockModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Warehousestock';

    protected $schema = [
        'StockId' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'WarehouseName' => 'nvarchar',
        'StockDate' => 'datetime',
        'BillType' => 'nvarchar',
        'BillId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'Quantity' => 'decimal',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
        'Remark' => 'nvarchar',
    ];

    const INSERT = [
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}