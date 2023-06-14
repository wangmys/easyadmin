<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 单据应收单 商品  model
 */
class ErpFnBillReceivableGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Fnbillreceivablegoods';

    protected $schema = [
        'BillReceivableGoodsID' => 'nvarchar',
        'BillReceivableID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Quantity' => 'decimal',
        'Amount' => 'decimal',
        'Remark' => 'nvarchar',
        'RetailPrice' => 'decimal',
        'SettlementRatio' => 'decimal',
        'SettlementType' => 'nvarchar',
    ];

}