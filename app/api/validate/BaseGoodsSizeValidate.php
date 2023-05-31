<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class BaseGoodsSizeValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'SizeId' => 'require',
        'SizeClass' => 'require',
        'ClassName' => 'require',
        'Size' => 'require',
        'ViewOrder' => 'require',
    ];

    protected $scene = [
        'create' => ['SizeId', 'SizeClass', 'ClassName', 'Size', 'ViewOrder'],
        'update' => ['SizeId', 'SizeClass', 'ClassName', 'Size', 'ViewOrder'],
        'delete' => ['SizeId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'SizeId.require' => 'SizeId不能为空',
        'SizeClass.require' => 'SizeClass不能为空',
        'ClassName.require' => 'ClassName不能为空',
        'Size.require' => 'Size不能为空',
        'ViewOrder.require' => 'ViewOrder不能为空',
    ];

}
