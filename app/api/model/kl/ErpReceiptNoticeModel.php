<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购入库指令单 model
 */
class ErpReceiptNoticeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receiptnotice';

    protected $schema = [
        'ReceiptNoticeId' => 'nvarchar',
        'ReceiptNoticeDate' => 'datetime',
        'ManualNo' => 'varchar',
        'WarehouseId' => 'nvarchar',
        'SupplyId' => 'nvarchar',
        'Remark' => 'varchar',
        'BranchId' => 'bigint',
        'IsCompleted' => 'bit',
        'IntType' => 'int',
        'CustomerId' => 'varchar',
        'InstructionId' => 'varchar',
        'PaymentDate' => 'datetime',
        'SalesItemId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
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
        'CodingCode' => 'StartNode1',
        'CodingCodeText' => '未提交',
        'WorkflowId' => 1,
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
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