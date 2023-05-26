<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class CustOutboundValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'CustOutboundId' => 'require',
        'CustomerId' => 'require',
        'CustomerName' => 'require',
        'InCustomerId' => 'require',
        'InCustomerName' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'InstructionId' => 'require',
    ];

    protected $scene = [
        'create' => ['CustOutboundId', 'CustomerId', 'CustomerName', 'InCustomerId', 'InCustomerName', 'Goods', 'CodingCode', 'InstructionId'],
        'update' => ['CustOutboundId', 'CodingCode'],
        'delete' => ['CustOutboundId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'CustOutboundId.require' => 'CustOutboundId不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'InCustomerId.require' => 'InCustomerId不能为空',
        'InCustomerName.require' => 'InCustomerName不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'InstructionId.require' => 'InstructionId不能为空',
    ];

}
