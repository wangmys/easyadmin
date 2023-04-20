<?php

namespace app\admin\model;
use app\common\model\TimeModel;

class Customorstocksale7 extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'customer_stock_sale_7day';

    public function yunChang()
    {
    	// 参数1：关联模型名称 参数2：关联模型的外键 参数3：当前模型的主键 
    	return $this->hasOne('app\admin\model\Wwcustomer', '店铺名称', '店铺名称');
    }

}