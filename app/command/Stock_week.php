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
use app\admin\model\bi\SpCustomerStockSaleThreeyear2WeekCacheModel;
use app\admin\service\ThreeyearService;

##每周一凌晨3点跑上一周的数据
class Stock_week extends Command
{
	protected $db_easy;
	protected $db_tianqi;

    protected function configure()
    {
        // 指令配置
        $this->setName('Stock_week')
            ->setDescription('the Stock_week command');
		$this->db_easy = Db::connect("mysql");
		$this->db_tianqi = Db::connect("tianqi");
    }

	protected function execute(Input $input, Output $output)
    {
		// ini_set('memory_limit','1024M');

		//生成上周数据入week表
		// $this->add_every_week();

		// //重新生成三年趋势首页缓存数据
		// $this->generate_index_data();

		// echo 'okk';die;




		//test....
		// $this->generate_index_data();die;

		//test....
		// $weather_data = $this->get_weather_data('广东省', '加盟', '两广', '南', '南一', '2023-08-07 00:00:00', '2023-08-13 00:00:00');
		// print_r($weather_data);die;

		//跑三年数据使用：
		ini_set('memory_limit','2048M');
		$week_date = $this->get_week_date();
		$year_start_time = array_column($week_date, 'year_start_time');
		$week_date = array_combine($year_start_time, $week_date);
		// print_r($week_date);die;
		$this->deal_week_data_2021($week_date);
		$this->deal_week_data_2022($week_date);
		$this->deal_week_data_2023($week_date);

		//重新生成三年趋势首页缓存数据
		$this->generate_index_data();

    }

	protected function deal_week_data_2021($week_date) {

		$date_arr = [
			['start_time' => '2021-01-04', 'end_time' => '2021-01-31'],
			['start_time' => '2021-02-01', 'end_time' => '2021-02-28'],
			['start_time' => '2021-03-01', 'end_time' => '2021-04-04'],
			['start_time' => '2021-04-05', 'end_time' => '2021-05-02'],
			['start_time' => '2021-05-03', 'end_time' => '2021-05-30'],
			['start_time' => '2021-05-31', 'end_time' => '2021-06-27'],
			['start_time' => '2021-06-28', 'end_time' => '2021-07-25'],

			['start_time' => '2021-07-26', 'end_time' => '2021-08-29'],
			['start_time' => '2021-08-30', 'end_time' => '2021-09-26'],
			['start_time' => '2021-09-27', 'end_time' => '2021-10-31'],
			['start_time' => '2021-11-01', 'end_time' => '2021-11-28'],
			['start_time' => '2021-11-29', 'end_time' => '2022-01-02'],
		];

		$this->deal_week_data_common($date_arr, $week_date, '2021');

	}

	protected function deal_week_data_2022($week_date) {

		$date_arr = [
			['start_time' => '2022-01-03', 'end_time' => '2022-01-30'],
			['start_time' => '2022-01-31', 'end_time' => '2022-02-13'],
			['start_time' => '2022-02-14', 'end_time' => '2022-03-06'],
			['start_time' => '2022-03-07', 'end_time' => '2022-04-03'],
			['start_time' => '2022-04-04', 'end_time' => '2022-05-01'],
			['start_time' => '2022-05-02', 'end_time' => '2022-05-29'],
			['start_time' => '2022-05-30', 'end_time' => '2022-06-26'],
			['start_time' => '2022-06-27', 'end_time' => '2022-07-17'],
			['start_time' => '2022-07-18', 'end_time' => '2022-07-31'],

			['start_time' => '2022-08-01', 'end_time' => '2022-09-04'],
			['start_time' => '2022-09-05', 'end_time' => '2022-09-18'],
			['start_time' => '2022-09-19', 'end_time' => '2022-10-02'],
			['start_time' => '2022-10-03', 'end_time' => '2022-10-16'],
			['start_time' => '2022-10-17', 'end_time' => '2022-10-30'],
			['start_time' => '2022-10-31', 'end_time' => '2022-11-27'],
			['start_time' => '2022-11-28', 'end_time' => '2022-12-18'],
			['start_time' => '2022-12-19', 'end_time' => '2023-01-01'],
		];

		$this->deal_week_data_common($date_arr, $week_date, '2022');

	}

	protected function deal_week_data_2023($week_date) {

		$date_arr = [
			['start_time' => '2023-01-02', 'end_time' => '2023-02-05'],

			['start_time' => '2023-02-06', 'end_time' => '2023-03-05'],
			['start_time' => '2023-03-06', 'end_time' => '2023-04-02'],
			['start_time' => '2023-04-03', 'end_time' => '2023-04-30'],
			['start_time' => '2023-05-01', 'end_time' => '2023-05-14'],
			['start_time' => '2023-05-15', 'end_time' => '2023-06-04'],

			['start_time' => '2023-06-05', 'end_time' => '2023-06-18'],
			['start_time' => '2023-06-19', 'end_time' => '2023-07-02'],
			['start_time' => '2023-07-03', 'end_time' => '2023-07-30'],

			['start_time' => '2023-07-31', 'end_time' => '2023-08-13'],
			['start_time' => '2023-08-14', 'end_time' => '2023-09-03'],
			['start_time' => '2023-09-04', 'end_time' => '2023-09-17'],
			['start_time' => '2023-09-18', 'end_time' => '2023-10-01'],
			['start_time' => '2023-10-02', 'end_time' => '2023-10-15'],
			['start_time' => '2023-10-16', 'end_time' => '2023-10-22'],
			['start_time' => '2023-10-23', 'end_time' => '2023-10-29'],
			['start_time' => '2023-10-30', 'end_time' => '2023-11-05'],
			['start_time' => '2023-11-06', 'end_time' => '2023-11-12'],
			['start_time' => '2023-11-13', 'end_time' => '2023-11-19'],
		];

		//test...
		// $date_arr = [['start_time' => '2023-06-19', 'end_time' => '2023-07-02']];
		// $date_arr = [['start_time' => '2023-07-03', 'end_time' => '2023-08-06']];
		// $date_arr = [['start_time' => '2023-09-11', 'end_time' => '2023-09-24']];

		$this->deal_week_data_common($date_arr, $week_date, '2023');

	}


	protected function deal_week_data_common($date_arr, $week_date, $which_year) {


		foreach ($date_arr as $v_date_arr) {

			$start_time = $v_date_arr['start_time'];
			$end_time = $v_date_arr['end_time'];

			$res = $this->get_sql($start_time, $end_time);
			if ($res) {
	

				$add_data = [];
				foreach ($res as $v_res) {
					
					$ex = $v_res['Start_time'] ? explode('-', $v_res['Start_time']) : [];
					$Year = $ex ? $ex[0] : '';
					$Month = $ex ? $ex[1] : 0;
					$each_week_date = $week_date[$Year.$v_res['Start_time']] ?? [];
	
					// $weather_data = $this->get_weather_data($v_res['State'], $v_res['Mathod'], $v_res['YunCang'], $v_res['WenDai'], $v_res['WenQu'], $each_week_date ? $each_week_date['start_time'] : '', $each_week_date ? $each_week_date['end_time'] : '');
					// print_r($weather_data);die;
	
					$add_data[] = [
						'Year' => $Year,
						'Week' => $each_week_date ? $each_week_date['week'] : 0,
						'Month' => $Month,
						'Start_time' => $each_week_date ? $each_week_date['start_time'] : null,
						'End_time' => $each_week_date ? $each_week_date['end_time'] : null,
	
						'YunCang' => $v_res['YunCang'],
						'CustomItem15' => $v_res['CustomItem15'],
						'CustomItem65' => $v_res['CustomItem65'],
						'CustomItem66' => $v_res['CustomItem66'],
						'WenDai' => $v_res['WenDai'],
						'WenQu' => $v_res['WenQu'],
						'State' => $v_res['State'],
						'Mathod' => $v_res['Mathod'],
						'NUM' => $v_res['NUM'],
						'StyleCategoryName' => $v_res['StyleCategoryName'],
						'TimeCategoryName1' => $v_res['TimeCategoryName1'],
						'Season' => $v_res['Season'],
						'TimeCategoryName2' => $v_res['TimeCategoryName2'],
						'TimeCategoryName' => $v_res['TimeCategoryName'],
						'CustomItem17' => $v_res['CustomItem17'],
						
						'CustomItem1' => $v_res['CustomItem1'],
						'CustomItem45' => $v_res['CustomItem45'],
						'CustomItem47' => $v_res['CustomItem47'],
						'CustomItem48' => $v_res['CustomItem48'],
						'CustomItem46' => $v_res['CustomItem46'],
						'CategoryName1' => $v_res['CategoryName1'],
						'CategoryName2' => $v_res['CategoryName2'],
						'CategoryName' => $v_res['CategoryName'],
						'StyleCategoryName1' => $v_res['StyleCategoryName1'],
						'StockQuantity' => $v_res['StockQuantity'],
						
						'StockAmount' => $v_res['StockAmount'],
						'StockCost' => $v_res['StockCost'],
						'SaleQuantity' => $v_res['SaleQuantity'],
						'SalesVolume' => $v_res['SalesVolume'],
						'RetailAmount' => $v_res['RetailAmount'],
						'CostAmount' => $v_res['CostAmount'],
						// 'max_c' => $weather_data['max_c'],
						// 'min_c' => $weather_data['min_c'],
					];
	
					// print_r($add_data);die;
	
					//test...
					// $chunk_list = array_chunk($add_data, 500);
					// foreach($chunk_list as $key => $val) {
					// 	$insert = $this->db_easy->table('sp_customer_stock_sale_threeyear2_week')->strict(false)->insertAll($val);
					// }
					// echo 'kkoo';die;
	
				}
	
	
				$chunk_list = array_chunk($add_data, 500);
				foreach($chunk_list as $key => $val) {
					$insert = $this->db_easy->table('sp_customer_stock_sale_threeyear2_week_'.$which_year)->strict(false)->insertAll($val);
					$insert = $this->db_easy->table('sp_customer_stock_sale_threeyear2_week')->strict(false)->insertAll($val);
				}

	
			}


		}

	}


	//每周一生成一次上周数据(待确认，等跑完全部数据后使用)
	protected function add_every_week() {

		$start_time = date('Y-m-d', time()-24*60*60*7);
		$end_time = date('Y-m-d', time()-24*60*60);
		$date_arr = [['start_time' => $start_time, 'end_time' => $end_time]];
		// $date_arr = [['start_time' => '2023-09-25', 'end_time' => '2023-10-08'], ['start_time' => '2023-10-09', 'end_time' => '2023-10-15']];

		$week_date = $this->get_week_date();
		$year_start_time = array_column($week_date, 'year_start_time');
		$week_date = array_combine($year_start_time, $week_date);

		//以一周 开始时间 的年份为准 定义所在年份
		$which_year = date('Y', strtotime($start_time));

		$this->deal_week_data_common($date_arr, $week_date, $which_year);

	}


	//每周一生成首页数据(无任何筛选条件的情况下)，缓存起来
	protected function generate_index_data() {

		$service = (new ThreeyearService())::getInstance();
		$res = $service->index(['from_cache'=>1]);
		if ($res) {
			SpCustomerStockSaleThreeyear2WeekCacheModel::where([['index_str', '=', 'threeyear_index']])->update(['cache_data' => json_encode($res)]);
		} else {
			SpCustomerStockSaleThreeyear2WeekCacheModel::where([['index_str', '=', 'threeyear_index']])->update(['cache_data' => json_encode([])]);
		}

	}



	protected function get_weather_data($province, $store_type, $yuncang, $wendai, $wenqu, $start_time, $end_time) {

		$yuncang = config('weather.yuncang')[$yuncang] ?? '';
		$sql = "select b.customer_name,max(d.max_c) as max_c, min(d.min_c) as min_c from cus_weather_data d 
		left join cus_weather_base b on d.weather_prefix=b.weather_prefix 
		where b.province='{$province}' and b.store_type='{$store_type}' and b.yuncang in ({$yuncang}) and b.wendai='{$wendai}' and b.wenqu='{$wenqu}' and (d.weather_time between '{$start_time}' and '{$end_time}') group by b.customer_name;";
		$res = $this->db_tianqi->Query($sql);
		// print_r($res);die;
		$return = ['count'=>0, 'max_c'=>0, 'min_c'=>0];
		if ($res) {
			$count = count($res);//店铺数
			foreach ($res as $v_res) {
				$return['max_c'] += $v_res['max_c'];
				$return['min_c'] += $v_res['min_c'];
			}
			$return['count'] = $count;
			$return['max_c'] = round($return['max_c']/$count, 0);
			$return['min_c'] = round($return['min_c']/$count, 0);
		}
		return $return;

	}

	protected function get_week_date() {

		$sql = 'select CONCAT(d.year, "", SUBSTRING(d.start_time, 1, 10) ) as year_start_time, d.* from sp_customer_stock_sale_week_date d;';// where d.year="2023"
		$res = $this->db_easy->Query($sql);
		return $res;

	}

	protected function get_sql($start_time, $end_time) {

		$sql = "select 
FROM_DAYS(TO_DAYS(DATE) - MOD(TO_DAYS(DATE) -2, 7)) as Start_time, 
max(DATE) as End_time, 
CONCAT(month(DATE)) as Month, 
YunCang, 
CustomItem15, 
CustomItem65, 
CustomItem66, 
WenDai, 
WenQu, 
State, 
Mathod, 
max(NUM) as NUM, 
StyleCategoryName, 
TimeCategoryName1, 
Season, 
TimeCategoryName2, 
TimeCategoryName, 

CustomItem17,
CustomItem1,
CustomItem45,
CustomItem47,
CustomItem48,
CustomItem46, 
CategoryName1, 
CategoryName2, 
CategoryName, 
StyleCategoryName1, 

sum(StockQuantity) as StockQuantity, 
sum(StockAmount) as StockAmount, 
sum(StockCost) as StockCost, 
sum(SaleQuantity) as SaleQuantity, 
sum(SalesVolume) as SalesVolume, 
sum(RetailAmount) as RetailAmount, 
sum(CostAmount) as CostAmount  
from sp_customer_stock_sale_threeyear2  
where DATE BETWEEN '{$start_time}' and '{$end_time}' 
group by Start_time,YunCang,CustomItem15,CustomItem65,CustomItem66,WenDai,WenQu,State,Mathod,TimeCategoryName,TimeCategoryName1,TimeCategoryName2,Season,StyleCategoryName,StyleCategoryName1,CategoryName1,CategoryName2,CategoryName,CustomItem46,CustomItem17,CustomItem1,CustomItem45,CustomItem47,CustomItem48;";
		$res = $this->db_easy->Query($sql);
		return $res;

	}


}
