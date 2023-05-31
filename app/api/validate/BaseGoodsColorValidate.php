<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class BaseGoodsColorValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'ColorId' => 'require',
        'ColorGroup' => 'require',
        'ColorCode' => 'require',
        'ColorDesc' => 'require',
    ];

    protected $scene = [
        'create' => ['ColorId', 'ColorGroup', 'ColorCode', 'ColorDesc'],
        'update' => ['ColorId', 'ColorGroup', 'ColorCode', 'ColorDesc'],
        'delete' => ['ColorId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'ColorId.require' => 'ColorId不能为空',
        'ColorGroup.require' => 'ColorGroup不能为空',
        'ColorCode.require' => 'ColorCode不能为空',
        'ColorDesc.require' => 'ColorDesc不能为空',
    ];

}
