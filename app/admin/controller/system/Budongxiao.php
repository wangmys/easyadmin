<?php
namespace app\admin\controller\system;

use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use app\common\service\WeatherService;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use think\cache\driver\Redis;
use app\admin\model\weather\Region;
use app\admin\model\bi\SpWwBudongxiaoDetail;
use app\admin\model\bi\SpXwBudongxiaoYuncangkeyong;

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

    // protected $request;

    // public function __construct(Request $request)
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->rand_code = $this->rand_code(4);
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
            return json(["code" => "0", "msg" => "", "count" => count($data), "data" => $data]);
        } else {
            // 齐码
            $typeQima = $this->getTypeQiMa();
            // 省份
            $province = SpWwBudongxiaoDetail::getProvince();
            // 商品负责人
            $people = SpWwBudongxiaoDetail::getPeople();
            return View('index',[
                'typeQima' => $typeQima,
                'province' => $province,
                'people' => $people,
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
            // ['中类' , '=', $this->params['中类']],

            ['店铺库存数量' , '>=', 1],
        ];

        // 没有上市日期就直接使用上市天数
        if (empty($this->params['上市时间'])) {
            $map[] = ['上市天数' , '>=', $this->params['上市天数']];
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

            foreach ($res_all_new as $key => $val) {
                // 30天以上
                if (empty($val['累销量'])) {
                    $res_all_new[$key]['不动销区间'] = '30天以上';
                    $day30 ++;
                // 20-30天
                } elseif (!empty($val['累销量']) && empty($val['二十天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '20-30天';
                    $day20_30 ++;
                // 15-20天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && empty($val['十五天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '15-20天';
                    $day15_20 ++;
                // 10-15天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && !empty($val['十五天销量']) && empty($val['十天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '10-15天';
                    $day10_15 ++;
                // 5-10天
                } elseif (!empty($val['累销量']) && !empty($val['二十天销量']) && !empty($val['十五天销量']) && !empty($val['十天销量']) && !empty($val['十天销量']) && empty($val['五天销量'])) {
                    $res_all_new[$key]['不动销区间'] = '5-10天';
                    $day5_10 ++;
                } else {
                    $res_all_new[$key]['不动销区间'] = '';
                }

                $res_all_new[$key]['不动销区间修订'] = $this->checkQiMa($res_all_new[$key]);
                $res_all_new[$key]['考核标准'] = input('param.khkey') ? input('param.khkey') : '30天以上';
                $res_all_new[$key]['create_time'] = $this->create_time;
                $res_all_new[$key]['rand_code'] = $this->rand_code;

                // die;
            }
            // 预计skc数
            $yujiSkc = count($res_all_new);

            // dump($res_all_new);
            $this->db_easyA->startTrans();
            $insert_history = $this->db_easyA->table('cwl_budongxiao_history')->insertAll($res_all_new);
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
            $res_end['预计SKC数'] = $yujiSkc;
            $res_end['5-10天'] = $day5_10;
            $res_end['10-15天'] = $day10_15;
            $res_end['15-20天'] = $day15_20;
            $res_end['20-30天'] = $day20_30;
            $res_end['30天以上'] = $day30;
            $res_end['【考核标准】键'] = input('param.khkey') ? input('param.khkey') : '30天以上';
            $res_end['【考核标准】值'] = round($this->zeroHandle($res_end[$res_end['【考核标准】键']], $res_end['预计SKC数'])  * 100, 2);
            $res_end['需要调整SKC数'] = $res_end['【考核标准】值'] >= 10 ? round((  $res_end['【考核标准】值'] - 10)/100  * $res_end['预计SKC数'], 0) : '';

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

    // 齐码默认值
    public function getTypeQiMa() {
        $type = [
            // '下装' => [
                '休闲长裤' => 6,
                '松紧短裤' => 6,
                '松紧长裤' => 6,
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

    // 历史记录展示
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

    public function test() {
        // 中山三店 乐从一店 上市天数不足30
        $map = [
            '省份' => '广东省',
            // '商品负责人' => '周奇志',
            '季节归集' => '春季',
            // '店铺名称' => '大石二店',
            // '上市时间' => '2023-04-01',
            // '中类' => '长T',
            '上市天数' => 30,
            'limit' => 10,
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
        // print_r($storeArr);
        foreach($storeArr as $key => $val) {
            $res = $this->store($val['店铺名称']);
            if ($res) {
                $data[] = $res;
            }
        }

        die;
        $this->db_easyA->table('cwl_budongxiao_history_map')->insert([
            'rand_code' => $this->rand_code,
            'create_time' => $this->create_time,
            'map' => json_encode($this->params),
        ]);
    }

    public function test2() {
        $map_input['page'] = 1;
        $map_input['limit'] = 100;
        $map_input['create_time'] = '2023-04-14 14:03:23';

        $select_history = $this->db_easyA->table('cwl_budongxiao_history')->where([
            'create_time' => $map_input['create_time']
        ])->page($map_input['page'], $map_input['limit'])->select()->toArray();

        echo $this->db_easyA->getLastSql();
        dump($select_history);

        echo $total = $this->db_easyA->table('cwl_budongxiao_history')->where([
            'create_time' => $map_input['create_time']
        ])->count();
    }

    public function test3() {
        echo 222;
        // $select_map = $this->db_easyA->table('cwl_budongxiao_history_map')->order('id DESC')->select()->toArray();
        // dump($select_map);
    }
}
