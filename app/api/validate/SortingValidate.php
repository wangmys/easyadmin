<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class SortingValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'SortingID' => 'require',
        'WarehouseId' => 'require',
        'CustomerId' => 'require',
//        'Remark' => 'require',
        'Goods' => 'require|array',
    ];

    protected $scene = [
        'create' => ['SortingID', 'WarehouseId', 'CustomerId', 'Remark', 'Goods'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'SortingID.require' => 'SortingID不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'Remark.require' => 'Remark不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
    ];

}
