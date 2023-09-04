<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpLypPuhuoTiGoodsTypeModel extends TimeModel
{
    protected $connection = 'mysql';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_lyp_puhuo_ti_goods_type';
}
