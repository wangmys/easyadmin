<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class PurchaseValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'PurchaseID' => 'require',
        'SupplyId' => 'require',
        'ReceiptWareId' => 'require',
        'NatureName' => 'require',
        'BillType' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['PurchaseID', 'SupplyId', 'ReceiptWareId', 'NatureName', 'BillType', 'Goods', 'CodingCode'],
        'update' => ['PurchaseID', 'CodingCode'],
        'delete' => ['PurchaseID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'PurchaseID.require' => 'PurchaseID不能为空',
        'SupplyId.require' => 'SupplyId不能为空',
        'ReceiptWareId.require' => 'ReceiptWareId不能为空',
        'NatureName.require' => 'NatureName不能为空',
        'BillType.require' => 'BillType不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
