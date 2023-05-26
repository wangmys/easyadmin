<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class CustStockAdjustValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'StockAdjustID' => 'require',
        'CustomerId' => 'require',
        'CustomerName' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['StockAdjustID', 'CustomerId', 'CustomerName', 'Goods', 'CodingCode'],
        'update' => ['StockAdjustID', 'CodingCode'],
        'delete' => ['StockAdjustID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'StockAdjustID.require' => 'StockAdjustID不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
