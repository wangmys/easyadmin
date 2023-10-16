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
 * Class Weatherdisplay
 * @package app\admin\controller\system\dingding
 * @ControllerAnnotation(title="气温陈列调整表")
 */
class Weatherdisplay extends AdminController
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

    // 测试用true，正式用false
    protected $debug = true;

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
     * @NodeAnotation(title="气温陈列信息设置")
     */
    public function config() {
        if (request()->isAjax()) {
            $input = input();
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
            // $云仓 = $input['yc'];
            // $货号 = $input['gdno'];
            // if (!empty($input['yc'])) {
            //     $map1 = " AND `云仓` = '{$云仓}云仓'";                
            // } else {
            //     $map1 = "";
            // }
            $sql = "
                SELECT 
                    *
                FROM 
                    dd_weatherdisplay_config
                WHERE 1
 
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM 
                        dd_weatherdisplay_config
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('config',[
            
            ]);
        }
    }

    // 陈列图上传
    public function uploadImgHandle() {
        if (request()->isAjax()) {
        // if (1) {
            $input = input();
            if (!empty($input['pid']) && !empty($input['title'])) {
                $find_img = $this->db_easyA->table('dd_temp_img')->where(['pid' => $input['pid']])->find();
                if ($find_img) {
                    $update = $this->db_easyA->table('dd_weatherdisplay_config')->where(['陈列方案' => $input['title']])->update([
                        'path' => $find_img['path'],
                        'updatetime' => date('Y-m-d H:i:s')
                    ]);
                    if ($update) {
                        return json(['code' => 0, 'msg' => '上传成功', 'data' => [
                            // 'path' => $url,
                            // 'pid' => $pid
                        ]]);
                    } else {
                        return json(['code' => 3, 'msg' => '服务器繁忙，请稍后再试']);
                    }
                } else {
                    return json(['code' => 2, 'msg' => '图片不存在，操作失败']);
                }
            } else {
                return json(['code' => 1, 'msg' => '参数缺失，上传失败']);
            }
        }        
    }

    /**
     * @NodeAnotation(title="添加推送信息")
     */
    public function addlist() {
        return View('addlist',[
            
        ]);
    }

    /**
     * @NodeAnotation(title="气温陈列推送列表")
     */
    public function list() {
        if (request()->isAjax()) {
            $input = input();
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
            // $云仓 = $input['yc'];
            // $货号 = $input['gdno'];
            // if (!empty($input['yc'])) {
            //     $map1 = " AND `云仓` = '{$云仓}云仓'";                
            // } else {
            //     $map1 = "";
            // }
            $sql = "
                SELECT 
                    *
                FROM 
                    dd_weatherdisplay_list
                WHERE 1
                ORDER BY id desc
 
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM 
                        dd_weatherdisplay_list
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('list',[
            
            ]);
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
        $select[0]['店铺名称'] = '店铺的名称';
        $select[0]['陈列方案'] = '秋转冬方案二';
        $select[0]['窗数'] = 2;
        $select[0]['备注'] = '推送备注';
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '店铺版_陈列调整推送模板_' . date('Ymd') . '_' . time() , 'xlsx');
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
        $select[0]['店铺名称'] = '店铺的名称';
        $select[0]['姓名'] = '钉钉昵称';
        $select[0]['手机'] = '手机';
        $select[0]['陈列方案'] = '秋转冬方案二';
        $select[0]['窗数'] = 3;
        $select[0]['备注'] = '推送备注';
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '用户版_陈列调整推送模板' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载识别错误用户信息
    public function download_errors() {
        $id = input('id');
        $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where(['id' => $id])->find();
        if ($find_list) {
            $sql = "
                SELECT
                    店铺名称, name as 姓名, mobile as 手机
                from dd_weatherdisplay_list_user
                where 1
                    AND uid = '{$find_list['uid']}'
                    AND (userid is null OR path is null)
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '陈列调整错误名单_' . date('Ymd') . '_' . time() , 'xlsx');
        } else {
            echo '参数有误';
        }
    }

    // 上次用户列表_店铺版 测试 $debug
    public function upload_excel_user() {
        if (request()->isAjax()) {
        // if (1) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $file->getOriginalName();
            $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_excel_user/' . date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            // 静态测试
            // $info = app()->getRootPath() . 'public/upload/dd_excel_user/'.date('Ymd',time()).'/气温陈列调整上传模板.xlsx';   //文件保存路径

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '店铺名称',
                    'B' => '陈列方案',
                    'C' => '窗数',
                    'D' => '备注',
                ];
                
                //读取数据
                $data = $this->readExcel_temp_excel_user($info, $read_column);
   

                if ($data) {
                    $model = new DingTalk;
                    $sucess_data = [];
                    $error_data = [];
                    $uid = rand_code(8);
                    $time = date('Y-m-d H:i:s');

                    $店铺str = ''; 
                    foreach ($data as $key => $val) {
                        // $data[$key]['uid'] = $uid;
                        // $data[$key]['aid'] = $this->authInfo['id'];
                        // $data[$key]['aname'] = $this->authInfo['name'];
                        // $data[$key]['店铺名称'] = @$val['店铺名称'];
                        // $data[$key]['姓名'] = @$val['姓名'];
                        // $data[$key]['手机'] = @$val['手机'];
                        // $data[$key]['time'] = $time;
                        
                        // 最后
                        if ($key == count($data) -1) {
                            $店铺str .=  "'{$val['店铺名称']}'";
                        } else {
                            $店铺str .=  "'{$val['店铺名称']}',";    
                        }
                    }

                    // 测试专用
                    if ($this->debug) {
                        $sql = "
                            select * from dd_customer_push where isCustomer = '是' and 店铺名称 in ({$店铺str}) and `name` in ('陈威良','王威','李雅婷','徐文娟')
                        ";
                    } else {
                        $sql = "
                            select * from dd_customer_push where isCustomer = '是' and 店铺名称 in ({$店铺str})
                        ";
                    }
                    // echo $sql;

                    // 方案图路径
                    $select_path = $this->db_easyA->query("
                        select 陈列方案,path from dd_weatherdisplay_config
                    ");
                    // 查询推送店铺店长名单
                    $select_customer_push = $this->db_easyA->query($sql);

         
                    // 陈列方案
                    foreach ($select_customer_push as $k1 => $v1) {
                        foreach ($data as $k2 => $v2) {
                            if ($v1['店铺名称'] == $v2['店铺名称']) {
                                $select_customer_push[$k1]['陈列方案'] = $v2['陈列方案'];
                                $select_customer_push[$k1]['窗数'] = $v2['窗数'];
                                $select_customer_push[$k1]['备注'] = $v2['备注'];
                                $select_customer_push[$k1]['uid'] = $uid;
                                $select_customer_push[$k1]['aid'] = $this->authInfo['id'];
                                $select_customer_push[$k1]['aname'] = $this->authInfo['name'];
                                $select_customer_push[$k1]['createtime'] = $time;
                            }
                        }
                    }

                    // path
                    foreach ($select_customer_push as $k3 => $v3) {
                        foreach ($select_path as $k4 => $v4) {
                            if ($v3['陈列方案'] == $v4['陈列方案']) {
                                $select_customer_push[$k3]['path'] = $v4['path'];
                            }
                        }
                    }

                    
                    // 删除临时excel表该用户上传的记录

                    $chunk_list = array_chunk($select_customer_push, 500);
                    // dump($chunk_list);
                    $this->db_easyA->startTrans();
                    $res = false;
                    foreach($chunk_list as $key => $val) {
                        $res = $this->db_easyA->table('dd_weatherdisplay_list_user')->strict(false)->insertAll($val);
                        if (!$res) {
                            break;
                        }
                    }

                    $insert_list['uid'] = $uid;
                    $insert_list['aid'] = $this->authInfo['id'];
                    $insert_list['aname'] = $this->authInfo['name'];
                    $insert_list['createtime'] = $time;
                    $insert_list['总数'] = count($select_customer_push);
                    $res2 = $this->db_easyA->table('dd_weatherdisplay_list')->strict(false)->insert($insert_list);

                    if ($res && $res2) {
                        $this->db_easyA->commit();
                    } else {
                        $this->db_easyA->rollback();
                    }
                    
                    return json(['code' => 0, 'msg' => "名单上传成功", 'data' => []]);
                }
                
            } else {
                echo '没数据';
            }
        }   
    }

    // 上次用户列表_用户版 测试 $debug
    public function upload_excel_user2() {
        if (request()->isAjax()) {
        // if (1) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $file->getOriginalName();
            $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_excel_user/' . date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            // 静态测试
            // $info = app()->getRootPath() . 'public/upload/dd_excel_user/'.date('Ymd',time()).'/用户版_陈列调整推送模板20231016_1697437700.xlsx';   //文件保存路径

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '店铺名称',
                    'B' => 'name',
                    'C' => 'mobile',
                    'D' => '陈列方案',
                    'E' => '窗数',
                    'F' => '备注',
                ];
                
                //读取数据
                $data = $this->readExcel_temp_excel_user($info, $read_column);
    
                // print($data);die;

                if ($data) {
                    $model = new DingTalk;
                    $sucess_data = [];
                    $error_data = [];
                    $uid = rand_code(8);
                    $time = date('Y-m-d H:i:s');

                    $手机号str = ''; 
                    foreach ($data as $key => $val) {
                        $data[$key]['userid'] = NULL;
                        $data[$key]['title'] = NULL;
                        $data[$key]['uid'] = $uid;
                        $data[$key]['aid'] = $this->authInfo['id'];
                        $data[$key]['aname'] = $this->authInfo['name'];
                        $data[$key]['isCustomer'] = '是';
                        $data[$key]['createtime'] = $time;
                        $data[$key]['path'] = NULL;
                        
                        // 最后
                        if ($key == count($data) -1) {
                            $手机号str .=  "'{$val['mobile']}'";
                        } else {
                            $手机号str .=  "'{$val['mobile']}',";    
                        }
                    }

                    // echo $手机号str;die;

                    $sql_dd_user = "
                        select mobile,title,userid from dd_user where mobile in ({$手机号str})
                    ";
                    $selct_dd_user = $this->db_easyA->query($sql_dd_user);

                    foreach ($data as $key2 => $val2) {
                        foreach ($selct_dd_user as $key2 => $val3) {
                            // 最后
                            if ($val3['mobile'] == $val2['mobile']) {
                                $data[$key2]['userid'] = $val3['userid'];
                                $data[$key2]['title'] = $val3['title'];
                                // $data[$key2]['uid'] = $uid;
                                // $data[$key2]['aid'] = $this->authInfo['id'];
                                // $data[$key2]['aname'] = $this->authInfo['name'];
                                $data[$key2]['isCustomer'] = '是';
                                $data[$key2]['createtime'] = $time;

                                $find_陈列方案 = $this->db_easyA->table('dd_weatherdisplay_config')->field('path')->where(['陈列方案' => $val2['陈列方案']])->find();
                                if ($find_陈列方案) {
                                    $data[$key2]['path'] = $find_陈列方案['path'];
                                } else {
                                    $data[$key2]['path'] = NULL;
                                }
                                break;
                            }
                        }
                    }

                    // dump($data);die;
                    // 测试专用
                    // if ($this->debug) {
                    //     $sql = "
                    //         select * from dd_customer_push where isCustomer = '是' and 店铺名称 in ({$店铺str}) and `name` in ('陈威良','王威','李雅婷','徐文娟')
                    //     ";
                    // } else {
                    //     $sql = "
                    //         select * from dd_customer_push where isCustomer = '是' and 店铺名称 in ({$店铺str})
                    //     ";
                    // }
                    // echo $sql;

                    // 方案图路径
                    // $select_path = $this->db_easyA->query("
                    //     select 陈列方案,path from dd_weatherdisplay_config
                    // ");
                    // // 查询推送店铺店长名单
                    // $select_customer_push = $this->db_easyA->query($sql);

            
                    // 陈列方案
                    // foreach ($data as $k1 => $v1) {
                    //     foreach ($data as $k2 => $v2) {
                    //         if ($v1['店铺名称'] == $v2['店铺名称']) {
                    //             $select_customer_push[$k1]['陈列方案'] = $v2['陈列方案'];
                    //             $select_customer_push[$k1]['窗数'] = $v2['窗数'];
                    //             $select_customer_push[$k1]['备注'] = $v2['备注'];
                    //             $select_customer_push[$k1]['uid'] = $uid;
                    //             $select_customer_push[$k1]['aid'] = $this->authInfo['id'];
                    //             $select_customer_push[$k1]['aname'] = $this->authInfo['name'];
                    //             $select_customer_push[$k1]['createtime'] = $time;
                    //         }
                    //     }
                    // }

                    // // path
                    // foreach ($select_customer_push as $k3 => $v3) {
                    //     foreach ($select_path as $k4 => $v4) {
                    //         if ($v3['陈列方案'] == $v4['陈列方案']) {
                    //             $select_customer_push[$k3]['path'] = $v4['path'];
                    //         }
                    //     }
                    // }

                    
                    // 删除临时excel表该用户上传的记录

                    $chunk_list = array_chunk($data, 500);
                    // dump($chunk_list);
                    $this->db_easyA->startTrans();
                    $res = false;
                    foreach($chunk_list as $key => $val) {
                        $res = $this->db_easyA->table('dd_weatherdisplay_list_user')->strict(false)->insertAll($val);
                        if (!$res) {
                            break;
                        }
                    }

                    // 统计错上传数
                    $错误num = 0;
                    foreach ($data as $k => $v) {
                        if (empty($v['userid'])) {
                            $错误num += 1; 
                        }
                    }

                    $insert_list['uid'] = $uid;
                    $insert_list['aid'] = $this->authInfo['id'];
                    $insert_list['aname'] = $this->authInfo['name'];
                    $insert_list['createtime'] = $time;


                    // $insert_list['错误'] = count($data);
                    $insert_list['总数'] = count($data) - $错误num;
                    $insert_list['错误'] = $错误num;
                    $res2 = $this->db_easyA->table('dd_weatherdisplay_list')->strict(false)->insert($insert_list);

                    if ($res && $res2) {
                        $this->db_easyA->commit();
                    } else {
                        $this->db_easyA->rollback();
                    }
                    
                    return json(['code' => 0, 'msg' => "名单上传成功", 'data' => []]);
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

    // 推送信息相关用户列表
    public function list_user() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');
            $id = $input['id'];
            $aid = $this->authInfo['id'];

            $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->field('uid')->where(['id' => $id])->find();
            // die;
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
                    dd_weatherdisplay_list_user   
                WHERE 1
                    AND uid = '{$find_list['uid']}'
                    AND userid is not null
                    {$mapSuper}
                ORDER BY
                    已读 ASC,店铺名称
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_weatherdisplay_list_user   
                WHERE 1
                    AND uid = '{$find_list['uid']}'
                    AND userid is not null
                    {$mapSuper}
                ORDER BY
                    已读 ASC,店铺名称
            ";
            $count = $this->db_easyA->query($sql2);
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('list_user', [
                // 'config' => ,
            ]);
        }        
    }

    // 获取钉钉用户信息
    public function getDingDingUserInfo() {
        if (request()->isAjax()) {
            if (!checkAdmin()) {
                $CustomItem17 = $this->authInfo['name'];
                $map = "AND c.CustomItem17 = '{$CustomItem17}'";
            } else {
                $map = "";
            }

            $sql_select = "
                select 
                    p.店铺名称,p.name,p.title,p.mobile,c.State,c.CustomItem17,'直营' as 经营模式 
                from dd_customer_push as p
                left join customer_pro as c on p.店铺名称 = c.CustomerName
                where 1 
                    AND name not in ('陈威良', '王威')
                    {$map}
            ";

            $sql_total = "
                select 
                    count(*) as total
                from dd_customer_push as p
                left join customer_pro as c on p.店铺名称 = c.CustomerName
                where 1 
                    AND name not in ('陈威良', '王威')
                    {$map}
            ";
            $select = $this->db_easyA->query($sql_select);
            $total = $this->db_easyA->query($sql_total);
            return json(["code" => "0", "msg" => "", "count" => $total[0]['total'], "data" => $select]);
        } else {
            return View('dduser', [
                // 'config' => ,
            ]);
        }

    }

    // 获取 全员 钉钉用户信息
    public function getDingDingAllUserInfo() {
        if (request()->isAjax()) {
            $sql_select = "
                select 
                    u.店铺名称,u.name,u.title,u.mobile,c.State, c.CustomItem17, c.Mathod as 经营模式  
                from dd_user as u
                left join customer as c on  u.店铺名称 = c.CustomerName
                where 1 
            ";

            $sql_total = "
                select 
                    count(*) as total
                from dd_user as u 
                left join customer as c on  u.店铺名称 = c.CustomerName
                where 1 
            ";
            $select = $this->db_easyA->query($sql_select);
            $total = $this->db_easyA->query($sql_total);
            return json(["code" => "0", "msg" => "", "count" => $total[0]['total'], "data" => $select]);
        } else {
            return View('ddalluser', [
                // 'config' => ,
            ]);
        }

    }


    // 发送 通知
    public function sendListHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg
        if (request()->isAjax() && $input['id'] && session('admin.name')) {
        // if (1) {
            $model = new DingTalk;
 
            // $input['id'] = 161;

            $date = date('Y-m-d H:i:s');
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }


            // print_r($find_list);die;

            if ($find_list) {
                $select_group = $this->db_easyA->query("
                    select 店铺名称,陈列方案,窗数,备注 from dd_weatherdisplay_list_user
                    where 
                        uid= '{$find_list['uid']}'
                        and userid is not null
                        and 陈列方案 is not null 
                    group by 店铺名称,陈列方案,窗数,备注
                ");
                // $select_group = $this->db_easyA->table('dd_weatherdisplay_list_user')->field('店铺名称,陈列方案,窗数,备注')->where([
                //     ['uid', '=', $find_list['uid']]
                // ])->group('店铺名称,陈列方案,窗数,备注')->select()->toArray();


                // $select_user = $this->db_easyA->table('dd_weatherdisplay_list_user')->field('id,uid,userid,陈列方案,备注')->where([
                //     ['uid', '=', $find_list['uid']]
                // ])->select()->toArray();


                // dump($select_group);
                // die;
                // dump($select_user);

                // 遍历分组
                foreach ($select_group as $k1 => $v1) {
                    $select_user = $this->db_easyA->table('dd_weatherdisplay_list_user')->where([
                        ['uid', '=', $find_list['uid']],
                        ['陈列方案', '=', $v1['陈列方案']],
                        ['店铺名称', '=', $v1['店铺名称']],
                        ['窗数', '=', $v1['窗数']],
                        ['备注', '=', $v1['备注']],
                    ])->select()->toArray();
                    // dump($select_user);

                    $chunk_list_success = array_chunk($select_user, 300);
                    // $chunk_list_success = array_chunk($select_user, 2);
                    $model = new DingTalk;
                    foreach($chunk_list_success as $key => $val) {
    
                        $userids = ''; // 发送用
                        $ids = ''; // 更新用
                        foreach ($val as $key2 => $val2) {
                            if ( ($key2 + 1) < count($val) ) {
                                $userids .= $val2['userid'] . ',';
                                $ids .= $val2['id'] . ',';
                            } else {
                                // 最后一次发送  发送id xxx,xxx,xxx
                                $userids .= $val2['userid'];
                                $update_uids = xmSelectInput($userids);

                                $ids .= $val2['id'];
                                $ids = xmSelectInput($ids);
    
                                // 发送
                                $path = $val2['path'] . '?t=' . time();

                                $dataVal['店铺名称'] = $val2['店铺名称'];
                                $dataVal['陈列方案'] = $val2['陈列方案'];
                                $dataVal['窗数'] = $val2['窗数'];
                                $dataVal['备注'] = $val2['备注'];
                                $dataVal['path'] = $path;
                                $res = json_decode($model->sendMarkdownImg_weatherdisplay($userids, $dataVal), true);
                                   
                                if ($res) {
                                    // 更新用户列表 task_id
                                    $this->db_easyA->execute("
                                        update dd_weatherdisplay_list_user
                                        set 
                                            task_id = '{$res['task_id']}',
                                            sendtime = '{$date}'
                                        where 1
                                            AND id IN ({$ids})
                                            AND uid = '{$val2['uid']}'
                                            AND userid IN ({$update_uids})
                                    ");
                                }
        
                            }
                        }
                    }
                }
  
                $this->db_easyA->execute("
                    update dd_weatherdisplay_list
                    set 
                        sendtime = '{$date}',
                        sendtimes = sendtimes + 1,
                        撤回时间 = NULL
                    where 1
                        AND id = '{$input['id']}'
                ");
                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 1, 'msg' => '权限不足，只能操作自己创建的记录']);
            }
        } else {
            return json(['code' => 2, 'msg' => '请勿非法请求']);
        }       
    }
    

    // 发送 工作通知
    // public function sendDingFileHandle() {
    //     $input = input();
    //     // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg
    //     if (request()->isAjax() && $input['id'] && session('admin.name')) {
    //         $model = new DingTalk;
    
    //         if (! checkAdmin()) {
    //             $find_list = $this->db_easyA->table('dd_userimg_list')->where([
    //                 ['id', '=', $input['id']],
    //                 ['aid', '=', $this->authInfo['id']],
    //             ])->find();
    //         } else {
    //             $find_list = $this->db_easyA->table('dd_userimg_list')->where([
    //                 ['id', '=', $input['id']],
    //             ])->find();    
    //         }

    //         if ($find_list) {
    //             $find_path = $this->db_easyA->table('dd_temp_img')->where([
    //                 ['pid', '=', $find_list['pid']]
    //             ])->find();
    //             // echo $find_path['path'];

    //             $select_user = $this->db_easyA->table('dd_temp_excel_user_success')->field('userid')->where([
    //                 ['uid', '=', $find_list['uid']]
    //             ])->group('userid')->select()->toArray();

    //             $chunk_list_success = array_chunk($select_user, 150);
    //             // $chunk_list_success = array_chunk($select_user, 2);
    //             $model = new DingTalk;
    //             foreach($chunk_list_success as $key => $val) {

    //                 $userids = '';
    //                 foreach ($val as $key2 => $val2) {
    //                     if ( ($key2 + 1) < count($val) ) {
    //                         $userids .= $val2['userid'] . ',';
    //                     } else {
    //                         // 最后一次发送  发送id xxx,xxx,xxx
    //                         $userids .= $val2['userid'];
    //                         $update_uids = xmSelectInput($userids);

    //                         // 发送
    //                         $res = json_decode($model->sendMarkdownImg_pro($userids, $find_list['title'], $find_path['path']), true);

    //                         // $res['request_id'] = rand_code();
    //                         // $res['task_id'] = rand_code();
    //                         // $res['errmsg'] = 'ok';
    //                         $this->db_easyA->table('dd_task_id')->insert([
    //                             'lid' => $find_list['id'],
    //                             'aid' => $find_list['aid'],
    //                             'aname' => $find_list['aname'],
    //                             'title' => $find_list['title'],
    //                             'request_id' => $res['request_id'],
    //                             'task_id' => $res['task_id'],
    //                             'errmsg' => $res['errmsg'],
    //                             'createtime' => date('Y-m-d H:i:s'),
    //                         ]);

            
    //                         // 更新用户列表 task_id
    //                         $this->db_easyA->execute("
    //                             update dd_temp_excel_user_success
    //                             set 
    //                                 task_id = '{$res['task_id']}'
    //                             where 1
    //                                 AND uid = '{$find_list['uid']}'
    //                                 AND userid IN ({$update_uids})
    //                         ");
    //                     }
    //                 }
    //             }

    //             $updatetime = date('Y-m-d H:i:s');
    //             $sql_更新 = "
    //                 update dd_userimg_list
    //                 set 
    //                     sendtimes = sendtimes + 1,
    //                     sendtime = '{$updatetime}',
    //                     撤回时间 = NULL
    //                 where id = '{$input['id']}'
    //             ";
    //             $this->db_easyA->execute($sql_更新);

    //             return json(['code' => 0, 'msg' => '执行成功']);
    //         } else {
    //             return json(['code' => 0, 'msg' => '信息有误，执行失败']);
    //         }
    //     } else {
    //         return json(['code' => 0, 'msg' => '请勿非法请求']);
    //     }       
    // }

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
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }


            if ($find_list) {
                $select_user = $this->db_easyA->table('dd_weatherdisplay_list_user')->field('task_id')->where([
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
                    $res1 = $this->db_easyA->table('dd_weatherdisplay_list_user')->where(['task_id' => $val['task_id']])->update([
                        '撤回时间' => date('Y-m-d H:i:s'),
                    ]);
                    $res2 = $this->db_easyA->table('dd_weatherdisplay_list')->where(['id' => $input['id']])->update([
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

    // 撤回 单记录消息
    public function recallHandle() {
        $input = input();
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg

        if (request()->isAjax() && $input['id'] && session('admin.name')) {
        // if (1) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;
            
            // $input['id'] = 167;
            
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }


            if ($find_list) {

                $find_task_id = $this->db_easyA->table('dd_weatherdisplay_list_user')->where([
                    ['task_id', '=', $input['task_id']],
                    ['uid', '=', $find_list['uid']],
                ])->find();

                if ($find_task_id) {
                    $res = json_decode($model->recallMessage($find_task_id['task_id']), true);
                    // print_r($res);
                    $res1 = $this->db_easyA->table('dd_weatherdisplay_list_user')->where(['id' => $input['id'],'task_id' => $find_task_id['task_id']])->update([
                        '撤回时间' => date('Y-m-d H:i:s'),
                    ]);
    
                    return json(['code' => 0, 'msg' => '执行成功']);
                } else {
                    return json(['code' => 1, 'msg' => '执行失败']);
                }


            } else {
                return json(['code' => 2, 'msg' => '权限不足，只能操作自己创建的记录']);
            }
        } else {
            return json(['code' => 3, 'msg' => '请勿非法请求']);
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
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->where([
                    ['id', '=', $input['id']],
                ])->find();    
            }

            if ($find_list) {
                $select_user = $this->db_easyA->table('dd_weatherdisplay_list_user')->where([
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
                                        dd_weatherdisplay_list_user 
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
                                        dd_weatherdisplay_list_user 
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
                        dd_weatherdisplay_list as l
                    SET 
                        l.已读 = (select count(*) from dd_weatherdisplay_list_user where uid = '{$find_list['uid']}' and 已读='Y')
                    WHERE 1
                        AND l.id = {$find_list['id']}
                ");

                // 统计list展示的已读未读数
                $this->db_easyA->execute("
                    UPDATE 
                        dd_weatherdisplay_list as l
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

     // 拉取 已读 未读  自动更新24小时之内的记录
    public function getReads_auto() {
        // $find_list = $this->db_easyA->table('dd_userimg_list')->where([
        //     ['id', '=', $input['id']],
        // ])->find();
        $hour24 = date('Y-m-d H:i:s', strtotime('-1day', time()));
        $sql = "
            SELECT id FROM dd_userimg_list
            WHERE
                sendtime >= '{$hour24}'
                AND 撤回时间 is null
        ";
        $select = $this->db_easyA->query($sql);
        
        foreach ($select as $key => $val) {
            $this->getReadsHandle_auto_handle($val['id']);
        }
    }

    // 拉取 已读 未读  自动更新24小时之内的记录
    private function getReadsHandle_auto_handle($id = '') {
        $input = input();

        $model = new DingTalk;
        
        $find_list = $this->db_easyA->table('dd_userimg_list')->where([
            ['id', '=', $id],
        ])->find();

        if ($find_list) {


            return json(['code' => 0, 'msg' => '执行成功']);
        } else {
            return json(['code' => 0, 'msg' => '执行失败']);
        }  
    }

    public function test() {
        // $str = '田珊';
        // $str2 = ' 11田珊珊的工作号';
        // $pattern = "/{$str}/i";
        // echo preg_match($pattern, $str2);
        $model = new DingTalk;
        $path = 'http://im.babiboy.com/upload/dd_img/20230911/9b568b8758b29f6097327eb76ae51720_523.pdf';
        // 上传图 
        echo $media_id = $model->uploadDingFile($path, "test_" . time(). '.pdf');
        $res = json_decode($model->sendFileMsg('350364576037719254', 'test', $media_id), true);
    }

    // 下载 未读 
    public function downloadListUser() {
        $id = input('id');
        $find_list = $this->db_easyA->table('dd_weatherdisplay_list')->field('uid')->where([
            ['id', '=', $id],
        ])->find();

        $sql = "
            SELECT 
                店铺名称,陈列方案,name as 姓名,mobile as 手机,title as 职位,
                '否' as 已读
            FROM 
                dd_weatherdisplay_list_user   
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

        return Excel::exportData($select, $header, '气温陈列调整表未读名单_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载 钉钉 工作通知已读未读 
    public function downloadDduser() {
        $uid = input('uid');

        if (!checkAdmin()) {
            $CustomItem17 = $this->authInfo['name'];
            $map = "AND c.CustomItem17 = '{$CustomItem17}'";
        } else {
            $map = "";
        }

        $sql = "
            select 
                p.店铺名称,p.name as 姓名,p.mobile as 手机,p.title as 职位,c.State as 省份,'直营' as 性质, c.CustomItem17 as 专员 
            from dd_customer_push as p
            left join customer_pro as c on p.店铺名称 = c.CustomerName
            where 1 
                AND name not in ('陈威良', '王威')
                {$map}
            ORDER BY 省份
        ";
        
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '钉钉店铺负责人名单_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    // 下载 钉钉 工作通知已读未读 
    public function downloadAllDduser() {

        $sql = "
            select 
                u.店铺名称,u.name,u.title,u.mobile,c.State, c.CustomItem17, c.Mathod as 经营模式  
            from dd_user as u
            left join customer as c on u.店铺名称 = c.CustomerName
            where 1 
        ";
        
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '索歌全员钉钉信息_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

}
