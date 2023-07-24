<?php
declare (strict_types = 1);

namespace app\api\controller\yinliu;
use app\api\constants\ApiConstant;
use app\BaseController;
use app\api\service\bi\yinliu\YinliuDataService;
use app\api\service\dingding\Sample;
use app\api\service\bi\report\ReportFormsService;
use think\Request;

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
        echo $media_id = $model->uploadDingFile($path, "每日业绩{$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        foreach ($parms as $key => $val) {
            $res = $model->sendImageMsg($val['userid'], $media_id);
            dump($res);
        }  
    }

    /**
     * 提送每日零售核销单
     * 131255621326201188
     * https://bx.babiboy.com/dingding/get?code=15880012590
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
            // [
            //     'name' => '王威',
            //     'tel' => '15880012590',
            //     'userid' => '0812473564939990'
            // ],
            // [
            //     'name' => '陈子文',
            //     'tel' => '13925028633',
            //     'userid' => '130821405237722239'
            // ],
            // [
            //     'name' => '谷冬冬',
            //     'tel' => '18557705286',
            //     'userid' => '1302252256669056'
            // ],
            // [
            //     'name' => '俞有岳',
            //     'tel' => '13757775761',
            //     'userid' => '051218272920490024'
            // ],
            // [
            //     'name' => '周欢',
            //     'tel' => '13576147826',
            //     'userid' => '0427616739697274'
            // ],
            // [
            //     'name' => '杨梦晓',
            //     'tel' => '18858012505',
            //     'userid' => '16205553134591457'
            // ],
            // [
            //     'name' => '徐亮滨',
            //     'tel' => '13799884322',
            //     'userid' => '16085153953415770'
            // ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s116($date);
        $path = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S116.jpg';

        // 上传图 
        echo $media_id = $model->uploadDingFile($path, "零售核销单报表 {$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        foreach ($parms as $key => $val) {
            $res = $model->sendImageMsg($val['userid'], $media_id);
            dump($res);
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
                'name' => '王威',
                'tel' => '15880012590',
                'userid' => '0812473564939990'
            ],
            // [
            //     'name' => '陈子文',
            //     'tel' => '13925028633',
            //     'userid' => '130821405237722239'
            // ],
            // [
            //     'name' => '谷冬冬',
            //     'tel' => '18557705286',
            //     'userid' => '1302252256669056'
            // ],
            // [
            //     'name' => '俞有岳',
            //     'tel' => '13757775761',
            //     'userid' => '051218272920490024'
            // ],
            // [
            //     'name' => '周欢',
            //     'tel' => '13576147826',
            //     'userid' => '0427616739697274'
            // ],
            // [
            //     'name' => '杨梦晓',
            //     'tel' => '18858012505',
            //     'userid' => '16205553134591457'
            // ],
            // [
            //     'name' => '徐亮滨',
            //     'tel' => '13799884322',
            //     'userid' => '16085153953415770'
            // ],
        ];

        $reportFormsService = new ReportFormsService();
        
        // 创建图
        $reportFormsService->create_table_s117($date);
        $path = $this->request->domain() . "/img/" . date('Ymd',strtotime('+1day')).'/S116.jpg';

        // 上传图 
        echo $media_id = $model->uploadDingFile($path, "零售核销单报表 {$date}");
        // $media_id = '@lAjPDfmVbpW7TQjOOJWLGM5pLQAn';
        // 发送图
        foreach ($parms as $key => $val) {
            $res = $model->sendImageMsg($val['userid'], $media_id);
            dump($res);
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
