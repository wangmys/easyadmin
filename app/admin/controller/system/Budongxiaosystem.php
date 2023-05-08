<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\F;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="不动销系统推送")
 */
class Budongxiaosystem extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->rand_code = $this->rand_code(10);
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
     * @NodeAnotation(title="不动销系统配置")
     */
    public function config() {
        $typeQima = $this->getTypeQiMa('in ("下装","内搭","外套","鞋履","松紧长裤","松紧短裤")');
        
        // 商品负责人
        $people = SpWwBudongxiaoDetail::getPeople([
            ['商品负责人', 'exp', new Raw('IS NOT NULL')]
        ]);

        // 
        $select_config = $this->db_easyA->table('cwl_budongxiao_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('system/budongxiao/config', [
            'config' => $select_config,
            'typeQima' => $typeQima,
            'people' => $people,
        ]);
    }

    public function testMap() {
        $select_map = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->where(1)->order('id desc')->find();
        $select_qima = $this->db_easyA->table('cwl_budongxiao_qima')->where(1)->select()->toArray();

                // 二维转一维
                // $typeQima = $this->arr2to1($typeQima, '类别', '尺码数');

        $select_qima = $this->arr2to1($select_qima, '类别', '尺码数');
        $map = json_decode($select_map['map'], true);
        
        dump($select_qima);
        $qima_str = '';
        foreach ($map as $key => $val) {
            foreach ($select_qima as $key2 => $val2) {
                if ($key == $key2) {
                    // $qima_str
                }
            }
        }
    }

    /**
     * @NodeAnotation(title="单店不动销系统推送结果")
     */
    public function sysResult() {
        if (request()->isAjax()) {
            // 考核结果
            $kaohejieguo = input('kaohejieguo') ? input('kaohejieguo') : '不合格';
            if ($kaohejieguo == '所有') {
                $map = 1;
            } else {
                $map = [
                    ['考核结果', '=', $kaohejieguo],
                ];
            }
            // 超管
            $select_map = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->where(1)->order('id desc')->find();
            if (checkAdmin()) {
                $select_result = $this->db_easyA->table('cwl_budongxiao_result_sys')->where($map)->select()->toArray();
            } else {
                $select_result = $this->db_easyA->table('cwl_budongxiao_result_sys')->where($map)->where([
                    ['商品负责人', '=', session('admin.name')],
                ])->select()->toArray();
            }
             
            // $select_static = $this->single_statistics($select_map['rand_code']);
            return json(["code" => "0", "msg" => "", "count" => count($select_result), "data" => $select_result, 'rand_code' => $select_map['rand_code'], 'create_time' => $select_map['create_time']]);
        } else {
            $find_result_sys = $this->db_easyA->table('cwl_budongxiao_result_sys')->where(1)->find();
            if ($find_result_sys) {
                $success_percent = $find_result_sys['合格率2'];
            } else {
                $success_percent = "";
            }
            $total_url = $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'] . url('admin/system.Budongxiaosystem/open_dandian_total');
            return View('system/budongxiao/sys_result', [
                // 'config' => $select_config,
                // 'typeQima' => $typeQima,
                'total_url' => $total_url,
                'success_percent' => $success_percent,
            ]);
        }
    }

    public function open_dandian_total() {
        // 超管
        // dump($_SERVER);
        // echo $_SERVER['HTTP_HOST']  . url('admin/system.Budongxiaosystem/open_dandian_total'); die;
        $find_map = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->where(1)->order('id desc')->find();

        return View('system/budongxiao/open_dandian_total', [
            // 'config' => $select_config,
            // 'typeQima' => $typeQima,
            'rand_code' => $find_map['rand_code'],
            'create_time' => $find_map['create_time'],
        ]);
    }

    /**
     * @NodeAnotation(title="区域不动销系统推送结果")
     */
    public function sysAreaResult() {
        if (request()->isAjax()) {

            $input = input();
             
            $select_map = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->where(1)->order('id desc')->find();
            $select_config = $this->db_easyA->table('cwl_budongxiao_config')->where('id=1')->find();
            $static_qima = $this->getTypeQiMa('not in ("下装","内搭","外套","鞋履")');
            $params = array_merge($select_config, $static_qima);
            // dump($select_config);
            // dump($static_qima);
            
            // 删除空参数
            foreach ($params as $key => $val) {
                if (empty($val)) {
                    unset($params[$key]);
                }
            }
            $this->params = $params;
    
            $map1 = " a.create_time='{$select_map['create_time']}' ";
            if (!empty($this->params['上柜率'])) {
                $map2 = " AND a.上柜率>='{$this->params['上柜率']}' ";
            } else {
                $map2 = " ";
            }
            if (!empty($this->params['省份售罄'])) {
                $map3 = " AND a.省份售罄<='{$this->params['省份售罄']}' ";
            } else {
                $map3 = " ";
            }
            if (!checkAdmin()) {
                $name = session('admin.name');
                $map4 = " AND a.商品负责人 = '{$name}'";
            } else {
                $map4 = " ";
            }
            if (!empty($map_input['排名率'])) {
                $map5 = " having 排名率 >={$map_input['排名率']} ";
            } else {
                $map5 = " ";
            }
    
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = $this->params['limit'];
    
            $sql = "
                SELECT
                    a.*,b.`相同货号数`, a.品类排名 / b.相同货号数 * 100 as 排名率
                FROM
                    `cwl_budongxiao_history_sys` AS a
                    LEFT JOIN (
                    SELECT
                        商品负责人,省份,货号,品类排名, 
                        count(*) AS 相同货号数
                    FROM
                        cwl_budongxiao_history_sys 
                    GROUP BY
                    商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
                    AND a.省份 = b.省份 
                    AND a.货号 = b.货号 
                WHERE 
                    " . $map1 . $map2 . $map3 . $map4. $map5 . "
                ORDER BY
                    a.商品负责人 ASC
                limit {$pageParams1}, {$pageParams2}    
            "; 
    
            $sql2 = "
                SELECT
                    count(*) as tatalCount
                FROM
                    `cwl_budongxiao_history_sys` AS a
                    LEFT JOIN (
                    SELECT
                        商品负责人,省份,货号,品类排名, 
                        count(*) AS 相同货号数
                    FROM
                        cwl_budongxiao_history_sys 
                    GROUP BY
                    商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
                    AND a.省份 = b.省份 
                    AND a.货号 = b.货号 
                WHERE 
                    " . $map1 . $map2 . $map3 . $map4 . $map5 ."
                ORDER BY
                    a.商品负责人 ASC
            "; 
            
            $select_history_area = Db::connect('mysql')->query($sql);
            $count = Db::connect('mysql')->query($sql2); 
    
            // print_r( $count);
            // die;
            return json(["code" => "0", "msg" => '', "count" => $count[0]['tatalCount'], "data" => $select_history_area, 'create_time' => $select_map['create_time']]);
        } else {
            return View('system/budongxiao/sys_area_result', [
                // 'config' => $select_config,
                // 'typeQima' => $typeQima,
                // 'people' => $people,
            ]);
        }
    }
    /**
     * 单店不动销计算明细 导出
     */
    public function excel_history_detail() {
        if (checkAdmin()) {
            $select_result = $this->db_easyA->table('cwl_budongxiao_history_sys')->where(1)->select()->toArray();
        } else {
            $select_result = $this->db_easyA->table('cwl_budongxiao_history_sys')->where([
                ['商品负责人', '=', session('admin.name')]
            ])->select()->toArray();
        }
        
        $header = [];
        foreach($select_result[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select_result, $header, '单店不动销明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * 区域不动销计算明细 导出
     */
    public function excel_history_detail_area() {
        $select_map = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->where(1)->order('id desc')->find();
        $select_config = $this->db_easyA->table('cwl_budongxiao_config')->where('id=1')->find();
        $static_qima = $this->getTypeQiMa('not in ("下装","内搭","外套","鞋履")');
        $params = array_merge($select_config, $static_qima);
        // dump($select_config);
        // dump($static_qima);
        
        // 删除空参数
        foreach ($params as $key => $val) {
            if (empty($val)) {
                unset($params[$key]);
            }
        }
        $this->params = $params;

        $map1 = " a.create_time='{$select_map['create_time']}' ";
        if (!empty($this->params['上柜率'])) {
            $map2 = " AND a.上柜率>='{$this->params['上柜率']}' ";
        } else {
            $map2 = " ";
        }
        if (!empty($this->params['省份售罄'])) {
            $map3 = " AND a.省份售罄<='{$this->params['省份售罄']}' ";
        } else {
            $map3 = " ";
        }
        if (!checkAdmin()) {
            $name = session('admin.name');
            $map4 = " AND a.商品负责人 = '{$name}'";
        } else {
            $map4 = " ";
        }
        if (!empty($map_input['排名率'])) {
            $map5 = " having 排名率 >={$map_input['排名率']} ";
        } else {
            $map5 = " ";
        }

        $sql = "
            SELECT
                a.*,b.`相同货号数`, a.品类排名 / b.相同货号数 * 100 as 排名率
            FROM
                `cwl_budongxiao_history_sys` AS a
                LEFT JOIN (
                SELECT
                    商品负责人,省份,货号,品类排名, 
                    count(*) AS 相同货号数
                FROM
                    cwl_budongxiao_history_sys 
                GROUP BY
                商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
                AND a.省份 = b.省份 
                AND a.货号 = b.货号 
            WHERE 
                " . $map1 . $map2 . $map3 . $map4. $map5 . "
            ORDER BY
                a.商品负责人 ASC 
        "; 
        $select_history_area = Db::connect('mysql')->query($sql);

        $header = [];
        foreach($select_history_area[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select_history_area, $header, '区域不动销明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 二维数组转一维数组
    public function arr2to1($arr, $key, $val) {
        $newArr = [];
        foreach ($arr as $key2 => $val2) {
            $newArr[$val2['类别']] = $val2['齐码数'];
        }

        return $newArr;
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 省份
        $provinceAll = SpWwBudongxiaoDetail::getMapProvince();
        // 门店
        $storeAll = SpWwBudongxiaoDetail::getMapStore();

        $select_config = $this->db_easyA->table('cwl_budongxiao_config')->field('省份,不考核门店')->where('id=1')->find();

        $select_nostore = explode(',', $select_config['不考核门店']);
        $select_province = explode(',', $select_config['省份']);
        // dump($select_province);

        // 省份选中
        foreach ($select_province as $key => $val) {
            foreach ($provinceAll as $key2 => $val2) {
                if ($val == $val2['name']) {
                    $provinceAll[$key2]['selected'] = true;
                }
            } 
        }

        // 不考核门店选中
        foreach ($select_nostore as $key => $val) {
            foreach ($storeAll as $key2 => $val2) {
                if ($val == $val2['name']) {
                    $storeAll[$key2]['selected'] = true;
                }
            } 
        }

        return json(["code" => "0", "msg" => "", "data" => ['provinceAll' => $provinceAll, 'storeAll' => $storeAll]]);
    }

    // 单店不动销
    public function store22222222222($store) {
        // 1. 预计库存大于1SKC   改规则，店铺库存数量>1并且上市天数满足的货号算一个skc
        // $yujiSkc = SpCustomerStockSkc::getSkc($store, $this->params);

        // =IF(L89<30,0,IF(N89="","30天以上",IF(S89="","20-30天",IF(R89="","15-20天",IF(Q89="","10-15天",IF(P89="","5-10天",))))))
        $map = [
            ['店铺名称', '=', $store],
            ['季节归集', '=', $this->params['季节归集']],
            ['大类' , '<>', '配饰'],
            ['店铺库存数量' , '>', 1],
        ];

        // dump($map); die;

        // 没有上市日期就直接使用上市天数
        if (empty($this->params['上市时间'])) {
            $map[] = ['上市天数' , '>=', $this->params['上市天数']];
            $map[] = ['商品负责人' , 'exp', new Raw('IS NOT NULL')];
            $res_all = SpWwBudongxiaoDetail::joinYuncang_all($map);
            $res_all_new = [];

            // echo SpWwBudongxiaoDetail::getLastSql(); die;
            
            // 赋值 上市时间修正 上市天数修正
            foreach($res_all as $key => $val) {
                $res_all[$key]['上市时间修正'] = $val['上市时间'];
                $res_all[$key]['上市天数修正'] = $val['上市天数'];
                $res_all_new[] = $res_all[$key];
            }
        } else {
            $res_all = SpWwBudongxiaoDetail::joinYuncang_all($map);
            $res_all_new = [];
            foreach($res_all as $key => $val) {
                $diffDay1 = $this->diffDay($val['上市时间'], $this->params['上市时间']);
                // 设置日期大于真实日期
                if ($diffDay1 >= 0) {
                    // 设置日期到昨天 上市天数改
                    $diffDay2 = $this->diffDay($this->params['上市时间'], date('Y-m-d', strtotime('-1day')));
                    $res_all[$key]['上市时间修正'] = $this->params['上市时间'];
                    $res_all[$key]['上市天数修正'] = $diffDay2;
                } else {
                    $res_all[$key]['上市时间修正'] = $this->params['上市时间'];
                    $res_all[$key]['上市天数修正'] = $val['上市天数'];
                }

                // 修正之后的天数>=设置天数
                if ($res_all[$key]['上市天数修正'] >= $this->params['上市天数']) {
                    $res_all_new[] = $res_all[$key];
                }
            }
        }

        // echo '<pre>';
        // print_r($res_all_new);die;

        if ($res_all_new) {
            // 货号累计不动销天数
            $day5_10 = 0;
            $day10_15 = 0;
            $day15_20 = 0;
            $day20_30 = 0;
            $day30 = 0;
            // 考核标准才插入历史记录
            $insert_history_data = [];
            foreach ($res_all_new as $key => $val) {
                // 30天以上
                if (empty($val['累销量'])) {
                    $res_all_new[$key]['不动销区间'] = '30天以上';
                    
                // 20-30天
                } elseif (!empty($val['累销量']) && empty($val['二十天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '20-30天';
                    
                // 15-20天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && empty($val['十五天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '15-20天';
                    
                // 10-15天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && !empty($val['十五天销量']) && empty($val['十天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '10-15天';
                    
                // 5-10天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && !empty($val['十五天销量']) && !empty($val['十天销量']) && !empty($val['十天销量']) && empty($val['五天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '5-10天';
                    
                } else {
                    $res_all_new[$key]['不动销区间'] = '';
                }

                $res_all_new[$key]['不动销区间修订'] = $this->checkQiMa($res_all_new[$key]);
                $res_all_new[$key]['考核标准'] = $this->params['考核区间'] ? $this->params['考核区间'] : '30天以上';
                $res_all_new[$key]['create_time'] = $this->create_time;
                $res_all_new[$key]['rand_code'] = $this->rand_code;

                // 累销天数计算 
                if ($res_all_new[$key]['不动销区间修订'] == '30天以上') {
                    $day30 ++;
                } elseif ($res_all_new[$key]['不动销区间修订'] == '20-30天') {
                    $day20_30 ++;
                } elseif ($res_all_new[$key]['不动销区间修订'] == '15-20天') {
                    $day15_20 ++;
                } elseif ($res_all_new[$key]['不动销区间修订'] == '10-15天') {
                    $day10_15 ++;
                } elseif ($res_all_new[$key]['不动销区间修订'] == '5-10天') {
                    $day5_10 ++;
                }

                if ($this->params['考核区间'] == '30天以上') {
                    if ($res_all_new[$key]['不动销区间'] == '30天以上') {
                        $insert_history_data[] = $res_all_new[$key];
                    }

                } elseif ($this->params['考核区间'] == '20-30天') {
                    if ($res_all_new[$key]['不动销区间'] == '30天以上' || $res_all_new[$key]['不动销区间'] == '20-30天') {
                        $insert_history_data[] = $res_all_new[$key];
                    }
                } elseif ($this->params['考核区间'] == '15-20天') {
                    if ($res_all_new[$key]['不动销区间'] == '30天以上' || $res_all_new[$key]['不动销区间'] == '20-30天' || $res_all_new[$key]['不动销区间'] == '15-20天') {
                        $insert_history_data[] = $res_all_new[$key];
                    }
                } elseif ($this->params['考核区间'] == '10-15天') {
                    if ($res_all_new[$key]['不动销区间'] == '30天以上' || $res_all_new[$key]['不动销区间'] == '20-30天' || $res_all_new[$key]['不动销区间'] == '15-20天' 
                    || $res_all_new[$key]['不动销区间'] == '10-15天') {
                        $insert_history_data[] = $res_all_new[$key];
                    }
                } elseif ($this->params['考核区间'] == '5-10天') {
                    if ($res_all_new[$key]['不动销区间'] == '30天以上' || $res_all_new[$key]['不动销区间'] == '20-30天' || $res_all_new[$key]['不动销区间'] == '15-20天' 
                    || $res_all_new[$key]['不动销区间'] == '10-15天' || $res_all_new[$key]['不动销区间'] == '5-10天') {
                        $insert_history_data[] = $res_all_new[$key];
                    }
                }
            }

            // echo '<pre>';
            // print_r($res_all_new);
            // dump($insert_history_data);
            // die;

            // 预计skc数
            $yujiSkc = count($res_all_new);

            // die;
            $this->db_easyA->startTrans();
            $insert_history = $this->db_easyA->table('cwl_budongxiao_history_sys')->insertAll($insert_history_data);
            if ($insert_history) {
                $this->db_easyA->commit();
            } else {
                $this->db_easyA->rollback();
            }

            $res_end['商品负责人'] = !empty($res_all_new[0]['商品负责人']) ? $res_all_new[0]['商品负责人'] : '';
            $res_end['省份'] = !empty($res_all_new[0]['省份']) ? $res_all_new[0]['省份'] : 0;
            $res_end['云仓简称'] = !empty($res_all_new[0]['云仓']) ? $res_all_new[0]['云仓'] : 0;
            $res_end['店铺简称'] = !empty($res_all_new[0]['店铺名称']) ? $res_all_new[0]['店铺名称'] : 0;
            $res_end['经营性质'] = !empty($res_all_new[0]['经营模式']) ? $res_all_new[0]['经营模式'] : 0;
            $res_end['季节归集'] = $this->params['季节归集'];
            $res_end['预计SKC数'] = $yujiSkc;
            $res_end['5-10天'] = $day5_10;
            $res_end['10-15天'] = $day10_15;
            $res_end['15-20天'] = $day15_20;
            $res_end['20-30天'] = $day20_30;
            $res_end['30天以上'] = $day30;
            $res_end['考核标准'] = $this->params['考核区间'] ? $this->params['考核区间'] : '30天以上';
            $res_end['考核标准占比'] = round($this->zeroHandle($res_end[$res_end['考核标准']], $res_end['预计SKC数'])  * 100, 2);
            $res_end['考核结果'] = $res_end['考核标准占比'] >= $this->params['合格率'] ? '不合格' : '合格';
            $res_end['合格率'] = $res_end['【考核标准占比'] >= $this->params['合格率'] ? "<span style='color: red;'>不合格</span>" : '';
            $res_end['合格率2'] = $this->params['合格率'];
            $res_end['需要调整SKC数'] = $res_end['考核标准占比'] >= $this->params['合格率'] ? round((  $res_end['考核标准占比'] - $this->params['合格率'])/100  * $res_end['预计SKC数'], 0) : '';
            $res_end['create_time'] = $this->create_time;
            $res_end['rand_code'] = $this->rand_code;

            // 统计插入
            $addPeople = CwlBudongxiaoStatisticsSys::addStatic($res_end);

            return $res_end;
        } else {
            return false;
        }

    }

    // 判断云仓不动销状态，齐码数，库存
    public function checkQiMa($data) {
        // 设置缓存 1小时
        if (!cache('static_qima_sys')) {
            $static_qima = $this->getTypeQiMa('not in ("下装","内搭","外套","鞋履")');
            cache('static_qima_sys', $static_qima, 3600);
        } else {
            $static_qima = cache('static_qima_sys');
        }
        
        // 结果
        $res = '';
        // 齐码率
        $qimalv = $static_qima[$data['中类']];
        // 改成或了 要么齐码 要么 大余等于 100
        if ($data['齐码情况'] >= $qimalv || $data['可用库存Quantity'] >= 100) {
            $res = ''; // 店铺合格
        } else {
            $res = $data['不动销区间']; // 店铺不合格
        }
        return $res;
    }

    // 单店不动销 详情
    public function single_statistics($rand_code) {
        $rand_code = input('rand_code');
        if (checkAdmin()) {
            $sql = "    
                SELECT
                    *,
                    @counter := @counter + 1 AS 排名 
                FROM
                    (
                    SELECT
                        a.商品负责人,
                        COUNT( 'a.店铺简称' ) AS 总家数,
                        b.合格家数,
                        IFNULL( c.不合格家数, 0 ) AS 不合格家数,
                        IFNULL( b.合格家数 / COUNT( 'a.店铺简称' ), 0 ) AS 合格率,
                        IFNULL( d.直营总家数, 0 ) AS 直营总家数, 
                        IFNULL( e.`直营合格家数`, 0 ) AS 直营合格家数,
                        IFNULL( f.`直营不合格家数`, 0 ) 直营不合格家数,
                        IFNULL( e.`直营合格家数` / d.直营总家数, 0 ) AS 直营合格率,
                        IFNULL( g.`加盟总家数`, 0 ) AS 加盟总家数,
                        IFNULL( h.`加盟合格家数`, 0 ) AS 加盟合格家数,
                        IFNULL( i.`加盟不合格家数`, 0 ) AS 加盟不合格家数,
                        IFNULL( h.`加盟合格家数` / g.`加盟总家数`, 0 ) AS 加盟合格率 
                    FROM
                        cwl_budongxiao_statistics_sys a
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 合格家数 FROM cwl_budongxiao_statistics_sys WHERE 考核结果 = '合格' AND rand_code = '{$rand_code}'  GROUP BY 商品负责人 ) AS b ON a.商品负责人 = b.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS c ON a.商品负责人 = c.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营总家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS d ON a.商品负责人 = d.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS e ON a.商品负责人 = e.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS f ON a.商品负责人 = f.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟总家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS g ON `a`.`商品负责人` = g.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS h ON a.商品负责人 = h.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS i ON a.商品负责人 = i.商品负责人 
                    WHERE a.rand_code = '{$rand_code}'    
                    GROUP BY
                        a.商品负责人 
                    ) AS aa 
                ORDER BY
                    aa.合格率 DESC 
            ";
        } else {
            $name = session('admin.name');
            $sql = "    
                SELECT
                    *,
                    @counter := @counter + 1 AS 排名 
                FROM
                    (
                    SELECT
                        a.商品负责人,
                        COUNT( 'a.店铺简称' ) AS 总家数,
                        b.合格家数,
                        IFNULL( c.不合格家数, 0 ) AS 不合格家数,
                        IFNULL( b.合格家数 / COUNT( 'a.店铺简称' ), 0 ) AS 合格率,
                        IFNULL( d.直营总家数, 0 ) AS 直营总家数, 
                        IFNULL( e.`直营合格家数`, 0 ) AS 直营合格家数,
                        IFNULL( f.`直营不合格家数`, 0 ) 直营不合格家数,
                        IFNULL( e.`直营合格家数` / d.直营总家数, 0 ) AS 直营合格率,
                        IFNULL( g.`加盟总家数`, 0 ) AS 加盟总家数,
                        IFNULL( h.`加盟合格家数`, 0 ) AS 加盟合格家数,
                        IFNULL( i.`加盟不合格家数`, 0 ) AS 加盟不合格家数,
                        IFNULL( h.`加盟合格家数` / g.`加盟总家数`, 0 ) AS 加盟合格率 
                    FROM
                        cwl_budongxiao_statistics_sys a
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 合格家数 FROM cwl_budongxiao_statistics_sys WHERE 考核结果 = '合格' AND rand_code = '{$rand_code}'  GROUP BY 商品负责人 ) AS b ON a.商品负责人 = b.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS c ON a.商品负责人 = c.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营总家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS d ON a.商品负责人 = d.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS e ON a.商品负责人 = e.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '直营' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS f ON a.商品负责人 = f.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟总家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS g ON `a`.`商品负责人` = g.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS h ON a.商品负责人 = h.商品负责人
                        LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟不合格家数 FROM cwl_budongxiao_statistics_sys WHERE 经营性质 = '加盟' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS i ON a.商品负责人 = i.商品负责人 
                    WHERE a.rand_code = '{$rand_code}' 
                        AND a.商品负责人 = '{$name}'
                    GROUP BY
                        a.商品负责人 
                    ) AS aa 
                ORDER BY
                    aa.合格率 DESC 
            ";
        }

        Db::connect('mysql')->query('SET @counter = 0;');
        $res = Db::connect('mysql')->query($sql);
        // dump($res);die;            
        // return $res;
        // return $res;
        return json(["code" => "0", "msg" => "",  "data" => $res]);
    }

    // 齐码默认值
    public function getTypeQiMa($exp = '') {
        $typeQima = $this->db_easyA->table('cwl_budongxiao_qima')
        // ->whereIn('类别', '下装,内搭,外套,鞋履,松紧长裤,松紧短裤')
        ->where('类别', 'exp', new Raw($exp))
        ->select()->toArray();
        // echo $this->db_easyA->getLastSql();die;
        // $type = [
        //     '休闲长裤' => 6,
        //     '松紧短裤' => 6,
        //     '松紧长裤' => 4,
        //     '牛仔长裤' => 6,
        //     '牛仔短裤' => 6,
        //     '西裤' => 6,
        //     '休闲短衬' => 4,
        //     '休闲长衬' => 4,
        //     '卫衣' => 4,
        //     '正统短衬' => 4,
        //     '正统长衬' => 4,
        //     '短T' => 4,
        //     '针织衫' => 4,
        //     '长T' => 4,
        //     '单西' => 4,
        //     '夹克' => 4,
        //     '套装' => 4,
        //     '套西' => 4,
        //     '套西裤' => 4,
        //     '牛仔衣' => 4,
        //     '皮衣' => 4,
        //     '休闲鞋' => 4,
        //     '凉鞋' => 4,
        //     '正统皮鞋' => 4,
        //     '运动鞋' => 4,
        // ];

        
        // 二维转一维
        $typeQima = $this->arr2to1($typeQima, '类别', '尺码数');
        return $typeQima;
    }

    // 0除以任何数都得0
    public function zeroHandle($num1, $num2) {
        if ($num1 == 0 || $num2 == 0) {
            return 0;
        } else {
            $res = $num1 / $num2;
            // $res = sprintf("%.3f", $res);
            // $res = $this->precision_restore($num1, $num2, '除法');
            return $res;
        }
    }

    // time1开始时间 time2结束时间 $this->diffDay('2023-03-23', '2023-04-12');
    public function diffDay($time1, $time2) {
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);
        $diff_seconds = $time2 - $time1;
        $diff_days = $diff_seconds / 86400;

        // echo($diff_days . "天");
        return $diff_days;
    }

    function rand_code($randLength=6,$chars="0123456789"){
        $randStr = '';
        $strLen = strlen($chars);
        // 循环输出没一个随机字符
        for($i=0;$i<$randLength;$i++){
            $randStr .= $chars[rand(0,$strLen-1)];
        }
        // tokenvalue=随机字符串+时间戳
        $tokenvalue = $randStr;
        return $tokenvalue;
    }

    public function history_map() {
        $map = input();

        // dump($map);
        // $map['type'] = 2;
        // $map['create_time'] = '2023-04-14 11:35:52';
        // if ($map['type'] == 1) {
        if ($map['type'] == 1) {
            $find_map = $this->db_easyA->table('cwl_budongxiao_history_map')->field('map')->where(['rand_code' => $map['rand_code']])->find();
        } elseif ($map['type'] == 2) {
            $find_map = $this->db_easyA->table('cwl_budongxiao_history_map')->field('map')->where(['create_time' => $map['create_time']])->find();
        }

        $find_map = json_decode($find_map['map'], true);
        $str = '';
        foreach ($find_map as $key => $val) {
            $str .= $key . ':' . $val . ' | ';
        }
        // return json(['str' => str]);
        return json(['str' => $str]);
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $qima_xiazhuang = $this->db_easyA->table('cwl_budongxiao_qima')->field('id')->where('类别', '=', '下装')->find();
            $qima_neida = $this->db_easyA->table('cwl_budongxiao_qima')->field('id')->where('类别', '=', '内搭')->find();
            $qima_waitao = $this->db_easyA->table('cwl_budongxiao_qima')->field('id')->where('类别', '=', '外套')->find();
            $qima_xielv = $this->db_easyA->table('cwl_budongxiao_qima')->field('id')->where('类别', '=', '鞋履')->find();
            $params = input();
            $qimaArr = [];

            $qimaArr = [];
            $qimaArr['aid'] = session('admin.id');
            $qimaArr['aname'] = session('admin.name');
            $qimaArr['update_time'] = date('Y-m-d H:i:s');
            // 删除空参数
            foreach ($params as $key => $val) {
                // if (empty($val)) {
                //     unset($params[$key]);
                // }

                // 齐码处理
                $qimaArr['齐码数'] = $val;
                switch ($key) {
                    case '下装':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '下装')->update($qimaArr);
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('pid', '=', $qima_xiazhuang['id'])
                        // ->whereNotIn('类别', '休闲短裤,休闲长裤')
                        ->update($qimaArr);
                        break;
                    case '内搭':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '内搭')->update($qimaArr);
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('pid', '=', $qima_neida['id'])->update($qimaArr);
                        break;
                    case '外套':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '外套')->update($qimaArr);
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('pid', '=', $qima_waitao['id'])->update($qimaArr);
                        break; 
                    case '鞋履':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '鞋履')->update($qimaArr);
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('pid', '=', $qima_xielv['id'])->update($qimaArr);
                        break;
                    case '松紧长裤':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '松紧长裤')->update($qimaArr); 
                        break;
                    case '松紧短裤':
                        $this->db_easyA->table('cwl_budongxiao_qima')->where('类别', '=', '松紧短裤')->update($qimaArr);     
                        break;    
                    default:
                        break;                                                                               
                }
            }

            $this->db_easyA->table('cwl_budongxiao_config')->where('id=1')->strict(false)->update($params);     

            // 保存map，清空缓存
            cache('static_qima_sys', null);
            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    public function saveMap2() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();

            $update_config = $this->db_easyA->table('cwl_budongxiao_config')->where(['id' => 1])->update([
                '上柜率' => $params['上柜率'],
                '省份售罄' => $params['省份售罄'],
                '排名率' => $params['排名率'],
            ]);

            return json(['status' => 1, 'msg' => '操作成功']);
            // 保存map，清空缓存
            // cache('static_qima', null);
            
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    public function test() {
        // 中山三店 乐从一店 上市天数不足30
        $map = [
            // '省份' => '广东省',
            '商品负责人' => '任文秀',
            '季节归集' => '夏季',
            '考核区间' => '30天以上',
            // '店铺名称' => '巴马一店',
            // '上市时间' => '2023-04-01',
            // '中类' => '长T',
            // '不考核门店' => '万年一店,万年二店',
            '上市天数' => 1,
            'limit' => 10000,
        ];
        // dump($map);
        $this->params = $map;
        // dump($storeArr);

        // echo $this->diffDay('2023-03-23', '2023-04-12');

        $data = [];
        $storeArr = SpWwBudongxiaoDetail::getStore($this->params);

        // foreach($storeArr as $key => $val) {
        //     $res = $this->store($val['店铺名称']);
        //     if ($res) {
        //         $data[] = $res;
        //     }
            
        // }

        // dump($data);

        $res = $this->store('云梦一店');
        // die;
        // $this->db_easyA->table('cwl_budongxiao_history_map')->insert([
        //     'rand_code' => $this->rand_code,
        //     'create_time' => $this->create_time,
        //     'map' => json_encode($this->params),
        // ]);
    }

    public function test2() {
        $map = [
            // '省份' => '广东省',
            '商品负责人' => '于燕华',
            '季节归集' => '春季',
            '考核区间' => '30天以上',
            // '店铺名称' => '巴马一店',
            // '上市时间' => '2023-04-01',
            // '中类' => '长T',
            '不考核门店' => '万年一店,万年二店',
            '上市天数' => 30,
            'limit' => 10000,
        ];
        $hello = explode(',',$map['不考核门店']);
        echo $str = $this->arrToStr($hello);
        dump($hello);
    }

    public function test3() {
        $auth = checkAdmin();
        dump($auth);
        dump(session('admin.id'));
    }

    public function arrToStr($arr) {
        $str = '';
        $len = count($arr);
        foreach ($arr as $key => $val) {
            if ($key < $len -1 ) {
                $str .= "'{$val}'" . ",";
            } else {
                $str .= "'{$val}'";
            }
        }
        return $str;
    }

    // 更新不动销齐码数据
    public function update_db_quma() {
        // $select_qima =  $this->db_bi->query("
        //     SELECT 大类 as 类别 FROM `sp_ww_budongxiao_detail` where 大类<>'配饰' GROUP BY 大类
        // ");

        // $insert_all_qima = $this->db_easyA->table('cwl_budongxiao_qima')->insertAll($select_qima);
        // dump($insert_all_qima);
        $select_qima = $this->db_easyA->table('cwl_budongxiao_qima')
            ->where([
                ['pid', '=', 0],
            ])
            ->field("id as pid, 类别")
            ->select()->toArray();
        
        dump($select_qima);

        foreach($select_qima as $key => $val) {
            $select_qima_2 = $this->db_bi->table('sp_ww_budongxiao_detail')->where([
                ['大类', '=', $val['类别']]
            ])->field('中类')
            ->group('大类,中类')
            ->select()->toArray();
            dump($select_qima_2);
            
        }
        
    }
    
}
