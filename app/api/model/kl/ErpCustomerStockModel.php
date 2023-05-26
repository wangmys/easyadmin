<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单库存流水表 model
 */
class ErpCustomerStockModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Customerstock';

    protected $schema = [
        'StockId' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CustomerName' => 'nvarchar',
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
    ];

    const INSERT = [
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}