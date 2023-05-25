<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库资料 model
 */
class ErpWarehouseModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Warehouse';

    protected $schema = [
        'WarehouseId' => 'nvarchar', 
        'WarehouseCode' => 'nvarchar',
        'WarehouseName' => 'nvarchar',
        'Tel' => 'nvarchar',
        'Contact' => 'nvarchar',
        'Fax' => 'nvarchar',
        'ShutOut' => 'bit',
        'ZipCode' => 'nvarchar',
        'RegionId' => 'bigint',
        'Address' => 'nvarchar',
        'RoleCategoryId' => 'bigint',
        'BranchId' => 'bigint',
        'AllowNegativeInventory' => 'bit', 
        'Isvirtual' => 'bit', 
        'CreateTime' => 'datetime', 
        'CreateUserId' => 'bigint', 
        'CreateUserName' => 'nvarchar', 
        'UpdateTime' => 'datetime', 
        'UpdateUserId' => 'bigint', 
        'UpdateUserName' => 'nvarchar', 
        'IsRepair' => 'bit', 
        'IsWholesaler' => 'bit', 
        'IsCreateInWare' => 'bit', 
        'IsHqSendGoods' => 'bit', 
        'InPriceId' => 'int', 
        'IsGoodsPublish' => 'bit', 
        'IsBoxBarCodeInput' => 'bit', 
        'IsGenerateReceipt' => 'bit', 
        'CompanyId' => 'nvarchar', 
        'IsPdaTaskSuggestAutoCompleted' => 'bit', 
        'IsSync' => 'bit', 
        'StateId' => 'bigint', 
        'State' => 'nvarchar', 
        'CityId' => 'bigint', 
        'City' => 'nvarchar', 
        'DistrictId' => 'bigint', 
        'District' => 'nvarchar', 
        'IsDefault' => 'bit', 
        'IsCosting' => 'bit', 
    ];

    const INSERT = [
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}