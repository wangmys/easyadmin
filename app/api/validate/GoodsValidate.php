<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class GoodsValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'GoodsId' => 'require',
        'GoodsNo' => 'require',
        'GoodsName' => 'require',
        'Status' => 'require',
        'UnitPrice' => 'require',
        'GoodsType' => 'require',
        'CategoryId' => 'require',
        'CategoryName' => 'require',
        'RoleCategoryId' => 'require',
        'RoleCategory' => 'require',
        'DiscountTypeId' => 'require',
        'IsHasSku' => 'require',
        'IsAdvance' => 'require',
        'IsOnlyShopPickUp' => 'require',
        'IsDecimal' => 'require',
        'IsOverseas' => 'require',
    ];

    protected $scene = [
        'create' => [
            'GoodsId', 'GoodsNo', 'GoodsName', 'Status', 'UnitPrice'
        , 'GoodsType', 'CategoryId', 'CategoryName', 'RoleCategoryId', 'RoleCategory', 'DiscountTypeId', 'IsHasSku', 'IsAdvance', 'IsOnlyShopPickUp', 'IsDecimal', 'IsOverseas'
    ],
        'update' => [
            'GoodsId', 'GoodsNo', 'GoodsName', 'Status', 'UnitPrice'
        , 'GoodsType', 'CategoryId', 'CategoryName', 'RoleCategoryId', 'RoleCategory', 'DiscountTypeId', 'IsHasSku', 'IsAdvance', 'IsOnlyShopPickUp', 'IsDecimal', 'IsOverseas'
        ],
        'delete' => ['GoodsId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'GoodsId.require' => 'GoodsId不能为空',
        'GoodsNo.require' => 'GoodsNo不能为空',
        'GoodsName.require' => 'GoodsName不能为空',
        'Status.require' => 'Status不能为空',
        'UnitPrice.require' => 'UnitPrice不能为空',
        'GoodsType.require' => 'GoodsType不能为空',
        'CategoryId.require' => 'CategoryId不能为空',
        'CategoryName.require' => 'CategoryName不能为空',
        'RoleCategoryId.require' => 'RoleCategoryId不能为空',
        'RoleCategory.require' => 'RoleCategory不能为空',
        'DiscountTypeId.require' => 'DiscountTypeId不能为空',
        'IsHasSku.require' => 'IsHasSku不能为空',
        'IsAdvance.require' => 'IsAdvance不能为空',
        'IsOnlyShopPickUp.require' => 'IsOnlyShopPickUp不能为空',
        'IsDecimal.require' => 'IsDecimal不能为空',
        'IsOverseas.require' => 'IsOverseas不能为空',
    ];

}
