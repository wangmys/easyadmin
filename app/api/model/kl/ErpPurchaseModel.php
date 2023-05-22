<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购单 model
 */
class ErpPurchaseModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchase';

    protected $schema = [
        'PurchaseID' => 'nvarchar',
        'SupplyId' => 'nvarchar',
        'PurchaseDate' => 'datetime',
        'ManualNo' => 'varchar',
        'Remark' => 'varchar',
        'IsCompleted' => 'bit',
        'BranchId' => 'bigint',
        'WorkflowId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
        'ReceiptWareId' => 'varchar',
        'Currency' => 'nvarchar',
        'NatureName' => 'nvarchar',
        'BillType' => 'int',
        'GiftPurchaseId' => 'nvarchar',
        'LogisticsAmount' => 'decimal',
        'sfAmount' => 'decimal',
        'CustomerId' => 'nvarchar',
        'OrderType' => 'nvarchar',
        'OrderTypeText' => 'nvarchar',
        'SalesItemId' => 'bigint',
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