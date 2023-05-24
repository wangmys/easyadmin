<?php
declare (strict_types = 1);

namespace app\api\validate;

use think\Validate;

class CustomerValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'CustomerId' => 'require',
        'CustomerCode' => 'require',
        'CustomerName' => 'require',
        'MathodId' => 'require',
        'ShutOut' => 'require',
        'StateId' => 'require',
        'RoleCategoryId' => 'require',
        'AccountId' => 'require',
        'PosSalesmanControl' => 'require',
        'RoundingMode' => 'require',
        'RoundingType' => 'require',
        'RoundingUnit' => 'require',
        'PosPrintNum' => 'require',
        'ShopNature' => 'require',
        'IsCustReceiptDelivery' => 'require',
        'IsInCustomer' => 'require',
        'AllowNegativeInventory' => 'require',
        'IsPosStockCheck' => 'require',
        'DeliveryRoundingMode' => 'require',
        'DeliveryRoundingType' => 'require',
        'DeliveryRoundingUnit' => 'require',
        'IsNotReturnDiscount' => 'require',
        'IsAllowShopPickUp' => 'require',
        'IsInstructionCustOutbound' => 'require',
        'IsPosRetailInProportion' => 'require',
        'SupplyStockType' => 'require',
        'IsGetDefaultCustStock' => 'require',
        'IsGetDefaultVIPCustStock' => 'require',
        'IsAllowPreSale' => 'require',
        'RegionId' => 'require',
    ];

    protected $scene = [
        'create' => [
            'CustomerId', 'CustomerCode', 'CustomerName', 'MathodId', 'ShutOut'
        , 'StateId', 'RoleCategoryId', 'AccountId', 'PosSalesmanControl', 'RoundingMode', 'RoundingType', 'RoundingUnit', 'PosPrintNum', 'ShopNature', 'IsCustReceiptDelivery'
        , 'IsInCustomer', 'AllowNegativeInventory', 'IsPosStockCheck', 'DeliveryRoundingMode', 'DeliveryRoundingType', 'DeliveryRoundingUnit', 'IsNotReturnDiscount', 'IsAllowShopPickUp', 'IsInstructionCustOutbound', 'IsPosRetailInProportion'
        , 'SupplyStockType', 'IsGetDefaultCustStock', 'IsGetDefaultVIPCustStock', 'IsAllowPreSale', 'RegionId'
    ],
        'update' => [
            'CustomerId', 'CustomerCode', 'CustomerName', 'MathodId', 'ShutOut'
            , 'StateId', 'RoleCategoryId', 'AccountId', 'PosSalesmanControl', 'RoundingMode', 'RoundingType', 'RoundingUnit', 'PosPrintNum', 'ShopNature', 'IsCustReceiptDelivery'
            , 'IsInCustomer', 'AllowNegativeInventory', 'IsPosStockCheck', 'DeliveryRoundingMode', 'DeliveryRoundingType', 'DeliveryRoundingUnit', 'IsNotReturnDiscount', 'IsAllowShopPickUp', 'IsInstructionCustOutbound', 'IsPosRetailInProportion'
            , 'SupplyStockType', 'IsGetDefaultCustStock', 'IsGetDefaultVIPCustStock', 'IsAllowPreSale', 'RegionId'
        ],
        'delete' => ['CustomerId'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'CustomerId.require' => 'CustomerId不能为空',
        'CustomerCode.require' => 'CustomerCode不能为空',
        'CustomerName.require' => 'CustomerName不能为空',
        'MathodId.require' => 'MathodId不能为空',
        'ShutOut.require' => 'ShutOut不能为空',
        'StateId.require' => 'StateId不能为空',
        'RoleCategoryId.require' => 'RoleCategoryId不能为空',
        'AccountId.require' => 'AccountId不能为空',
        'PosSalesmanControl.require' => 'PosSalesmanControl不能为空',
        'RoundingMode.require' => 'RoundingMode不能为空',
        'RoundingType.require' => 'RoundingType不能为空',
        'RoundingUnit.require' => 'RoundingUnit不能为空',
        'PosPrintNum.require' => 'PosPrintNum不能为空',
        'ShopNature.require' => 'ShopNature不能为空',
        'IsCustReceiptDelivery.require' => 'IsCustReceiptDelivery不能为空',
        'IsInCustomer.require' => 'IsInCustomer不能为空',
        'AllowNegativeInventory.require' => 'AllowNegativeInventory不能为空',
        'IsPosStockCheck.require' => 'IsPosStockCheck不能为空',
        'DeliveryRoundingMode.require' => 'DeliveryRoundingMode不能为空',
        'DeliveryRoundingType.require' => 'DeliveryRoundingType不能为空',
        'DeliveryRoundingUnit.require' => 'DeliveryRoundingUnit不能为空',
        'IsNotReturnDiscount.require' => 'IsNotReturnDiscount不能为空',
        'IsAllowShopPickUp.require' => 'IsAllowShopPickUp不能为空',
        'IsInstructionCustOutbound.require' => 'IsInstructionCustOutbound不能为空',
        'IsPosRetailInProportion.require' => 'IsPosRetailInProportion不能为空',
        'SupplyStockType.require' => 'SupplyStockType不能为空',
        'IsGetDefaultCustStock.require' => 'IsGetDefaultCustStock不能为空',
        'IsGetDefaultVIPCustStock.require' => 'IsGetDefaultVIPCustStock不能为空',
        'IsAllowPreSale.require' => 'IsAllowPreSale不能为空',
        'RegionId.require' => 'RegionId不能为空',
    ];

}
