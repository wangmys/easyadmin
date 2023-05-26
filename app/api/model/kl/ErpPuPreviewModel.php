<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购预收单 model
 */
class ErpPuPreviewModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Pupreview';

    protected $schema = [
        'PuPreviewID' => 'nvarchar',
        'WarehouseId' => 'nvarchar',
        'PuPreviewDate' => 'datetime',
        'PreviewTaskID' => 'nvarchar',
        'IsComplete' => 'bit',
        'CompleteTime' => 'datetime',
        'WorkerID' => 'bigint',
        'IsCancel' => 'bit',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
        'IsPosition' => 'bit',
        'BranchId' => 'bigint',
        'CodingCode' => 'nvarchar',
        'CodingCodeText' => 'nvarchar',
        'WorkflowId' => 'bigint',
        'ReceiptNoticeId' => 'nvarchar',
    ];

    const INSERT = [
        'IsComplete' => 0,
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