<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class RetailValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'RetailID' => 'require',
        'CustomerId' => 'require',
        'CustomerName' => 'require',
        'Goods' => 'require|array',
        'RetailPayInfo' => 'require|array',
        'CodingCode' => 'require',
        'BillType' => 'require',
        'PrintNum' => 'require',
    ];

    protected $scene = [
        'create' => ['RetailID', 'CustomerId', 'CustomerName', 'Goods', 'RetailPayInfo', 'CodingCode', 'BillType', 'PrintNum'],
        'update' => ['RetailID', 'CodingCode'],
        'delete' => ['RetailID'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'RetailID.require' => 'RetailID不能为空',
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'Goods.array' => 'Goods必须为数组',
        'Goods.require' => 'Goods不能为空',
        'RetailPayInfo.array' => 'RetailPayInfo必须为数组',
        'RetailPayInfo.require' => 'RetailPayInfo不能为空',
        'CodingCode.require' => 'CodingCode不能为空',
        'BillType.require' => 'BillType不能为空',
        'PrintNum.require' => 'PrintNum不能为空',
    ];

}
