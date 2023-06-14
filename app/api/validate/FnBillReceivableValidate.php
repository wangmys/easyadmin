<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class FnBillReceivableValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'BillReceivableID' => 'require',
        'BillType' => 'require',
        'Summary' => 'require',
        'AccountID' => 'require',
        'BillID' => 'require',
        'Quantity' => 'require',
        'Amount' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['BillReceivableID', 'BillType',  'Summary', 'AccountID', 'BillID', 'Quantity', 'Amount',  'Goods', 'CodingCode'],
        'update' => ['BillReceivableID', 'CodingCode'],
        'delete' => ['BillReceivableID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'BillReceivableID.require' => 'BillReceivableID不能为空',
        'BillType.require' => 'BillType不能为空',
        'Summary.require' => 'Summary不能为空',
        'AccountID.require' => 'AccountID不能为空',
        'BillID.require' => 'BillID不能为空',
        'Quantity.require' => 'Quantity不能为空',
        'Amount.require' => 'Amount不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
