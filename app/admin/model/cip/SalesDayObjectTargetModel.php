<?php
namespace app\admin\model\cip;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SalesDayObjectTargetModel extends TimeModel
{
    protected $connection = 'cip';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sales_day_object_target';
}
