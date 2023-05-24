<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨指令单/店铺调拨指令单 model
 */
class ErpInstructionModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Instruction';

    protected $schema = [
        'Type' => 'nvarchar',
        'OutItemId' => 'nvarchar',
        'InItemId' => 'nvarchar',
        'IsJizhang' => 'bit',
        'JizhangTime' => 'datetime',
        'OutBillId' => 'varchar',
        'InBillId' => 'varchar',
        'OrderType' => 'nvarchar',
        'OrderTypeText' => 'nvarchar',
        'InOrderType' => 'nvarchar',
        'InOrderTypeText' => 'nvarchar',
        'InstructionId' => 'nvarchar',
        'InstructionDate' => 'datetime',
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
        'InOrderType' => 'ErpOrder_BU',
        'InOrderTypeText' => '补货',
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

    const CodingCode = [
        'NOTCOMMIT'    => 'StartNode1', #'未提交'
        'HADCOMMIT'    => 'EndNode2' #'已审结'
    ];

    const CodingCode_TEXT = [
        self::CodingCode['NOTCOMMIT']  => '未提交',
        self::CodingCode['HADCOMMIT']  => '已审结'
    ];

}