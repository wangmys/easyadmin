<?php

namespace app\admin\model\weather;


use app\common\model\TimeModel;

class Region extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'tianqi';

    // 表名
    protected $name = 'customers_region';

}