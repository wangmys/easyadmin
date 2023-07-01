<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\weather\CusWeatherOutput;
use app\admin\service\CusWeatherService;
use think\facade\Log;
use jianyan\excel\Excel;

class Cus_weather_output extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('cus_weather_output')
            ->setDescription('the cus_weather_output command');
    }

    public function execute(Input $input, Output $output)
    {

		$out = CusWeatherOutput::where([['current_status', '=', CusWeatherOutput::CURRENT_STATUS['DEFAULT']]])->find();
		if ($out && $out['current_status']==CusWeatherOutput::CURRENT_STATUS['DEFAULT']) {

			ini_set('memory_limit','3072M');
			$service = new CusWeatherService();

			$id = $out['id'];
			CusWeatherOutput::where([['id', '=', $id]])->update(['current_status' => CusWeatherOutput::CURRENT_STATUS['RUNNING']]);
			$params = cache($out['code']);
            $params = $params ? json_decode($params, true) : [];

            $params['limit'] = 100000000;

            $select = $service->get_cus_weather($params, 'cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, SUBSTRING(cwd.weather_time, 1, 10) as weather_time');

			$title_arr = [
				'customer_name' => '店铺名称',
				'province' => '省',
				'city' => '市',
				'area' => '区',
				'store_type' => '经营模式',
				'wendai' => '温带',
				'wenqu' => '温区',
				'goods_manager' => '商品负责人',
				'yuncang' => '云仓',
				'store_level' => '店铺等级',
				'nanzhongbei' => '南中北',
				'min_c' => '最低温',
				'max_c' => '最高温',
				'weather_time' => '日期',
			];
			$des_file = root_path().'public/'.'upload/weather/customer_weather_'.$out['code'].'.csv';
			$this->exportCSV($title_arr, $select['data'], $des_file);

			CusWeatherOutput::where([['id', '=', $id]])->update(['current_status' => CusWeatherOutput::CURRENT_STATUS['FINISHED']]);
			die;

		}

		echo 'okkk';die;

    }

	public function exportCSV($title_arr, $new_arr, $des_file) {

		foreach($new_arr as $k => $v){
			$res['allList'][] = $v;
		}
		$res['title'] = $title_arr;
		$headerList = array(); //定义表头
		$machineData = array(); //定义数据
		//处理表头放入新的数组中
		foreach ($res['title'] as $k => $v){
			$headerList[] = iconv("UTF-8", "GB2312//IGNORE", $v);
		}
		//处理数据放入新的数组中
		foreach ($res['allList'] as $k => $v){
			foreach ($v as $key => $val){
				$machineData[$k][] = iconv("UTF-8", "GB2312//IGNORE", $val);
			}
		}
		$result = array();
		// 打开文件资源，不存在则创建
		$time = time();
		$fileName = date('Ym',time()).$time;

		$fp = fopen($des_file,'a');
		// 处理头部标题
		$header = implode(',', $headerList) . PHP_EOL;
		// 处理内容
		$content = '';
		foreach ($machineData as $k => $v) {
			$content .= implode(',', $v) . PHP_EOL;
		}
		// 拼接
		$csv = $header.$content;
		// 写入并关闭资源
		fwrite($fp, $csv);
		fclose($fp);
		//把文件输出到下载
		// $file = fopen($des_file,"r"); // 打开文件
		// $size=filesize($des_file);
		// Header("Content-type: application/octet-stream");
		// Header("Accept-Ranges: bytes");
		// Header("Accept-Length: ".$size);
		// Header("Content-Disposition: attachment; filename=download.csv");
		// echo fread($file,$size);
		// fclose($file);
		// $result['status'] = 200;
		// $result['msg'] = '成功';
		// //打印csv文件的地址
		// $result['url'] = 'https://************/Uploads/CSV/'.$fileName.'_export.csv';
		// $this->ajaxReturn($result,'JSON');
	}

}
