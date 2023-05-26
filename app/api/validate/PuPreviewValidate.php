<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class PuPreviewValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'PuPreviewID' => 'require',
        'WarehouseId' => 'require',
        'PreviewTaskID' => 'require',
        'WorkerID' => 'require',
        'IsCancel' => 'require',
        'IsPosition' => 'require',
        'CodingCode' => 'require',
        'Goods' => 'require|array',
    ];

    protected $scene = [
        'create' => ['PuPreviewID', 'WarehouseId', 'PreviewTaskID', 'WorkerID', 'IsCancel', 'IsPosition', 'CodingCode', 'Goods'],
        'update' => ['PuPreviewID', 'CodingCode'],
        'delete' => ['PuPreviewID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'PuPreviewID.require' => 'PuPreviewID不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'PreviewTaskID.require' => 'PreviewTaskID不能为空',
        'WorkerID.require' => 'WorkerID不能为空',
        'IsCancel.require' => 'IsCancel不能为空',
        'IsPosition.require' => 'IsPosition不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
