<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="断码率")
 */
class Duanmalv extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    // 更新周推条件
    protected function updateWeekpushMap() {
        $this->db_easyA->table('cwl_budongxiao_weekpush_map')->insert([
            'create_time' => date('Y-m-d H:i:s', time()),
            'update_time' => date('Y-m-d H:i:s', time()),
            'map' => json_encode($this->params),
        ]); 
    }

    /**
     * @NodeAnotation(title="周销")
     */
    public function zhouxiao() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    * 
                FROM cwl_duanmalv_retail 
                WHERE 
                    1
                ORDER BY 
                    `商品负责人`, 省份, 店铺名称, 大类, 中类, 小类, 领型, 风格
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_retail 
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $select[0]['更新日期']]);
        } else {

            return View('zhouxiao', [

            ]);
        }
    }

    // 下载周销
    public function excel_zhouxiao() {
        $sql = "
            SELECT 
                * 
            FROM cwl_duanmalv_retail 
            WHERE 
                1
            ORDER BY 
                `商品负责人`, 省份, 店铺名称, 大类, 中类, 小类, 领型, 风格
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '断码率周销明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

     /**
     * @NodeAnotation(title="云仓在途")
     */
    public function zt() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    * 
                FROM cwl_duanmalv_zt 
                WHERE 
                    1
                ORDER BY 
                    `云仓`, 季节, 一级分类, 二级分类
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_zt 
                WHERE 
                    1
                ORDER BY 
                    `云仓`, 季节, 一级分类, 二级分类
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $select[0]['更新日期']]);
        } else {

            return View('zt', [

            ]);
        }
    }

    /**
     * @NodeAnotation(title="单店断码明细") 表7
     */
    public function sk() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_sk WHERE 1
                ORDER BY 
                    云仓, `商品负责人` desc, 店铺名称, 风格, 季节, 一级分类, 二级分类, 分类, 领型
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_sk
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('sk', [

            ]);
        }
    }

    // 下载单店断码明细
    public function excel_sk() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_sk WHERE 1
            ORDER BY 
                云仓, `商品负责人` desc, 店铺名称, 风格, 季节, 一级分类, 二级分类, 分类, 领型
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店断码明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * @NodeAnotation(title="单店TOP60及断码数") 
     */
    public function handle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_handle_1 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_handle_1
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('handle', [

            ]);
        }        
    }

    // 下载单店TOP60及断码数
    public function excel_handle() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_handle_1 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店TOP60及断码数_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * @NodeAnotation(title="单店品类断码情况") 
     */
    public function table6() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_table6 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table6
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table6', [

            ]);
        }        
    }

    // 下载单店品类断码情况
    public function excel_table6() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_table6 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店品类断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

        /**
     * @NodeAnotation(title="单店断码情况") 
     */
    public function table5() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_table5 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table5
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table5', [

            ]);
        }        
    }

    // 下载单店断码情况
    public function excel_table5() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_table5 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

     /**
     * @NodeAnotation(title="单店单款断码情况") 
     */
    public function table4() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT
                    t4.风格,
                    t4.大类,
                    t4.中类,
                    t4.领型,
                    t4.货号,
                    yn.省份 as '省份-滇',
                    yn.上柜数 as '上柜数-滇',
                    yn.断码家数 as '断码家数-滇',
                    yn.断码率 as '断码率-滇',
                    yn.周转 as '周转-滇',
                    sc.省份 as '省份-蜀',
                    sc.上柜数 as '上柜数-蜀',
                    sc.断码家数 as '断码家数-蜀',
                    sc.断码率 as '断码率-蜀',
                    sc.周转 as '周转-蜀',
                    tj.省份 as '省份-津',
                    tj.上柜数 as '上柜数-津',
                    tj.断码家数 as '断码家数-津',
                    tj.断码率 as '断码率-津',
                    tj.周转 as '周转-津',
                    nx.省份 as '省份-宁',
                    nx.上柜数 as '上柜数-宁',
                    nx.断码家数 as '断码家数-宁',
                    nx.断码率 as '断码率-宁',
                    nx.周转 as '周转-宁',
                    ah.省份 as '省份-皖',
                    ah.上柜数 as '上柜数-皖',
                    ah.断码家数 as '断码家数-皖',
                    ah.断码率 as '断码率-皖',
                    ah.周转 as '周转-皖',
                    gd.省份 as '省份-粤',
                    gd.上柜数 as '上柜数-粤',
                    gd.断码家数 as '断码家数-粤',
                    gd.断码率 as '断码率-粤',
                    gd.周转 as '周转-粤',
                    gx.省份 as '省份-桂',
                    gx.上柜数 as '上柜数-桂',
                    gx.断码家数 as '断码家数-桂',
                    gx.断码率 as '断码率-桂',
                    gx.周转 as '周转-桂',
                    xj.省份 as '省份-新',
                    xj.上柜数 as '上柜数-新',
                    xj.断码家数 as '断码家数-新',
                    xj.断码率 as '断码率-新',
                    xj.周转 as '周转-新',
                    jx.省份 as '省份-赣',
                    jx.上柜数 as '上柜数-赣',
                    jx.断码家数 as '断码家数-赣',
                    jx.断码率 as '断码率-赣',
                    jx.周转 as '周转-赣',
                    henan.省份 as '省份-豫',
                    henan.上柜数 as '上柜数-豫',
                    henan.断码家数 as '断码家数-豫',
                    henan.断码率 as '断码率-豫',
                    henan.周转 as '周转-豫',
                    hb.省份 as '省份-鄂',
                    hb.上柜数 as '上柜数-鄂',
                    hb.断码家数 as '断码家数-鄂',
                    hb.断码率 as '断码率-鄂',
                    hb.周转 as '周转-鄂',
                    hunan.省份 as '省份-湘',
                    hunan.上柜数 as '上柜数-湘',
                    hunan.断码家数 as '断码家数-湘',
                    hunan.断码率 as '断码率-湘',
                    hunan.周转 as '周转-湘',
                    gs.省份 as '省份-甘',
                    gs.上柜数 as '上柜数-甘',
                    gs.断码家数 as '断码家数-甘',
                    gs.断码率 as '断码率-甘',
                    gs.周转 as '周转-甘',
                    fj.省份 as '省份-闽',
                    fj.上柜数 as '上柜数-闽',
                    fj.断码家数 as '断码家数-闽',
                    fj.断码率 as '断码率-闽',
                    fj.周转 as '周转-闽',
                    gz.省份 as '省份-贵',
                    gz.上柜数 as '上柜数-贵',
                    gz.断码家数 as '断码家数-贵',
                    gz.断码率 as '断码率-贵',
                    gz.周转 as '周转-贵',
                    cq.省份 as '省份-渝',
                    cq.上柜数 as '上柜数-渝',
                    cq.断码家数 as '断码家数-渝',
                    cq.断码率 as '断码率-渝',
                    cq.周转 as '周转-渝',
                    xx.省份 as '省份-陕',
                    xx.上柜数 as '上柜数-陕',
                    xx.断码家数 as '断码家数-陕',
                    xx.断码率 as '断码率-陕',
                    xx.周转 as '周转-陕',
                    qh.省份 as '省份-青',
                    qh.上柜数 as '上柜数-青',
                    qh.断码家数 as '断码家数-青',
                    qh.断码率 as '断码率-青',
                    qh.周转 as '周转-青'
                FROM
                    cwl_duanmalv_table4 AS t4
                    left join cwl_duanmalv_table4 as yn on t4.货号=yn.货号 and yn.省份='云南省'
                    left join cwl_duanmalv_table4 as sc on t4.货号=sc.货号 and sc.省份='四川省'
                    left join cwl_duanmalv_table4 as tj on t4.货号=tj.货号 and tj.省份='天津'
                    left join cwl_duanmalv_table4 as nx on t4.货号=nx.货号 and nx.省份='宁夏回族自治区'
                    left join cwl_duanmalv_table4 as ah on t4.货号=ah.货号 and ah.省份='安徽省'
                    left join cwl_duanmalv_table4 as gd on t4.货号=gd.货号 and gd.省份='广东省'
                    left join cwl_duanmalv_table4 as gx on t4.货号=gx.货号 and gx.省份='广西壮族自治区'
                    left join cwl_duanmalv_table4 as xj on t4.货号=xj.货号 and xj.省份='新疆维吾尔自治区'
                    left join cwl_duanmalv_table4 as jx on t4.货号=jx.货号 and jx.省份='江西省'
                    left join cwl_duanmalv_table4 as henan on t4.货号=henan.货号 and henan.省份='河南省'
                    left join cwl_duanmalv_table4 as zj on t4.货号=zj.货号 and zj.省份='浙江省'
                    left join cwl_duanmalv_table4 as hainan on t4.货号=hainan.货号 and hainan.省份='海南省'
                    left join cwl_duanmalv_table4 as hb on t4.货号=hb.货号 and hb.省份='湖北省'
                    left join cwl_duanmalv_table4 as hunan on t4.货号=hunan.货号 and hunan.省份='湖南省'
                    left join cwl_duanmalv_table4 as gs on t4.货号=gs.货号 and gs.省份='甘肃省'
                    left join cwl_duanmalv_table4 as fj on t4.货号=fj.货号 and fj.省份='福建省'
                    left join cwl_duanmalv_table4 as gz on t4.货号=gz.货号 and gz.省份='贵州省'
                    left join cwl_duanmalv_table4 as cq on t4.货号=cq.货号 and cq.省份='重庆'
                    left join cwl_duanmalv_table4 as xx on t4.货号=xx.货号 and xx.省份='陕西省'
                    left join cwl_duanmalv_table4 as qh on t4.货号=qh.货号 and qh.省份='青海省'
                --  where sk.省份='浙江省'
                --  and sk.货号='B32502003'
                GROUP BY
                t4.风格, t4.大类, t4.中类, t4.货号
                ORDER BY t4.风格
                    LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table4 AS t4
                GROUP BY
                t4.风格, t4.大类, t4.中类, t4.货号
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table4', [

            ]);
        }        
    }

    // 下载单店单款断码情况
    public function excel_table4() {
        $sql = "
            SELECT
                t4.风格,
                t4.大类,
                t4.中类,
                t4.领型,
                t4.货号,
                yn.省份 as '省份-滇',
                yn.上柜数 as '上柜数-滇',
                yn.断码家数 as '断码家数-滇',
                yn.断码率 as '断码率-滇',
                yn.周转 as '周转-滇',
                sc.省份 as '省份-蜀',
                sc.上柜数 as '上柜数-蜀',
                sc.断码家数 as '断码家数-蜀',
                sc.断码率 as '断码率-蜀',
                sc.周转 as '周转-蜀',
                tj.省份 as '省份-津',
                tj.上柜数 as '上柜数-津',
                tj.断码家数 as '断码家数-津',
                tj.断码率 as '断码率-津',
                tj.周转 as '周转-津',
                nx.省份 as '省份-宁',
                nx.上柜数 as '上柜数-宁',
                nx.断码家数 as '断码家数-宁',
                nx.断码率 as '断码率-宁',
                nx.周转 as '周转-宁',
                ah.省份 as '省份-皖',
                ah.上柜数 as '上柜数-皖',
                ah.断码家数 as '断码家数-皖',
                ah.断码率 as '断码率-皖',
                ah.周转 as '周转-皖',
                gd.省份 as '省份-粤',
                gd.上柜数 as '上柜数-粤',
                gd.断码家数 as '断码家数-粤',
                gd.断码率 as '断码率-粤',
                gd.周转 as '周转-粤',
                gx.省份 as '省份-桂',
                gx.上柜数 as '上柜数-桂',
                gx.断码家数 as '断码家数-桂',
                gx.断码率 as '断码率-桂',
                gx.周转 as '周转-桂',
                xj.省份 as '省份-新',
                xj.上柜数 as '上柜数-新',
                xj.断码家数 as '断码家数-新',
                xj.断码率 as '断码率-新',
                xj.周转 as '周转-新',
                jx.省份 as '省份-赣',
                jx.上柜数 as '上柜数-赣',
                jx.断码家数 as '断码家数-赣',
                jx.断码率 as '断码率-赣',
                jx.周转 as '周转-赣',
                henan.省份 as '省份-豫',
                henan.上柜数 as '上柜数-豫',
                henan.断码家数 as '断码家数-豫',
                henan.断码率 as '断码率-豫',
                henan.周转 as '周转-豫',
                hb.省份 as '省份-鄂',
                hb.上柜数 as '上柜数-鄂',
                hb.断码家数 as '断码家数-鄂',
                hb.断码率 as '断码率-鄂',
                hb.周转 as '周转-鄂',
                hunan.省份 as '省份-湘',
                hunan.上柜数 as '上柜数-湘',
                hunan.断码家数 as '断码家数-湘',
                hunan.断码率 as '断码率-湘',
                hunan.周转 as '周转-湘',
                gs.省份 as '省份-甘',
                gs.上柜数 as '上柜数-甘',
                gs.断码家数 as '断码家数-甘',
                gs.断码率 as '断码率-甘',
                gs.周转 as '周转-甘',
                fj.省份 as '省份-闽',
                fj.上柜数 as '上柜数-闽',
                fj.断码家数 as '断码家数-闽',
                fj.断码率 as '断码率-闽',
                fj.周转 as '周转-闽',
                gz.省份 as '省份-贵',
                gz.上柜数 as '上柜数-贵',
                gz.断码家数 as '断码家数-贵',
                gz.断码率 as '断码率-贵',
                gz.周转 as '周转-贵',
                cq.省份 as '省份-渝',
                cq.上柜数 as '上柜数-渝',
                cq.断码家数 as '断码家数-渝',
                cq.断码率 as '断码率-渝',
                cq.周转 as '周转-渝',
                xx.省份 as '省份-陕',
                xx.上柜数 as '上柜数-陕',
                xx.断码家数 as '断码家数-陕',
                xx.断码率 as '断码率-陕',
                xx.周转 as '周转-陕',
                qh.省份 as '省份-青',
                qh.上柜数 as '上柜数-青',
                qh.断码家数 as '断码家数-青',
                qh.断码率 as '断码率-青',
                qh.周转 as '周转-青'
            FROM
                cwl_duanmalv_table4 AS t4
                left join cwl_duanmalv_table4 as yn on t4.货号=yn.货号 and yn.省份='云南省'
                left join cwl_duanmalv_table4 as sc on t4.货号=sc.货号 and sc.省份='四川省'
                left join cwl_duanmalv_table4 as tj on t4.货号=tj.货号 and tj.省份='天津'
                left join cwl_duanmalv_table4 as nx on t4.货号=nx.货号 and nx.省份='宁夏回族自治区'
                left join cwl_duanmalv_table4 as ah on t4.货号=ah.货号 and ah.省份='安徽省'
                left join cwl_duanmalv_table4 as gd on t4.货号=gd.货号 and gd.省份='广东省'
                left join cwl_duanmalv_table4 as gx on t4.货号=gx.货号 and gx.省份='广西壮族自治区'
                left join cwl_duanmalv_table4 as xj on t4.货号=xj.货号 and xj.省份='新疆维吾尔自治区'
                left join cwl_duanmalv_table4 as jx on t4.货号=jx.货号 and jx.省份='江西省'
                left join cwl_duanmalv_table4 as henan on t4.货号=henan.货号 and henan.省份='河南省'
                left join cwl_duanmalv_table4 as zj on t4.货号=zj.货号 and zj.省份='浙江省'
                left join cwl_duanmalv_table4 as hainan on t4.货号=hainan.货号 and hainan.省份='海南省'
                left join cwl_duanmalv_table4 as hb on t4.货号=hb.货号 and hb.省份='湖北省'
                left join cwl_duanmalv_table4 as hunan on t4.货号=hunan.货号 and hunan.省份='湖南省'
                left join cwl_duanmalv_table4 as gs on t4.货号=gs.货号 and gs.省份='甘肃省'
                left join cwl_duanmalv_table4 as fj on t4.货号=fj.货号 and fj.省份='福建省'
                left join cwl_duanmalv_table4 as gz on t4.货号=gz.货号 and gz.省份='贵州省'
                left join cwl_duanmalv_table4 as cq on t4.货号=cq.货号 and cq.省份='重庆'
                left join cwl_duanmalv_table4 as xx on t4.货号=xx.货号 and xx.省份='陕西省'
                left join cwl_duanmalv_table4 as qh on t4.货号=qh.货号 and qh.省份='青海省'
            --  where sk.省份='浙江省'
            --  and sk.货号='B32502003'
            GROUP BY
            t4.风格, t4.大类, t4.中类, t4.货号
            ORDER BY t4.风格
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店单款断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }    
}
