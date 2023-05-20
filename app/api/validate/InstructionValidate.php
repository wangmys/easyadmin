<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class InstructionValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'InstructionId' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'Type' => 'require',//1 仓库调拨指令 2 店铺调拨 3 店铺退货 4渠道调拨
        'OutItemId' => 'require',
        'InItemId' => 'require',
        'IsJizhang' => 'require',
    ];

    protected $scene = [
        'create' => ['InstructionId', 'Goods', 'CodingCode', 'Type', 'OutItemId', 'InItemId', 'IsJizhang'],
        'update' => ['InstructionId', 'CodingCode'],
        'delete' => ['InstructionId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'InstructionId.require' => 'InstructionId不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'Type.require' => 'Type不能为空',
        'OutItemId.require' => 'OutItemId不能为空',
        'InItemId.require' => 'InItemId不能为空',
        'IsJizhang.require' => 'IsJizhang不能为空',
    ];

}
