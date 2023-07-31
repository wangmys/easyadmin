<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class Ww_data_cussale14day extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Ww_data_cussale14day')
            ->setDescription('the Ww_data_cussale14day command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','500M');

		//生成json文件
		$data = Db::connect("mysql2")->Query($this->get_sql());
        $db = Db::connect("mysql");
        if ($data) {
            //先清空旧数据再跑
            $db->Query("truncate table ea_lyp_ww_cussale14day;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $db->table('ea_lyp_ww_cussale14day')->strict(false)->insertAll($val);
            }
        }
        echo 'okk';die;
    }

    protected function get_sql() {

        return "select 店铺名称, 二级时间分类 as 季节, 一级时间分类 as 年份, CASE 
        WHEN 二级时间分类 like '%夏%' and 二级分类='短T' and (当前零售价/零售价)<1 and 当前零售价<=50 THEN '引流款' 
        WHEN 二级时间分类 like '%夏%' and right(二级分类, 2)='长裤' and (当前零售价/零售价)<1 and 当前零售价<=100 THEN '引流款' 
        WHEN 二级时间分类 like '%夏%' and right(二级分类, 2)='短裤' and (当前零售价/零售价)<1 and 当前零售价<=70 THEN '引流款' 
        WHEN 二级时间分类 like '%夏%' and right(二级分类, 1)='衬' and (当前零售价/零售价)<1 and 当前零售价<=80 THEN '引流款' 
        WHEN 二级时间分类 not like '%夏%' and (当前零售价/零售价)<=0.9 THEN '引流款' 
        ELSE 风格 END AS 修改后风格, 一级分类, 二级分类, 分类, sum(数量) as 数量, sum(销售金额) as 销售金额, sum(零售价金额) as 零售价金额  from sp_customer_14day where 一级分类 not in ('人事物料', '物料', '助销品') group by 分类,店铺名称,修改后风格;";

    }

}
