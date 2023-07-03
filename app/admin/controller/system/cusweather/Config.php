<?php

namespace app\admin\controller\system\cusweather;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\model\SystemConfig;
use app\admin\service\CusWeatherService;

/**
 * Class Config
 * 新店拼音配置
 */
class Config extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new CusWeatherService;
    }

    /**
     * 新店拼音配置
     */
    public function index() {

        $customer_list = $this->service->get_customer_list();
        $this->assign([
            'customer_list' => $customer_list,
        ]);

        return $this->fetch();
    }

    /**
     * 新店拼音配置
     * @return mixed
     */
    public function pinyin() {

        $customer_list = $this->service->get_customer_list();
        $this->assign([
            'customer_list' => $customer_list,
        ]);

        return $this->fetch();
    }

    /**
     * 保存
     */
    public function saveConfig() {

        $post = $this->request->post();
        if (!$post['id'] || !$post['weather_prefix']) {
            return $this->error('存在值为空的情况，请检查');
        }

        $res_id = $this->service->save_customer_info($post);

        return $this->success('成功',['id' => $post['id']]);

    }

}
