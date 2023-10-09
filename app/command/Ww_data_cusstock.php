<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class Ww_data_cusstock extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Ww_data_cusstock')
            ->setDescription('the Ww_data_cusstock command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','500M');

		//生成json文件
		$data = Db::connect("mysql2")->Query($this->get_sql());
        $db = Db::connect("mysql");
        if ($data) {
            //先清空旧数据再跑
            $db->Query("truncate table ea_lyp_ww_cusstock;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $db->table('ea_lyp_ww_cusstock')->strict(false)->insertAll($val);
            }
        }
        echo 'okk';die;
    }

    protected function get_sql() {

        return "select T.店铺名称, T.季节, T.一级时间分类 as 年份, T.修改后风格, T.一级分类, T.二级分类, T.分类, sum(T.库存数量合计) as 库存数量合计, sum(T.SKC数) as SKC数, sum(T.库存金额) as 库存金额  
        from 
        (
        select cs.店铺名称
				, case when cs.季节 like '%春%' then '春季' 
				when cs.季节 like '%夏%' then '夏季' 
				when cs.季节 like '%秋%' then '秋季' 
				when cs.季节 like '%冬%' then '冬季' 
				else  cs.季节 end as 季节 
				, g.一级时间分类, CASE 
                     WHEN cs.季节 like '%夏%' and g.二级分类='短T' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=50 THEN '引流款' 
                     WHEN cs.季节 like '%夏%' and right(g.二级分类, 2)='长裤' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=100 THEN '引流款' 
                     WHEN cs.季节 like '%夏%' and right(g.二级分类, 2)='短裤' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=70 THEN '引流款' 
                     WHEN cs.季节 like '%夏%' and right(g.二级分类, 1)='衬' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=80 THEN '引流款' 
                     WHEN cs.季节 not like '%夏%' and (cs.当前零售价/cs.零售价)<=0.9 THEN '引流款' 
                     ELSE g.风格 END AS 修改后风格, g.一级分类, g.二级分类, g.分类, sum(cs.合计) as 库存数量合计
										 , case when cs.合计>0 then 1  
										 else 0 end   
										 as SKC数 
										 , cs.当前零售价*cs.合计 as 库存金额, g.货号 from sjp_customer_stock cs 
        inner join sjp_goods g on cs.货号=g.货号 
        group by cs.店铺名称, g.货号 
        ) as T 
				group by T.分类,T.店铺名称,T.修改后风格,T.季节,T.一级时间分类;";

    }

}
