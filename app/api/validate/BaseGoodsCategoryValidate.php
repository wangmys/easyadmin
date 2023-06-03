<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class BaseGoodsCategoryValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'Type' => 'require',
        'CategoryId' => 'require',
        'ParentId' => 'require',
        'CategoryName' => 'require',
        'ViewOrder' => 'require',
    ];

    protected $scene = [
        'create' => ['Type', 'CategoryId', 'ParentId', 'CategoryName', 'ViewOrder'],
        'update' => ['Type', 'CategoryId', 'ParentId', 'CategoryName', 'ViewOrder'],
        'delete' => ['Type', 'CategoryId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'Type.require' => 'Type不能为空',
        'CategoryId.require' => 'CategoryId不能为空',
        'ParentId.require' => 'ParentId不能为空',
        'CategoryName.require' => 'CategoryName不能为空',
        'ViewOrder.require' => 'ViewOrder不能为空',
    ];

}
