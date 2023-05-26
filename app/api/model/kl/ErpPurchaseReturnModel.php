<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购退货单 model
 */
class ErpPurchaseReturnModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchasereturn';

    protected $schema = [
        'PurchaseReturnId' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'PurchaseReturnDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'WaitReceiptId' => 'nvarchar',
        'CheckReceiptId' => 'nvarchar',
        'PurchaseID' => 'nvarchar',
        'SupplyId' => 'nvarchar',
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
        'PrintNum' => 'int',
        'BranchType' => 'int',
        'PaymentDate' => 'datetime',
        'BillSource' => 'varchar',
        'SalesItemId' => 'bigint',
        'BusinessManId' => 'bigint',
        'NatureId' => 'bigint',
    ];

    const INSERT = [
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
        'CodingCode' => 'StartNode1',
        'CodingCodeText' => '未提交',
        'WorkflowId' => 1,
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