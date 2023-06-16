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
use think\facade\Log;

/**
 * 码比-偏码数据处理
 * 1.拉取码比数据源储存在redis中
 * 2.从缓存中将码比数据源同步至MySQL
 * 3.根据据源统计全体偏码数据
 * 4.根据数据源统计云仓偏码数据
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
        // 记录执行
        pullLog($code,$model,'排名数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'周销数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'累销数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'店铺预计库存数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'云仓可用库存数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'云仓在途库存数据');
        return $model->getError($code);
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
        // 记录执行
        pullLog($code,$model,'仓库采购库存数据');
        return $model->getError($code);
    }

    /**
     * 1.拉取偏码数据源保存至Redis缓存
     */
    public function pullDataToSaveCache()
    {
        // 执行结果集
        $result = [];
        // 排名数据源
        $result['排名数据源'] = $this->pullData();
        // 周销数据源
        $result['周销数据源'] = $this->pull7DaySale();
        // 累销数据源
        $result['累销数据源'] = $this->pullAccumulatedSale();
        // 店铺预计库存数据源
        $result['店铺预计库存数据源'] = $this->pullShopEstimatedStock();
        // 云仓可用库存数据源
        $result['云仓可用库存数据源'] = $this->pullWarehouseAvailableStock();
        // 云仓在途库存数据源
        $result['云仓在途库存数据源'] = $this->pullWarehouseTransitStock();
        // 仓库采购数据源
        $result['仓库采购数据源'] = $this->pullPurchaseStock();
        echo '<pre>';
        print_r($result);
        die;
    }


    /**
     * 2.从缓存里把码比数据源同步至数据库
     * @return false|string
     */
    public function saveSaleData()
    {
        // 执行结果集
        $result = [];
        $server = new CodeService;
        $model = $server;
        foreach (ApiConstant::RATIO_PULL_REDIS_KEY as $k => $v){
            // 从缓存同步到MYSQL数据库
            $code = $model->saveSaleData($v);
            // 记录执行
            pullLog($code,$model,$v);
            $result[$v] = $model->getError();
        }
        echo '<pre>';
        print_r($result);
        die;
    }

    /**
     * 3.根据数据源计算并保存全体总体偏码数据
     */
    public function saveRatio()
    {
        $res = \app\admin\model\code\SizeAllRatio::saveData();
        print_r($res);die;
    }

    /**
     * 4.根据数据源计算并保存云仓偏码数据
     */
    public function selectRationData()
    {
        $res = \app\admin\model\code\SizeWarehouseRatio::saveData();
        print_r($res);die;
    }
}
