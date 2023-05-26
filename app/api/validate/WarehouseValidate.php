<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class WarehouseValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'WarehouseId' => 'require',
        'WarehouseCode' => 'require',
        'WarehouseName' => 'require',
        'ShutOut' => 'require',
        'RoleCategoryId' => 'require',
        'AllowNegativeInventory' => 'require',
        'Isvirtual' => 'require',
        'IsCreateInWare' => 'require',
        'IsBoxBarCodeInput' => 'require',
        'IsGenerateReceipt' => 'require',
        'IsDefault' => 'require',
    ];

    protected $scene = [
        'create' => [
            'WarehouseId', 'WarehouseCode', 'WarehouseName', 'ShutOut', 'RoleCategoryId'
        , 'AllowNegativeInventory', 'Isvirtual', 'IsCreateInWare', 'IsBoxBarCodeInput', 'IsGenerateReceipt', 'IsDefault'
    ],
        'update' => [
            'WarehouseId', 'WarehouseCode', 'WarehouseName', 'ShutOut', 'RoleCategoryId'
        , 'AllowNegativeInventory', 'Isvirtual', 'IsCreateInWare', 'IsBoxBarCodeInput', 'IsGenerateReceipt', 'IsDefault'
    ],
        'delete' => ['WarehouseId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'WarehouseId.require' => 'WarehouseId不能为空',
        'WarehouseCode.require' => 'WarehouseCode不能为空',
        'WarehouseName.require' => 'WarehouseName不能为空',
        'ShutOut.require' => 'ShutOut不能为空',
        'RoleCategoryId.require' => 'RoleCategoryId不能为空',
        'AllowNegativeInventory.require' => 'AllowNegativeInventory不能为空',
        'Isvirtual.require' => 'Isvirtual不能为空',
        'IsCreateInWare.require' => 'IsCreateInWare不能为空',
        'IsBoxBarCodeInput.require' => 'IsBoxBarCodeInput不能为空',
        'IsGenerateReceipt.require' => 'IsGenerateReceipt不能为空',
        'IsDefault.require' => 'IsDefault不能为空',
    ];

}
