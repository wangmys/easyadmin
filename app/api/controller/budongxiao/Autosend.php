<?php
namespace app\api\controller\budongxiao;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="不动销")
 */
class Autosend extends BaseController
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


    // 自动执行不动销计算
    public function sysReshandle() {
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

        $data = [];
        $storeArr = SpWwBudongxiaoDetail::getStore($this->params);

        // 删除所有基础计算结果
        $this->db_easyA->table('cwl_budongxiao_result_sys')->where(1)->delete();
        // 删除所有详情结果
        $this->db_easyA->table('cwl_budongxiao_history_sys')->where(1)->delete();
        // 删除所有统计结果
        CwlBudongxiaoStatisticsSys::delStatic();

        foreach($storeArr as $key => $val) {
            $res = $this->store($val['店铺名称']);
            if ($res) {
                $data[] = $res;
            }
        }
        // $data[] = $this->store();

        // 插入map记录
        if ($data) {
            // 统计详情
            $res1 = $this->db_easyA->table('cwl_budongxiao_history_map_sys')->insert([
                'create_time' => $this->create_time,
                'rand_code' => $this->rand_code,
                'map' => json_encode($this->params),
            ]);

            // 基础结果 
            $this->db_easyA->table('cwl_budongxiao_result_sys')->strict(false)->insertAll($data);
            echo 1;
        }

    }


    // 二维数组转一维数组
    public function arr2to1($arr, $key, $val) {
        $newArr = [];
        foreach ($arr as $key2 => $val2) {
            $newArr[$val2['类别']] = $val2['齐码数'];
        }

        return $newArr;
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
            $res_end['【考核标准】键'] = $this->params['考核区间'] ? $this->params['考核区间'] : '30天以上';
            $res_end['【考核标准】值'] = round($this->zeroHandle($res_end[$res_end['【考核标准】键']], $res_end['预计SKC数'])  * 100, 2);
            // $res_end['考核结果'] = $res_end['【考核标准】值'] >= 10 ? '不合格' : '合格';
            $res_end['考核结果'] = $res_end['【考核标准】值'] >= $this->params['合格率'] ? '不合格' : '合格';
            // $res_end['合格率'] = $res_end['【考核标准】值'] >= 10 ? "<span style='color: red;'>不及格</span>" : '';
            $res_end['合格率'] = $res_end['【考核标准】值'] >= $this->params['合格率'] ? "<span style='color: red;'>不及格</span>" : '';
            $res_end['需要调整SKC数'] = $res_end['【考核标准】值'] >= 10 ? round((  $res_end['【考核标准】值'] - 10)/100  * $res_end['预计SKC数'], 0) : '';
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
}
