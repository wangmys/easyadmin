<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库收货单 model
 */
class ErpDeliveryModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Delivery';

    protected $schema = [
        'DeliveryID' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'DeliveryDate' => 'datetime',
        'CustomerId' => 'nvarchar',
        'IsCompleted' => 'bit',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'Remark' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
    ];

    const INSERT = [
        'IsCompleted' => 0,
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
        'CodingCode' => 'StartNode1',
        'CodingCodeText' => '未提交',
        'WorkflowId' => 1,
        'PrintNum' => '0',
    ];

    const CodingCode = [
        'NOTCOMMIT'    => 'StartNode1', #'未提交'
        'HADCOMMIT'    => 'EndNode2' #'已审结'
    ];

    const CodingCode_TEXT = [
        self::CodingCode['NOTCOMMIT']  => '未提交',
        self::CodingCode['HADCOMMIT']  => '已审结'
    ];

}