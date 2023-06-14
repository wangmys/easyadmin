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

/**
 * 码比数据处理
 * Class SizeRatio
 * @package app\api\controller
 */
class SizeRatio
{

    /**
     * 服务
     * @var CodeService|null
     */
    protected $service = null;
    // 日期
    protected $Date = '';

    /**
     * 从康雷查询排名数据并保存到当前数据库
     * @return \think\response\Json
     */
    public function pullData()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullData();
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

    /**
     * 拉取7天周销数据到缓存
     * @return \think\response\Json
     */
    public function pull7DaySale()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pull7DaySale();
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

    /**
     * 拉取累销数据保存到缓存
     * @return \think\response\Json
     */
    public function pullAccumulatedSale()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullAccumulatedSale();
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

    /**
     * 拉取店铺预计库存到缓存
     * @return \think\response\Json
     */
    public function pullShopEstimatedStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullShopEstimatedStock();
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

    /**
     * 拉取云仓可用库存到缓存
     * @return \think\response\Json
     */
    public function pullWarehouseAvailableStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullWarehouseAvailableStock();
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

    /**
     * 拉取云仓在途库存保存到缓存
     * @return \think\response\Json
     */
    public function pullWarehouseTransitStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullWarehouseTransitStock();
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

    /**
     * 拉取仓库采购库存到缓存
     * @return \think\response\Json
     */
    public function pullPurchaseStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullPurchaseStock();
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


    /**
     * 从缓存里把码比数据源同步至数据库
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

    /**
     * 根据码比数据源计算最终码比数据
     */
    public function saveRatio()
    {
        $res = \app\admin\model\code\SizeAllRatio::saveData();
        print_r($res);die;
    }
}
