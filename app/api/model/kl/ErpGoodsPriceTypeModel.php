<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpGoodsPriceTypeModel
 */
class ErpGoodsPriceTypeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Goodspricetype';

    protected $schema = [
        'GoodsId' => 'bigint', 
        'PriceId' => 'bigint', 
        'PriceName' => 'nvarchar', 
        'UnitPrice' => 'decimal', 
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
    ];

    const INSERT = [
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}