<?php
declare (strict_types = 1);

namespace app\api\controller\report;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\api\service\bi\report\ReportFormsServicePro;
use app\api\service\bi\report\ReportFormsServiceJiameng;
use app\BaseController;
use think\Request;
use think\facade\Db;

class SendReport extends BaseController
{
    /**
     * 服务
     * @var ReportFormsService|null
     */
    protected $service = null;
    protected $servicePro = null;
    protected $service_jiameng = null;
    // 日期
    protected $Date = '';

    protected $db_easyA = '';
    protected $db_sqlsrv = '';
    protected $db_bi = '';


    public function __construct(Request $request)
    {
        // 初始化日期
        $this->Date = date('Y-m-d');
        $this->service = new ReportFormsService;
        $this->servicePro = new ReportFormsServicePro;
        $this->request = $request;

        $this->db_easyA = Db::connect('mysql');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_bi = Db::connect('mysql2');
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
        $type = input('param.type') ? input('param.type') : '';
        cache('dingding_table_name', rand_code(5), 3600);
        if ($name =='S007') {
            $this->service->create_table_s007();
        } elseif ($name =='S008') {
            $this->service->create_table_s008();
        } elseif ($name =='S009') {
            $this->service->create_table_s009();
        } elseif ($name =='S010') {
            $this->service->create_table_s010();
        } elseif ($name =='S012') {
            $this->service->create_table_s012();
        } elseif ($name =='S013') {
            $this->service->create_table_s013();
        } elseif ($name =='S014') {
            $this->service->create_table_s014();
        } elseif ($name =='S015') {
            $this->service->create_table_s015();
        } elseif ($name =='S016') {
            $this->service->create_table_s016();
        } elseif ($name =='S017') {
            $this->service->create_table_s017();
        } elseif ($name =='S018') {
            $this->service->create_table_s018();
        } elseif ($name =='S019') {
            $this->service->create_table_s019();
        } elseif ($name =='S101') {
            $this->service->create_table_s101('S101', $date);
        } elseif ($name =='S102') {
            $this->service->create_table_s102($date);
        } elseif ($name =='S103') {
            $this->service->create_table_s103($date);
        } elseif ($name =='S103B') {
            $this->service->create_table_s103B($date);
        } elseif ($name =='S104') {
            $this->service->create_table_s101('S104', $date);
        } elseif ($name =='S104C') {
            $this->service->create_table_s104C($date);
        } elseif ($name =='S106') {
            $this->service->create_table_s106();
        } elseif ($name =='S107') {
            $this->service->create_table_s107();
        } elseif ($name =='S108A') {
            $this->service->create_table_s108A();
        } elseif ($name =='S108B') {
            $this->service->create_table_s108B();
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
            $this->service->create_table_s103C($date);
        } elseif ($name =='S023') {
            $this->service->create_table_s023();
        } elseif ($name =='S025') {
            $this->service->create_table_s025();
        } elseif ($name =='S030') {
            $this->service->create_table_s030();
        } elseif ($name =='S031') {
            $this->service->create_table_s031();
        } elseif ($name =='S043') {
            $this->service->create_table_s043();
        } elseif ($name =='S045') {
            $this->service->create_table_s045();
        } elseif ($name =='S111' || $name == 'S112') {
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
        } elseif ($name =='S113') {

            $res = http_get("http://im.babiboy.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_date_handle?date={$date}");
            // $res = http_get("http://www.easyadmin1.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_date_handle?date={$date}");
            
            $res = json_decode($res, true);
            if ($res['status'] == 1) {
                $res2 = http_get("http://im.babiboy.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_handle?date={$date}");
                // $res2 = http_get("http://www.easyadmin1.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_handle?date={$date}");
                $this->service->create_table_s113($date);
            }
      
            // $this->service->create_table_s113($date);
        } elseif ($name == 'S114') {
            $this->service->create_table_s114();
        } elseif ($name == 'S115A') {
            $type = input('type');
            $this->service->create_table_s115A($type);
        } elseif ($name == 'S115B') {
            $this->service->create_table_s115B();
        } elseif ($name == 'S115C') {
            $this->service->create_table_s115C();
        } elseif ($name == 'S115D') {
            $this->service->create_table_s115D();
        } elseif ($name == 'S116') {
            $this->service->create_table_s116($date);
        } elseif ($name == 'S117') {
            $this->service->create_table_s117();
        } elseif ($name == 'S118') {
            $this->service->create_table_s118($date, $type);
        }
    }

    // cwl
    public function create_test_pro() {
        $name = input('param.name') ? input('param.name') : 'S101'; 
        $date = input('param.date') ? input('param.date') : '';
        $type = input('param.type') ? input('param.type') : '';
        cache('dingding_table_name', rand_code(5), 3600);
        if ($name =='S101') {
            $this->servicePro->create_table_s101('S101', $date);
        } elseif ($name =='S102') {
            $this->servicePro->create_table_s102($date);
        } elseif ($name =='S103') {
            $this->servicePro->create_table_s103($date);
        } elseif ($name =='S103B') {
            $this->servicePro->create_table_s103B($date);
        } elseif ($name =='S103C') {
            $this->servicePro->create_table_s103C($date);
        } elseif ($name =='S104') {
            $this->servicePro->create_table_s101('S104', $date);
        } elseif ($name =='S104C') {
            $this->servicePro->create_table_s104C($date);
        }elseif ($name =='S108A') {
            $this->servicePro->create_table_s108A($date);
        } elseif ($name =='S108B') {
            $this->servicePro->create_table_s108B($date);
        } elseif ($name =='S109') {
            $this->servicePro->create_table_s109($date);
        } elseif ($name =='S109B') {
            $this->servicePro->create_table_s109B($date);
        } elseif ($name =='S110A') {
            $this->servicePro->create_table_s110A($date);
        } elseif ($name =='S110B') {
            $this->servicePro->create_table_s110B($date);
        }  else {
            echo 'unknow';
        }
    }

    // 门店业绩环比
    public function createS113()
    {
        $date = input('param.date') ? input('param.date') : date('Y-m-d');
        // $date = date('Y-m-d');
        $res = http_get("http://im.babiboy.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_date_handle?date={$date}"); 
        // $res = http_get("http://www.easyadmin1.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_date_handle?date={$date}");
        
        $res = json_decode($res, true);
        if ($res['status'] == 1) {
            $res2 = http_get("http://im.babiboy.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_handle?date={$date}");
            // $res2 = http_get("http://www.easyadmin1.com/api/lufei.Dianpuyejihuanbi/dianpuyejihuanbi_handle");
            $this->service->create_table_s113($date);

            $name = '\app\api\service\DingdingService';
            $model = new $name;
            $send_data = [
                'S113' => [
                    'title' => '门店业绩环比 表号:S113',
                    'jpg_url' => $this->request->domain()."./img/".date('Ymd',strtotime('+1day')).'/S113B.jpg'
                ],
            ];
            $res = [];
            foreach ($send_data as $k=>$v){
                $headers = get_headers($v['jpg_url']);
                if(substr($headers[0], 9, 3) == 200){
                    // 推送
                    $res[] = $model->send($v['title'], $v['jpg_url']);
                    // echo $v['title'];
                    // echo '<br>';
                }
            }
            return json($res);
        }
    }

    // 配饰每日销售数量
    public function createS105()
    {
        $this->service->create_table_s105(date('Y-m-d'));
    }

    // 23:44
    public function send()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S101' => [
                'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd',strtotime('+1day'))."/S101{$dingName}.jpg"
            ],
            'S104' => [
                'title' => '直营老店同比环比递增及完成率 表号:S104',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S104{$dingName}.jpg"
            ],
            'S102' => [
                'title' => '省份老店业绩同比 表号:S102',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S102{$dingName}.jpg"
            ],
            'S103' => [
                'title' => '省份老店业绩同比-分经营模式 表号:S103',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S103{$dingName}.jpg"
            ],
        ];

        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
                // echo $v['title'];
                // echo '<br>';
            }
        }
        return json($res);
    }

    // 0:10
    public function send_1()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S108A' => [
                'title' => '督导挑战目标完成率 表号:S108A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S108A{$dingName}.jpg"
            ],
            'S108B' => [
                'title' => '区域挑战目标完成率 表号:S108B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S108B{$dingName}.jpg"
            ],
            'S109' => [
                'title' => '各省挑战目标完成情况 表号:S109',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S109{$dingName}.jpg"
            ],
            'S110A' => [
                'title' => '直营单店目标达成情况 表号:S110A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S110A{$dingName}.jpg"
            ],
            'S110B' => [
                'title' => '加盟单店目标达成情况 表号:S110B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S110B{$dingName}.jpg"
            ],
        ];

        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
                // echo $v['title'];
                // echo '<br>';
            }
        }
        return json($res);
    }

    // 推送到加盟群 23:46
    public function send2()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S101' => [
                'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd',strtotime('+1day'))."/S101{$dingName}.jpg"
            ],
            'S103B' => [
                'title' => '省份老店业绩同比-加盟 表号:S103B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S103B{$dingName}.jpg"
            ],
        ];
        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送 测试
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");

                // 推送 加盟
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754');
            }
        }
        return json($res);
    }

    // 推送到丽丽群 23:48
    public function send2_lili()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S103C' => [
                'title' => '省份老店业绩同比-直营 表号:S103C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S103C{$dingName}.jpg"
            ],
            'S104C' => [
                'title' => '省直营老店同比环比递增及完成率 表号:S104C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S104C{$dingName}.jpg"
            ],
        ];
        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送 测试
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");

                // 推送 丽丽群
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=3e9461c0a1c013f084a1575ae487131a52717d4d259a3ec8ab65f75283d3430e');
            }
        }
        return json($res);
    }

    // 推送到加盟群 0:13
    public function send2_2()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S108B' => [
                'title' => '区域挑战目标完成率 表号:S108B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S108B{$dingName}.jpg"
            ],
            'S109B' => [
                'title' => '各省挑战目标完成情况-加盟 表号:S109B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S109B{$dingName}.jpg"
            ],
            'S110B' => [
                'title' => '加盟单店目标达成情况 表号:S110B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S110B{$dingName}.jpg"
            ],
        ];
        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送 测试
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");

                // 推送 加盟
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
            'S016' => [
                'title' => '商品部-直营春夏老品库存结构报表 表号:S016',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S016.jpg'
            ],
            'S018' => [
                'title' => '商品部-加盟春夏老品库存结构报表 表号:S018',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S018.jpg'
            ],
            'S023' => [
                'title' => '商品部-所有年份各品类销售占比 表号:S023',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S023.jpg'
            ],
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
            'S045' => [
                'title' => '其他省份7天季节占比 表号:S045',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S045.jpg'
            ],
        ];
        $res = [];

        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                // 测试群 https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
            }
        }
        return json($res);
    }

    // 推送到打群 0：40
    public function send5()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S009' => [
                'title' => '商品部-2023秋季货品销售报表 表号:S009',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S009.jpg'
            ],
            'S015' => [
                'title' => '商品部-2023秋季货品零售汇总表 表号:S015',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S015.jpg'
            ],
        ];
        $res = [];

        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                // 测试群 https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
            }
        }
        return json($res);
    }

    // 推送到打群 0：42
    public function send6()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S007' => [
                'title' => '2023 春季货品销售报表 表号:S007',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S007.jpg'
            ],
            'S008' => [
                'title' => '2023 夏季货品销售报表 表号:S008',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S008.jpg'
            ],
            'S013' => [
                'title' => '2023 春季货品零售汇总报表 表号:S013',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S013.jpg'
            ],
            'S014' => [
                'title' => '2023 夏季货品零售汇总报表 表号:S014',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S014.jpg'
            ],
        ];
        $res = [];

        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                // 测试群 https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
            }
        }
        return json($res);
    }

    // 推送到打群 0：33
    public function send7()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S017' => [
                'title' => '商品部-直营秋冬老品库存结构报表 表号:S017',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S017.jpg'
            ],
            'S019' => [
                'title' => '商品部-加盟秋冬老品库存结构报表 表号:S019',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S019.jpg'
            ],
        ];
        $res = [];

        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                // 测试群 https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
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
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111A.jpg'
            ],
            'S111B' => [
                'title' => '夏季新品发货及入库明细 表号:S111B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111B.jpg'
            ],
            'S111C' => [
                'title' => '秋季新品发货及入库明细 表号:S111C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111C.jpg'
            ],
            'S111D' => [
                'title' => '冬季新品发货及入库明细 表号:S111D',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S111D.jpg'
            ],
            'S112A' => [
                'title' => '春季新品发货及入库汇总 表号:S112A',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112A.jpg'
            ],
            'S112B' => [
                'title' => '夏季新品发货及入库汇总 表号:S112B',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112B.jpg'
            ],
            'S112C' => [
                'title' => '秋季新品发货及入库汇总 表号:S112C',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112C.jpg'
            ],
            'S112D' => [
                'title' => '冬季新品发货及入库汇总 表号:S112D',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd', strtotime('+1day')).'/S112D.jpg'
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
                // 数据测试群
                // $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2');
                // dump($v);
                // 采购群
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=751850d0366d9494e16070bdbf14a5459b76c59ced68c86ac3d46c53869d908f');

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

    
    // 工厂直发仓库超五天未验收单据
    public function sendS114() {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S107' => [
                'title' => '工厂直发仓库超五天未验收单据 表号:S114',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day')).'/S114.jpg'
            ]
        ];
        // dump($send_data);die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送

                // 采购群
                $res[] = $model->send($v['title'],$v['jpg_url'], 'https://oapi.dingtalk.com/robot/send?access_token=751850d0366d9494e16070bdbf14a5459b76c59ced68c86ac3d46c53869d908f');
            }
        }
        return json($res);
    }

    // 00:42
    public function sendS012()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S012' => [
                'title' => '饰品销售情况表 表号:S012',
                // 'jpg_url' => $this->request->domain()."/img/".date('Ymd',strtotime('+1day'))."/S012.jpg"
                'jpg_url' => $this->request->domain()."/img/".date('Ymd')."/S012.jpg"
            ],
        ];

        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                $res[] = $model->send($v['title'],$v['jpg_url']);
                // $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
                // echo $v['title'];
                // echo '<br>';
            }
        }
        return json($res);
    }

    // 报表主群
    public function run_pro()
    {
        $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));
        // echo rand_code(5);die;
        cache('dingding_table_name', rand_code(5), 3600);
        // 生成图片 s101
        $this->service->create_table_s101('S101', $date);
        $this->service->create_table_s102($date);
        $this->service->create_table_s103($date);
        $this->service->create_table_s101('S104', $date);

        // 108-110
        // $this->service->create_table_s108A($date);
        // $this->service->create_table_s108B($date);
        // $this->service->create_table_s109($date);
        // $this->service->create_table_s110A($date);
        // $this->service->create_table_s110B($date);
        // 发送数据报表
        $this->send();
    }

    // 报表主群
    public function run_pro2()
    {
        // $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));
        // echo rand_code(5);die;
        cache('dingding_table_name', rand_code(5), 3600);

        // 108-110
        $this->service->create_table_s108A();
        $this->service->create_table_s108B();
        $this->service->create_table_s109();
        $this->service->create_table_s110A();
        $this->service->create_table_s110B();
        // 发送数据报表
        $this->send_1();
    }

    // 王丽丽群 直营
    public function run2_lili()
    {
        $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));

        cache('dingding_table_name', rand_code(5), 3600);
        // 生成图片 s101
        $this->service->create_table_s103C($date);
        $this->service->create_table_s104C($date);
        // $this->service->create_table_s108B($date);
        // $this->service->create_table_s109B($date);
        // $this->service->create_table_s110B($date);

        // https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754

        // 发送数据报表
        $this->send2_lili();
    }

    // 加盟群
    public function run2()
    {
        $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));

        cache('dingding_table_name', rand_code(5), 3600);
        // 生成图片 s101
        $this->service->create_table_s101('S101', $date);
        $this->service->create_table_s103B($date);
        // $this->service->create_table_s108B($date);
        // $this->service->create_table_s109B($date);
        // $this->service->create_table_s110B($date);

        // https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754

        // 发送数据报表
        $this->send2();
    }

    // 加盟群
    public function run2_2()
    {
        // $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));

        cache('dingding_table_name', rand_code(5), 3600);
        // 生成图片 s101
        // $this->service->create_table_s101('S101', $date);
        // $this->service->create_table_s103B($date);
        $this->service->create_table_s108B();
        $this->service->create_table_s109B();
        $this->service->create_table_s110B();

        // https://oapi.dingtalk.com/robot/send?access_token=881fad3de403f47f88b3d03ad5acbb72c05ef015573b4830d5aa71de88aec754

        // 发送数据报表
        $this->send2_2();
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
        
        $this->service->create_table_s016();
        $this->service->create_table_s018();
        $this->service->create_table_s023();
        $this->service->create_table_s025();
        $this->service->create_table_s030();
        $this->service->create_table_s031();
        $this->service->create_table_s043();
        $this->service->create_table_s045();

        // 发送数据报表
        $this->send4();
    }

    // 测试用的 00:40
    public function run5()
    {
        $this->service->create_table_s009();
        $this->service->create_table_s015();
        // 发送数据报表
        $this->send5();
    }

    public function run6() {
        $this->service->create_table_s007();
        $this->service->create_table_s008();
        $this->service->create_table_s013();
        $this->service->create_table_s014();
        $this->send6();
    }

    public function run7() {
        $this->service->create_table_s017();
        $this->service->create_table_s019();
        $this->send7();
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

        $res = http_get('http://im.babiboy.com/api/Tableupdate/receipt_receiptNotice');
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

            // 发送数据报表
            $this->send_caigoudingtui();
        } else {
            return $res;
        }
    }

    // 
    public function run_caigoudingtui_s114()
    {
        $find = $this->db_easyA->table('dd_baobiao')->field('状态')->where(['编号' => 's114'])->find();
        // dump($find);
        // die;
        if ($find && $find['状态'] == '开') {
            $this->service->create_table_s114();
            // 发送数据报表
            $this->sendS114();
        } 
    }

    public function run_s012()
    {
        $this->service->create_table_s012();

        // 发送数据报表
        $this->sendS012();

    }

    //   cwl
    public function run_pro_test()
    {
        $date = input('date') ? input('date') : date('Y-m-d', strtotime('+1day'));
        // echo rand_code(5);die;
        cache('dingding_table_name', rand_code(5), 3600);
        // 生成图片 s101
        $this->servicePro->create_table_s101('S101', $date);
        // $this->service->create_table_s102($date);
        // $this->service->create_table_s103($date);
        // $this->service->create_table_s101('S104', $date);

        // 108-110
        // $this->service->create_table_s108A($date);
        // $this->service->create_table_s108B($date);
        // $this->service->create_table_s109($date);
        // $this->service->create_table_s110A($date);
        // $this->service->create_table_s110B($date);
        // 发送数据报表
        $this->send_pro_test();
    }

    public function send_pro_test()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $dingName = cache('dingding_table_name');
        $send_data = [
            'S101' => [
                // 'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'title' => '测试S101',
                'jpg_url' => $this->request->domain()."./img/cwl/".date('Ymd',strtotime('+1day'))."/S101.jpg?v=" . time()
            ],
            'S104' => [
                'title' => ' 测试S104',
                // 'title' => '直营老店同比环比递增及完成率 表号:S104',
                'jpg_url' => $this->request->domain()."/img/cwl/".date('Ymd',strtotime('+1day'))."/S104.jpg?v=" . time()
            ],
            'S102' => [
                // 'title' => '省份老店业绩同比 表号:S102',
                'title' => '测试S102',
                'jpg_url' => $this->request->domain()."/img/cwl/".date('Ymd',strtotime('+1day'))."/S102.jpg?v=" . time()
            ],
            'S103' => [
                // 'title' => '省份老店业绩同比-分经营模式 表号:S103',
                'title' => '测试S103',
                'jpg_url' => $this->request->domain()."/img/cwl/".date('Ymd',strtotime('+1day'))."/S103.jpg?v=" . time()
            ],
        ];

        // dump($send_data);
        // die;
        $res = [];
        foreach ($send_data as $k=>$v){
            $headers = get_headers($v['jpg_url']);
            if(substr($headers[0], 9, 3) == 200){
                // 推送
                // $res[] = $model->send($v['title'],$v['jpg_url']);
                $res[] = $model->send($v['title'],$v['jpg_url'], "https://oapi.dingtalk.com/robot/send?access_token=5091c1eb2c0f4593d79825856f26bc30dcb5f64722c3909e6909a1255630f8a2");
                // echo $v['title'];
                // echo '<br>';
            }
        }
        return json($res);
    }

}
