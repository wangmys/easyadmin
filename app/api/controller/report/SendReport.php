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
        $date = input('param.date') ? input('param.date') : '';
        if ($name =='S101') {
            $this->service->create_table_s101('S101', $date);
        } elseif ($name =='S102') {
            $this->service->create_table_s102($date);
        } elseif ($name =='S103') {
            $this->service->create_table_s103($date);
        } elseif ($name =='S103B') {
            $this->service->create_table_s103B($date);
        } elseif ($name =='S104') {
            $this->service->create_table_s101('S104', '2023-05-04');
        } elseif ($name =='S106') {
            $this->service->create_table_s106();
        } elseif ($name =='S107') {
            $this->service->create_table_s107();
        } elseif ($name =='S108A') {
            $this->service->create_table_s108A();
        } elseif ($name =='S108B') {
            $this->service->create_table_s108B('2023-05-12');
        } elseif ($name =='S109') {
            $this->service->create_table_s109();
        } elseif ($name =='S109B') {
            $this->service->create_table_s109B();
        } elseif ($name =='S110A') {
            $this->service->create_table_s110A();
        } elseif ($name =='S110B') {
            $this->service->create_table_s110B();
        } elseif ($name =='S101C') {
            $this->service->create_table_s101C('S101C');
        } elseif ($name =='S104C') {
            $this->service->create_table_s101C('S104C');
        } elseif ($name =='S102C') {
            $this->service->create_table_s102C();
        } elseif ($name =='S103C') {
            $this->service->create_table_s103C();
        }  elseif ($name =='S025') {
            $this->service->create_table_s025();
        }  elseif ($name =='S030') {
            $this->service->create_table_s030();
        } elseif ($name =='S031') {
            $this->service->create_table_s031();
        } elseif ($name =='S043') {
            $this->service->create_table_s043();
        } elseif ($name =='S111' || $name = 'S112') {
            $res = http_get('http://im.babiboy.com//api/Tableupdate/receipt_receiptNotice');
            // $res = http_get('http://www.easyadmin1.com/api/Tableupdate/receipt_receiptNotice');
            $res =json_decode($res, true);
            if ($res['status'] == 1) {
            $this->service->create_table_s111('春季');
            $this->service->create_table_s111('夏季');
            $this->service->create_table_s111('秋季');
            $this->service->create_table_s111('冬季');

            $this->service->create_table_s112('春季');
            $this->service->create_table_s112('夏季');
            $this->service->create_table_s112('秋季');
            $this->service->create_table_s112('冬季');
            } else {
                return $res;
            }

        } elseif ($name =='S112') {
            $this->service->create_table_s112('春季');
            $this->service->create_table_s112('夏季');
            $this->service->create_table_s112('秋季');
            $this->service->create_table_s112('冬季');
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

    // 推送到加盟群
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
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S109B.jpg'
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

    // 推送到打群 0：45
    public function send4()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S025' => [
                'title' => '商品部-各季节销售占比 表号:S025',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S025.jpg'
            ],
            'S030' => [
                'title' => '昨天各省各季节销售占比 表号:S030',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S030.jpg'
            ],
            'S031' => [
                'title' => '近三天各省各季节销售占比 表号:S031',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S031.jpg'
            ],
            'S043' => [
                'title' => '各省7天季节占比（粤/桂/贵/鄂/湘/赣） 表号:S043',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S043.jpg'
            ],
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


    public function send_caigoudingtui()
    {
        
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S111A' => [
                'title' => '春季新品发货及入库明细 表号:S111A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111春季.jpg'
            ],
            'S111B' => [
                'title' => '夏季新品发货及入库明细 表号:S111B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111夏季.jpg'
            ],
            'S111C' => [
                'title' => '秋季新品发货及入库明细 表号:S111C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111秋季.jpg'
            ],
            'S111D' => [
                'title' => '冬季新品发货及入库明细 表号:S111C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111冬季.jpg'
            ],
            'S112A' => [
                'title' => '春季新品发货及入库汇总 表号:S112A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112春季.jpg'
            ],
            'S112B' => [
                'title' => '夏季新品发货及入库汇总 表号:S112B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112夏季.jpg'
            ],
            'S112C' => [
                'title' => '秋季新品发货及入库汇总 表号:S112C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112秋季.jpg'
            ],
            'S112D' => [
                'title' => '冬季新品发货及入库汇总 表号:S112D',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112冬季.jpg'
            ],            
        ];
        $res = [];

        foreach ($send_data as $k=>$v){
            // echo $v['jpg_url'];
            // echo '<br>';
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送 测试群https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2
                // $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=b9c3d11ba661bf4d45f7bee40ed7d92e5f5b3cc92365c29492d129a6c105940b');
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2');
                // dump($v);
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
        $this->service->create_table_s101('S101');
        $this->service->create_table_s102();
        $this->service->create_table_s103();
        $this->service->create_table_s101('S104');

        // 108-110
        $this->service->create_table_s108A();
        $this->service->create_table_s108B();
        $this->service->create_table_s109();
        $this->service->create_table_s110A();
        $this->service->create_table_s110B();
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
        $this->service->create_table_s103B();

        // 108-110
        $this->service->create_table_s109B();
        // https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754

        // 发送数据报表
        $this->send2();
    }

    // 51 推送 11：46
    public function run3_close()
    {
        // $this->service->create_table_s101C('S101C');
        // $this->service->create_table_s101C('S104C');
        // $this->service->create_table_s102C();
        // $this->service->create_table_s103C();

        // 发送数据报表
        // $this->send3();
    }

    // 00:45
    public function run4()
    {
        $this->service->create_table_s025();
        $this->service->create_table_s030();
        $this->service->create_table_s031();
        $this->service->create_table_s043();

        // 发送数据报表
        $this->send4();
    }

    // 51 推送 11：46
    public function create51()
    {
        // $this->service->create_table_s101C('S101C');
        // $this->service->create_table_s101C('S104C');
        // $this->service->create_table_s102C();
        // $this->service->create_table_s103C();

        // 发送数据报表
        // $this->send3();
    }

    // 采购定推
    public function run_caigoudingtui()
    {
        $this->service->create_table_s111('春季');
        $this->service->create_table_s111('夏季');
        $this->service->create_table_s111('秋季');
        $this->service->create_table_s111('冬季');

        $this->service->create_table_s112('春季');
        $this->service->create_table_s112('夏季');
        $this->service->create_table_s112('秋季');
        $this->service->create_table_s112('冬季');

        // 发送数据报表
        $this->send_caigoudingtui();
    }

    public function testSend() {
        // $name = '\app\api\service\DingdingService';
        // $model = new $name;
        // $res[] = $model->send('夏季新品发货及入库汇总 表号:S112', 'http://im.babiboy.com/img/20230520/S112%E5%A4%8F%E5%AD%A3.jpg', 'https://oapi.dingtalk.com/robot/send?access_token=b9c3d11ba661bf4d45f7bee40ed7d92e5f5b3cc92365c29492d129a6c105940b');
        // return json($res);
    }

}
