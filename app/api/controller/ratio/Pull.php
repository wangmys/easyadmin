<?php

namespace app\api\controller\ratio;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\facade\Db;
use app\api\service\ratio\CodeService;

/**
 * 第三放数据拉取
 * Class Pull
 * @package app\api\controller\command
 */
class Pull extends BaseController
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
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }

    /**
     * 拉取云仓可用库存到缓存
     * @return \think\response\Json
     */
    public function pullWarehouseTransitStock()
    {
        $this->service = new CodeService;
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullWarehouseTransitStock();
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
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }
}
