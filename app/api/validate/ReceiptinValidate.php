<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class ReceiptinValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'ReceiptId' => 'require',
        'WarehouseId' => 'require',
        'WarehouseName' => 'require',
        'Type' => 'require',//单据类型：1 采购收货，2 仓库调入，3 收渠道退货单，4 收店铺退货单
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'InstructionId' => 'require',
    ];

    protected $scene = [
        'create' => ['ReceiptId', 'WarehouseId', 'WarehouseName', 'Type', 'Goods', 'CodingCode', 'InstructionId'],
        'update' => ['ReceiptId', 'CodingCode'],
        'delete' => ['ReceiptId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'ReceiptId.require' => 'ReceiptId不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'WarehouseName.require' => 'WarehouseName不能为空',
        'Type.require' => 'Type不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'InstructionId.require' => 'InstructionId不能为空',
    ];

}
