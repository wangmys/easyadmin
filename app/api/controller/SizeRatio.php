<?php
declare (strict_types = 1);

namespace app\api\controller;
use app\admin\model\dress\YinliuStore;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\cache\driver\Redis;
use think\facade\Db;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\Yinliu;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Customers;
use app\api\service\ratio\CodeService;

class SizeRatio
{
    /**
     * 同步码比数据
     * @return \think\response\Json
     */
    public function saveSaleData()
    {
        $server = new CodeService;
        $model = $server;
        foreach (ApiConstant::RATIO_PULL_REDIS_KEY as $k => $v){
            // 从缓存同步到MYSQL数据库
            $code = $model->saveSaleData($v);
        }
        echo '<pre>';
        print_r([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
        die;
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }

    public function saveRatio()
    {
        $res = \app\admin\model\code\SizeAllRatio::saveData();
        echo '<pre>';
        print_r($res);
    }
}
