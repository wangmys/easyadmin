<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoDaxiaomaCustomerSortModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_daxiaoma_customer_sort';

    const store_type = [
        '大码店'    => '1', 
        '正常店'    => '2',
        '小码店'    => '3',
    ];
}
