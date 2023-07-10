<?php
namespace app\admin\model\bi;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class CustomerKcSkByWangweiModel extends TimeModel
{
    protected $connection = 'mysql2';
    protected $table = 'customer_kc_sk_by_wangwei';
}
