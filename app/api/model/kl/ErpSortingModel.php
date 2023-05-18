<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单支付表 model
 */
class ErpSortingModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Sorting';

    protected $schema = [
        'OrderType' => 'nvarchar',
        'OrderTypeText' => 'nvarchar',
        'SortingID' => 'nvarchar',
        'SortingDate' => 'datetime',
        'WarehouseId' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CodingCode' => 'nvarchar',
        'Remark' => 'nvarchar',
        'IsCompleted' => 'bit',
        'BranchId' => 'bigint',
        'WorkflowId' => 'bigint',
        'CodingCodeText' => 'nvarchar',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
    ];

    const INSERT = [
        'OrderType' => 'ErpOrder_BU',
        'OrderTypeText' => '补货',
        'IsCompleted' => 0,
        'BranchId' => '2',
        'CodingCode' => 'StartNode1',
        'CodingCodeText' => '未提交',
        'WorkflowId' => 1,
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
        'Type' => '0',
        'PrintNum' => '0',
    ];

}