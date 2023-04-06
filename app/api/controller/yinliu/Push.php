<?php
declare (strict_types = 1);

namespace app\api\controller\yinliu;
use app\api\constants\ApiConstant;
use app\BaseController;
use app\api\service\bi\yinliu\YinliuDataService;
use app\api\service\dingding\Sample;

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
    public function __construct()
    {
        // 初始化日期
        $this->Date = date('Y-m-d');
        $this->service = new YinliuDataService;
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
       $this->pushToManage();
       // 推送专员任务
       $this->pushToSpecialist();
   }
}
