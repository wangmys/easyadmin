<?php

namespace app\api\controller\command;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\facade\Db;
use app\api\service\bi\command\CommandService;

/**
 * 第三放数据拉取
 * Class Pull
 * @package app\api\controller\command
 */
class Pull extends BaseController
{
     /**
     * 服务
     * @var YinliuDataService|null
     */
    protected $service = null;
    // 日期
    protected $Date = '';

    /**
     * 初始化
     * Pull constructor.
     */
    public function __construct()
    {
        $this->service = new CommandService;
    }
    /**
     * 拉取商品专员指令调拨记录
     */
    public function pullData()
    {
        $model = $this->service;
        // 拉取调拨数据
        $code = $model->pullData();
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }
}
