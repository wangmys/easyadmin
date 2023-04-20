<?php
namespace app\admin\model\budongxiao;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpXwBudongxiaoYuncangkeyong extends TimeModel
{

    protected $connection = 'mysql2';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_ww_budongxiao_yuncangkeyong';

}
