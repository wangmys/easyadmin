<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\api\service\dingding\Sample;
use think\facade\Log;

class Sendpic extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sendpic')
			->addArgument('sign',Argument::OPTIONAL,'1')
            ->setDescription('the sendpic command');
    }

    public function execute(Input $input, Output $output)
    {

		ini_set('memory_limit','500M');

		$sign = $input->getArgument('sign');// 1:daogou-night 2:daogou-morning 3:dianzhang-night 4:dianzhang-morning

		//所有店铺数据
		$sql = "select id, name, erp_shop_id from dept where del_flag=0 and is_virtual=0 and type=1 and sale_quality='ZY';";//只针对直营店推送
		$data = Db::connect("cip")->Query($sql);
		// print_r($data);die;

		if ($sign == 1) {
	
			if ($data) {
				foreach ($data as $v_data) {
	
					$url = env('APP.APP_DOMAIN').'/api/daogou.SendPic/daogou_night';
					$path = curl_post_pro($url, json_encode($v_data));
					$path = $path ? json_decode($path, true) : [];
					// print_r($path);die;
	
					if ($path) {
						foreach ($path as $v_path) {
							// $v_path['userid'] = '1344391026107390';//test...
							$this->send_dingding($v_path['img_url'], $v_path['userid']);
						}
					}

					//记录发送日志：
					Log::write(json_encode($path), 'sendpic:sign=1,dept_id='.$v_data['id']);

					// die;
	
				}
			}	

		} elseif ($sign == 2) {

			if ($data) {
				foreach ($data as $v_data) {
	
					$url = env('APP.APP_DOMAIN').'/api/daogou.SendPic/daogou_morning';
					$path = curl_post_pro($url, json_encode($v_data));
					$path = $path ? json_decode($path, true) : [];
					// print_r($path);die;
	
					if ($path) {
						foreach ($path as $v_path) {
							// $v_path['userid'] = '1344391026107390';//test...
							$this->send_dingding($v_path['img_url'], $v_path['userid']);
						}
					}

					//记录发送日志：
					Log::write(json_encode($path), 'sendpic:sign=2,dept_id='.$v_data['id']);

					// die;
	
				}
			}

		} elseif ($sign == 3) {

			if ($data) {
				foreach ($data as $v_data) {
	
					$url = env('APP.APP_DOMAIN').'/api/daogou.SendPic/dianzhang_night';
					$v_data['c_count'] = count($data);
					$path = curl_post_pro($url, json_encode($v_data));
					$path = $path ? json_decode($path, true) : [];
					// print_r($path);die;
	
					if ($path) {
						foreach ($path as $v_path) {
							// $v_path['userid'] = '1344391026107390';//test...
							$this->send_dingding($v_path['img_url'], $v_path['userid']);
						}
					}

					//记录发送日志：
					Log::write(json_encode($path), 'sendpic:sign=3,dept_id='.$v_data['id']);

					// die;
	
				}
			}

		} elseif ($sign == 4) {

			if ($data) {
				foreach ($data as $v_data) {
	
					$url = env('APP.APP_DOMAIN').'/api/daogou.SendPic/dianzhang_morning';
					$path = curl_post_pro($url, json_encode($v_data));
					$path = $path ? json_decode($path, true) : [];
					// print_r($path);die;
	
					if ($path) {
						foreach ($path as $v_path) {
							// $v_path['userid'] = '1344391026107390';//test...
							$this->send_dingding($v_path['img_url'], $v_path['userid']);
						}
					}

					//记录发送日志：
					Log::write(json_encode($path), 'sendpic:sign=4,dept_id='.$v_data['id']);

					// die;
	
				}
			}

		}

		echo 'okkk';die;

    }

	protected function send_dingding($img_url, $userid) {

		//推送钉钉 ：
		$sample = new Sample();
		$img_url = app()->getRootPath().'/public'.$img_url;
		//上传图
		$media_id = $sample->uploadDingFile($img_url, "");//每日导购业绩{$date}
		$res = $sample->sendImageMsg($userid, $media_id);

	}


}
