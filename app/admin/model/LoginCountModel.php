<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\facade\Db;

class LoginCountModel extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql';

    // 表名
    protected $name = 'login_count';

}