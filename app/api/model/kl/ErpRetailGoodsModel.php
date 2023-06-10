<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单货品表 model
 */
class ErpRetailGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Retailgoods';

    protected $schema = [
        'RetailGoodsID' => 'nvarchar',
        'RetailID' => 'nvarchar',
        'SalesmanID' => 'nvarchar',
        'SalesmanName' => 'nvarchar',
        'Status' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'DiscountPrice' => 'decimal',
        'Discount' => 'decimal',
        'Quantity' => 'decimal',
        'Remark' => 'nvarchar',
        'PromotionId' => 'varchar',
        'CostPrice' => 'decimal',
        'GUnitPrice' => 'decimal',
        'GDiscount' => 'decimal',
        'DzUnitPrice' => 'decimal',
        'RetailPrice' => 'decimal',
        'ReturnRetailID' => 'nvarchar',
        'SalesPromotionId' => 'varchar',
    ];

}