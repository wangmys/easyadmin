<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class OutboundValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'OutboundId' => 'require',
        'WarehouseId' => 'require',
        'WarehouseName' => 'require',
        'InWarehouseId' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'InstructionId' => 'require',
    ];

    protected $scene = [
        'create' => ['OutboundId', 'WarehouseId', 'WarehouseName', 'InWarehouseId', 'Goods', 'CodingCode', 'InstructionId'],
        'update' => ['OutboundId', 'CodingCode'],
        'delete' => ['OutboundId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'OutboundId.require' => 'OutboundId不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'WarehouseName.require' => 'WarehouseName不能为空',
        'InWarehouseId.require' => 'InWarehouseId不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'InstructionId.require' => 'InstructionId不能为空',
    ];

}
