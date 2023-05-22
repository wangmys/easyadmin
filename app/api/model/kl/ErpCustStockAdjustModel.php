<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 店铺调整单 model
 */
class ErpCustStockAdjustModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custstockadjust';

    protected $schema = [
        'StockAdjustID' => 'nvarchar',
        'CustomerId' => 'nvarchar',
        'AdjustDate' => 'datetime',
        'ManualNo' => 'varchar',
        'Remark' => 'varchar',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'StockAdjustAttributesId' => 'bigint',
        'BillSource' => 'varchar',
        'SalesmanID' => 'nvarchar',
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