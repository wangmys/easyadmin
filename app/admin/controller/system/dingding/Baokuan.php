<?php
namespace app\admin\controller\system\dingding;

use AlibabaCloud\SDK\Dingtalk\Vworkflow_1_0\Models\QuerySchemaByProcessCodeResponseBody\result\schemaContent\items\props\push;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\admin\controller\system\dingding\DingTalk;

/**
 * Class Baokuan
 * @package app\admin\controller\system\dingding
 * @ControllerAnnotation(title="身份温区爆款推送")
 */
class Baokuan extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';

    // 用户信息
    protected $authInfo = '';

    public $debug = false;
    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');

        $this->authInfo = session('admin');
    }

    public function list() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            if (!empty($input['更新日期'])) {
                $map1 = " AND `更新日期` = '{$input['更新日期']}'";                
            } else {
                $today = date('Y-m-d');
                $map1 = " AND `更新日期` = '{$today}'";            
            }
            $sql = "
                SELECT 
                   *
                FROM 
                    dd_customer_push_baokuan
                WHERE 1
                    {$map1}
                order by 已读 ASC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_customer_push_baokuan
                WHERE 1
                    {$map1}
            ";
            $count = $this->db_easyA->query($sql2);

            $reads = $this->db_easyA->table('dd_customer_push_baokuan')->where([
                ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
                ['已读', '=', 'Y'],
            ])->count('*');

            $noReads = $this->db_easyA->table('dd_customer_push_baokuan')->where([
                ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
            ])->count('*');
            $noReads = $noReads - $reads;
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'readsData' => ['reads' => $reads, 'noReads' => $noReads]]);
        } else {
            $time = time();
            // if ($time < strtotime(date('Y-m-d 20:30:00'))) {
            //     // echo '显示昨天';
            //     $today = date('Y-m-d', strtotime('-1 day', $time));
            // } else {
            //     // echo '显示今天';
            //     $today = date('Y-m-d');
            // }
            $today = date('Y-m-d');
            return View('list', [
                'today' => $today,
            ]);
        }        
    }

    public function res() {
        // $uid = '13698126';
        // echo '<pre>';
        // print_r($_SERVER);die;
        $input = input();
        
        if (empty($input['店铺名称'])) {
            die('参数有误，请勿非法访问');
        }
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . "/admin/system.dingding.Baokuan/res"; 

        if (isMobile()) {
        // if (1) {
            
            if (!empty($input['大类']) && $input['大类'] != '大类') {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['大类']);
                $map1 = " AND t.大类 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['中类']) && $input['中类'] != '中类') {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['中类']);
                $map2 = " AND t.中类 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['货号']) && $input['货号'] != '货号') {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['货号']);
                $map3 = " AND t.货号 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            if (!empty($input['季节归集']) && $input['季节归集'] != '季节') {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['季节归集']);
                $map4 = " AND t.季节归集 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            $sql_基础数据 = "
                SELECT
                    c.CustomerName,
                    t.*
                FROM
                    customer as c
                LEFT JOIN cwl_baokuan_7day as t on c.State = t.省份 and c.CustomItem36 = t.温区
                WHERE
                    c.CustomerName in ('{$input['店铺名称']}')
                    and t.use4 IS NOT NULL
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                ORDER BY t.中类, t.排名 ASC
            ";
            // die;
            $select_基础数据 = $this->db_easyA->query($sql_基础数据);
            if ($select_基础数据 ) {
                // **分组不要带货号查询，影响共计款数量**
                // $sql_二级分类分组 = "
                //     SELECT
                //         t.中类
                //     FROM
                //         customer as c
                //     LEFT JOIN cwl_baokuan_7day as t on c.State = t.省份 and c.CustomItem36 = t.温区
                //     WHERE
                //         c.CustomerName in ('{$input['店铺名称']}')
                //         and t.use4 IS NOT NULL
                //         {$map1}
                //         {$map2}
                //         {$map3}
                //         {$map4}
                //     GROUP BY t.中类
                // ";
                // $select_二级分类分组 = $this->db_easyA->query($sql_二级分类分组);

                // $二级分类数组 = [];
                // foreach ($select_二级分类分组 as $key => $val) {
                //     $二级分类数组[$val['中类']] = $val;
                // }

                // $二级分类数组
                // dump($二级分类数组);die;

                return View('res',[
                    'select' => $select_基础数据,
                    // 'uid' => $input['uid'],
                    'customerName' => $input['店铺名称'],
                    // 'categoryName2' => $二级分类数组,
                    'url' => $url
                ]);
            } else {
                // echo '参数有误，请勿非法访问[2]';  
                return View('res',[
                    'select' => $select_基础数据,
                    // 'uid' => $input['uid'],
                    'customerName' => $input['店铺名称'],
                    'categoryName2' => [],
                    'url' => $url
                ]);  
            }
        } else {
            echo '请在手机上进行访问！';  
        }
    }

    // pc 端 店铺推送列表ajax
    public function pcAjax() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            if (empty($input['uid']) || empty($input['customerName'])) {
                die('参数有误，请勿非法访问');
            }
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // 非系统管理员
            // if (! checkAdmin()) { 
            //     $aname = session('admin.name');       
            //     $aid = session('admin.id');   
            //     $mapSuper = " AND list.aid='{$aid}'";  
            // } else {
            //     $mapSuper = '';
            // }
            // if (!empty($input['更新日期'])) {
            //     $map1 = " AND `更新日期` = '{$input['更新日期']}'";                
            // } else {
            //     $today = date('Y-m-d');
            //     $map1 = " AND `更新日期` = '{$today}'";            
            // }
            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['一级分类']);
                $map1 = " AND 一级分类 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['二级分类']);
                $map2 = " AND 二级分类 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['货号']);
                $map3 = " AND 货号 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['季节归集']);
                $map4 = " AND 季节归集 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            $sql = "
                select * from dd_tiaojia_customer_temp
                where 1
                    AND uid = '{$input['uid']}'
                    AND 店铺名称 IN ('{$input['customerName']}')
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                ORDER BY `key`,二级分类,分组排名,分类
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_tiaojia_customer_temp
                WHERE 1
                    AND uid = '{$input['uid']}'
                    AND 店铺名称 IN ('{$input['customerName']}')
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } 
    }

    // 更新编辑状态
    public function statusHandle() {
        if (request()->isAjax()) {
            $input = input();
            // print_r($input); 
            
            $map1 = " AND uid='{$input['uid']}'";
            $map2 = " AND 店铺名称='{$input['customerName']}'";
            $map3 = " AND 货号='{$input['goodsNo']}'";

            $sql = "
                select `status` from dd_tiaojia_customer_temp
                where 1
                    {$map1}
                    {$map2}
                    {$map3}
            ";
            $select = $this->db_easyA->query($sql);
            // print_r($select);
            $new_status = '';
            if ($select[0]['status'] == 'Y') {
                $new_status = 'N';
            } else {
                $new_status = 'Y';
            }

            $sql_更新 = "
                update dd_tiaojia_customer_temp
                set 
                    `status` = '{$new_status}'
                where 1
                    {$map1}
                    {$map2}
                    {$map3}
            ";
            $this->db_easyA->execute($sql_更新);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        $input = input();
        // print_r($input); 
        $map1 = " AND uid='{$input['uid']}'";
        $map2 = " AND 店铺名称='{$input['customerName']}'";

        $一级分类 = $this->db_easyA->query("
            SELECT 一级分类 as name, 一级分类 as value FROM dd_tiaojia_customer_temp WHERE  一级分类 IS NOT NULL {$map1} {$map2} GROUP BY 一级分类
        ");
        $二级分类 = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM dd_tiaojia_customer_temp WHERE  二级分类 IS NOT NULL {$map1} {$map2} GROUP BY 二级分类
        ");
        $货号 = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM dd_tiaojia_customer_temp WHERE  货号 IS NOT NULL {$map1} {$map2} GROUP BY 货号
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['yjfl' => $一级分类, 'ejfl' => $二级分类,  'hh' => $货号]]);
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect_mobile() {
        $input = input();
        // print_r($input); 
        
        $find_customer = $this->db_easyA->table('customer')->field('State as 省份, CustomItem36 as 温区')->where(['customerName' => $input['customerName']])->find();
        // print_r($find_customer);
        // die;
        if (!empty($input['季节归集']) && $input['季节归集'] != '季节') {
            $map1 = " AND 季节归集 IN ('{$input['季节归集']}')";
        } else {
            $map1 = "";
        }

        if (!empty($input['大类']) && $input['大类'] != '大类') {
            $map2 = " AND 大类 IN ('{$input['大类']}')";
        } else {
            $map2 = "";
        }

        if (!empty($input['中类']) && $input['中类'] != '中类') {
            $map3 = " AND 中类 IN ('{$input['中类']}')";
        } else {
            $map3 = "";
        }

        if (!empty($input['货号']) && $input['货号'] != '货号') {
            $map4 = " AND 货号 IN ('{$input['货号']}')";
        } else {
            $map4 = "";
        }

        $季节归集 = $this->db_easyA->query("
            SELECT
                季节归集 as label,
                季节归集 as value
            FROM
                cwl_baokuan_7day
            WHERE
                省份 = '{$find_customer['省份']}' and 温区 = '{$find_customer['温区']}' and use4 is not null
    
                {$map2}
                {$map3}
                {$map4}
            GROUP BY 季节归集
        ");

        $一级分类 = $this->db_easyA->query("
            SELECT
                大类 as label,
                大类 as value
            FROM
                cwl_baokuan_7day
            WHERE
                省份 = '{$find_customer['省份']}' and 温区 = '{$find_customer['温区']}' and use4 is not null
                {$map1}

                {$map3}
                {$map4}
            GROUP BY 大类
        ");
        
        $二级分类 = $this->db_easyA->query("
            SELECT
                中类 as label,
                中类 as value
            FROM
                cwl_baokuan_7day
            WHERE
                省份 = '{$find_customer['省份']}' and 温区 = '{$find_customer['温区']}' and use4 is not null
                {$map1}
                {$map2}

                {$map4}
            GROUP BY 中类
        ");
        $货号 = $this->db_easyA->query("
            SELECT
                货号 as label,
                货号 as value
            FROM
                cwl_baokuan_7day
            WHERE
                省份 = '{$find_customer['省份']}' and 温区 = '{$find_customer['温区']}' and use4 is not null
                {$map1}
                {$map2}
                {$map3}

            GROUP BY 货号
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['yjfl' => $一级分类, 'ejfl' => $二级分类,  'hh' => $货号, 'jjgj' => $季节归集]]);
    }

    // 发送 通知
    public function sendListHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg
        // if (request()->isAjax() && $input['id'] && session('admin.name')) {
        if (1) {
            $model = new DingTalk;
    
            // 测试用
            // $input['id'] = 11;

            $date = date('Y-m-d H:i:s');

            $更新日期 = date('Y-m-d');
            // 测试推送这里修改条件
            $select_user = $this->db_easyA->query("
                select * from dd_customer_push_baokuan
                where 
                    更新日期 = '{$更新日期}'
                    AND 星期 = 推送星期
            ");

            // dump($select_user);die;

            if ($select_user) {
                // 遍历分组
                foreach ($select_user as $k1 => $v1) {
                    // dump($select_user);
                    // die;

                    $data['店铺名称'] = $v1['店铺名称'];
                    $data['userid'] = $v1['userid'];
                    $data['path'] = $v1['path'];
                    $data['url'] = $v1['url'];
                    // dump($data);die;
                    $res = json_decode($model->sendLinkMsg_baokuan($data), true);
                                    
                    if ($res) {
                        // 更新用户列表 task_id
                        $this->db_easyA->execute("
                            update dd_customer_push_baokuan
                            set 
                                task_id = '{$res['task_id']}',
                                sendtime = '{$date}'
                            where 1
                                AND mobile = '{$v1['mobile']}'
                        ");
                    }

                }

                // $this->db_easyA->execute("
                //     update dd_tiaojia_list
                //     set 
                //         sendtime = '{$date}',
                //         sendtimes = sendtimes + 1,
                //         撤回时间 = NULL
                //     where 1
                //         AND id = '{$input['id']}'
                // ");
                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 1, 'msg' => '数据异常，执行失败']);
            }
        } else {
            return json(['code' => 2, 'msg' => '请勿非法请求']);
        }       
    }

    // 拉取 已读 未读 用户主动执行
    public function getReadsHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg

        if (request()->isAjax() && $input['id'] && session('admin.name')) {
        // if (1) {
        // if (request()->isAjax()) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;

            // $input['id'] = 163;
            
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }

            if ($find_list) {
                $select_user = $this->db_easyA->table('dd_tiaojia_list_user')->where([
                    ['uid', '=', $find_list['uid']]
                ])->group('task_id')->select()->toArray();

                foreach($select_user as $key => $val) {
                    $res = json_decode($model->getsendresult($val['task_id']), true);
                    
                    // print_r($res);
                    try {
                        if ($res['errmsg'] = 'ok' && $res['send_result']) {  
                            if (count($res['send_result']['read_user_id_list']) > 0) {
                                // 已读
                                $reads = arrToStr($res['send_result']['read_user_id_list']);    
                                $this->db_easyA->execute("
                                    UPDATE 
                                        dd_tiaojia_list_user 
                                    SET 
                                        已读 = 'Y'
                                    WHERE 1
                                        AND task_id = '{$val['task_id']}'
                                        AND userid in ($reads)
                                ");
                            }
    
                            if (count($res['send_result']['unread_user_id_list']) > 0) {
                                // 未读
                                $unReads = arrToStr($res['send_result']['unread_user_id_list']);   
                                $this->db_easyA->execute("
                                    UPDATE 
                                        dd_tiaojia_list_user 
                                    SET 
                                        已读 = 'N'
                                    WHERE 1
                                        AND task_id = '{$val['task_id']}'
                                        AND userid in ($unReads)
                                ");
                            }
    
                        } else {
                            return json(['code' => 0, 'msg' => '数据异常']);
                        }
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                // 统计list展示的已读未读数
                $this->db_easyA->execute("
                    UPDATE 
                        dd_tiaojia_list as l
                    SET 
                        l.已读 = (select count(*) from dd_tiaojia_list_user where uid = '{$find_list['uid']}' and 已读='Y')
                    WHERE 1
                        AND l.id = {$find_list['id']}
                ");

                // 统计list展示的已读未读数
                $this->db_easyA->execute("
                    UPDATE 
                        dd_tiaojia_list as l
                    SET 
                        l.未读 = 总数 - ifnull(已读, 0)
                    WHERE 1
                        AND l.id = {$find_list['id']}
                ");

                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 1, 'msg' => '权限不足，只能操作自己创建的记录']);
            }
        } else {
            return json(['code' => 2, 'msg' => '请勿非法访问']);
        }      
    }

    // 撤回 全部消息
    public function recallAllHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg

        if (request()->isAjax() && $input['id'] && session('admin.name')) {
        // if (1) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;
            
            // $input['id'] = 167;
            
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }


            if ($find_list) {
                $select_user = $this->db_easyA->table('dd_tiaojia_list_user')->field('task_id')->where([
                    ['uid', '=', $find_list['uid']]
                ])->group('task_id')->select()->toArray();
                // ])->select()->toArray();

                // echo '<pre>';
                // print_r($select_user);
                $model = new DingTalk;
                foreach($select_user as $key => $val) {
                    // echo  $val['task_id'];
                    // echo '<br>';
                    $res = json_decode($model->recallMessage($val['task_id']), true);
                    // print_r($res);
                    $res1 = $this->db_easyA->table('dd_tiaojia_list_user')->where(['task_id' => $val['task_id']])->update([
                        '撤回时间' => date('Y-m-d H:i:s'),
                    ]);
                    $res2 = $this->db_easyA->table('dd_tiaojia_list')->where(['id' => $input['id']])->update([
                        '撤回时间' => date('Y-m-d H:i:s'),
                        'sendtime' => null,
                    ]);
                }

                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 1, 'msg' => '权限不足，只能操作自己创建的记录']);
            }
        } else {
            return json(['code' => 2, 'msg' => '请勿非法请求']);
        }       
    }

    // 下载用户信息模板
    public function download_excel_user_demo() {
        // $sql = "
        //     SELECT
        //         店铺名称, 陈列方案, 备注
        //     from dd_temp_excel_user_demo
        //     where 1
        //     limit 1
        // ";
        // $select = $this->db_easyA->query($sql);
        $select[0]['货号'] = 'F82109030';
        $select[0]['调价价格'] = 69;
        $select[0]['调价时间范围'] = '调价相关时间说明文案';
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '货号匹配店铺调价模板_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载用户信息模板
    public function download_excel_user_demo2() {
        // $sql = "
        //     SELECT
        //         店铺名称, 陈列方案, 备注
        //     from dd_temp_excel_user_demo
        //     where 1
        //     limit 1
        // ";
        // $select = $this->db_easyA->query($sql);
        $select[0]['店铺名称'] = '万年一店';
        $select[0]['货号'] = 'F82109030';
        $select[0]['调价价格'] = 69;
        $select[0]['调价时间范围'] = '调价相关时间说明文案';
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '指定店铺货号调价模板_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载 未读 
    public function downloadListUser() {
        $id = input('id');
        $find_list = $this->db_easyA->table('dd_tiaojia_list')->field('uid')->where([
            ['id', '=', $id],
        ])->find();

        $sql = "
            SELECT 
                店铺名称,name as 姓名,mobile as 手机,title as 职位, url as 推送链接,
                '否' as 已读
            FROM 
                dd_tiaojia_list_user   
            WHERE 1
                AND uid = '{$find_list['uid']}'
                AND 已读 = 'N'
            ORDER BY
                已读 ASC,店铺名称
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        if ($select) {
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
        }

        return Excel::exportData($select, $header, '调价通知表未读名单_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 拉取 已读 未读  自动更新24小时之内的记录
    public function getReads_auto() {
        // $find_list = $this->db_easyA->table('dd_userimg_list')->where([
        //     ['id', '=', $input['id']],
        // ])->find();
        if (request()->isAjax() && input('更新日期')) {
            $更新日期 = input('更新日期');
            $sql = "
                SELECT 店铺名称,name,userid,task_id,更新日期 FROM dd_customer_push_baokuan
                WHERE
                    sendtime is not null
                    AND 撤回时间 is null
                    AND task_id is not null
                    AND (已读 is null OR 已读 = 'N')
                    AND sendtime is not null
                    AND 更新日期 = '{$更新日期}';
            ";
        } else {
            // 非ajax请求
            $time = time();
            if ($time >= strtotime(date('Y-m-d 08:30:00')) && $time <= strtotime(date('Y-m-d 23:59:59'))) {
                // echo '时间范围内';
            } else {
                // echo '时间范围外';
                die;
            }
            // die;
            $hour24 = date('Y-m-d H:i:s', strtotime('-1day', time()));
            $sql = "
                SELECT 店铺名称,name,userid,task_id,更新日期 FROM dd_customer_push_baokuan
                WHERE
                    sendtime is not null
                    AND 撤回时间 is null
                    AND task_id is not null
                    AND (已读 is null OR 已读 = 'N')
                    -- AND 已读 is null
                    AND sendtime >= '{$hour24}'
            ";
        }   



        $select = $this->db_easyA->query($sql);
        
        $model = new DingTalk;
        foreach ($select as $key => $val) {
            // dump($val);
            // $this->getReadsHandle_auto_handle($val['id']);
            $res = json_decode($model->getsendresult($val['task_id']), true);
            // dump($res);
            try {
                if ($res['errmsg'] = 'ok' && $res['send_result']) {  
                    // 已读
                    if (count($res['send_result']['read_user_id_list']) > 0) {
                        $reads = arrToStr($res['send_result']['read_user_id_list']);    
                        $this->db_easyA->execute("
                            UPDATE 
                                dd_customer_push_baokuan 
                            SET 
                                已读 = 'Y'
                            WHERE 1
                                AND task_id = '{$val['task_id']}'
                        ");
                    } else {
                        $update = $this->db_easyA->table('dd_customer_push_baokuan')->where([
                            ['task_id', '=', $val['task_id']],
                        ])->update([
                            '已读' => 'N',
                        ]);
                    }
    
                } else {
                    return json(['code' => 0, 'msg' => '数据异常']);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
    }

    // 下载未读
    public function download_noreads() {
        $date = input('date');
        $sql = "
            SELECT 
                店铺名称,name as 姓名,title as 职位,mobile as 手机,
                case
                    when sendtime is not null then '已发送' else '未发送'
                end as 发送状态,
                case
                    when 已读 = 'Y' then '已读' else '未读'
                end as 阅读状态,
                更新日期 as 日期
            FROM 
                dd_customer_push_baokuan   
            WHERE 1
                AND 更新日期 = '{$date}'
                AND ( `已读` is null || `已读` = 'N')
        ";

        $select = $this->db_easyA->query($sql);
        if ($select) {
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            
        } else {
            $header = []; 
        }

        return Excel::exportData($select, $header, '省份温区爆款未读名单_'  . '_' . $date . '_' . time() , 'xlsx');
    }
}
