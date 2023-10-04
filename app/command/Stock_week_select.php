<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpCustomerStockSaleThreeyear2WeekSelectModel;

class Stock_week_select extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Stock_week_select')
            ->setDescription('the Stock_week_select command');
    }

	protected function execute(Input $input, Output $output)
    {
		$db = Db::connect("mysql");
		$db->Query("truncate table sp_customer_stock_sale_threeyear2_week_select;");
		
		$TimeCategoryName2 = $db->query("select 'TimeCategoryName2' as param_name, TimeCategoryName2 as name, TimeCategoryName2 as value from sp_customer_stock_sale_threeyear2_week where TimeCategoryName2<>'' group by TimeCategoryName2;");
		$TimeCategoryName = $db->query("select 'TimeCategoryName' as param_name, TimeCategoryName as name, TimeCategoryName as value from sp_customer_stock_sale_threeyear2_week where TimeCategoryName<>'' group by TimeCategoryName;");
		$CustomItem17 = $db->query("select 'CustomItem17' as param_name, CustomItem17 as name, CustomItem17 as value from sp_customer_stock_sale_threeyear2_week where CustomItem17<>'' group by CustomItem17;");
		$CustomItem1 = $db->query("select 'CustomItem1' as param_name, CustomItem1 as name, CustomItem1 as value from sp_customer_stock_sale_threeyear2_week where CustomItem1<>'' group by CustomItem1;");
		$CustomItem45 = $db->query("select 'CustomItem45' as param_name, CustomItem45 as name, CustomItem45 as value from sp_customer_stock_sale_threeyear2_week where CustomItem45<>'' group by CustomItem45;");
		$CustomItem47 = $db->query("select 'CustomItem47' as param_name, CustomItem47 as name, CustomItem47 as value from sp_customer_stock_sale_threeyear2_week where CustomItem47<>'' group by CustomItem47;");
		$CustomItem48 = $db->query("select 'CustomItem48' as param_name, CustomItem48 as name, CustomItem48 as value from sp_customer_stock_sale_threeyear2_week where CustomItem48<>'' group by CustomItem48;");

		$merge = array_merge($TimeCategoryName2, $TimeCategoryName, $CustomItem17, $CustomItem1, $CustomItem45, $CustomItem47, $CustomItem48);
		// print_r($merge);die;

		$chunk_list = array_chunk($merge, 500);
		foreach($chunk_list as $key => $val) {
			$insert = $db->table('sp_customer_stock_sale_threeyear2_week_select')->strict(false)->insertAll($val);
		}
		echo 'okk';die;

    }

}
