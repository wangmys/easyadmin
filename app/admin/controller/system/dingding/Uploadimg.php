<?php
namespace app\admin\controller\system\dingding;

use think\facade\Db;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\admin\controller\system\dingding\DingTalk;
use app\api\service\dingding\Sample;

/**
 * Class Uploadimg
 * @package app\admin\controller\system\dingding
 * @ControllerAnnotation(title="钉钉操作")
 */
class Uploadimg extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_sqlsrv = '';
    protected $db_bi = '';
    // 用户信息
    protected $authInfo = '';
    
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_bi = Db::connect('mysql2');

        $this->authInfo = session('admin');
        // $this->rand_code = $this->rand_code(10);
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    /**
     * @NodeAnotation(title="添加推送信息")
     */
    public function index() {
        return View('index',[
            
        ]);
    }

    // 上传excel 店铺补货
    public function upload_img() {
        if (request()->isAjax()) {
        // if (1) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $file->getOriginalName();

            $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
            $url = $_SERVER['HTTP_ORIGIN'] . '/upload/dd_img/' . date('Ymd',time()).'/' . $new_name;
            $info = $file->move($save_path, $new_name);

            // 静态测试
            // $url = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/17c72a9e640be14d4c6a11e8fdebd6a5_156.png';   //文件保存路径

            $time = date('Y-m-d H:i:s');
            $data = [
                'aid' => $this->authInfo['id'],
                'aname' => $this->authInfo['name'],
                'path' => $url,
                'time' => $time
            ];
            $pid = $this->db_easyA->table('dd_temp_img')->strict(false)->insertGetId($data);
            return json(['code' => 0, 'msg' => '上传成功', 'data' => [
                'path' => $url,
                'pid' => $pid
            ]]);
        }
    }

    // 下载用户信息模板
    public function download_excel_user_demo() {
        $sql = "
            SELECT
                店铺名称, 姓名, 手机
            from dd_temp_excel_user_demo
            where 1
            limit 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '钉钉定推用户名单模板_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载识别错误用户信息
    public function download_excel_user_error() {
        $uid = input('uid');
        if ($uid) {
            $sql = "
                SELECT
                    店铺名称, 姓名, 手机
                from dd_temp_excel_user_error
                where 1
                    AND aid = '{$this->authInfo['id']}'
                    AND uid = '{$uid}'
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '钉钉定推用户名错误名_' . date('Ymd') . '_' . time() , 'xlsx');
        } else {
            echo 'UID有误！';
        }
    }

    public function upload_excel_user() {
        if (request()->isAjax()) {
        // if (1) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $file->getOriginalName();
            $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_excel_user/' . date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            // 静态测试
            // $info = app()->getRootPath() . 'public/upload/dd_excel_user/'.date('Ymd',time()).'/666.xlsx';   //文件保存路径
            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '店铺名称',
                    'B' => '姓名',
                    'C' => '手机',
                ];
                
                //读取数据
                $data = $this->readExcel_temp_excel_user($info, $read_column);
                // echo '<pre>';
                // print_r($data); die;

                if ($data) {
                    $model = new DingTalk;
                    $sucess_data = [];
                    $error_data = [];
                    $uid = rand_code(8);
                    $time = date('Y-m-d H:i:s');


                    foreach ($data as $key => $val) {
                        $data[$key]['uid'] = $uid;
                        $data[$key]['aid'] = $this->authInfo['id'];
                        $data[$key]['aname'] = $this->authInfo['name'];
                        $data[$key]['店铺名称'] = @$val['店铺名称'];
                        $data[$key]['姓名'] = @$val['姓名'];
                        $data[$key]['手机'] = @$val['手机'];
                        $data[$key]['time'] = $time;
                    }

                    // 删除临时excel表该用户上传的记录
                    $select_user_temp = $this->db_easyA->execute("delete from dd_temp_excel_user where aid = '{$this->authInfo['id']}'");

                    $chunk_list = array_chunk($data, 500);
                    foreach($chunk_list as $key => $val) {
                        $this->db_easyA->table('dd_temp_excel_user')->strict(false)->insertAll($val);
                    }

                    $sql_成功名单 = "
                        SELECT
                            临时.*,
                            u.title,
                            u.userid 
                        FROM
                            `dd_temp_excel_user` as 临时
                        LEFT JOIN dd_user as u on 临时.手机 = u.mobile and mobile is not null
                        WHERE
                            临时.手机 = u.mobile
                            AND 临时.aid = '{$this->authInfo['id']}'
                            AND 临时.uid = '{$uid}'
                    ";
                    $select_成功名单 = $this->db_easyA->query($sql_成功名单);

                    if ($select_成功名单) {
                        // 成功
                        $chunk_list_success = array_chunk($select_成功名单, 500);
                        foreach($chunk_list_success as $key => $val) {
                            $this->db_easyA->table('dd_temp_excel_user_success')->strict(false)->insertAll($val);
                        }

                        $sucess_data_num = count($select_成功名单);
                    } else {
                        $sucess_data_num = 0;
                    }

                    $sql_失败名单 = "
                        SELECT
                            *
                        FROM
                            `dd_temp_excel_user`
                        WHERE
                            aid = '{$this->authInfo['id']}'
                            AND uid = '{$uid}'
                            AND 手机 not in (select mobile from dd_user where mobile is not null)
                    ";
                    $select_失败名单 = $this->db_easyA->query($sql_失败名单);

                    if ($select_失败名单) {
                        // 失败
                        $chunk_list_error = array_chunk($select_失败名单, 500);
                        foreach($chunk_list_error as $key => $val) {
                            $this->db_easyA->table('dd_temp_excel_user_error')->strict(false)->insertAll($val);
                        }
                        $error_data_num = count($select_失败名单);
                    } else {
                        $error_data_num = 0;
                    }

                    return json(['code' => 0, 'msg' => "定推名单上传成功，识别成功：{$sucess_data_num}行，识别失败：{$error_data_num}行。", 'data' => [
                        'uid' => $uid,
                        'error_data_num' => $error_data_num,
                    ]]);
                }
                
            } else {
                echo '没数据';
            }
        }   
    }

    /** 
     * 读取excel里面的内容保存为数组
     * @param string $file_path  导入文件的路径
     * @param array $read_column  要返回的字段
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExcel_temp_excel_user($file_path = '/', $read_column = array())
    {
        $reader = IOFactory::createReader('Xlsx');
    
        $reader->setReadDataOnly(TRUE);
    
        //载入excel表格
        $spreadsheet = $reader->load($file_path);
    
        // 读取第一個工作表
        $sheet = $spreadsheet->getSheet(0);
    
        // 取得总行数
        $highest_row = $sheet->getHighestRow();
    
        // 取得总列数
        $highest_column = $sheet->getHighestColumn();
    
        //读取内容
        $data_origin = array();
        $data = array();
        for ($row = 2; $row <= $highest_row; $row++) { //行号从2开始
            for ($column = 'A'; $column <= $highest_column; $column++) { //列数是以A列开始
                $str = $sheet->getCell($column . $row)->getValue();
                //保存该行的所有列
                $data_origin[$column] = $str;
                // if ($column == "C" || $column == "D") {
                //     if (is_numeric($data_origin[$column])) {
                //         $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                //         $data_origin[$column] = gmdate('Y/m/d',$t1);
                //     } else {
                //         $data_origin[$column] = $data_origin[$column];
                //     }
                // }
            }

            // 删除空行，好用的很
            if(!implode('', $data_origin)){
                //删除空行
                continue;
            }

            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = @$data_origin[$key] ? $data_origin[$key] : '';
            }
        }
        return $data;
    }

    // 提交
    public function submitHandle() {
        if (request()->isAjax()) {
            $input = input();
            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            
            $time = date('Y-m-d H:i:s');
            $data = [
                'aid' => $this->authInfo['id'],
                'aname' => $this->authInfo['name'],
                'title' => $input['title'],
                'pid' => $input['pid'],
                'uid' => $input['uid'],
                'createtime' => $time
            ];

            $id = $this->db_easyA->table('dd_userimg_list')->strict(false)->insertGetId($data);
            return json(['code' => 0, 'msg' => '提交成功', 'data' => [
                'id' => $id
            ]]);
            
        }
    }

    /**
     * @NodeAnotation(title="钉钉推送列表")
     */
    public function list() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // 非系统管理员
            if (! checkAdmin()) { 
                $aname = session('admin.name');       
                $aid = session('admin.id');   
                $mapSuper = " AND list.aid='{$aid}'";  
            } else {
                $mapSuper = '';
            }
            // $云仓 = $input['yc'];
            // $货号 = $input['gdno'];
            // if (!empty($input['yc'])) {
            //     $map1 = " AND `云仓` = '{$云仓}云仓'";                
            // } else {
            //     $map1 = "";
            // }
            // if (!empty($input['gdno'])) {
            //     $map2 = " AND `货号` = '{$货号}'";                
            // } else {
            //     $map2 = "";
            // }

            // if (!empty($input['云仓'])) {
            //     // echo $input['商品负责人'];
            //     $map3Str = xmSelectInput($input['云仓']);
            //     $map3 = " AND 云仓 IN ({$map3Str})";
            // } else {
            //     $map3 = "";
            // }
            // if (!empty($input['经营模式'])) {
            //     // echo $input['商品负责人'];
            //     $map4Str = xmSelectInput($input['经营模式']);
            //     $map4 = " AND 经营模式 IN ({$map4Str})";
            // } else {
            //     $map4 = "";
            // }   
            // if (!empty($input['货号'])) {
            //     // echo $input['商品负责人'];
            //     $map5Str = xmSelectInput($input['货号']);
            //     $map5 = " AND 货号 IN ({$map5Str})";
            // } else {
            //     $map5 = "";
            // }
            
            // if (!empty($input['店铺名称'])) {
            //     // echo $input['商品负责人'];
            //     $map6Str = xmSelectInput($input['店铺名称']);
            //     $map6 = " AND 店铺名称 IN ({$map6Str})";
            // } else {
            //     $map6 = "";
            // }
            $sql = "
                SELECT 
                    list.*,
                    img.path
                FROM 
                    dd_userimg_list as list 
                LEFT JOIN dd_temp_img as img ON list.pid = img.pid 
                WHERE 1
                    {$mapSuper}
                ORDER BY
                    id DESC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM dd_userimg_list as list
                WHERE 1
                    {$mapSuper}
                ORDER BY
                    id DESC
            ";
            $count = $this->db_easyA->query($sql2);
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('list', [
                // 'config' => ,
            ]);
        }
    }

    public function userList() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');
            $uid = $input['uid'];
            $aid = $this->authInfo['id'];

            // 非系统管理员
            if (! checkAdmin()) { 
                $mapSuper = " AND aid='{$aid}'";  
            } else {
                $mapSuper = '';
            }
 
            $sql = "
                SELECT 
                    *
                FROM 
                    dd_temp_excel_user_success   
                WHERE 1
                    AND uid = '{$uid}'
                    {$mapSuper}
                ORDER BY
                    店铺名称 ASC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_temp_excel_user_success   
                WHERE 1
                    AND uid = '{$uid}'
                    {$mapSuper}
                ORDER BY
                    店铺名称 ASC
            ";
            $count = $this->db_easyA->query($sql2);
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('userList', [
                // 'config' => ,
            ]);
        }        
    }

    // 获取钉钉用户信息
    public function getDingDingUserInfo() {
        if (request()->isAjax()) {
            if (!checkAdmin()) {
                $CustomItem17 = $this->authInfo['name'];
                $map = "AND EC.CustomItem17 = '{$CustomItem17}'";
            } else {
                $map = "";
            }
        // if (1) {
            $sql_康雷 = "
                SELECT
                    State AS 省份,
                    CustomerName AS 店铺名称,
                    EBC.Mathod AS 经营模式,
                    CustomerId,
                    CustomerCode,
                    EC.RegionId,
                    CustomItem17 AS 商品负责人,
                    CustomItem18 AS 督导负责人,
                    CustomItem19 AS 店铺负责人
                FROM
                    ErpCustomer AS EC 
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                WHERE 
                EC.RegionId NOT IN ('8','40', '55' ,'84', '85',  '97')
                AND EBC.Mathod IN ('直营', '加盟')
                AND EC.CustomerCode IN (
                                SELECT
                                        EC.CustomerCode 
                                FROM
                                        ErpRetail AS ER
                                        LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                                        LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                                        LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId 
                                WHERE
                                        ER.CodingCodeText = '已审结' 
                                        AND EC.ShutOut = 0 
                                        AND EC.RegionId <> 55 
                                        AND EBC.Mathod IN ( '直营')
                                        {$map}    
                                GROUP BY
                                        ER.CustomerName,
                                        EC.RegionId,
                                        EC.CustomerCode
                )
                ORDER BY EBC.Mathod DESC
            ";
            $select_customer = $this->db_sqlsrv->query($sql_康雷);
            $select_user = $this->db_easyA->table('dd_user')->select();
            
            // echo '<pre>';
            // print_r($select_customer);
            // print_r($select_user);
            // die;
            foreach ($select_customer as $key => $val) {
                foreach ($select_user as $key2 => $val2) {

                    if ($val['店铺名称'] == $val2['店铺名称']) {
                        $店铺负责人 = trim($val['店铺负责人']);
                        $pattern = "/{$店铺负责人}/i";
                        $find_user = preg_match($pattern, $val2['name']);
                        if ($find_user) {
                            $select_customer[$key]['店铺负责人手机'] = $val2['mobile'];
                        }
                    }
                }
                foreach ($select_user as $key2 => $val2) {
                    if ($val['督导负责人']) {
                        $pattern_督导 = "/督导/i";
                        $find_督导 = preg_match($pattern_督导, $val2['title']);

                        $pattern_name = "/{$val['督导负责人']}/i";
                        $find_name = preg_match($pattern_name, $val2['name']);

                        if (($find_督导 || $val2['title'] == '区域大店长') && $find_name) {
                            $select_customer[$key]['督导负责人手机'] = $val2['mobile'];
                            break;
                        }
                    }
                }
            }
            // echo '<pre>';
            // print_r($select_customer);
            // // print_r($select_user);
            // die;
            return json(["code" => "0", "msg" => "", "count" => count($select_customer), "data" => $select_customer]);
        } else {
            return View('dduser', [
                // 'config' => ,
            ]);
        }

        // echo '<pre>';
        // print_r($select_customer);
    }

    // 获取钉钉用户信息
    public function getDingDingUserInfo2() {
        // if (request()->isAjax()) {
        if (1) {
            if (!checkAdmin()) {
                $CustomItem17 = $this->authInfo['name'];
                $map = "AND EC.CustomItem17 = '{$CustomItem17}'";
            } else {
                $map = "";
            }

            $select_user = $this->db_easyA->table('dd_user')->select();
            
            echo '<pre>';
            // print_r($select_customer);
            print_r($select_user);
            die;
            // foreach ($select_customer as $key => $val) {
            //     foreach ($select_user as $key2 => $val2) {

            //         if ($val['店铺名称'] == $val2['店铺名称']) {
            //             $店铺负责人 = trim($val['店铺负责人']);
            //             $pattern = "/{$店铺负责人}/i";
            //             $find_user = preg_match($pattern, $val2['name']);
            //             if ($find_user) {
            //                 $select_customer[$key]['店铺负责人手机'] = $val2['mobile'];
            //             }
            //         }
            //     }
            //     foreach ($select_user as $key2 => $val2) {
            //         if ($val['督导负责人']) {
            //             $pattern_督导 = "/督导/i";
            //             $find_督导 = preg_match($pattern_督导, $val2['title']);

            //             $pattern_name = "/{$val['督导负责人']}/i";
            //             $find_name = preg_match($pattern_name, $val2['name']);

            //             if (($find_督导 || $val2['title'] == '区域大店长') && $find_name) {
            //                 $select_customer[$key]['督导负责人手机'] = $val2['mobile'];
            //                 break;
            //             }
            //         }
            //     }
            // }
            // echo '<pre>';
            // print_r($select_customer);
            // // print_r($select_user);
            // die;
            
            return json(["code" => "0", "msg" => "", "count" => count($select_user), "data" => $select_user]);
        } else {
            return View('dduser', [
                // 'config' => ,
            ]);
        }

        // echo '<pre>';
        // print_r($select_customer);
    }

    // 发送测试
    public function sendDingImg() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg
        // if (request()->isAjax() && $input['id']) {
        if (1 && $input['id']) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;
            
            $find_list = $this->db_easyA->table('dd_userimg_list')->where([
                ['id', '=', $input['id']]
            ])->find();

            if ($find_list) {
                $find_path = $this->db_easyA->table('dd_temp_img')->where([
                    ['pid', '=', $find_list['pid']]
                ])->find();
                // echo $find_path['path'];

                $select_user = $this->db_easyA->table('dd_temp_excel_user_success')->where([
                    ['uid', '=', $find_list['uid']]
                ])->select();

                foreach ($select_user as $key => $val) {
                    // echo $val['姓名'];
                    $res = $model->sendMarkdownImg($val['userid'], $find_list['title'], $find_path['path']);
                    dump($res);
                }
            }
        }
    }

    public function sendDingImgHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg

        if (request()->isAjax() && $input['id'] && session('admin.name')) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;
            
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_userimg_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_userimg_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }


            if ($find_list) {
                $find_path = $this->db_easyA->table('dd_temp_img')->where([
                    ['pid', '=', $find_list['pid']]
                ])->find();
                // echo $find_path['path'];

                $select_user = $this->db_easyA->table('dd_temp_excel_user_success')->field('userid')->where([
                    ['uid', '=', $find_list['uid']]
                ])->group('userid')->select()->toArray();

                
                $chunk_list_success = array_chunk($select_user, 996);
                $model = new DingTalk;
                foreach($chunk_list_success as $key => $val) {
                    // $this->db_easyA->table('dd_temp_excel_user_success')->strict(false)->insertAll($val);
                    $userids = '';
                    foreach ($val as $key2 => $val2) {
                        if ( ($key2 + 1) < count($val) ) {
                            $userids .= $val2['userid'] . ',';
                        } else {
                            $userids .= $val2['userid'];
                            $res = $model->sendMarkdownImg_pro($userids, $find_list['title'], $find_path['path']);
                        }
                    }
                    
                }

                $updatetime = date('Y-m-d H:i:s');
                $sql_更新 = "
                    update dd_userimg_list
                    set 
                        sendtimes = sendtimes + 1,
                        sendtime = '{$updatetime}'
                    where id = '{$input['id']}'
                ";
                $this->db_easyA->execute($sql_更新);

                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 0, 'msg' => '信息有误，执行失败']);
            }
        } else {
            return json(['code' => 0, 'msg' => '请勿非法请求']);
        }       
    }

    public function test() {
        $str = '田珊';
        $str2 = ' 11田珊珊的工作号';
        $pattern = "/{$str}/i";
        echo preg_match($pattern, $str2);
    }
}
