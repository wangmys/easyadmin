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

    /**
     * @NodeAnotation(title="调价推送列表")
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
                    dd_tiaojia_list
                WHERE 1
                ORDER BY id desc
 
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM 
                        dd_tiaojia_list
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

    // 推送信息相关用户列表
    public function list_user() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');
            $id = $input['id'];
            $aid = $this->authInfo['id'];

            $find_list = $this->db_easyA->table('dd_tiaojia_list')->field('uid')->where(['id' => $id])->find();
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
                    dd_tiaojia_list_user   
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
                    dd_tiaojia_list_user   
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

    // 上传货号找店铺
    public function upload_excel1() {
        if (request()->isAjax() || $this->debug) {
        // if (1) {
            if ($this->debug) {
            // 静态测试
                $info = app()->getRootPath() . 'public/upload/dd_tiaojia/'.date('Ymd',time()).'/调价上传模板.xlsx';   //文件保存路径
            } else {
                $file = request()->file('file');  //这里‘file’是你提交时的name
                $file->getOriginalName();
                $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
                $save_path = app()->getRootPath() . 'public/upload/dd_tiaojia/' . date('Ymd',time()).'/';   //文件保存路径
                $info = $file->move($save_path, $new_name);
            }

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '货号',
                    'B' => '调价',
                    'C' => '调价时间范围',

                ];
                
                //读取数据
                $data = $this->readExcel_temp_excel($info, $read_column);

                // echo 111;
                // echo '<pre>';
                // dump($data);
                if ($data) {
                    $model = new DingTalk;
                    $sucess_data = [];
                    $error_data = [];
                    $uid = rand_code(8);
                    $time = date('Y-m-d H:i:s');

                    $货号str = ''; 
                    foreach ($data as $key => $val) {
                        $data[$key]['uid'] = $uid;
                        // $data[$key]['aid'] = $this->authInfo['id'];
                        // $data[$key]['aname'] = $this->authInfo['name'];
                        // $data[$key]['店铺名称'] = @$val['店铺名称'];
                        // $data[$key]['姓名'] = @$val['姓名'];
                        // $data[$key]['手机'] = @$val['手机'];
                        $data[$key]['createtime'] = $time;

                        if ($key + 1 == count($data)) {
                            $货号str .= "'" . $val['货号'] . "'";
                        } else {
                            $货号str .= "'" . $val['货号'] . "',";
                        }
                    }


                    // 删除临时excel表该用户上传的记录
                    $chunk_list = array_chunk($data, 500);
                    // dump($chunk_list);
                    // $this->db_easyA->startTrans();
                    $res = false;
                    foreach($chunk_list as $key => $val) {
                        $res = $this->db_easyA->table('dd_tiaojia_temp')->strict(false)->insertAll($val);
                        if (!$res) {
                            break;
                        }
                    }

                    if ($res) {
                        // 顺序不能变
                        $this->getCustomer($uid, $货号str);
                        $推送总数 = $this->db_easyA->query("
                            SELECT count(*) as total FROM `dd_tiaojia_list_user` where uid='{$uid}' 
                        ");
                        $res = $this->db_easyA->table('dd_tiaojia_list')->insert([
                            'aid' => session('admin.id'),
                            'aname' => session('admin.name'),
                            'uid' => $uid,
                            'createtime' => $time,
                            '总数' => $推送总数[0]['total']
                        ]);
                    }
                    
                    return json(['code' => 0, 'msg' => "名单上传成功", 'data' => []]);
                }
                
            } else {
                echo '没数据';
            }
        }   
    }

    // 上传指定店铺货号 上传文件2的情况
    public function upload_excel2() {
        if (request()->isAjax() || $this->debug) {
        // if (1) {
            if ($this->debug) {
            // 静态测试
                $info = app()->getRootPath() . 'public/upload/dd_tiaojia/'.date('Ymd',time()).'/调价指点店铺样式推送.xlsx';   //文件保存路径
            } else {
                $file = request()->file('file');  //这里‘file’是你提交时的name
                $file->getOriginalName();
                $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
                $save_path = app()->getRootPath() . 'public/upload/dd_tiaojia/' . date('Ymd',time()).'/';   //文件保存路径
                $info = $file->move($save_path, $new_name);
            }

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '店铺名称',
                    'B' => '货号',
                    'C' => '调价',
                    'D' => '调价时间范围',

                ];
                
                //读取数据
                $data = $this->readExcel_temp_excel($info, $read_column);

                // echo 111;
                // echo '<pre>';
                // print_r($data);

                // die;
                if ($data) {
                    $model = new DingTalk;
                    $sucess_data = [];
                    $error_data = [];
                    $uid = rand_code(8);
                    $time = date('Y-m-d H:i:s');

                    // $货号str = ''; 
                    foreach ($data as $key => $val) {
                        $data[$key]['uid'] = $uid;
                        // $data[$key]['aid'] = $this->authInfo['id'];
                        // $data[$key]['aname'] = $this->authInfo['name'];
                        // $data[$key]['店铺名称'] = @$val['店铺名称'];
                        // $data[$key]['姓名'] = @$val['姓名'];
                        // $data[$key]['手机'] = @$val['手机'];
                        $data[$key]['createtime'] = $time;

                        // if ($key + 1 == count($data)) {
                        //     $货号str .= "'" . $val['货号'] . "'";
                        // } else {
                        //     $货号str .= "'" . $val['货号'] . "',";
                        // }
                    }


                    // 删除临时excel表该用户上传的记录
                    $chunk_list = array_chunk($data, 500);
                    // dump($chunk_list);
                    // $this->db_easyA->startTrans();
                    $res = false;
                    foreach($chunk_list as $key => $val) {
                        $res = $this->db_easyA->table('dd_tiaojia_temp_2')->strict(false)->insertAll($val);
                        if (!$res) {
                            break;
                        }
                    }

                    

                    if ($res) {
                        $select_group = $this->db_easyA->table('dd_tiaojia_temp_2')->field("店铺名称")->where([
                            'uid' => $uid,
                        ])->group('店铺名称')->select();

                        foreach ($select_group as $kk => $vv) {
                            $select_temp = $this->db_easyA->table('dd_tiaojia_temp_2')->field("店铺名称,货号")->where([
                                'uid' => $uid,
                                '店铺名称' => $vv['店铺名称']
                            ])->select();
                            $货号str = "";
                            foreach ($select_temp as $kk1 => $vv1) {
                                if ($kk1 + 1 == count($select_temp)) {
                                    // 结束
                                    $货号str .= "'" . $vv1['货号'] . "'";
                                } else {
                                    $货号str .= "'" . $vv1['货号'] . "',";
                                }
                            }
                            $this->getCustomer2($uid, $vv['店铺名称'], $货号str);
                            
                        }   
                        // // 顺序不能变
                        // $this->getCustomer2($uid, $货号str);
                        $推送总数 = $this->db_easyA->query("
                            SELECT count(*) as total FROM `dd_tiaojia_list_user` where uid='{$uid}' 
                        ");
                        $res = $this->db_easyA->table('dd_tiaojia_list')->insert([
                            'aid' => session('admin.id'),
                            'aname' => session('admin.name'),
                            'uid' => $uid,
                            'createtime' => $time,
                            '总数' => $推送总数[0]['total']
                        ]);
                    }
                    
                    return json(['code' => 0, 'msg' => "名单上传成功", 'data' => []]);
                }
                
            } else {
                echo '没数据';
            }
        }   
    }

    public function test() {
        $总店铺数 = $this->db_easyA->query("
            SELECT 店铺名称 as total FROM `dd_tiaojia_customer_temp` where uid='68466592' group by 店铺名称
        ");
        // dump($总店铺数 );
        echo count($总店铺数);
    }

    // 更新调价模板店铺信息
    private function getCustomer($uid = "", $货号str = "") {
        if (empty($uid) || empty($货号str)) {
            return false;
        }
        // $uid = 6636;
        // $sql_temp = "
        //     select 货号 from dd_tiaojia_temp
        //     where id = '{$id}'
        // ";
        // $select_temp = $this->db_easyA->query($sql_temp);

        // $货号str = "";
        // foreach ($select_temp as $key => $val) {
        //     if ($key + 1 == count($select_temp)) {
        //         $货号str .= "'" . $val['货号'] . "'";
        //     } else {
        //         $货号str .= "'" . $val['货号'] . "',";
        //     }
        // }

        $time = date('Y-m-d H:i:s');
        // $货号str;
        $sql_店铺可用库存 = "
                SELECT 
                    '{$uid}' as uid,
                    '{$time}' as createtime,
                    T.CustomerName AS 店铺名称,
                    T.货号,
                    SUM(T.店铺库存) as 店铺库存,
                    SUM(T.在途库存) as 在途库存,
                    SUM(T.店铺库存) + SUM(T.在途库存) as 店铺可用库存
                FROM
                ( -- 店铺库存
                        SELECT 
                                EC.CustomerName,
                                EG.GoodsNo AS 货号,
                                SUM(ECSD.Quantity) AS 店铺库存,
                                0 as 在途库存
                        FROM ErpCustomerStock ECS 
                        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId
                        LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
                        LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                        WHERE EC.MathodId IN (4)
                        AND EC.ShutOut=0
                        AND EG.GoodsNo in ({$货号str})
                        GROUP BY 
                                EG.GoodsNo,
                                EC.CustomerName
                        HAVING SUM(ECSD.Quantity)!=0
                
                        UNION ALL
                        
                        -- 在途库存 			
                        SELECT 
                            m.CustomerName,
                            m.货号,
                            0 AS 店铺库存,
                            SUM(m.Quantity) as 在途库存 
                        FROM												
                        (--仓库发货在途
                            SELECT  
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EDGD.Quantity) AS Quantity
                            FROM ErpDelivery ED 
                            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
                            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
                            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
                            WHERE ED.CodingCodeText='已审结'
                                    AND ED.IsCompleted=0
                                    AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                                    
                            UNION ALL
                
                            --店铺调拨在途
                            SELECT 
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EIGD.Quantity) AS Quantity
                            FROM ErpCustOutbound EI 
                            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
                            LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
                            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
                            WHERE EI.CodingCodeText='已审结'
                                    AND EI.IsCompleted=0
                                    AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                        ) as m
                        GROUP BY 
                        m.CustomerName,
                        m.货号
                ) as t
                    GROUP BY 
                        T.CustomerName,
                        T.货号
        ";
        try {
            $select_店铺可用库存 = $this->db_sqlsrv->query($sql_店铺可用库存);
            if ($select_店铺可用库存) {
                // 删除历史数据
                $this->db_easyA->table('dd_tiaojia_customer_temp')->where(['uid' => $uid])->delete();
                // $this->db_easyA->execute('TRUNCATE dd_tiaojia_customer_temp;');
                $chunk_list = array_chunk($select_店铺可用库存, 500);
                // $this->db_easyA->startTrans();
    
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $insert = $this->db_easyA->table('dd_tiaojia_customer_temp')->strict(false)->insertAll($val);
                }

                $sql_删除店铺可用库存小于等于零 = "
                    delete from dd_tiaojia_customer_temp where uid='{$uid}' and `店铺可用库存` <= 0
                ";
                $this->db_easyA->execute($sql_删除店铺可用库存小于等于零);
    
                $sql_分组货号 = "
                     select
                        uid,货号
                    from dd_tiaojia_customer_temp
                    where uid='{$uid}'
                    group by 货号
                ";
                $select_分组货号 = $this->db_easyA->query($sql_分组货号);
                $分组货号str2 = "";
                foreach ($select_分组货号 as $key2 => $val2) {
                    if ($key2 + 1 == count($select_分组货号)) {
                        $分组货号str2 .= "'" . $val2['货号'] . "'";
                    } else {
                        $分组货号str2 .= "'" . $val2['货号'] . "',";
                    }
                }
                $sql_信息明细2 = "
                    SELECT 
                        '{$uid}' as uid,
                        EG.GoodsNo as 货号,
                        EG.GoodsId,
                        EG.CategoryName1 as 一级分类,
                        EG.CategoryName2 as 二级分类,
                        EG.CategoryName as 分类,
                        EGPT.UnitPrice as 零售价,
                        EGC.ColorDesc as 颜色,
                        CASE
                            WHEN EG.TimeCategoryName2 in ('初春','正春','春季') THEN '春季'
                            WHEN EG.TimeCategoryName2 in ('初夏','盛夏','夏季') THEN '夏季'	
                            WHEN EG.TimeCategoryName2 in ('初秋','深秋','秋季') THEN '秋季'
                            WHEN EG.TimeCategoryName2 in ('初冬','深冬','冬季') THEN '冬季'
                        END AS 季节归集,
                        EGI.Img
                    FROM ErpGoods AS EG
                    LEFT JOIN ErpGoodsColor AS EGC ON EG.GoodsId = EGC.GoodsId
                    LEFT JOIN ErpGoodsPriceType AS EGPT ON EG.GoodsId = EGPT.GoodsId AND EGPT.PriceId = 1
                    LEFT JOIN ErpGoodsImg AS EGI ON EG.GoodsId = EGI.GoodsId
                    where EG.GoodsNo in ($分组货号str2)
                ";
                $select_信息明细2 = $this->db_sqlsrv->query($sql_信息明细2);
                if ($select_信息明细2) {
                    $this->db_easyA->table('dd_tiaojia_goods_info')->where(['uid' => $uid])->delete();
                    $chunk_list2 = array_chunk($select_信息明细2, 500);
                    foreach($chunk_list2 as $key3 => $val3) {
                        // 基础结果 
                        $insert = $this->db_easyA->table('dd_tiaojia_goods_info')->strict(false)->insertAll($val3);
                    }

                    $sql_调价_零售价_颜色_图片 = "
                        update dd_tiaojia_customer_temp as c 
                        left join dd_tiaojia_goods_info as i on c.uid = i.uid and c.货号 = i.货号
                        left join dd_tiaojia_temp as t on c.uid = t.uid and c.货号 = t.货号  
                        SET
                            c.调价 = t.调价,
                            c.调价时间范围 = t.调价时间范围,
                            c.零售价 = i.零售价,
                            c.颜色 = i.颜色,
                            c.Img = i.Img,
                            c.季节归集 = i.季节归集,
                            c.一级分类 = i.一级分类,
                            c.二级分类 = i.二级分类,
                            c.分类 = i.分类
                        where 1
                    ";
                    $this->db_easyA->execute($sql_调价_零售价_颜色_图片);

                    $sql_key = "
                        update 
                            dd_tiaojia_customer_temp
                        set 
                            `key` = case
                                when 一级分类 = '内搭' then 0
                                when 一级分类 = '外套' then 1
                                when 一级分类 = '下装' then 2
                                when 一级分类 = '鞋履' then 3
                                else 10
                            end
                    ";
                    $this->db_easyA->execute($sql_key);

                    // 分组排名
                    $sql_更新排名 = "
                        update dd_tiaojia_customer_temp as T1
                        LEFT JOIN (
                            SELECT
                                a.uid,
                                a.店铺名称,
                                a.货号,
                                a.零售价,
                                CASE
                                        WHEN 
                                                a.一级分类 = @一级分类 AND
                                                a.二级分类 = @二级分类 
                                        THEN
                                                @rank := @rank + 1 ELSE @rank := 1
                                END AS 排名,
                                @`一级分类` := a.`一级分类` AS `一级分类`,
                                @二级分类 := a.二级分类 AS 二级分类
                            FROM
                                    (select 
                                            uid,
                                            店铺名称,
                                            货号,
                                            一级分类,
                                            二级分类,
                                            零售价,
                                            `key`
                                    from dd_tiaojia_customer_temp
                                    where 1
                                            AND uid = '{$uid}'
                                    GROUP BY 店铺名称,一级分类,二级分类,货号
                                    ) as a,
                                    ( SELECT @一级分类 := null,  @二级分类 := null,  @rank := 0 ) as T
                            WHERE
                                1
                            ORDER BY
                                    a.店铺名称,`key`, a.二级分类 ASC, a.零售价 ASC
                        ) AS T2 ON T1.uid = T2.uid AND T1.店铺名称=T2.店铺名称 AND T1.货号=T2.货号
                        SET
                            T1.分组排名=T2.排名
                    ";
                    $this->db_easyA->execute($sql_更新排名);

                    $aid = session('admin.id');
                    $aname = session('admin.name');
                    $sql_list_user = "
                        SELECT 
                            '{$aid}' as aid,
                            '{$aname}' as aname,
                            p.店铺名称,
                            p.name,
                            p.mobile,
                            p.title,
                            p.userid,
                            p.isCustomer,
                            p.userid,
                            t.uid,
                            t.Img as path,
                            concat('http://im.babiboy.com/admin/system.dingding.Tiaojia/res?uid=', t.uid, '&店铺名称=', p.店铺名称) as url
                        FROM
                            dd_customer_push as p
                        LEFT JOIN dd_tiaojia_customer_temp as t on  t.uid = '{$uid}' and p.店铺名称 = t.店铺名称
                        WHERE
                            p.店铺名称 = t.店铺名称
                        group by 
                            p.店铺名称

                    ";
                    $select_list_user = $this->db_easyA->query($sql_list_user);
                    if ($select_list_user) {
                        $chunk_list5 = array_chunk($select_list_user, 500);
                        foreach($chunk_list5 as $key5 => $val5) {
                            // 基础结果 
                            $insert = $this->db_easyA->table('dd_tiaojia_list_user')->strict(false)->insertAll($val5);
                        }
                    }
                }
                // return true;
            } else {
                // return false;
            }
        } catch (\Throwable $th) {
            throw $th;
            // return false;
        }
        
    }

    // 更新调价模板店铺信息 针对上传2的模板
    private function getCustomer2($uid = "",$店铺名称="", $货号str = "") {
        if (empty($uid) || empty($店铺名称) || empty($货号str)) {
            return false;
        }
        // echo $uid;
        // echo '<br>';
        // echo  $店铺名称;
        // echo '<br>';
        // echo $货号str;
        // echo '---------------<br>';

        
        $time = date('Y-m-d H:i:s');
        // $货号str;
         $sql_店铺可用库存 = "
                SELECT 
                    '{$uid}' as uid,
                    '{$time}' as createtime,
                    T.CustomerName AS 店铺名称,
                    T.货号,
                    SUM(T.店铺库存) as 店铺库存,
                    SUM(T.在途库存) as 在途库存,
                    SUM(T.店铺库存) + SUM(T.在途库存) as 店铺可用库存
                FROM
                ( 
                    -- 店铺库存
                        SELECT 
                                EC.CustomerName,
                                EG.GoodsNo AS 货号,
                                SUM(ECSD.Quantity) AS 店铺库存,
                                0 as 在途库存
                        FROM ErpCustomerStock ECS 
                        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId
                        LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
                        LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                        WHERE EC.MathodId IN (4)
                        AND EC.ShutOut=0
                        AND EG.GoodsNo in ({$货号str})
                        AND EC.CustomerName in ('{$店铺名称}')
                        GROUP BY 
                                EG.GoodsNo,
                                EC.CustomerName
                        HAVING SUM(ECSD.Quantity)!=0
                
                        UNION ALL
                        
                        -- 在途库存 			
                        SELECT 
                            m.CustomerName,
                            m.货号,
                            0 AS 店铺库存,
                            SUM(m.Quantity) as 在途库存 
                        FROM												
                        (
                            --仓库发货在途
                            SELECT  
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EDGD.Quantity) AS Quantity
                            FROM ErpDelivery ED 
                            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
                            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
                            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
                            WHERE ED.CodingCodeText='已审结'
                                    AND ED.IsCompleted=0
                                    AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                                    AND EC.CustomerName in ('{$店铺名称}')
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                                    
                            UNION ALL
                
                            --店铺调拨在途
                            SELECT 
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EIGD.Quantity) AS Quantity
                            FROM ErpCustOutbound EI 
                            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
                            LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
                            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
                            WHERE EI.CodingCodeText='已审结'
                                    AND EI.IsCompleted=0
                                    AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                                    AND EC.CustomerName in ('{$店铺名称}')
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                        ) as m
                        GROUP BY 
                        m.CustomerName,
                        m.货号
                ) as t
                    GROUP BY 
                        T.CustomerName,
                        T.货号
        ";
        try {
            $select_店铺可用库存 = $this->db_sqlsrv->query($sql_店铺可用库存);
            if ($select_店铺可用库存) {
                // $this->db_easyA->execute('TRUNCATE dd_tiaojia_customer_temp;');
                $chunk_list = array_chunk($select_店铺可用库存, 500);
                // $this->db_easyA->startTrans();
    
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $insert = $this->db_easyA->table('dd_tiaojia_customer_temp')->strict(false)->insertAll($val);
                }

                $sql_删除店铺可用库存小于等于零 = "
                    delete from dd_tiaojia_customer_temp where uid='{$uid}' and `店铺可用库存` <= 0
                ";
                $this->db_easyA->execute($sql_删除店铺可用库存小于等于零);
    
                $sql_分组货号 = "
                        select
                        uid,货号
                    from dd_tiaojia_customer_temp
                    where uid='{$uid}'
                    group by 货号
                ";
                $select_分组货号 = $this->db_easyA->query($sql_分组货号);
                $分组货号str2 = "";
                foreach ($select_分组货号 as $key2 => $val2) {
                    if ($key2 + 1 == count($select_分组货号)) {
                        $分组货号str2 .= "'" . $val2['货号'] . "'";
                    } else {
                        $分组货号str2 .= "'" . $val2['货号'] . "',";
                    }
                }
                $sql_信息明细2 = "
                    SELECT 
                        '{$uid}' as uid,
                        EG.GoodsNo as 货号,
                        EG.GoodsId,
                        EG.CategoryName1 as 一级分类,
                        EG.CategoryName2 as 二级分类,
                        EG.CategoryName as 分类,
                        EGPT.UnitPrice as 零售价,
                        EGC.ColorDesc as 颜色,
                        CASE
                            WHEN EG.TimeCategoryName2 in ('初春','正春','春季') THEN '春季'
                            WHEN EG.TimeCategoryName2 in ('初夏','盛夏','夏季') THEN '夏季'	
                            WHEN EG.TimeCategoryName2 in ('初秋','深秋','秋季') THEN '秋季'
                            WHEN EG.TimeCategoryName2 in ('初冬','深冬','冬季') THEN '冬季'
                        END AS 季节归集,
                        EGI.Img
                    FROM ErpGoods AS EG
                    LEFT JOIN ErpGoodsColor AS EGC ON EG.GoodsId = EGC.GoodsId
                    LEFT JOIN ErpGoodsPriceType AS EGPT ON EG.GoodsId = EGPT.GoodsId AND EGPT.PriceId = 1
                    LEFT JOIN ErpGoodsImg AS EGI ON EG.GoodsId = EGI.GoodsId
                    where EG.GoodsNo in ($分组货号str2)
                ";
                $select_信息明细2 = $this->db_sqlsrv->query($sql_信息明细2);
                if ($select_信息明细2) {
                    $this->db_easyA->table('dd_tiaojia_goods_info')->where(['uid' => $uid])->delete();
                    $chunk_list2 = array_chunk($select_信息明细2, 500);
                    foreach($chunk_list2 as $key3 => $val3) {
                        // 基础结果 
                        $insert = $this->db_easyA->table('dd_tiaojia_goods_info')->strict(false)->insertAll($val3);
                    }

                    $sql_调价_零售价_颜色_图片 = "
                        update dd_tiaojia_customer_temp as c 
                        left join dd_tiaojia_goods_info as i on c.uid = i.uid and c.货号 = i.货号
                        left join dd_tiaojia_temp_2 as t on c.uid = t.uid and c.货号 = t.货号  
                        SET
                            c.调价 = t.调价,
                            c.调价时间范围 = t.调价时间范围,
                            c.零售价 = i.零售价,
                            c.颜色 = i.颜色,
                            c.Img = i.Img,
                            c.季节归集 = i.季节归集,
                            c.一级分类 = i.一级分类,
                            c.二级分类 = i.二级分类,
                            c.分类 = i.分类
                        where 1
                    ";
                    $this->db_easyA->execute($sql_调价_零售价_颜色_图片);

                    $sql_key = "
                        update 
                            dd_tiaojia_customer_temp
                        set 
                            `key` = case
                                when 一级分类 = '内搭' then 0
                                when 一级分类 = '外套' then 1
                                when 一级分类 = '下装' then 2
                                when 一级分类 = '鞋履' then 3
                                else 10
                            end
                    ";
                    $this->db_easyA->execute($sql_key);

                    // 分组排名
                    $sql_更新排名 = "
                        update dd_tiaojia_customer_temp as T1
                        LEFT JOIN (
                            SELECT
                                a.uid,
                                a.店铺名称,
                                a.货号,
                                a.零售价,
                                CASE
                                        WHEN 
                                                a.一级分类 = @一级分类 AND
                                                a.二级分类 = @二级分类 
                                        THEN
                                                @rank := @rank + 1 ELSE @rank := 1
                                END AS 排名,
                                @`一级分类` := a.`一级分类` AS `一级分类`,
                                @二级分类 := a.二级分类 AS 二级分类
                            FROM
                                    (select 
                                            uid,
                                            店铺名称,
                                            货号,
                                            一级分类,
                                            二级分类,
                                            零售价,
                                            `key`
                                    from dd_tiaojia_customer_temp
                                    where 1
                                            AND uid = '{$uid}'
                                    GROUP BY 店铺名称,一级分类,二级分类,货号
                                    ) as a,
                                    ( SELECT @一级分类 := null,  @二级分类 := null,  @rank := 0 ) as T
                            WHERE
                                1
                            ORDER BY
                                    a.店铺名称,`key`, a.二级分类 ASC, a.零售价 ASC
                        ) AS T2 ON T1.uid = T2.uid AND T1.店铺名称=T2.店铺名称 AND T1.货号=T2.货号
                        SET
                            T1.分组排名=T2.排名
                    ";
                    $this->db_easyA->execute($sql_更新排名);

                    $aid = session('admin.id');
                    $aname = session('admin.name');
                    $sql_list_user = "
                        SELECT 
                            '{$aid}' as aid,
                            '{$aname}' as aname,
                            p.店铺名称,
                            p.name,
                            p.mobile,
                            p.title,
                            p.userid,
                            p.isCustomer,
                            p.userid,
                            t.uid,
                            t.Img as path,
                            concat('http://im.babiboy.com/admin/system.dingding.Tiaojia/res?uid=', t.uid, '&店铺名称=', p.店铺名称) as url
                        FROM
                            dd_customer_push as p
                        LEFT JOIN dd_tiaojia_customer_temp as t on  t.uid = '{$uid}' and p.店铺名称 = t.店铺名称
                        WHERE
                            p.店铺名称 = t.店铺名称
                            AND p.店铺名称 = '{$店铺名称}'
                        group by 
                            p.店铺名称

                    ";
                    $select_list_user = $this->db_easyA->query($sql_list_user);
                    if ($select_list_user) {
                        $chunk_list5 = array_chunk($select_list_user, 500);
                        foreach($chunk_list5 as $key5 => $val5) {
                            // 基础结果 
                            $insert = $this->db_easyA->table('dd_tiaojia_list_user')->strict(false)->insertAll($val5);
                        }
                    }
                }
                // return true;
            } else {
                // return false;
            }
        } catch (\Throwable $th) {
            throw $th;
            // return false;
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
    public function readExcel_temp_excel($file_path = '/', $read_column = array())
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

    public function picker() {
        return View('picker', [
            
        ]);
     
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
            if (! checkAdmin()) {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                    ['aid', '=', $this->authInfo['id']],
                    ['总数', '>', 0],
                ])->find();
            } else {
                $find_list = $this->db_easyA->table('dd_tiaojia_list')->where([
                    ['id', '=', $input['id']],
                    ['总数', '>', 0],
                ])->find();    
            }


            // print_r($find_list);die;

            if ($find_list) {
                // 测试推送这里修改条件
                $select_user = $this->db_easyA->query("
                    select * from dd_tiaojia_list_user
                    where 
                        uid = '{$find_list['uid']}'
                    -- limit 10
                ");

                // dump($select_user);
                // die;
                // dump($select_user);

                // 遍历分组
                foreach ($select_user as $k1 => $v1) {
                    // dump($select_user);
                    // die;

                    $data['店铺名称'] = $v1['店铺名称'];
                    $data['userid'] = $v1['userid'];
                    $data['path'] = $v1['path'];
                    $data['url'] = $v1['url'];
                    // dump($data);die;
                    $res = json_decode($model->sendLinkMsg($data), true);
                                    
                    if ($res) {
                        // 更新用户列表 task_id
                        $this->db_easyA->execute("
                            update dd_tiaojia_list_user
                            set 
                                task_id = '{$res['task_id']}',
                                sendtime = '{$date}'
                            where 1
                                AND id = '{$v1['id']}'
                        ");
                    }

                }
    
                $this->db_easyA->execute("
                    update dd_tiaojia_list
                    set 
                        sendtime = '{$date}',
                        sendtimes = sendtimes + 1,
                        撤回时间 = NULL
                    where 1
                        AND id = '{$input['id']}'
                ");
                return json(['code' => 0, 'msg' => '执行成功']);
            } else {
                return json(['code' => 1, 'msg' => '数据不存在/权限不足，无法操作']);
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
}
