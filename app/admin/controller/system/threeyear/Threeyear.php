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
     * 获取某年的每周第一天和最后一天
     * @param  [int] $year [年份]
     * @return [arr]       [每周的周一和周日]
     */
    function get_week($year) {
        $year_start = $year . "-01-01";
        $year_end = $year . "-12-31";
        $startday = strtotime($year_start);
        if (intval(date('N', $startday)) != '1') {
            $startday = strtotime("next monday", strtotime($year_start)); //获取年第一周的日期
        }
        $year_mondy = date("Y-m-d", $startday); //获取年第一周的日期

        $endday = strtotime($year_end);
        if (intval(date('W', $endday)) == '7') {
            $endday = strtotime("last sunday", strtotime($year_end));
        }

        $num = intval(date('W', $endday));
        for ($i = 1; $i <= $num; $i++) {
            $j = $i -1;
            $start_date = date("Y-m-d", strtotime("$year_mondy $j week "));

            $end_day = date("Y-m-d", strtotime("$start_date +6 day"));

            $week_array[$i] = array (
                str_replace("-",
                    ".",
                    $start_date
                ), str_replace("-", ".", $end_day));
        }
        return $week_array;
    }

    /**
     * @NodeAnotation(title="三年趋势-获取筛选栏多选参数")
     */
    public function getXmMapSelect() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);
        
    }

    /**
     * @NodeAnotation(title="三年趋势-保存筛选条件")
     */
    public function saveSelect() {

        $params = $this->request->param();

        if (request()->isAjax()) {
            // print_r([$params]);die;

            $params['from_cache'] = 1;

            $res = $this->service->index($params);
            $this->service->save_threeyear_cache($res, $params);
            
            return json(["code" => "0", "msg" => "", "count" => count($res), "data" => $res,  'create_time' => date('Y-m-d'), 'if_save_select'=>1]);
        } 
        else {
            echo 'error';die;
        }    

    }


}
