<?php
declare (strict_types = 1);

namespace app\api\controller\report;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\BaseController;
use think\Request;

class SendReport extends BaseController
{
    /**
     * 服务
     * @var ReportFormsService|null
     */
    protected $service = null;
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
//     * 创建报表
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
            ]
        ];
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
            }
        }
        return json($res);
    }

    // 23:40推送
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

    /**
     * 执行指定任务
     * @return \think\response\Json
     */
    public function run()
    {
        // 生成图片 s101
        $this->service->create_table_s101();
        $this->service->create_table_s101('S104');
        $this->service->create_table_s102();
        $this->service->create_table_s103();
        // 发送数据报表
        $this->send();
    }
}
