<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调出单 model
 */
class ErpCustOutboundModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custoutbound';

    protected $schema = [
        'CustOutboundId' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'CustomerName' => 'nvarchar',
        'InCustomerId' => 'nvarchar',
        'InCustomerName' => 'nvarchar',
        'CustOutboundDate' => 'datetime',
        'ManualNo' => 'nvarchar',
        'Remark' => 'nvarchar',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'IsCompleted' => 'bit',
        'PrintNum' => 'int',
        'CompletedTime' => 'datetime',
        'SalesmanID' => 'nvarchar',
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