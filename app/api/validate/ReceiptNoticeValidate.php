<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class ReceiptNoticeValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'ReceiptNoticeId' => 'require',
        'PurchaseID' => 'require',
        'WarehouseId' => 'require',
        'SupplyId' => 'require',
        'Goods' => 'require|array',
        'CodingCode' => 'require',
    ];

    protected $scene = [
        'create' => ['ReceiptNoticeId', 'PurchaseID', 'WarehouseId', 'SupplyId', 'Goods', 'CodingCode'],
        'update' => ['ReceiptNoticeId', 'CodingCode'],
        'delete' => ['ReceiptNoticeId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'ReceiptNoticeId.require' => 'ReceiptNoticeId不能为空',
        'PurchaseID.require' => 'PurchaseID不能为空',
        'WarehouseId.require' => 'WarehouseId不能为空',
        'SupplyId.require' => 'SupplyId不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
    ];

}
