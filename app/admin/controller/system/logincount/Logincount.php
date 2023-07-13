<?php
namespace app\admin\controller\system\logincount;

use app\common\controller\AdminController;
use app\admin\service\LoginService;
use jianyan\excel\Excel;
use think\Request;

/**
 * Class Logincount
 * @package app\admin\controller\system
 */
class Logincount extends AdminController
{
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->service = LoginService::getInstance();
        $this->request = $request;
    }

    /**
     * 单店上装明细
     */
    public function login_count() {
        
        if (request()->isAjax()) {

            $params = $this->request->param();
            $res = $this->service->get_login_count_list($params);

            return json(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'], 'month_field' => json_encode($res['month_field']),  'create_time' => date('Y-m-d')]);
        } else {
            $res = $this->service->get_login_count_list([]);
            return View('system/logincount/login_count', [
                'month_field' => $res['month_field'],
            ]);
        }        

    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);
        
    }

}
