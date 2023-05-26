<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 供应商资料 model
 */
class ErpSupplyModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Supply';

    protected $schema = [
        'SupplyId' => 'nvarchar',
        'SupplyCode' => 'nvarchar',
        'SupplyName' => 'nvarchar',
        'Tel' => 'nvarchar',
        'Contact' => 'nvarchar',
        'Fax' => 'nvarchar',
        'ShutOut' => 'bit',
        'ZipCode' => 'nvarchar',
        'RegionId' => 'bigint',
        'StateId' => 'bigint',
        'State' => 'nvarchar',
        'CityId' => 'bigint',
        'City' => 'nvarchar',
        'DistrictId' => 'bigint',
        'District' => 'nvarchar',
        'Address' => 'nvarchar',
        'RoleCategoryId' => 'bigint',
        'BranchId' => 'bigint',
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
        // 'Version' => 'nvarchar',
        'IsHeadquarters' => 'bit',
        'SupplyNameLength' => 'nvarchar',
        'CustomItem1' => 'nvarchar',
        'CustomItem2' => 'nvarchar',
        'CustomItem3' => 'nvarchar',
        'IsEc_GetE3Stock' => 'bit',
        'AllowNegativeInventory' => 'bit',
        'IsDefault' => 'bit',
    ];

    const INSERT = [
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}