<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class SupplyValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'SupplyId' => 'require',
        'SupplyCode' => 'require',
        'SupplyName' => 'require',
        'ShutOut' => 'require',
        'RoleCategoryId' => 'require',
        'AllowNegativeInventory' => 'require',
        'IsDefault' => 'require',
    ];

    protected $scene = [
        'create' => ['SupplyId', 'SupplyCode', 'SupplyName', 'ShutOut', 'RoleCategoryId', 'AllowNegativeInventory', 'IsDefault'],
        'update' => ['SupplyId', 'SupplyCode', 'SupplyName', 'ShutOut', 'RoleCategoryId', 'AllowNegativeInventory', 'IsDefault'],
        'delete' => ['SupplyId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'SupplyId.require' => 'SupplyId不能为空',
        'SupplyCode.require' => 'SupplyCode不能为空',
        'SupplyName.require' => 'SupplyName不能为空',
        'ShutOut.require' => 'ShutOut不能为空',
        'RoleCategoryId.require' => 'RoleCategoryId不能为空',
        'AllowNegativeInventory.require' => 'AllowNegativeInventory不能为空',
        'IsDefault.require' => 'IsDefault不能为空',
    ];

}
