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

    /**
     * 发送报表
     */
    public function send_s101()
    {
        // 生成图片
        $this->service->create_table2();
    }
    
    public function test()
    {
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $send_data = [
            'S101' => [
                'title' => 'S101',
                'jpg_url' => $this->request->domain()."./img/".date('Ymd').'/S101.jpg'
            ],
            'S102' => [
                'title' => 'S102',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S101.jpg'
            ],
            'S103' => [
                'title' => 'S103',
                'jpg_url' => $this->request->domain()."/img/".date('Ymd').'/S101.jpg'
            ]
        ];
        $res = [];
        foreach ($send_data as $k=>$v){
            // 推送
             $res[] = $model->send($v['title'],$v['jpg_url']);
        }
        return json($res);
    }
    
    /**
     * 执行指定任务
     * @return \think\response\Json
     */
    public function run()
    {
        // 发送数据报表
        $this->create_s101();
    }
}
