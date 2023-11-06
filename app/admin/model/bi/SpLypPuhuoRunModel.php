<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoRunModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_run';

    const PUHUO_STATUS = [
        'ready'    => 0, #未开始
        'running'    => 1, #铺货中
        'finish'    => 2, #铺货完成
    ];

    const PUHUO_STATUS_TEXT = [
        self::PUHUO_STATUS['ready']  => '未开始',
        self::PUHUO_STATUS['running']  => '铺货中',
        self::PUHUO_STATUS['finish']  => '铺货完成',
    ];
}
