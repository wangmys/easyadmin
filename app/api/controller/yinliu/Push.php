<?php
declare (strict_types = 1);

namespace app\api\controller\yinliu;
use app\api\constants\ApiConstant;
use app\BaseController;
use app\api\service\bi\yinliu\YinliuDataService;
use app\api\service\dingding\Sample;
use app\api\service\bi\report\ReportFormsService;
use think\Request;
use think\facade\Db;

/**
 * 引流数据推送
 * Class Push
 * @package app\api\controller\yinliu
 */
class Push extends BaseController
{
    /**
     * 服务
     * @var YinliuDataService|null
     */
    protected $service = null;
    // 日期
    protected $Date = '';

    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';

    /**
     * 初始化参数
     * Push constructor.
     */
    public function __construct(Request $request)
    {
        // 初始化日期
        $this->Date = date('Y-m-d');
        $this->service = new YinliuDataService;
        $this->request = $request;

        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    /**
     * 推送任务至各商品专员
     *
     */
   public function pushToSpecialist()
   {
       /**
        * 推送列表
        */
       $list = ApiConstant::DINGDING_ID_LIST;
       $model = new Sample;
       // 循环推送
       foreach ($list as $key => $val){
            $parms = [
               'name' => $val['商品负责人'],
               'tel' => $val['tel'],
               'userid' => $val['userid']
            ];
            $model->send($parms);
       }
   }

    /**
     * 提送每日业绩至老板、王威
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function pushYeji()
    {
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '杨岳敏',
                'tel' => '13362067222',
                'userid' => '131255621326201188'
            ],
            [
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            [
                'name' => '杨剑',
                'tel' => '15200838578',
                'userid' => '1369166106841705'
            ]
        ];

        $reportFormsService = new ReportFormsService();
        $date = date('Y-m-d');
        // 创建图
        $reportFormsService->create_table_s106($date);
        $path = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S106.jpg';

        // 上传图 
        // echo $media_id = $model->uploadDingFile($path, "每日业绩{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        // foreach ($parms as $key => $val) {
        //     $res = $model->sendImageMsg($val['userid'], $media_id);
        //     dump($res);
        // } 
        foreach ($parms as $key => $val) {
            $res = $model->sendMarkdownImg($val['userid'], '每日业绩', $path);
        }   
    }

    /**
     * 提送每日零售核销单
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     * 数据源执行 http://im.babiboy.com/api/lufei.Hexiao/dataHandle
     */
    public function pushHexiao()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '杨岳敏',
                'tel' => '13362067222',
                'userid' => '131255621326201188'
            ],
            [
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            [
                'name' => '杨剑',
                'tel' => '15200838578',
                'userid' => '1369166106841705'
            ],
            [
                'name' => '陈子文',
                'tel' => '13925028633',
                'userid' => '130821405237722239'
            ],
            [
                'name' => '谷冬冬',
                'tel' => '18557705286',
                'userid' => '1302252256669056'
            ],
            [
                'name' => '俞有岳',
                'tel' => '13757775761',
                'userid' => '051218272920490024'
            ],
            [
                'name' => '周欢',
                'tel' => '13576147826',
                'userid' => '0427616739697274'
            ],
            [
                'name' => '杨梦晓',
                'tel' => '18858012505',
                'userid' => '16205553134591457'
            ],
            [
                'name' => '徐亮滨',
                'tel' => '13799884322',
                'userid' => '16085153953415770'
            ],
            [
                'name' => '辛斌',
                'tel' => '13387007546',
                'userid' => '12525915671165649'
            ],
            [
                'name' => '李雅婷',
                'tel' => '15298454189',
                'userid' => '284616312226634272'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s116($date);
        $path = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S116.jpg';

        // 上传图 
        // $media_id = $model->uploadDingFile($path, "零售核销单报表日统计{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        foreach ($parms as $key => $val) {
            $res = $model->sendMarkdownImg($val['userid'], '省份单日毛利表', $path);
        }  
    }

    /**
     * 提送每日零售核销单
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function pushHexiao2()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '杨岳敏',
                'tel' => '13362067222',
                'userid' => '131255621326201188'
            ],
            [
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            [
                'name' => '杨剑',
                'tel' => '15200838578',
                'userid' => '1369166106841705'
            ],
            [
                'name' => '陈子文',
                'tel' => '13925028633',
                'userid' => '130821405237722239'
            ],
            [
                'name' => '谷冬冬',
                'tel' => '18557705286',
                'userid' => '1302252256669056'
            ],
            [
                'name' => '俞有岳',
                'tel' => '13757775761',
                'userid' => '051218272920490024'
            ],
            [
                'name' => '周欢',
                'tel' => '13576147826',
                'userid' => '0427616739697274'
            ],
            [
                'name' => '杨梦晓',
                'tel' => '18858012505',
                'userid' => '16205553134591457'
            ],
            [
                'name' => '徐亮滨',
                'tel' => '13799884322',
                'userid' => '16085153953415770'
            ],
            [
                'name' => '辛斌',
                'tel' => '13387007546',
                'userid' => '12525915671165649'
            ],
            [
                'name' => '李雅婷',
                'tel' => '15298454189',
                'userid' => '284616312226634272'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s117($date);
        $path = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S117.jpg';

        // 上传图 
        // $media_id = $model->uploadDingFile($path, "零售核销单报表月统计{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        foreach ($parms as $key => $val) {
            $res = $model->sendMarkdownImg($val['userid'], '月度毛利表', $path);
        }  
    }


    /**
     * 连带客单件单
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function pushLiandai()
    {
        $time = time();
        if ($time >= strtotime(date('Y-m-d 23:25:00')) && $time <= strtotime(date('Y-m-d 23:40:00'))) {
        } else {
            // echo '时间范围外';
            die;
        }

        // if ($time >= strtotime(date('Y-m-d 14:50:00')) && $time <= strtotime(date('Y-m-d 15:00:00'))) {
        // } else {
        //     // echo '时间范围外';
        //     die;
        // }
        // echo '时间范围内';
        // die;
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            // [
            //     'name' => '王梦园',
            //     'tel' => '17775611493',
            //     'userid' => '293746204229278162'
            // ],
            [
                'name' => '杨岳敏',
                'tel' => '13362067222',
                'userid' => '131255621326201188'
            ],
            [
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            [
                'name' => '杨剑',
                'tel' => '15200838578',
                'userid' => '1369166106841705'
            ],
            [
                'name' => '陈子文',
                'tel' => '13925028633',
                'userid' => '130821405237722239'
            ],
            [
                'name' => '王俊',
                'tel' => '17324278693',
                'userid' => '022306185006937375'
            ],
            [
                'name' => '于艳茹',
                'tel' => '19128657880',
                'userid' => '02183032091220394548'
            ],
            [
                'name' => '李雅婷',
                'tel' => '15298454189',
                'userid' => '284616312226634272'
            ],
        ];

        http_get("http://im.babiboy.com/api/lufei.Ldkdjd/dataHandle?date={$date}");
        http_get("http://im.babiboy.com/api/lufei.Ldkdjd/handle?date={$date}");
        http_get("http://im.babiboy.com/api/lufei.Ldkdjd/handle_jm?date={$date}");
        http_get("http://im.babiboy.com/api/lufei.Ldkdjd/handle_zy?date={$date}");

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s118($date, 'A');
        $reportFormsService->create_table_s118($date, 'B');
        $reportFormsService->create_table_s118($date, 'C');

        $pathA = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S118A.jpg';
        $pathB = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S118B.jpg';
        $pathC = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S118C.jpg';
        // 上传图 
        // $media_idA = $model->uploadDingFile($pathA, "连带客单件单_{$date}");
        // $media_idB = $model->uploadDingFile($pathB, "连带客单件单_{$date}");
        // $media_idC = $model->uploadDingFile($pathC, "连带客单件单_{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        // foreach ($parms as $key => $val) {
        //     $res = $model->sendImageMsg($val['userid'], $media_idA);
        //     $res = $model->sendImageMsg($val['userid'], $media_idB);
        //     $res = $model->sendImageMsg($val['userid'], $media_idC);
        // }  

        foreach ($parms as $key => $val) {
            $model->sendMarkdownImg($val['userid'], '直营连带、客单、件单及同比表', $pathA);
            $model->sendMarkdownImg($val['userid'], '加盟连带、客单、件单及同比表', $pathB);
            $model->sendMarkdownImg($val['userid'], '总体连带、客单、件单及同比表', $pathC);
        }  
    }

    /**
     * 提送配饰
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function pushS012()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '李逢生',
                'tel' => '13927687768',
                'userid' => '010946151826588427'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s012();
        $path = $this->request->domain() . "/img/" . date('Ymd').'/S012.jpg';

        // 上传图 
        // $media_id = $model->uploadDingFile($path, "饰品销售状况表{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        // foreach ($parms as $key => $val) {
        //     $res = $model->sendImageMsg($val['userid'], $media_id);
        // } 
        foreach ($parms as $key => $val) {
            $model->sendMarkdownImg($val['userid'], '饰品销售状况表', $path);
        }  
    }

     /**
     * 推送鞋履
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function pushS107()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '王慧淼',
                'tel' => '15868571991',
                'userid' => '033834553729226560'
            ],
            [
                'name' => '吴杭飞',
                'tel' => '17307695571',
                'userid' => '691524040721575237'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s107();
        $path = $this->request->domain() . "/img/" . date('Ymd').'/S107.jpg';

        // 上传图 
        // $media_id = $model->uploadDingFile($path, "鞋履报表{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        // foreach ($parms as $key => $val) {
        //     $res = $model->sendImageMsg($val['userid'], $media_id);
        // }  
        foreach ($parms as $key => $val) {
            $model->sendMarkdownImg($val['userid'], '鞋履报表', $path);
        } 
    }

    /**
     * 采购定推
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function cgdt_s119()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            [
                'name' => '俞有岳',
                'tel' => '13757775761',
                'userid' => '051218272920490024'
            ],
            [
                'name' => '杨剑',
                'tel' => '15200838578',
                'userid' => '1369166106841705'
            ],
            // [
            //     'name' => '李雅婷',
            //     'tel' => '15298454189',
            //     'userid' => '284616312226634272'
            // ],
            // [
            //     'name' => '何发惠',
            //     'tel' => '15019347538',
            //     'userid' => '111131100920206916'
            // ],
        ];

        $reportFormsService = new ReportFormsService();
        
        $userids = "";
        foreach ($parms as $key => $val) {
            if (count($parms) == $key + 1 ) {
                $userids .= $val['userid'];
            } else {
                $userids .= $val['userid'] . ',';
            }
        } 

        $sql = "select * from cwl_cgzdt_config";
        $select = $this->db_easyA->query($sql);

        $find = $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->field('更新日期')->where(['风格' => '基本款'])->find();
        // dump($find);die;
        
        if ($select) {
            foreach ($select as $key => $val) {
                // $res = system("wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}", $result);
                $jpg_url = $this->request->domain()."/img/".date('Ymd') . "/cgzdt_{$val['值']}.jpg?v=" . time();

                $更新日期 = date('Y-m-d', time());
                $headers = get_headers($jpg_url);
                if (substr($headers[0], 9, 3) == 200) {
                    $model->sendMarkdownImg($userids, $val['值'] . " 基本款 " . $更新日期 . " 表：S119 " , $jpg_url);
                }
            }
        }
    }

    /**
     * 采购定推 针织衫单独推送
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function cgdt_s119_zhenzhishan()
    {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '程帅杰',
                'tel' => '13583209142',
                'userid' => '15853812794255837'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        $userids = "";
        foreach ($parms as $key => $val) {
            if (count($parms) == $key + 1 ) {
                $userids .= $val['userid'];
            } else {
                $userids .= $val['userid'] . ',';
            }
        } 

        $jpg_url = $this->request->domain()."/img/".date('Ymd') . "/cgzdt_针织衫.jpg?v=" . time();
        // $jpg_url = $this->request->domain()."/img/20231115/cgzdt_针织衫.jpg?v=" . time();

        $更新日期 = date('Y-m-d', time());
        $headers = get_headers($jpg_url);
        if (substr($headers[0], 9, 3) == 200) {
            $model->sendMarkdownImg($userids, "针织衫 基本款 " . $更新日期 . " 表：S119 " , $jpg_url);
        }
    }

    /**
     * 采购定推 鞋履单独推送
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
     */
    public function cgdt_s119_xielv()
    {
        // $date = input('date') ? input('date') : date('Y-m-d');
        $model = new Sample;
        $parms = [
            [
                'name' => '陈威良',
                'tel' => '13066166636',
                'userid' => '350364576037719254'
            ],
            [
                'name' => '王慧淼',
                'tel' => '15868571991',
                'userid' => '033834553729226560'
            ],
            [
                'name' => '吴杭飞',
                'tel' => '17307695571',
                'userid' => '691524040721575237'
            ],
        ];

        $reportFormsService = new ReportFormsService();
        
        $userids = "";
        foreach ($parms as $key => $val) {
            if (count($parms) == $key + 1 ) {
                $userids .= $val['userid'];
            } else {
                $userids .= $val['userid'] . ',';
            }
        } 

        $jpg_url = $this->request->domain()."/img/".date('Ymd') . "/cgzdt_鞋履.jpg?v=" . time();
        // $jpg_url = $this->request->domain()."/img/20231117/cgzdt_鞋履.jpg?v=" . time();

        // die;
        // $jpg_url = $this->request->domain()."/img/20231115/cgzdt_针织衫.jpg?v=" . time();

        $更新日期 = date('Y-m-d', time());
        $headers = get_headers($jpg_url);
        if (substr($headers[0], 9, 3) == 200) {
            $model->sendMarkdownImg($userids, "鞋履 基本款 " . $更新日期 . " 表：S119 " , $jpg_url);
        }
    }

    /**
     * 推送消息至至管理者
     */
   public function pushToManage()
   {
       /**
        * 推送列表
        */
       $list = ApiConstant::DINGDING_MANAGE_LIST;
       $model = new Sample;
       // 循环推送
       foreach ($list as $key => $val){
           $parms = [
               'name' => $val['name'],
               'tel' => $val['tel'],
               'userid' => $val['userid']
           ];
           $model->main($parms);
       }
   }

    /**
     * 执行推送(商品专员 + 管理层)
     */
   public function run()
   {
       // 推送总览
//       $this->pushToManage();
       // 推送专员任务
       $this->pushToSpecialist();
   }
}
