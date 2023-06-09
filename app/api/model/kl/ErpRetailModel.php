<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单主表 model
 */
class ErpRetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Retail';

    protected $schema = [
        'RetailID' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CustomerName' => 'nvarchar',
        'RetailDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'Remark' => 'nvarchar',
        'ClassName' => 'nvarchar',
        'VIPNo' => 'nvarchar',
        'SalesmanID' => 'nvarchar',
        'SalesmanName' => 'nvarchar',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'BillType' => 'int',
        'PrintNum' => 'int',
        'BillStatus' => 'int',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
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
        // 'PrintNum' => '0',
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