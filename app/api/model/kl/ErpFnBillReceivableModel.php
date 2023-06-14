<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 单据应收单 model
 */
class ErpFnBillReceivableModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Fnbillreceivable';

    protected $schema = [
        'BillReceivableID' => 'nvarchar',
        'BillReceivableDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'BillType' => 'varchar',
        'Summary' => 'varchar',
        'AccountID' => 'nvarchar',
        'BillID' => 'nvarchar',
        'Quantity' => 'decimal',
        'Amount' => 'decimal',
        'IsCompleted' => 'bit',
        'BranchId' => 'bigint',
        'CodingCode' => 'varchar',
        'CodingCodeText' => 'varchar',
        'WorkflowId' => 'bigint',
        'Remark' => 'nvarchar',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
        'BillDate' => 'datetime',
        'CustomerId' => 'nvarchar',
        'RoundingAmount' => 'decimal',
        'PrintNum' => 'int',
        'SalesItemId' => 'bigint',
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