<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpWwChunxiaStockModel extends TimeModel
{
    protected $connection = 'mysql2';
    protected $table = 'sp_ww_chunxia_stock';
}
