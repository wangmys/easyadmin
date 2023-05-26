<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨入库单 model
 */
class ErpReceiptModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receipt';

    protected $schema = [
        'ReceiptId' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'ReceiptDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'Type' => 'int',
        'WaitReceiptId' => 'nvarchar',
        'CheckReceiptId' => 'nvarchar',
        'SupplyId' => 'nvarchar',
        'DeliveryId' => 'nvarchar',
        'OutboundId' => 'nvarchar',
        'FromWarehouseId' => 'nvarchar',
        'ReturnId' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'Remark' => 'nvarchar',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',

        'IsDiff' => 'bit',
        'OrderType' => 'nvarchar',
        'OrderTypeText' => 'nvarchar',
        'PrintNum' => 'int',
        'BranchType' => 'int',
        'RoundingAmount' => 'decimal',
        'IsDefectiveGoods' => 'bit',
        'PaymentDate' => 'datetime',
        'BillType' => 'varchar',
        'IsNoCreateReceivable' => 'bit',
        'BillSource' => 'varchar',
        'SalesItemId' => 'bigint',
        'BusinessManId' => 'bigint',
        'NatureName' => 'nvarchar',
    ];

    const INSERT = [
        'BranchId' => '2',
        'BranchType' => '0',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
        'CodingCode' => 'StartNode1',
        'CodingCodeText' => '未提交',
        'OrderType' => 'ErpOrder_BU',
        'OrderTypeText' => '补货',
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