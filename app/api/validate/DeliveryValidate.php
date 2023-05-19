<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class DeliveryValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'DeliveryID' => 'require',
        'WarehouseId' => 'require',
        'CustomerId' => 'require',
//        'Remark' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['DeliveryID', 'WarehouseId', 'CustomerId', 'Goods', 'CodingCode'],
        'update' => ['DeliveryID', 'CodingCode'],
        'delete' => ['DeliveryID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'DeliveryID.require' => 'DeliveryID不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'Remark.require' => 'Remark不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
