<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatistics;
use think\db\Raw;

// class Budongxiao extends BaseController
class Budongxiao
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->rand_code = $this->rand_code(10);
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    public function index() {
        if (request()->isAjax()) {
            // 筛选条件

            $params = input();
            // 删除空参数
            foreach ($params as $key => $val) {
                if (empty($val)) {
                    unset($params[$key]);
                }
            }
            $this->params = $params;

            $data = [];
            $storeArr = SpWwBudongxiaoDetail::getStore($this->params);

            foreach($storeArr as $key => $val) {
                $res = $this->store($val['店铺名称']);
                if ($res) {
                    $data[] = $res;
                }
            }
            // $data[] = $this->store();

            // 插入map记录
            if ($data) {
                $this->db_easyA->table('cwl_budongxiao_history_map')->insert([
                    'rand_code' => $this->rand_code,
                    'create_time' => $this->create_time,
                    'map' => json_encode($this->params),
                ]);
            }
            return json(["code" => "0", "msg" => "", "count" => count($data), "data" => $data, 'rand_code' => $this->rand_code, 'create_time' => $this->create_time]);
        } else {
            // 齐码
            $typeQima = $this->getTypeQiMa();
            // 省份
            $province = SpWwBudongxiaoDetail::getProvince();
            // 商品负责人
            $people = SpWwBudongxiaoDetail::getPeople([
                ['商品负责人', 'exp', new Raw('IS NOT NULL')]
            ]);
            $storeArr = SpWwBudongxiaoDetail::getStore([]);
            return View('index',[
                'typeQima' => $typeQima,
                'province' => $province,
                'people' => $people,
                'storeArr' => json($storeArr),
            ]);
        }
    }

    // 单店不动销
    public function store($store) {
        // 1. 预计库存大于1SKC   改规则，店铺库存数量>1并且上市天数满足的货号算一个skc
        // $yujiSkc = SpCustomerStockSkc::getSkc($store, $this->params);

        // =IF(L89<30,0,IF(N89="","30天以上",IF(S89="","20-30天",IF(R89="","15-20天",IF(Q89="","10-15天",IF(P89="","5-10天",))))))
        $map = [
            ['店铺名称', '=', $store],
            ['季节归集', '=', $this->params['季节归集']],
            ['大类' , '<>', '配饰'],
            ['店铺库存数量' , '>', 1],
        ];

        // 没有上市日期就直接使用上市天数
        if (empty($this->params['上市时间'])) {
            $map[] = ['上市天数' , '>=', $this->params['上市天数']];
            $map[] = ['商品负责人' , 'exp', new Raw('IS NOT NULL')];
            $res_all = SpWwBudongxiaoDetail::joinYuncang_all($map);
            $res_all_new = [];
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
        // print_r($res_all_new);
        // die;

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

            // dump($res_all_new);
            // die;

            $this->db_easyA->startTrans();
            $insert_history = $this->db_easyA->table('cwl_budongxiao_history')->insertAll($insert_history_data);
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
            $res_end['【考核标准】键'] = $this->params['考核区间'] ? $this->params['考核区间'] : '30天以上';
            $res_end['【考核标准】值'] = round($this->zeroHandle($res_end[$res_end['【考核标准】键']], $res_end['预计SKC数'])  * 100, 2);
            $res_end['考核结果'] = $res_end['【考核标准】值'] >= 10 ? '不合格' : '合格';
            $res_end['合格率10%以下'] = $res_end['【考核标准】值'] >= 10 ? "<span style='color: red;'>不及格</span>" : '';
            $res_end['需要调整SKC数'] = $res_end['【考核标准】值'] >= 10 ? round((  $res_end['【考核标准】值'] - 10)/100  * $res_end['预计SKC数'], 0) : '';
            $res_end['create_time'] = $this->create_time;
            $res_end['rand_code'] = $this->rand_code;

            // 统计插入
            $addPeople = CwlBudongxiaoStatistics::addStatic($res_end);

            return $res_end;
        } else {
            return false;
        }

    }

    // 判断云仓不动销状态，齐码数，库存
    public function checkQiMa($data) {
        // 齐码率数组
        if (!empty($this->params['isAjax'])) {
            // dump($this->params);
            $typeQiMa = $this->params;

        } else {
            $typeQiMa = $this->getTypeQiMa();
        }

        // 结果
        $res = '';
        // 齐码率
        $qimalu = $typeQiMa[$data['中类']];
        if ($data['齐码情况'] >= $qimalu && $data['可用库存Quantity'] >= 100) {
            $res = ''; // 店铺合格
        } else {
            $res = $data['不动销区间']; // 店铺不合格
        }
        return $res;
    }

    // 单店不动销 详情
    public function single_statistics() {
        // $create_time = '2023-04-18 19:46:49';
        // $create_time = $this->create_time;
        $rand_code = input('rand_code');

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
                    cwl_budongxiao_statistics a
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 合格家数 FROM cwl_budongxiao_statistics WHERE 考核结果 = '合格' AND rand_code = '{$rand_code}'  GROUP BY 商品负责人 ) AS b ON a.商品负责人 = b.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 不合格家数 FROM cwl_budongxiao_statistics WHERE 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS c ON a.商品负责人 = c.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营总家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '直营' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS d ON a.商品负责人 = d.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营合格家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '直营' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS e ON a.商品负责人 = e.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 直营不合格家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '直营' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS f ON a.商品负责人 = f.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟总家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '加盟' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS g ON `a`.`商品负责人` = g.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟合格家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '加盟' AND 考核结果 = '合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS h ON a.商品负责人 = h.商品负责人
                    LEFT JOIN ( SELECT 商品负责人, count(*) AS 加盟不合格家数 FROM cwl_budongxiao_statistics WHERE 经营性质 = '加盟' AND 考核结果 = '不合格' AND rand_code = '{$rand_code}' GROUP BY 商品负责人 ) AS i ON a.商品负责人 = i.商品负责人 
                WHERE a.rand_code = '{$rand_code}'    
                GROUP BY
                    a.商品负责人 
                ) AS aa 
            ORDER BY
                aa.合格率 DESC 
        ";

        Db::connect('mysql')->query('SET @counter = 0;');
        $res = Db::connect('mysql')->query($sql);
        // dump($res);die;            
        // return $res;
        return json(["code" => "0", "msg" => "",  "data" => $res]);
    }

    // 齐码默认值
    public function getTypeQiMa() {
        $map = [
            
        ];
        $res = SpWwBudongxiaoDetail::getTypeQiMa([
            // ['季节归集', '=', '春季'],
            ['大类', '<>', '配饰'],
        ]);
        // echo '<pre>';
        // print_r($res);
        $type = [
            // '下装' => [
                '休闲长裤' => 6,
                '松紧短裤' => 6,
                '松紧长裤' => 5,
                '牛仔长裤' => 6,
                '牛仔短裤' => 6,
                '西裤' => 6,
            // ],
            // '内搭' => [
                '休闲短衬' => 4,
                '休闲长衬' => 4,
                '卫衣' => 4,
                '正统短衬' => 4,
                '正统长衬' => 4,
                '短T' => 4,
                '针织衫' => 4,
                '长T' => 4,
            // ],
            // '外套' => [
                '单西' => 4,
                '夹克' => 4,
                '套装' => 4,
                '套西' => 4,
                '套西裤' => 4,
                '牛仔衣' => 4,
                '皮衣' => 4,
            // ],
            // '鞋履' => [
                '休闲鞋' => 4,
                '凉鞋' => 4,
                '正统皮鞋' => 4,
            // ],
        ];
        return $type;
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

    // 单店不动销 历史记录展示
    public function history() {
        if (request()->isAjax()) {
        // if (1) {
            $map_input = input();

            // $map_input['page'] = 1;
            // $map_input['limit'] = 100;
            // $map_input['create_time'] = '2023-04-14 16:31:54';

            $select_history = $this->db_easyA->table('cwl_budongxiao_history')->where([
                'create_time' => $map_input['create_time']
            ])->page($map_input['page'], $map_input['limit'])->select()->toArray();

            $total = $this->db_easyA->table('cwl_budongxiao_history')->where([
                'create_time' => $map_input['create_time']
            ])->count();

            return json(["code" => "0", "msg" => "", "count" => $total, "data" => $select_history]);
        } else {
            $select_map = $this->db_easyA->table('cwl_budongxiao_history_map')->order('id DESC')->select()->toArray();

            return View('history', [
                'select_map' => $select_map,
            ]);
        }
    }

    // 单店不动销 筛选项
    public function history_map() {
        $map = input();
        // dump($map);
        // $map['type'] = 2;
        // $map['create_time'] = '2023-04-14 11:35:52';
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
        return json(['str' => $str]);
    }

    // 区域动销 历史记录展示
    // 区域动销排名：商品负责人+省份+货号的排名/负责人+省份+中类的排名
    public function history_area() {
        if (request()->isAjax()) {
        // if (1) {
            $map_input = input();
            // 删除空参数
            foreach ($map_input as $key => $val) {
                if (empty($val)) {
                    unset($map_input[$key]);
                }
            }

            $map1 = " a.create_time='{$map_input['create_time']}' ";
            if (!empty($map_input['上柜率'])) {
                $map2 = " AND a.上柜率>='{$map_input['上柜率']}' ";
            } else {
                $map2 = " ";
            }
            if (!empty($map_input['省份售罄'])) {
                $map3 = " AND a.省份售罄>='{$map_input['省份售罄']}' ";
            } else {
                $map3 = " ";
            }
            if (!empty($map_input['排名率'])) {
                $map4 = " having 排名率 >={$map_input['排名率']} ";
            } else {
                $map4 = " ";
            }

            $pageParams1 = ($map_input['page'] - 1) * $map_input['limit'];
            $pageParams2 =  $map_input['limit'];

            $sql = "
                SELECT
                    a.*,b.`相同货号数`, a.品类排名 / b.相同货号数 * 100 as 排名率
                FROM
                    `cwl_budongxiao_history` AS a
                    LEFT JOIN (
                    SELECT
                        商品负责人,省份,货号,品类排名, 
                        count(*) AS 相同货号数
                    FROM
                        cwl_budongxiao_history 
                    GROUP BY
                    商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
                    AND a.省份 = b.省份 
                    AND a.货号 = b.货号 
                WHERE 
                    " . $map1 . $map2 . $map3 . $map4 . "
                ORDER BY
                    a.商品负责人 ASC
                limit {$pageParams1}, {$pageParams2}    
            ";    

            $sql2 = "
                SELECT
                    count(*) as tatalCount
                FROM
                    `cwl_budongxiao_history` AS a
                    LEFT JOIN (
                    SELECT
                        商品负责人,省份,货号,品类排名, 
                        count(*) AS 相同货号数
                    FROM
                        cwl_budongxiao_history 
                    GROUP BY
                    商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
                    AND a.省份 = b.省份 
                    AND a.货号 = b.货号 
                WHERE 
                    " . $map1 . $map2 . $map3 . $map4 . "
                ORDER BY
                    a.商品负责人 ASC
            "; 
            

            $select_history_area = Db::connect('mysql')->query($sql);
            $count = Db::connect('mysql')->query($sql2); 

            // print_r( $count);
            // die;
            return json(["code" => "0", "msg" => '', "count" => $count[0]['tatalCount'], "data" => $select_history_area]);
        } else {
            $select_map = $this->db_easyA->table('cwl_budongxiao_history_map')->order('id DESC')->select()->toArray();

            return View('history_area', [
                'select_map' => $select_map,
            ]);
        }
    }

    public function test() {
        // 中山三店 乐从一店 上市天数不足30
        $map = [
            // '省份' => '广东省',
            // '商品负责人' => '曹太阳',
            '季节归集' => '春季',
            '考核区间' => '30天以上',
            // '店铺名称' => '巴马一店',
            // '上市时间' => '2023-04-01',
            // '中类' => '长T',
            '上市天数' => 30,
            'limit' => 1000,
        ];
        // dump($map);
        $this->params = $map;
        // dump($storeArr);

        // die;

        // $res = $this->store('中山三店');
        // $res = $this->store('中山三店');
        // dump($res);

        // echo $this->diffDay('2023-03-23', '2023-04-12');

        $data = [];
        $storeArr = SpWwBudongxiaoDetail::getStore($this->params);
        // $people = SpWwBudongxiaoDetail::getPeople(); 
        // $cwlBudongxiaoStatistics = new CwlBudongxiaoStatistics();
        // $addPeople = $cwlBudongxiaoStatistics->saveAll($people);


        // $cwlBudongxiaoStatistics = new CwlBudongxiaoStatistics();
        // $addPeople = $cwlBudongxiaoStatistics->allowField(['商品负责人'])->saveAll($people);
        // $addPeople = $cwlBudongxiaoStatistics->saveAll($people);
        // echo $cwlBudongxiaoStatistics->getLastSql();

        // $addPeople = CwlBudongxiaoStatistics::addPeople($people);

        // $addPeople = $this->db_easyA->table('cwl_budongxiao_statistics')->insertAll($people);
        // dump($people);
        // dump($addPeople);
        // die;
        foreach($storeArr as $key => $val) {
            $res = $this->store($val['店铺名称']);
            if ($res) {
                $data[] = $res;
            }
            die;
        }

        dump($data);
        die;
        $this->db_easyA->table('cwl_budongxiao_history_map')->insert([
            'rand_code' => $this->rand_code,
            'create_time' => $this->create_time,
            'map' => json_encode($this->params),
        ]);
    }

    public function test2() {
        $sql = "
        SELECT
        count(*) as totalA
    FROM
        `cwl_budongxiao_history` AS a
        LEFT JOIN (
        SELECT
            商品负责人,省份,货号,品类排名, 
            count(*) AS 相同货号数
        FROM
            cwl_budongxiao_history 
        GROUP BY
        商品负责人,省份,货号) AS b ON a.商品负责人 = b.商品负责人 
        AND a.省份 = b.省份 
        AND a.货号 = b.货号 

    ORDER BY
        a.商品负责人 ASC

        ";
        $count = Db::connect('mysql')->query($sql); 

        dump($count[0]['totalA']);
    }
}
