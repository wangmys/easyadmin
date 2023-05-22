<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class ReturnValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'ReturnID' => 'require',
        'CustomerId' => 'require',
        'CustomerName' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'InstructionId' => 'require',
    ];

    protected $scene = [
        'create' => ['ReturnID', 'CustomerId', 'CustomerName', 'Goods', 'CodingCode', 'InstructionId'],
        'update' => ['ReturnID', 'CodingCode'],
        'delete' => ['ReturnID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'ReturnID.require' => 'ReturnID不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'InstructionId.require' => 'InstructionId不能为空',
    ];

}
