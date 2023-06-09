<?php
namespace app\admin\model\cip;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class DeptModel extends TimeModel
{
    protected $connection = 'cip';
    protected $table = 'dept';
}
