<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpSkcConfigModel extends TimeModel
{
    protected $connection = 'mysql2';
    protected $table = 'sp_skc_config';
}
