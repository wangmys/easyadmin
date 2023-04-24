<?php
declare (strict_types = 1);

namespace app\api\controller\report;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\api\service\bi\report\ReportFormsServiceJiameng;
use app\BaseController;
use think\Request;

class SendReport extends BaseController
{
    /**
     * 服务
     * @var ReportFormsService|null
     */
    protected $service = null;
    protected $service_jiameng = null;
    // 日期
    protected $Date = '';


    public function __construct(Request $request)
    {
        // 初始化日期
        $this->Date = date('Y-m-d');
        $this->service = new ReportFormsService;
        $this->request = $request;
    }

//    /**
//     * 创建报表666
//     */
    public function create()
    {
        // 生成图片 s101
        $this->service->create_table_s101('S101',date('Y-m-d'));
        $this->service->create_table_s101('S104',date('Y-m-d'));
        $this->service->create_table_s102(date('Y-m-d'));
        $this->service->create_table_s103(date('Y-m-d'));
    }

    public function create_test() {
        $name = input('param.name') ? input('param.name') : 'S101';
        if ($name =='S101') {
            $this->service->create_table_s101('S101',date('Y-m-d'));
        } elseif ($name =='S102') {
            $this->service->create_table_s102(date('Y-m-d'));
        } elseif ($name =='S103') {
            $this->service->create_table_s103(date('Y-m-d'));
        } elseif ($name =='S104') {
            $this->service->create_table_s101('S104',date('Y-m-d'));
        } elseif ($name =='S106') {
            $this->service->create_table_s106(date('Y-m-d'));
        } elseif ($name =='S107') {
            $this->service->create_table_s107(date('Y-m-d'));
        } elseif ($name =='S108A') {
            $this->service->create_table_s108A(date('Y-m-d'));
        } elseif ($name =='S108B') {
            $this->service->create_table_s108B(date('Y-m-d'));
        } elseif ($name =='S109') {
            $this->service->create_table_s109(date('Y-m-d'));
        } elseif ($name =='S110A') {
            $this->service->create_table_s110A(date('Y-m-d'));
        }  elseif ($name =='S110B') {
            $this->service->create_table_s110B(date('Y-m-d'));
        }           
    }

    // 配饰每日销售数量
    public function createS105()
    {
        $this->service->create_table_s105(date('Y-m-d'));
    }

    public function send()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S101' => [
                'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd',strtotime('+1day')).'/S101.jpg'
            ],
            'S104' => [
                'title' => '直营老店同比环比递增及完成率 表号:S104',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S104.jpg'
            ],
            'S102' => [
                'title' => '省份老店业绩同比 表号:S102',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S102.jpg'
            ],
            'S103' => [
                'title' => '省份老店业绩同比-分经营模式 表号:S103',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S103.jpg'
            ],
            'S108A' => [
                'title' => '督导挑战目标完成率 表号:S108A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S108A.jpg'
            ],
            'S108B' => [
                'title' => '区域挑战目标完成率 表号:S108B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S108B.jpg'
            ],
            'S109' => [
                'title' => '各省挑战目标完成情况 表号:S109',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S109.jpg'
            ],
            'S110A' => [
                'title' => '直营单店目标达成情况 表号:S110A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S110A.jpg'
            ],
            'S110B' => [
                'title' => '加盟单店目标达成情况 表号:S110B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S110B.jpg'
            ],
        ];
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // echo $v['title'];
                // echo '<br>';
            }
        }
        return json($res);
    }

    public function send2()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S101' => [
                'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd',strtotime('+1day')).'/S101.jpg'
            ],
            'S103B' => [
                'title' => '省份老店业绩同比-加盟 表号:S103B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S103B.jpg'
            ],
            'S108B' => [
                'title' => '区域挑战目标完成率 表号:S108B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S108B.jpg'
            ],
            'S109B' => [
                'title' => '各省挑战目标完成情况-加盟 表号:S109B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S109.jpg'
            ],
            'S110B' => [
                'title' => '加盟单店目标达成情况 表号:S110B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S110B.jpg'
            ],
        ];
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754');
            }
        }
        return json($res);
    }

    // 23:40推送 个人
    public function sendS105()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S105' => [
                'title' => '配饰每日销售数量 表号:S105',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S105.jpg'
            ]
        ];
        // dump($send_data);die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2');
            }
        }

        return json($res);
    }

    // 鞋履报表 0:31:00
    public function sendS107() {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S107' => [
                'title' => '鞋履报表 表号:S107',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S107.jpg'
            ]
        ];
        // dump($send_data);die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2');
            }
        }
        return json($res);
    }

    /**
     * 执行指定任务
     * @return \think\response\Json
     */
    public function run()
    {
        // 生成图片 s101
        $this->service->create_table_s101('S101',date('Y-m-d'));
        $this->service->create_table_s102(date('Y-m-d'));
        $this->service->create_table_s103(date('Y-m-d'));
        $this->service->create_table_s101('S104',date('Y-m-d'));

        // 108-110
        $this->service->create_table_s108A(date('Y-m-d'));
        $this->service->create_table_s108B(date('Y-m-d'));
        $this->service->create_table_s109(date('Y-m-d'));
        $this->service->create_table_s110A(date('Y-m-d'));
        $this->service->create_table_s110B(date('Y-m-d'));
        // 发送数据报表
        $this->send();
    }

    /**
     * 执行指定任务 只创建不发送
     * @return \think\response\Json
     */
    public function run2()
    {
        // 生成图片 s101
        $this->service->create_table_s103B(date('Y-m-d'));

        // 108-110
        $this->service->create_table_s109B(date('Y-m-d'));
        // https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754
    }
}
