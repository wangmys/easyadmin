<?php
namespace app\admin\controller\system\threeyear;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\service\ThreeyearService;
use jianyan\excel\Excel;
use think\Request;

/**
 * Class Threeyear
 * @package app\admin\controller\system\threeyear
 * @ControllerAnnotation(title="三年趋势")
 */
class Threeyear extends AdminController
{
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->service = (new ThreeyearService())::getInstance();
        $this->request = $request;
    }

    /**
     * @NodeAnotation(title="三年趋势最终结果")
     */
    public function index() {
        ini_set('memory_limit','1024M');

        $params = $this->request->param();

        //test...
        // $res = $this->service->index($params);
        // print_r($res);die;

        if (request()->isAjax()) {
            
            $res = $this->service->index($params);
            
            return json(["code" => "0", "msg" => "", "count" => count($res), "data" => $res,  'create_time' => date('Y-m-d')]);
        } else {
            return View('system/threeyear/index', $this->service->getXmMapSelect());
        }        

    }

    /**
     * @NodeAnotation(title="三年趋势-获取筛选栏多选参数")
     */
    public function getXmMapSelect() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);
        
    }


}
