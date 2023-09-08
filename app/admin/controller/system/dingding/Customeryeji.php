<?php
namespace app\admin\controller\system\dingding;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 店铺业绩 直营
 * Class Weather
 * @package app\dingtalk
 * 
 * 1.获取数据源 http://www.easyadmin1.com/admin/system.dingding.Customeryeji/getData?date=2023-09-05
 * 2.生成店铺图 http://www.easyadmin1.com/admin/system.dingding.Customeryeji/getCustomer?date=2023-09-05
 */
class Customeryeji extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
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
    }

    // 更新店铺cid
    public function getData() {
        $date = input('date') ? input('date') : date('Y-m-d');
        $dataEnd = date('Y-m-d', strtotime('+1day', strtotime($date)));
 
        $sql_数据源 = "

            SELECT
                ER.CustomerName AS 店铺名称,
                EBC.Mathod AS 经营模式,
                EC.CustomItem36 AS 温区,
                SUM(ERG.Quantity) AS 数量,
                SUM(ERG.Quantity * ERG.DiscountPrice) AS 销售金额,
                CASE
                    WHEN EG.TimeCategoryName2 in ('初春','正春','春季') THEN '春季'
                    WHEN EG.TimeCategoryName2 in ('初夏','盛夏','夏季') THEN '夏季'	
                    WHEN EG.TimeCategoryName2 in ('初秋','深秋','秋季') THEN '秋季'
                    WHEN EG.TimeCategoryName2 in ('初冬','深冬','冬季') THEN '冬季'
                END AS 季节归集,
                EG.TimeCategoryName2 AS 季节,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期
            FROM
                ErpRetail AS ER
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            WHERE
                ER.CodingCodeText = '已审结'
                AND EG.CategoryName1 IN ('内搭', '外套', '下装')
                AND EBC.Mathod IN ('直营')
                AND ER.RetailDate between '{$date}' and '{$dataEnd}'
            GROUP BY ER.CustomerName,EBC.Mathod,EC.CustomItem36,EG.CategoryName1,EG.CategoryName2,EG.TimeCategoryName2,FORMAT(ER.RetailDate, 'yyyy-MM-dd')
            ORDER BY ER.CustomerName  
        ";
        $select = $this->db_sqlsrv->query($sql_数据源);
        // dump($select);
        // die;
        if ($select) {
            // $this->db_easyA->execute('TRUNCATE dd_weather_customer;');
            $this->db_easyA->table('dd_customer_yeji_data')->where([
                ['销售日期', '=', $date]
            ])->delete();

            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('dd_customer_yeji_data')->strict(false)->insertAll($val);
            }

            $sql_修正 = "
                UPDATE `dd_customer_yeji_data` 
                SET
                    季节修正 = 
                        case
                            when 季节归集 in ('春季', '秋季') then '春/秋季' else 季节归集
                        end,
                    二级分类修正 = 
                        case
                            when 二级分类 in ('短T', '长T', '套装') then 'T恤'
                            when 二级分类 in ('卫衣') then '卫衣'
                            when 二级分类 in ('休闲短衬','休闲长衬', '正统长衬', '正统短衬') then '衬衫'
                            when 二级分类 in ('针织衫', '羊毛衫') then '毛衣'
                            when 二级分类 in ('单西') then '单西'
                            when 二级分类 in ('夹克') then '夹克'
                            when 二级分类 in ('皮衣') then '皮衣'
                            when 二级分类 in ('牛仔短裤', '松紧短裤') then '短裤'
                            when 二级分类 in ('牛仔长裤') then '牛仔'
                            when 二级分类 in ('松紧长裤') then '松紧'
                            when 二级分类 in ('休闲长裤') then '休闲'
                            when 二级分类 in ('羽绒服') then '羽绒服'
                        end
                    WHERE 1
                        AND 销售日期 = '{$date}'
            ";
            $this->db_easyA->query($sql_修正);


            $sql_更新计算源 = "
                SELECT
                    店铺名称,温区,
                    sum(数量) AS 数量,
                    sum(销售金额) AS 销售金额,
                    季节修正,
                    一级分类,
                    二级分类修正,
                    CONCAT(一级分类,'/',二级分类修正) AS 品类,
                    销售日期
                FROM
                    `dd_customer_yeji_data` 
                WHERE 1
                    AND 二级分类修正 IS NOT NULL
                    AND 销售日期 = '{$date}'
                GROUP BY 店铺名称, 季节修正,一级分类,二级分类修正
                ORDER BY 店铺名称
            ";
            $select2 = $this->db_easyA->query($sql_更新计算源);
            $this->db_easyA->table('dd_customer_yeji')->where([
                ['销售日期', '=', $date]
            ])->delete();

            $chunk_list2 = array_chunk($select2, 500);
            foreach($chunk_list2 as $key => $val) {
                $this->db_easyA->table('dd_customer_yeji')->strict(false)->insertAll($val);
            }

            $this->db_easyA->table('dd_customer_yeji_avg')->where([
                ['销售日期', '=', $date]
            ])->delete();
            $sql_更新同区平均值 = "
                SELECT
                    '同区平均' as 店铺名称,温区,
                    round(avg(数量), 0) 数量,
                    季节修正,
                    一级分类,二级分类修正,品类,销售日期 
                FROM
                    `dd_customer_yeji` 
                WHERE 1
                    AND 销售日期 = '{$date}'
                GROUP BY
                    温区,季节修正,一级分类,二级分类修正
            ";
            $select3 = $this->db_easyA->query($sql_更新同区平均值);

            $chunk_list3 = array_chunk($select3, 500);
            foreach($chunk_list3 as $key => $val) {
                $this->db_easyA->table('dd_customer_yeji_avg')->strict(false)->insertAll($val);
            }

            $sql_更新店铺上装总计 = "
                SELECT
                    '上装总计' AS 品类,
                    店铺名称,
                    温区,
                    季节修正,
                    sum(数量) AS 数量,
                    销售日期 
                FROM
                    `dd_customer_yeji_data` 
                WHERE 1
                    AND 一级分类 IN ('外套', '内搭')
                    AND 销售日期 = '{$date}'
                GROUP BY 店铺名称,季节修正
            ";
            $select4 = $this->db_easyA->query($sql_更新店铺上装总计);

            $chunk_list4 = array_chunk($select4, 500);
            foreach($chunk_list4 as $key => $val) {
                $this->db_easyA->table('dd_customer_yeji')->strict(false)->insertAll($val);
            }


            $sql_更新区平均上装总计 = "
                select
                    '同区平均' as 店铺名称,
                    '上装总计' as 品类,
                    m.温区,m.季节修正,m.销售日期,
                    avg(m.数量) as 数量
                from
                (
                    select t.* from (
                    SELECT
                        店铺名称,温区,季节修正,销售日期,
                        sum(数量) as 数量
                    FROM
                        dd_customer_yeji_data 
                    WHERE
                        销售日期 = '{$date}' 
                        AND 一级分类 IN ( '外套', '内搭' ) 
                    GROUP BY 温区,店铺名称,季节修正
                    ) as t
                    GROUP BY 店铺名称,季节修正
                ) as m
                GROUP BY 温区,季节修正
            ";
            $select5 = $this->db_easyA->query($sql_更新区平均上装总计);

            $chunk_list5 = array_chunk($select5, 500);
            foreach($chunk_list5 as $key => $val) {
                $this->db_easyA->table('dd_customer_yeji_avg')->strict(false)->insertAll($val);
            }

        }
    }

    // 单店整理
    public function getCustomer() {
        $date = input('date') ? input('date') : date('Y-m-d');
        $select_customer = $this->db_easyA->table('dd_customer_yeji')->field('店铺名称,温区')->where([
            ['销售日期', '=', $date],
            // ['店铺名称', '=', '南宁二店'],
        ])->group('店铺名称')->select()->toArray();

        // dump($select_customer);die;

        foreach ($select_customer as $k => $v) {
            $sql = "
                SELECT
                    avg.品类,
                    c.店铺名称,c.温区,c.数量,
                    avg.季节修正,  
                    avg.数量 as 平均数
                FROM
                    dd_customer_yeji_avg AS avg
                LEFT JOIN 
                    dd_customer_yeji AS c ON  avg.销售日期 = c.销售日期 AND  avg.温区 = c.温区 AND avg.季节修正 = c.季节修正 AND avg.品类 = c.品类 
                    AND c.店铺名称 = '{$v["店铺名称"]}'
                WHERE 1
                    AND avg.店铺名称 = '同区平均'
                    AND avg.温区 = '{$v["温区"]}'
                    AND avg.销售日期 = '{$date}'
                ORDER BY 
                    avg.季节修正,avg.一级分类,avg.二级分类修正           
            ";
            $select = $this->db_easyA->query($sql);
            
            $业绩_春秋季 =$this->db_easyA->table('dd_customer_yeji_data')->field('店铺名称,温区')->where([
                ['销售日期', '=', $date],
                ['店铺名称', '=', $v['店铺名称']],
                ['季节修正', '=', '春/秋季'],
            ])->sum('销售金额');

            $业绩_夏季 =$this->db_easyA->table('dd_customer_yeji_data')->field('店铺名称,温区')->where([
                ['销售日期', '=', $date],
                ['店铺名称', '=', $v['店铺名称']],
                ['季节修正', '=', '夏季'],
            ])->sum('销售金额');

            $业绩_冬季 =$this->db_easyA->table('dd_customer_yeji_data')->field('店铺名称,温区')->where([
                ['销售日期', '=', $date],
                ['店铺名称', '=', $v['店铺名称']],
                ['季节修正', '=', '冬季'],
            ])->sum('销售金额');

            $data = [
                [
                    '品类' => '上装总计',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '内搭/T恤',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '内搭/衬衫',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '内搭/卫衣',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '内搭/毛衣',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '外套/夹克',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '外套/皮衣',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '下装/短裤',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '下装/牛仔',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '下装/松紧',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '下装/休闲',
                    PHP_EOL.'春/秋季' => '',
                    '本店卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季' => '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '店铺业绩',
                    PHP_EOL.'春/秋季' => $业绩_春秋季 ? '￥' . $业绩_春秋季 : '',
                    '本店卖的数量'.PHP_EOL.'夏季' => $业绩_夏季 ? '￥' . $业绩_夏季 : '',
                    PHP_EOL.'冬季' => $业绩_冬季 ? '￥' . $业绩_冬季 : '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '业绩不含鞋子跟配件',
                    PHP_EOL.'冬季.' => '',
                ],
                [
                    '品类' => '业绩占比',
                    PHP_EOL.'春/秋季' => $业绩_春秋季 ? round($业绩_春秋季 / ($业绩_春秋季 + $业绩_夏季 + $业绩_冬季), 3) * 100 . '%'  : '',
                    '本店卖的数量'.PHP_EOL.'夏季' => $业绩_夏季 ? round($业绩_夏季 / ($业绩_春秋季 + $业绩_夏季 + $业绩_冬季), 3) * 100 . '%' : '',
                    PHP_EOL.'冬季' => $业绩_冬季 ? round($业绩_冬季 / ($业绩_春秋季 + $业绩_夏季 + $业绩_冬季), 3) * 100 . '%'  : '',
                    PHP_EOL.'春/秋季.' => '',
                    '同区平均卖的数量'.PHP_EOL.'夏季' => '',
                    PHP_EOL.'冬季.' => '',
                ]
            ];
            
            // dump($业绩_春秋季); $ 500 $
            // dump($业绩_夏季);
            // dump($业绩_冬季);
            foreach ($select as $key => $val) {
                foreach ($data as $key2 => $val2) {
                    if ($val['品类'] == $val2['品类']) {
                        if (!empty($val['数量'])) {
                            if ($val['季节修正'] == '夏季') {
                                $data[$key2]['本店卖的数量'.PHP_EOL."{$val['季节修正']}"] = $val['数量'];
                            } else {
                                $data[$key2][PHP_EOL."{$val['季节修正']}"] = $val['数量'];
                            }                            
                        }
                        if (!empty($val['平均数'])) {
                            if ($val['季节修正'] == '夏季') {
                                $data[$key2]['同区平均卖的数量'.PHP_EOL."{$val['季节修正']}"] = $val['平均数'];
                            } else {
                                $data[$key2][PHP_EOL."{$val['季节修正']}."] = $val['平均数'];
                            }  
                        }
                        // break;
                    }
                }
            }

            // echo '<pre>';
            // print_r($data); die;
            // dump($data);
            // echo $v['店铺名称'];die;

            // echo $data[0]['春/秋季'.PHP_EOL.'本店卖的数量'];

            // dump($data);die;

            if ($data) {
                $table_header = ['ID'];
                $table_header = array_merge($table_header, array_keys($data[0]));
                foreach ($table_header as $kk => $vv) {
                    $field_width[$kk] = 130;
                }
    
                $field_width[0] = 35;
                $field_width[1] = 100;
                $field_width[2] = 90;
                $field_width[3] = 150;
                $field_width[4] = 90;
                $field_width[5] = 90;
                $field_width[6] = 150;
                $field_width[7] = 90;
     
                $table_explain = [
                    // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                    0 => " ",
                ];
                //参数 $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
                $params = [
                    'row' => count($data),          //数据的行数
                    'file_name' => $v['店铺名称'] . '.jpg',      //保存的文件名
                    'title' => $v['店铺名称'] .'今日销售情况 [' . $date . ']',
                    'table_time' => date("Y-m-d H:i:s"),
                    'data' => $data,
                    'table_explain' => $table_explain,
                    'table_header' => $table_header,
                    'field_width' => $field_width,
                    'banben' => '',
                    'file_path' => app()->getRootPath() . 'public/upload/dd_customer_yeji/' . date('Ymd', time()) . '/'  //文件保存路径
                ];
    
                // 生成图片
                $this->create_image_bgcolor($params,
                    [
                        // '最低温~最高温' => 3,
                        // '去年日增长' => 4,
                        // '前年月增长' => 5,
                        // '去年月增长' => 6,
                    ]
                );
            }
        }
    }

    // // 图片生成1
    // public function weather_pic() {
    //     $select = $this->db_easyA->query("
    //         SELECT 
    //             店铺名称
    //         FROM
    //             dd_weather_customer
    //         where 1
    //             -- AND 店铺名称 in ('仁寿一店')
    //     ");

    //     foreach ($select as $key => $val) {
    //         $this->create_table_weather($val['店铺名称']);
    //     }
    // }

    // 图片生成2
    // protected function create_table_weather($customer = '', $date = '')
    // {
    //     $date = $date ?: date('Y-m-d', time());
        
    //     $sql = "
    //         SELECT 
    //             店铺名称,
    //             day4,
    //             day5,
    //             day6,
    //             day7,
    //             day8,
    //             day9,
    //             day10,
    //             day4_min,
    //             day5_min,
    //             day6_min,
    //             day7_min,
    //             day8_min,
    //             day9_min,
    //             day10_min,
    //             day0_max,
    //             day1_max,
    //             day2_max,
    //             day3_max,
    //             day4_max,
    //             day5_max,
    //             day6_max,
    //             day7_max,
    //             day8_max,
    //             day9_max,
    //             day10_max
    //         FROM
    //             dd_weather_customer
    //         where 店铺名称 in ('{$customer}')
    //     ";

    //     $select = $this->db_easyA->query($sql);

    //     $data = $select[0];


    //     // echo '<pre>';
    //     // print_r($data);
        
    //     $weather_data = [];
    //     $weather_data[0]['日期'] = $data['day4'];
    //     $weather_data[0]['星期'] = date_to_week3($data['day4']);
    //     $weather_data[0]['最低温~最高温']= $data['day4_min'] . '~' . $data['day4_max']; 
    //     $weather_data[1]['日期'] = $data['day5'];
    //     $weather_data[1]['星期'] = date_to_week3($data['day5']);
    //     $weather_data[1]['最低温~最高温']= $data['day5_min'] . '~' . $data['day5_max']; 
    //     $weather_data[2]['日期'] = $data['day6'];
    //     $weather_data[2]['星期'] = date_to_week3($data['day6']);
    //     $weather_data[2]['最低温~最高温']= $data['day6_min'] . '~' . $data['day6_max']; 
    //     $weather_data[3]['日期'] = $data['day7'];
    //     $weather_data[3]['星期'] = date_to_week3($data['day7']);
    //     $weather_data[3]['最低温~最高温']= $data['day7_min'] . '~' . $data['day7_max']; 
    //     $weather_data[4]['日期'] = $data['day8'];
    //     $weather_data[4]['星期'] = date_to_week3($data['day8']);
    //     $weather_data[4]['最低温~最高温']= $data['day8_min'] . '~' . $data['day8_max']; 
    //     $weather_data[5]['日期'] = $data['day9'];
    //     $weather_data[5]['星期'] = date_to_week3($data['day8']);
    //     $weather_data[5]['最低温~最高温']= $data['day9_min'] . '~' . $data['day9_max']; 
    //     $weather_data[6]['日期'] = $data['day10'];
    //     $weather_data[6]['星期'] = date_to_week3($data['day10']);
    //     $weather_data[6]['最低温~最高温']= $data['day10_min'] . '~' . $data['day10_max']; 

    //     $weather_data_对比 = [];
    //     $weather_data_对比[0]['日期'] = $data['day4'];
    //     $weather_data_对比[0]['最低温']= $data['day4_min']; 
    //     $weather_data_对比[0]['最高温']= $data['day4_max']; 
    //     $weather_data_对比[1]['日期'] = $data['day5'];
    //     $weather_data_对比[1]['最低温']= $data['day5_min']; 
    //     $weather_data_对比[1]['最高温']= $data['day5_max']; 

    //     $weather_data_对比[2]['日期'] = $data['day6'];
    //     $weather_data_对比[2]['最低温']= $data['day6_min']; 
    //     $weather_data_对比[2]['最高温']= $data['day6_max']; 

    //     $weather_data_对比[3]['日期'] = $data['day7'];
    //     $weather_data_对比[3]['最低温']= $data['day7_min']; 
    //     $weather_data_对比[3]['最高温']= $data['day7_max']; 
    //     $weather_data_对比[4]['日期'] = $data['day8'];
    //     $weather_data_对比[4]['最低温']= $data['day8_min']; 
    //     $weather_data_对比[4]['最高温']= $data['day8_max']; 
    //     $weather_data_对比[5]['日期'] = $data['day9'];
    //     $weather_data_对比[5]['最低温']= $data['day9_min']; 
    //     $weather_data_对比[5]['最高温']= $data['day9_max']; 
    //     $weather_data_对比[6]['日期'] = $data['day10'];
    //     $weather_data_对比[6]['最低温'] = $data['day10_min'];
    //     $weather_data_对比[6]['最高温'] = $data['day10_max'];


    //     foreach ($weather_data as $key => $val) {
    //         $weather_data[$key]['日期'] = date('m-d', strtotime($val['日期']));
    //     }

    //     foreach ($weather_data_对比 as $key => $val) {
    //         $weather_data_对比[$key]['日期'] = date('m-d', strtotime($val['日期']));
    //     }

    //     if ($weather_data) {

    //         $table_header = ['ID'];
    //         $table_header = array_merge($table_header, array_keys($weather_data[0]));
    //         foreach ($table_header as $v => $k) {
    //             $field_width[$v] = 150;
    //         }

    //         $field_width[0] = 35;
    //         $field_width[1] = 60;
    //         $field_width[2] = 60;
 
    //         $table_explain = [
    //             // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
    //             0 => " ",
    //         ];
    //         //参数 $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
    //         $params = [
    //             'row' => count($weather_data),          //数据的行数
    //             'file_name' => $data['店铺名称'] . '.jpg',      //保存的文件名
    //             'title' => $data['店铺名称'] . ' 未来七天天气',
    //             'table_time' => date("Y-m-d H:i:s"),
    //             'data' => $weather_data,
    //             'data_对比' => $weather_data_对比,
    //             'table_explain' => $table_explain,
    //             'table_header' => $table_header,
    //             'field_width' => $field_width,
    //             'banben' => '',
    //             'file_path' => app()->getRootPath() . 'public/upload/dd_weather/' . date('Ymd', time()) . '/'  //文件保存路径
    //         ];

    //         // 生成图片
    //         return $this->create_image_bgcolor($params,
    //             [
    //                 '最低温~最高温' => 3,
    //                 // '去年日增长' => 4,
    //                 // '前年月增长' => 5,
    //                 // '去年月增长' => 6,
    //             ]
    //         );
    //     }
    // }

    // 格子带背景色
    public function create_image_bgcolor($params, $set_bgcolor = [])
    {
        // echo '<pre>';
        // print_r($params);die;
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            // 'title_height' => 35, //报表名称高度
            'title_height' => 50, //报表名称高度
            'title_font_size' => 18, //报表名称字体大小
            'font_ulr' => app()->getRootPath() . '/public/Medium.ttf', //字体文件路径
            'text_size' => 13, //正文字体大小
            'row_hight' => 60, //每行数据行高
        ];

        $y1 = 36;
        $x2 = 1542;
        $y2 = 65;
        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'); //字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if (!empty($params['table_explain'])) {
            $base['title_height'] =   $base['title_height'] * count($params['table_explain']);
        }

        //计算图片总宽
        $w_sum = $base['border'];
        foreach ($params['field_width'] as $key => $value) {
            //图片总宽
            $w_sum += $value;
            //计算每一列的位置
            $base['column_x_arr'][$key] = $w_sum;
        }

        $base['img_width'] = $w_sum + $base['border'] * 2 - $base['border']; //图片宽度
        $base['img_height'] = ($params['row'] + 1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height']; //图片高度
        $border_top = $base['border'] + $base['title_height']; //表格顶部高度
        $border_bottom = $base['img_height'] - $base['border']; //表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']); //创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24, 98, 229); //设定图片背景色


        $yellow = imagecolorallocate($img, 238, 228, 0); //设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $text_coler2 = imagecolorallocate($img, 255, 255, 255); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $red2 = imagecolorallocate($img, 251, 89, 62); //设定图片背景色
        $blue1 = imagecolorallocate($img, 168, 203, 255); //设定图片背景色
        $blue2 = imagecolorallocate($img, 66, 182, 255); //设定图片背景色
        $yellow2 = imagecolorallocate($img, 250, 233, 84); //设定图片背景色
        $yellow3 = imagecolorallocate($img, 230, 244, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $green2 = imagecolorallocate($img, 75, 234, 32); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $gray = imagecolorallocate($img, 37, 240, 240); //设定图片背景色
        $littleblue = imagecolorallocate($img, 22, 172, 176); //设定图片背景色
        $orange = imagecolorallocate($img, 255, 192, 0); //设定图片背景色
        $purple = imagecolorallocate($img, 252, 213, 180); //设定图片背景色

        // 天气温度
        $color1 = imagecolorallocate($img, 22, 108, 221); //设定图片背景色
        $color2 = imagecolorallocate($img, 103, 184, 249); //设定图片背景色
        $color3 = imagecolorallocate($img, 248, 243, 162); //设定图片背景色
        $color4 = imagecolorallocate($img, 253, 206, 74); //设定图片背景色
        $color5 = imagecolorallocate($img, 241, 124, 0); //设定图片背景色
        $color6 = imagecolorallocate($img, 204, 41, 49); //设定图片背景色

        imagefill($img, 0, 0, $bg_color); //填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color); //画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color); //画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];


        $y1 = 52.5;
        $y2 = 110;
        // 1 统计上色
        foreach ($params['data'] as $key => $item) {
            if (isset($item['品类']) && $item['品类'] == '上装总计') {
                imagefilledrectangle($img, 3, $y1 + $base['row_hight'] * ($key + 1), $base['img_width'] - 3, $y2 + $base['row_hight'] * ($key + 1), $purple);
            } 
            if (isset($item['品类']) && $item['品类'] == '店铺业绩') {
                imagefilledrectangle($img, 3, $y1 + $base['row_hight'] * ($key + 1), $base['img_width'] - 3, $y2 + $base['row_hight'] * ($key + 1), $orange);
            } 
            if (isset($item['品类']) && $item['品类'] == '业绩占比') {
                imagefilledrectangle($img, 3, $y1 + $base['row_hight'] * ($key + 1), $base['img_width'] - 3, $y2 + $base['row_hight'] * ($key + 1), $yellow);
            } 
        }

        // 标题颜色特殊处理
        $s118_y1 = 52;
        // 直营
        $s118_x1 = $params['field_width'][0] + $params['field_width'][1];
        $s118_x2 = $s118_x1 +  $params['field_width'][2] + $params['field_width'][3] + $params['field_width'][4];
        // 加盟
        $s118_x3 = $s118_x2 + $params['field_width'][5] + $params['field_width'][6] + $params['field_width'][7];
        // 直营
        imagefilledrectangle($img, $s118_x1, $s118_y1, $s118_x2, $y2, $blue2);
        // 加盟
        imagefilledrectangle($img, $s118_x2, $s118_y1, $s118_x3, $y2, $blue1);


    
        foreach ($base['column_x_arr'] as $key => $x) {
            imageline($img, $x, $border_top, $x, $border_bottom, $border_coler); //画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $params['table_header'][$key]); //写入表头文字
            $sum += $params['field_width'][$key];
        }

        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0] / 2 - $first_len / 2 + $base['border'], $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $key + 1); //写入序号
            $sub = 0;
            $sum = $params['field_width'][0] + $base['border'];
            foreach ($item as $k => $value) {
                // dump($value);
                if (empty($value)) {
                    $value = '';
                }
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                // imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                $sum += $params['field_width'][$sub];
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $font_west, $params['title']); //imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0]; //右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7]; //左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path'])) //判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, 30, $xb, $font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if (!empty($params['table_explain'])) {
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20 + $a_hight, $yellow, $font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20 + $a_hight, $xb, $font_west, $params['banben']);
                $a_hight += 20;
            }
        }

        imagepng($img, $save_path); //输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        // echo '<img src="/' . $save_path . '"/>';
    }


    // 发送测试2
    public function sendDingImg() {
        $date = input('date') ? input('date') : date('Y-m-d');
        $model = new DingTalk;
        $select = $this->db_easyA->query("
            SELECT 
                *
            FROM
                dd_weather_user 
            where 1
                AND name in ('陈威良', '王威')
        ");

        // print_r($select);die;

        $datatime = date('Ymd');
        foreach ($select as $key => $val) {
            // 线上
            $path = "http://im.babiboy.com/upload/dd_customer_yeji/{$datatime}/{$val['店铺名称']}.jpg?v=" . time();

            // 本地
            // $path = "http://www.easyadmin1.com/upload/dd_customer_yeji/{$datatime}/{$val['店铺名称']}.jpg?v=" . time();

            $headers = get_headers($path);
            if(substr($headers[0], 9, 3) == 200){
                $res = $model->sendMarkdownImg_pro($val['userid'], "{$val['店铺名称']} 今日销售情况", $path);    
            } else {
                echo '图片不存在';
            }
        }
    }
}
