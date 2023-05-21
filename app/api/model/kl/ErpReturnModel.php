<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺退货单 model
 */
class ErpReturnModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Return';

    protected $schema = [
        'ReturnID' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CustomerName' => 'nvarchar',
        'ReturnDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'ReturnNoticeID' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'WarehouseName' => 'nvarchar',
        'Remark' => 'nvarchar',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'BranchId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
        'IsCompleted' => 'bit',
        'IsDefectiveGoods' => 'bit',
        'IsUnsalable' => 'bit',
        'IsToPDA' => 'bit',
        'PrintNum' => 'int',
        'StateId' => 'bigint',
        'State' => 'nvarchar',
        'CityId' => 'bigint',
        'City' => 'nvarchar',
        'DistrictId' => 'bigint',
        'District' => 'nvarchar',
        'StreetId' => 'bigint',
        'Street' => 'nvarchar',
        'Address' => 'nvarchar',
        'Contact' => 'nvarchar',
        'Tel' => 'nvarchar',
        'CompletedTime' => 'datetime',
        'BillSource' => 'varchar',
        'SalesItemId' => 'bigint',
        'SalesmanID' => 'nvarchar',
        'OrderType' => 'nvarchar',
        'OrderTypeText' => 'nvarchar',
        'BillType' => 'int',
        'BusinessManId' => 'bigint',
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
//        'OrderType' => 'ErpOrder_BU',
//        'OrderTypeText' => '补货',
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