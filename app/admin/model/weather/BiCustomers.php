<?php

namespace app\admin\model\weather;


use app\common\model\TimeModel;

class BiCustomers extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql2';

    // 表名
    protected $table = 'customer';

    /**
     * 获取店铺省份列表
     */
    public function getList()
    {
        // 实例化
        $model = $this;
        $list = $model->group('State')->column('LEFT(State,2)  State');
        if(!empty($list[0]['State'])){
            return array_column($list,'State');
        }
        return [];
    }
}