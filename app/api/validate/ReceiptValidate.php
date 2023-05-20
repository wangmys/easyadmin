<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class ReceiptValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'ReceiptID' => 'require',
        'WarehouseId' => 'require',
        'WarehouseName' => 'require',
        'CustomerId' => 'require',
        'CustomerName' => 'require',
//        'Remark' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
        'Type' => 'require',
    ];

    protected $scene = [
        'create' => ['ReceiptID', 'WarehouseId', 'WarehouseName', 'CustomerId', 'CustomerName', 'Goods', 'CodingCode', 'Type'],
        'update' => ['ReceiptID', 'CodingCode'],
        'delete' => ['ReceiptID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'ReceiptID.require' => 'ReceiptID不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'WarehouseName.require' => 'WarehouseName不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'Remark.require' => 'Remark不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'Type.require' => 'Type不能为空',
    ];

}
