<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\api\model\store\Stock as StockM;
use app\api\model\store\StockSaleTwoyear;
use app\api\model\store\SpCustomerStockSaleThreeyearModel;

class Stock_week_date extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Stock_week_date')
            ->setDescription('the Stock_week_date command');
    }

	protected function execute(Input $input, Output $output)
    {
		$db = Db::connect("mysql");
		$years = [2021, 2022, 2023, 2024, 2025, 2026];

		$arr = [];
		foreach ($years as $v_year) {
			for ($i=1; $i<=52; $i++) {
				$tmp = getWeekDate($v_year, $i);
				$arr[] = [
					'year' => $v_year,
					'week' => $i,
					'start_time' => $tmp[0],
					'end_time' => $tmp[1],
				];
			}
		}
		$chunk_list = array_chunk($arr, 500);
		foreach($chunk_list as $key => $val) {
			$insert = $db->table('sp_customer_stock_sale_week_date')->strict(false)->insertAll($val);
		}
		echo 'okk';die;

    }

}
