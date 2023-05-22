<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购单商品 model
 */
class ErpPurchaseGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchasegoods';

    protected $schema = [
        'PurchaseGoodsID' => 'nvarchar',
        'PurchaseID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'Remark' => 'nvarchar',
        'OrderID' => 'nvarchar',
        'DeliveryDate' => 'datetime',
        'CostPrice' => 'decimal',
        'CurrDeliveryDate' => 'datetime',
        'ExchangeRate' => 'decimal',
        'TaxRate' => 'decimal',
        'fcPrice' => 'decimal',
        'NoTaxRatePrice' => 'decimal',
        'IsCompleted' => 'bit',
        'CompletedUserId' => 'bigint',
        'CompletedUserName' => 'nvarchar',
        'CompletedTime' => 'datetime',
        'TrialCostPrice' => 'decimal',
    ];
}