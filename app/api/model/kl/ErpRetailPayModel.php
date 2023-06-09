<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单支付表 model
 */
class ErpRetailPayModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Retailpay';

    protected $schema = [
        'RetailID' => 'nvarchar',
        'PaymentID' => 'nvarchar',
        'PaymentName' => 'nvarchar',
        'PayMoney' => 'decimal',
        'Balance' => 'decimal',
        'Remark' => 'varchar',
        'PayBillId' => 'varchar',
        'CreateUserName' => 'nvarchar',
        'UpdateUserName' => 'nvarchar',
        'CreateUserId' => 'bigint',
        'UpdateUserId' => 'bigint',
        'CreateTime' => 'datetime',
        'UpdateTime' => 'datetime',
    ];

    const INSERT = [
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}