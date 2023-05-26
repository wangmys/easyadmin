<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class StockAdjustValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'StockAdjustID' => 'require',
        'WarehouseId' => 'require',
        'WarehouseName' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['StockAdjustID', 'WarehouseId', 'WarehouseName', 'Goods', 'CodingCode'],
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
        'WarehouseId.require' => 'WarehouseId不能为空',
        'WarehouseName.require' => 'WarehouseName不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
