<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpWwXiaStock2022Model extends TimeModel
{
    protected $connection = 'mysql2';
    protected $table = 'sp_ww_xia_stock_2022';
}
