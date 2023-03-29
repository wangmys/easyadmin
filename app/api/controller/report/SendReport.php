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
        $this->service->create_table_s101();
        $this->service->create_table_s101('S104');
        $this->service->create_table_s102();
        $this->service->create_table_s103();
    }
    
    public function send()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S101' => [
                'title' => '加盟老店同比环比递增及完成率 表号:S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd').'/S101.jpg'
            ],
            'S104' => [
                'title' => '直营老店同比环比递增及完成率 表号:S104',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S104.jpg'
            ],
            'S102' => [
                'title' => '省份老店业绩同比 表号:S102',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S102.jpg'
            ],
            'S103' => [
                'title' => '省份老店业绩同比-分经营模式 表号:S103',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S103.jpg'
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
