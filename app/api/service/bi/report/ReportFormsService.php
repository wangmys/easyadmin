<?php


namespace app\api\service\bi\report;

use app\admin\model\dress\Yinliu;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\App;
use think\facade\Db;
use think\cache\driver\Redis;

/**
 * 引流配饰数据拉取服务
 * Class AuthService
 * @package app\common\service
 */
class ReportFormsService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [];

    protected $code = 0;
    protected $msg = '';

    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';

    public function __construct()
    {
        $this->model = new Yinliu();
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    public function task($number)
    {
        switch ($number) {
            case 100:
                break;
        }
    }

    //2023 秋季货品销售报表
    public function create_table_s009()
    {
        $code = 'S009';
        $date = date('Y-m-d');
        $sql = "select 性质,风格,一级分类 as 大类,二级分类 as 中类,采购入库数,仓库库存,仓库可用库存,仓库库存成本,收仓在途,收仓在途成本,已配未发,最后一周销,昨天销,累计销售,累销成本,在途库存数量,店库存数量,合计库存数,合计库存数占比,合计库存成本,数量售罄率,成本售罄率,前四周销量,前三周销量,前两周销量,前一周销量,周转周 from autumn_report where 更新日期 = '$date'";
        $data = $this->db_bi->Query($sql);

        // echo '<pre>';
        // print_r($data); die;
        // $table_header = ['ID'];
        // $title = ['性质','风格','二级分类','采购入库数','累计销售','数量售罄率','合计库存数','合计库存成本','周转周','仓库库存','仓库可用库存',
        //     '仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累销成本','在途库存数量','店库存数量','前四周销量','前三周销量'
        //     ,'前两周销量','前一周销量','成本售罄率'];

        // $table_header = array_merge($table_header, $title);

        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));

        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 55;
        $field_width[3] = 45;
        $field_width[4] = 70;
        $field_width[5] = 90;
        $field_width[6] = 70;
        $field_width[9] = 70;
        $field_width[11] = 70;
        $field_width[12] = 82;
        $field_width[13] = 55;
        $field_width[14] = 70;
        $field_width[15] = 70;
        $field_width[17] = 82;
        $field_width[18] = 82;
        $field_width[19] = 120;
        $field_width[21] = 82;
        $field_width[22] = 82;
        $field_width[23] = 82;
        $field_width[24] = 82;
        $field_width[25] = 82;
        $field_width[26] = 82;
        $field_width[27] = 70;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "2023 秋季货品销售报表 [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '二级分类',
            'color'=>16711877,
            'field' => ['合计'],
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $this->create_image($params);
    }

    //2022 冬季货品销售报表
    public function create_table_s010()
    {
        $code = 'S010';
        $date = date('Y-m-d');
        $sql = "
            select 
                性质,
                风格,
                一级分类,
                二级分类,
                采购入库数,
                仓库库存,
                仓库可用库存,
                仓库库存成本,
                收仓在途,
                收仓在途成本,
                已配未发,
                最后一周销,
                昨天销,
                累计销售,
                累销成本,
                在途库存数量,
                店库存数量,
                合计库存数,
                合计库存数占比,
                合计库存成本,
                数量售罄率,
                成本售罄率,
                前四周销量,
                前三周销量,
                前两周销量,
                前一周销量,
                周转周 
            from winter_report 
            WHERE 1
                -- AND 更新日期 = '{$date}'
                AND 更新日期 = '2023-01-08'
            LIMIT 10    
            ";
        $data = $this->db_bi->Query($sql);
        echo '<pre>';
        print_r($data);die;

        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));

        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }

        $field_width[0] = 40;
        $field_width[1] = 80;
        $field_width[2] = 80;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $table_data= [];



        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today, 
        ];

        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "2023 冬季货品销售报表  [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '风格',
            'field' => '合计',
            'color'=>16711877,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $this->create_image($params);

    }

    public function arr_add_member($arr,$add,$members){
        foreach ($members as $k){
            if(isset($arr[$k])){
                $arr[$k] +=$add[$k];
            }else{
                $arr[$k]  =$add[$k];
            }
        }
        return $arr;
    }

    public function create_table_s015()
    {
        $code = 'S015';
        $date = date('Y-m-d');
        $sql = "select 风格,一级分类 AS 大类,二级分类 AS 中类,SUM(采购入库数) AS 采购入库数,SUM(仓库库存) AS 仓库库存,SUM(仓库可用库存) AS 仓库可用库存,SUM(仓库库存成本) AS 仓库库存成本,SUM(收仓在途) AS 收仓在途,SUM(收仓在途成本) AS 收仓在途成本,sum(已配未发) as 已配未发,sum(最后一周销) as 最后一周销,sum(昨天销) as 昨天销,sum(累计销售) as 累计销售,sum(累销成本) as 累销成本,sum(在途库存数量) as 在途库存数量,sum(店库存数量) as 店库存数量,sum(合计库存数) as 合计库存数,sum(合计库存成本) as 合计库存成本,sum(前四周销量) as 前四周销量,sum(前三周销量) as 前三周销量,sum(前两周销量) as 前两周销量,sum(前一周销量) as 前一周销量 from autumn_report where 更新日期 = '$date' and  一级分类 <> '合计' group by 风格,一级分类,二级分类  order by 风格,一级分类,二级分类";
        $data = $this->db_bi->Query($sql);
        $arr =  array_column($data, '合计库存数');
        $sum = array_sum($arr);
        $cols = array_column($data, '风格');
        $styles = array_values( array_unique($cols));

        $arr_counts =  array_count_values($cols);
        $arr = [];
        $all['风格'] = '总计';
        $all['大类'] = '';
        $all['中类'] = '合计';
        foreach ($data as $v=>$k){
            foreach ($styles as $sv=>$sk){
                if($k['风格'] === $sk){
                    $arr[$sv]['风格'] =$sk;
                    $arr[$sv]['大类'] =$sk.'合计';
                    $arr[$sv]['中类'] ='合计';
                    $arr[$sv] = $this->arr_add_member($arr[$sv],$k,['采购入库数','仓库库存','仓库可用库存','仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累计销售','累销成本','在途库存数量','店库存数量','合计库存数','合计库存成本','前四周销量','前三周销量','前两周销量','前一周销量']);
                }
            }

            $all = $this->arr_add_member($all,$k,['采购入库数','仓库库存','仓库可用库存','仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累计销售','累销成本','在途库存数量','店库存数量','合计库存数','合计库存成本','前四周销量','前三周销量','前两周销量','前一周销量']);
        }
        $ls[0] = $arr[0];

        array_splice($data,13,0,$ls);
        $ls[0] = $arr[1];
        $length = count($data);
        array_splice($data,$length,0,$ls);
        $ls[0] = $all;
        array_splice($data,$length+1,0,$ls);

        foreach ($data as $v=>$k){
            $data[$v]['合计库存占比'] =number_format(round( $k['合计库存数'] / $sum,4)*100,2).'%';
            if($k['累计销售']  + $k['合计库存数'] >0 ){
                $data[$v]['数量售罄率'] =  number_format(round( $k['累计销售'] /( $k['累计销售']  + $k['合计库存数']) ,4)*100,2).'%';
            }else{
                $data[$v]['数量售罄率'] = '';
            }
//            $data[$v]['数量售罄率'] =  number_format(round( $k['累计销售'] /( $k['累计销售']  + $k['合计库存数']) ,4)*100,2).'%';

            if( $k['累销成本']  + $k['合计库存成本'] > 0){
                $data[$v]['成本售罄率'] =  number_format(round( $k['累销成本'] /( $k['累销成本']  + $k['合计库存成本']) ,4)*100,2).'%';
            }else{
                $data[$v]['成本售罄率'] ='';
            }

            if($k['合计库存数'] > 0 &&$k['前一周销量'] >0){
                $data[$v]['周转周'] =  number_format(round( $k['合计库存数'] /( ($k['前一周销量'] +$k['前两周销量'] +$k['前三周销量'])/3) ,4),2);
            }else{
                $data[$v]['周转周'] = 0;
            }
        }
        $table_header = ['ID'];
        $title = ['风格','中类','采购入库数','累计销售','数量售罄率','合计库存数','合计库存成本','周转周','仓库库存','仓库可用库存',
            '仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累销成本','在途库存数量','店库存数量','前四周销量','前三周销量'
            ,'前两周销量','前一周销量','合计库存占比','成本售罄率'];

        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 30;
        $field_width[1] = 60;
        $field_width[2] = 80;
        $field_width[3] = 90;
        $field_width[4] = 80;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[8] = 80;
        $field_width[9] = 80;
        $field_width[12] = 80;
        $field_width[14] = 80;
        $field_width[15] = 90;
        $field_width[16] = 60;
        $field_width[17] = 80;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $table_data= [];
        foreach ($data as $V=>$k){
            $new = [
                '风格'=>$k['风格'],
                '中类'=>$k['中类'],
                '采购入库数'=>$k['采购入库数'],
                '累计销售'=>$k['累计销售'],
                '数量售罄率'=>$k['数量售罄率'],
                '合计库存数'=>$k['合计库存数'],
                '合计库存成本'=>$k['合计库存成本'],
                '周转周'=>$k['周转周'],
                '仓库库存'=>$k['仓库库存'],
                '仓库可用库存'=>$k['仓库可用库存'],
                '仓库库存成本'=>$k['仓库库存成本'],
                '收仓在途'=>$k['收仓在途'],
                '收仓在途成本'=>$k['收仓在途成本'],
                '已配未发'=>$k['已配未发'],
                '最后一周销'=>$k['最后一周销'],
                '昨天销'=>$k['昨天销'],
                '累销成本'=>$k['累销成本'],
                '在途库存数量'=>$k['在途库存数量'],
                '店库存数量'=>$k['店库存数量'],
                '前四周销量'=>$k['前四周销量'],
                '前三周销量'=>$k['前三周销量'],
                '前两周销量'=>$k['前两周销量'],
                '前一周销量'=>$k['前一周销量'],
                '合计库存占比'=>$k['合计库存占比'],
                '成本售罄率'=>$k['成本售罄率'],
            ];

            $table_data[]=$new;

        }

        // echo '<pre>';
        // print_r($table_data);die;

        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
        ];
        $params = [
            'code' => $code,
            'row' => count($table_data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "商品部-2023秋季货品零售汇总表 [". date("Y-m-d", strtotime("-1 day")) ."]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $table_data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '二级分类',
            'color'=>16711877,
            'field' => '合计',
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        // $res = table_pic_new($params);
        // halt($res);
        $this->create_image($params);
    }

    // 商品部直营春夏老店库存结构报表
    public function create_table_s016()
    {
        $code = 'S016';
        $date = date('Y-m-d');
        $sql = "select 
            性质,
            一级分类 as 大类,
            二级分类 as 中类,
            _2023春 as `2023春`,
            _2023夏 as `2023夏`,
            _2023汇总 as `2023汇总`,
            _2022春 as `2022春`,
            _2022夏 as `2022夏`,
            _2022汇总 as `2022汇总`,
            _2021春 as `2021春`,
            _2021夏 as `2021夏`,
            _2021汇总 as `2021汇总`,
            _2020春 as `2020春`,
            _2020夏 as `2020夏`,
            _2020汇总 as `2020汇总`,
            _2019春 as `2019春`,
            _2019夏 as `2019夏`,
            _2019汇总 as `2019汇总`,
            _2018春 as `2018春`,
            _2018夏 as `2018夏`,
            _2018汇总 as `2018汇总`,
            _2017春 as `2017春`,
            _2017夏 as `2017夏`,
            _2017汇总 as `2017汇总`,
            合计库存数
        from sp_zy_spring_and_summer_stock where 更新日期 = '$date'";
        $data = $this->db_bi->query($sql);
        
        // foreach ($data as $key => $val) {
        //     $data[$key]['省份'] = province2zi($val['省份']);
        // }
        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 70;
        }
        $field_width[0] = 35;
        $field_width[1] = 45;
        $field_width[2] = 45;
        $field_width[3] = 80;
        $field_width[25] = 90;
        // // $field_width[2] = 140;
        // $field_width[11] = 60;
        // $field_width[12] = 60;
        // $field_width[13] = 60;
        // $field_width[14] = 60;
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            //    0 => "昨天:".$week. " 去年昨天:".$last_year_week_today,
            //            1 => '[类型]：说明8 说明16 ',
            //            2 => '[类型]：说明8 说明16 ',
            0 => ' '
        ];
        //参数
        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' => $code . '.jpg',      //保存的文件名
            'title' => "商品部-直营春夏老品库存结构报表 [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '省份',
            'color' => 16711877,
            'field' => '合计',
            'banben' => '  图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd') . '/'  //文件保存路径
        ];
        $this->create_image($params);
    }

    public function create_table_s018()
    {
        $code = 'S018';
        $date = date('Y-m-d');
        $sql = "select 
            性质,
            一级分类 as 大类,
            二级分类 as 中类,
            _2023春 as `2023春`,
            _2023夏 as `2023夏`,
            _2023汇总 as `2023汇总`,
            _2022春 as `2022春`,
            _2022夏 as `2022夏`,
            _2022汇总 as `2022汇总`,
            _2021春 as `2021春`,
            _2021夏 as `2021夏`,
            _2021汇总 as `2021汇总`,
            _2020春 as `2020春`,
            _2020夏 as `2020夏`,
            _2020汇总 as `2020汇总`,
            _2019春 as `2019春`,
            _2019夏 as `2019夏`,
            _2019汇总 as `2019汇总`,
            _2018春 as `2018春`,
            _2018夏 as `2018夏`,
            _2018汇总 as `2018汇总`,
            _2017春 as `2017春`,
            _2017夏 as `2017夏`,
            _2017汇总 as `2017汇总`,
            合计库存数
        from sp_jm_spring_and_summer_stock where 更新日期 = '$date'";
        $data = $this->db_bi->Query($sql);

        // foreach ($data as $v=>$k){
        //     // unset($data[$v]['ID']);
        //     unset($data[$v]['更新日期']);
        // }
        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 65;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 45;
        $field_width[3] = 70;
        $field_width[4] = 70;
        $field_width[6] = 80;
        $field_width[9] = 80;
        $field_width[12] = 80;
        $field_width[15] = 80;
        $field_width[18] = 80;
        $field_width[21] = 80;
        $field_width[24] = 80;
        $field_width[25] = 90;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
//            1 => '[类型]：说明8 说明16 ',
//            2 => '[类型]：说明8 说明16 ',
        ];
        //参数
        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "商品部-加盟春夏老品库存结构报表 [". date("Y-m-d", strtotime("-1 day")) ."]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '一级分类',
            'color'=>16711877,
            'field' => '合计',
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $this->create_image($params);
    }   

    public function create_table_s023()
    {
        $code = 'S023';
        $date = date('Y-m-d');
        $sql = "select 一级分类,二级分类,前一天,前二天,前三天,前四天,前五天,前六天,前七天 from sp_category_proportion where 更新日期 = '$date'";
        $data = Db::connect("mysql2")->Query($sql);
        if ($data) {
            $table_header = ['行号'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 130;
            }
            foreach ($data as $v=>$teams){
                $fields = array_keys($teams);
                foreach ($fields as $vv=> $team){
                    $leng = strlen($team);
                    $field_width[$vv] = $leng*20;
                    if($data[$v][$team] === '0'){
                        $data[$v][$team] = '';
                    }
                }
            }
            $field_width[0] = 60;
            $field_width[1] = 80;
            $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
    //            1 => '[类型]：说明8 说明16 ',
    //            2 => '[类型]：说明8 说明16 ',
            ];
            //参数
            $params = [
                'row' => count($data),          //数据的行数
                'file_name' =>$code.'.jpg',      //保存的文件名
                'title' => "商品部-所有年份各品类销售占比 [" . date("Y-m-d", strtotime("-1 day")) . "]",
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'col' => '二级分类',
                'color'=>16711877,
                'field' => ['内搭汇总','下装汇总','外套汇总','鞋履汇总'],
                'banben' => '图片报表编号: '.$code,
                'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
            ];
            $this->create_table($params);
        } else {
            return false;
        }

    }

    // 0点30分
    public function create_table_s025()
    {
        $code = 'S025';
        $date = date('Y-m-d');
        $sql = "select 二级时间分类 as 季节,前一天,前二天,前三天,前四天,前五天,前六天,前七天 from sp_time_proportion where 更新日期 = '$date'";
        $data = Db::connect("mysql2")->query($sql);
        if ($data) {
            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 130;
            }
            foreach ($data as $v => $teams) {
                $fields = array_keys($teams);
                foreach ($fields as $vv => $team) {
                    $leng = strlen($team);
                    $field_width[$vv] = $leng * 20;
                    if ($data[$v][$team] === '0') {
                        $data[$v][$team] = '';
                    }
                }
            }
    
            // echo '<pre>';
            // print_r($data);die;
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 130;
            $field_width[3] = 130;
            $field_width[4] = 130;
            $field_width[5] = 130;
            $field_width[6] = 130;
            $field_width[7] = 130;
            $field_width[8] = 130;
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                0 => "昨天:" . $week . " 去年昨天:" . $last_year_week_today,
                //            1 => '[类型]：说明8 说明16 ',
                //            2 => '[类型]：说明8 说明16 ',
            ];
            //参数
            $params = [
                'row' => count($data),          //数据的行数
                'file_name' => $code . '.jpg',      //保存的文件名
                'title' => "商品部-各季节销售占比 [" . date("Y-m-d", strtotime("-1 day")) . "]",
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'col' => '二级时间分类',
                'color' => 16711877,
                'field' => ['通季', '下装汇总', '外套汇总', '鞋履汇总'],
                'banben' => '  图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd') . '/'  //文件保存路径
            ];
    
            // table_pic_new_1($params);
            $this->create_table($params);
        } else {
            return false;
        }

    }

    public function create_table_s030()
    {
        $code = 'S030';
        $sql = "
            SELECT
                CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' ELSE '总计' END AS 经营,
                ISNULL(EC.State,'合计') AS 省份,
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%春%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年春],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%夏%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年夏],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%秋%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年秋],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%冬%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年冬],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2022 AND EG.TimeCategoryName2 LIKE '%春%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2022年春],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2022 AND EG.TimeCategoryName2 LIKE '%夏%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2022年夏],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2022 AND EG.TimeCategoryName2 LIKE '%秋%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2022年秋],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2022 AND EG.TimeCategoryName2 LIKE '%冬%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2022年冬],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2022 AND EG.TimeCategoryName2 LIKE '%春%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品春],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2022 AND EG.TimeCategoryName2 LIKE '%夏%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品夏],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2022 AND EG.TimeCategoryName2 LIKE '%秋%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品秋],
                CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2022 AND EG.TimeCategoryName2 LIKE '%冬%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品冬]
            FROM ErpCustomer EC
            LEFT JOIN ErpRetail ER ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpGoods EG ON EG.GoodsId=ERG.GoodsId
            WHERE EC.MathodId IN (4,7)
            AND EG.CategoryName1 IN ('内搭','下装','外套','鞋履')
            AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','秋季','初秋','深秋','冬季','初冬','深冬')
            AND CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,GETDATE()-1,23)
            AND ER.CodingCodeText='已审结'
            GROUP BY ROLLUP
                (EC.MathodId,
                EC.State)
        ";
        $data = Db::connect("sqlsrv")->query($sql);
        foreach ($data as $key => $val) {
            $data[$key]['省份'] = province2zi($val['省份']);
        }
        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 75;
        }
        $field_width[0] = 35;
        $field_width[1] = 45;
        $field_width[2] = 45;
        // $field_width[1] = 100;
        // $field_width[2] = 140;
        $field_width[11] = 60;
        $field_width[12] = 60;
        $field_width[13] = 60;
        $field_width[14] = 60;
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            //    0 => "昨天:".$week. " 去年昨天:".$last_year_week_today,
            //            1 => '[类型]：说明8 说明16 ',
            //            2 => '[类型]：说明8 说明16 ',
            0 => ' '
        ];
        //参数
        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' => $code . '.jpg',      //保存的文件名
            'title' => "昨天各省各季节销售占比 [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '省份',
            'color' => 16711877,
            'field' => '合计',
            'banben' => '  图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd') . '/'  //文件保存路径
        ];
        $this->create_image($params);
    }

    public function create_table_s031()
    {
        $code = 'S031';
        $sql = "
        SELECT
            CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' ELSE '总计' END AS 经营,
            ISNULL(EC.State,'合计') AS 省份,
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%春%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年春],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%夏%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年夏],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%秋%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年秋],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1=2023 AND EG.TimeCategoryName2 LIKE '%冬%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [2023年冬],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2023 AND EG.TimeCategoryName2 LIKE '%春%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品春],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2023 AND EG.TimeCategoryName2 LIKE '%夏%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品夏],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2023 AND EG.TimeCategoryName2 LIKE '%秋%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品秋],
            CAST(CONVERT(DECIMAL(10,2),SUM(CASE WHEN EG.TimeCategoryName1<2023 AND EG.TimeCategoryName2 LIKE '%冬%' THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/SUM(ERG.Quantity*ERG.DiscountPrice)*100) as varchar) + '%' AS [旧品冬]

        FROM ErpCustomer EC
        LEFT JOIN ErpRetail ER ON ER.CustomerId=EC.CustomerId
        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
        LEFT JOIN ErpGoods EG ON EG.GoodsId=ERG.GoodsId
        WHERE EC.MathodId IN (4,7)
        AND EG.CategoryName1 IN ('内搭','下装','外套','鞋履')
        AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','秋季','初秋','深秋','冬季','初冬','深冬')
        AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-3,23) AND CONVERT(VARCHAR,GETDATE()-1,23)
        AND ER.CodingCodeText='已审结'
        GROUP BY ROLLUP
            (EC.MathodId,
            EC.State)
        ;";
        $data = Db::connect("sqlsrv")->query($sql);
        foreach ($data as $key => $val) {
            $data[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 80;
        }
        $field_width[0] = 35;
        $field_width[1] = 45;
        $field_width[2] = 45;
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => ' '
            //            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
            //            1 => '[类型]：说明8 说明16 ',
            //            2 => '[类型]：说明8 说明16 ',
        ];
        //参数
        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' => $code . '.jpg',      //保存的文件名
            'title' => "近三天各省各季节销售占比 [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '省份',
            'color' => 16711877,
            'field' => '合计',
            'banben' => '  图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd') . '/'  //文件保存路径
        ];
        $this->create_image($params);
    }

    public function create_table_s043()
    {
        $arr = ['广东省', '广西壮族自治区', '江西省', '湖北省', '湖南省', '贵州省'];
        $code = 'S043';
        $date_ = date('Y-m-d');
        $start = date("Y-m-d", strtotime("$date_ -1 day"));
        $data_date = date("Y-m-d", strtotime("-7 day"));
        // $data_date = date("Y-m-d", strtotime("-1 day"));
        $date_arr = getDateFromRange_m($data_date, $start);
        $date_arr = array_reverse($date_arr);
        $table_header = array_merge(['ID', '省份', '季节'], $date_arr);


        $res = Db::connect("mysql2")->table('sp_time_proportion_state')->where('更新日期', $date_)
            ->wherein('省份', $arr)
            // ->field('ID,省份,二级时间分类 as 季节,前一天,前二天,前三天,前四天,前五天,前六天,前七天')
            ->select()
            ->toArray();

        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['省份'] = province2zi($val['省份']);
            }
            // dump($res);die;
            foreach ($res as $v => $k) {
                unset($res[$v]['ID']);
                unset($res[$v]['更新日期']);
            }
    
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 135;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 45;
            //        $field_width[4] = 260;
            //        $field_width[7] = 260;
            $table_explain = [
                // 0 => "报表更新日期" . $data_date,
                0 => "报表更新日期" . date("Y-m-d", strtotime("-1 day")),
            ];
            $params = [
                'row' => count($res), //数据的行数
                'file_name' => $code . '.jpg',      //保存的文件名
                'title' => "各省7天季节占比（粤/桂/贵/鄂/湘/赣）",
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $res,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'col' => '省份',
                'color' => 16711877,
                'field' => [''],
                'banben' => '  图片报表编号: ' . $code,
                'last' => 'no',
                'file_path' => "./img/" . date('Ymd') . '/'  //文件保存路径
            ];
            $this->create_image($params);
        } else {
            return false;
        }
    }

    public function create_table_s045(){
        // $arr = '广东省','广西壮族自治区','江西省','湖北省','湖南省','贵州省';
        $code ='S045';
        $date_ = date('Y-m-d');
        $start = date("Y-m-d", strtotime( "$date_ -1 day"));
        $data_date = date("Y-m-d", strtotime( "-7 day"));
        $date_arr = getDateFromRange_m($data_date,$start);
        $date_arr = array_reverse($date_arr);
        $table_header = array_merge(['ID','省份','季节'],$date_arr);

        // $res = self::where('更新日期',$date_)
        //     ->whereNotIn('省份',$arr)
        //     ->where('ID','<>',0)
        //     ->select()
        //     ->toArray();
        $res = $this->db_bi->query("
            SELECT 
                left(省份, 2),
                二级时间分类 AS 季节,
                前一天,
                前二天,
                前三天,
                前四天,
                前五天,
                前六天,
                前七天
            FROM sp_time_proportion_state
            WHERE 
                省份 NOT IN ('广东省','广西壮族自治区','江西省','湖北省','湖南省','贵州省')
                AND ID <> 0
                AND 更新日期 = '{$date_}'
        ");    
        // foreach ($res as $v=>$k){
        //     unset($res[$v]['ID']);
        //     unset($res[$v]['更新日期']);
        // }
        // dump($res);die;

        foreach ($table_header as $v => $k) {
            $field_width[$v] = 140;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 40;
//        $field_width[4] = 260;
//        $field_width[7] = 260;
        $table_explain = [
            // 0 => "报表更新日期".$data_date,
            0 => ' '
        ];
        $params = [
            'code' => $code,
            'row'=>count($res), //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "其他省份7天季节占比 [" . date("Y-m-d", strtotime(date('Y-m-d') . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data'=>$res,
            'table_explain'=>$table_explain,
            'table_header'=>$table_header,
            'field_width'=>$field_width,
            'col' => '省份',
            'color'=>16711877,
            'field' =>[''],
            'banben' => '图片报表编号: '.$code,
            'last' => 'no',
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $this->create_image($params);
    }

    public function create_table_s101($code = 'S101', $date = '')
    {
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        switch ($code) {
            case 'S101':
                // $sql = "select 经营模式,省份,店铺名称,首单日期 as 开店日期,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('加盟','加盟合计')";
                $title = "加盟老店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';
                $jingyingmoshi = '【加盟】';
                $sql = "
                SELECT
                    省份,店铺名称,
                    前年对比今年昨日递增率 AS 前年日增长,
                    昨日递增率 AS 去年日增长,
                    前年对比今年累销递增率 AS 前年月增长,
                    累销递增率 AS 去年月增长,
                    前年同日 as 前年同日销额,
                    去年同日 as 去年同日销额,
                    昨天销量 as 昨天销额,
                    前年同月 as 前年同月销额,
                    去年同月 as 去年同月销额,
                    本月业绩 as 本月销额
                    from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('加盟','加盟合计')";
                break;
            default:
                $title = "直营老店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';
                $jingyingmoshi = '【直营】';
                // $sql = "select 经营模式,省份,店铺名称,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,
                // 昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,
                // 累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
                $sql = "select
                    省份,店铺名称,
                    前年对比今年昨日递增率 AS 前年日增长,
                    昨日递增率 AS 去年日增长,
                    前年对比今年累销递增率 AS 前年月增长,
                    累销递增率 AS 去年月增长,
                    前年同日 as 前年同日销额,
                    去年同日 as 去年同日销额,
                    昨天销量 AS 昨天销额,
                    前年同月 as 前年同月销额,
                    去年同月 AS 去年同月销额,
                    本月业绩 as 本月销额
                    from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
                break;
        }
        $data = Db::connect("mysql2")->query($sql);
        if ($data) {
            // echo '<pre>';
            // print_r($data);
            foreach ($data as $key => $val) {
                $data[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 130;
            }

            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 90;
            $field_width[3] = 90;
            $field_width[4] = 90;
            $field_width[5] = 90;
            $field_width[6] = 90;
            $field_width[7] = 100;
            $field_width[8] = 100;
            $field_width[9] = 90;
            $field_width[10] = 100;
            $field_width[11] = 100;
            $field_width[12] = 90;
            // $field_width[13] = 150;
            // $field_width[14] = 120;
            // $field_width[15] = 90;

            // $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空

            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "{$jingyingmoshi} 今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
            //参数
            $params = [
                'row' => count($data),          //数据的行数
                'file_name' =>  $code . $dingName . '.jpg',      //保存的文件名
                'title' => $title,
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // return $this->create_image_bgcolor($params, [
            //     '前年日增长' => 3,
            //     '去年日增长' => 4,
            //     '前年月增长' => 5,
            //     '去年月增长' => 6,
            // ]);
                // 生成图片
                return $this->create_image_bgcolor($params,
                [
                    // '前年日增长' => 3,
                    // '去年日增长' => 4,
                    // '前年月增长' => 5,
                    // '去年月增长' => 6,
                ]
            );
        }
    }

    public function create_table_s102($date = '')
    {
        // 编号
        $code = 'S102';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,省份,前年同日,去年同日,
        // 昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,
        // 去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,
        // 累销递增金额差 from old_customer_state_2 where 更新时间 = '$date'";
        $sql = "select
            省份,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额
            from old_customer_state_2 where 更新时间 = '$date'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
    
            $field_width[6] = 100;
            $field_width[7] = 100;
            $field_width[9] = 100;
            $field_width[10] = 100;
    
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                'file_name' => $code . $dingName  .'.jpg',   //保存的文件名
                'title' =>  "省份老店业绩同比 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // 防止多次创建
            //        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
            //        if(file_exists($file)){
            //            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
            //        }
            // 生成图片
            return $this->create_image_bgcolor($params, [
                // '前年日增长' => 2,
                // '去年日增长' => 3,
                // '前年月增长' => 4,
                // '去年月增长' => 5,
            ]);
        }
    }

    public function create_table_s103($date = '')
    {
        // 编号
        $code = 'S103';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $sql = "select
            经营模式 as 经营,
            省份,

            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额

            from old_customer_state  where 更新时间 = '$date'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 45;
            $field_width[7] = 100;
            $field_width[8] = 100;
    
    
            $field_width[10] = 100;
            $field_width[11] = 100;
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . $dingName . '.jpg',   //保存的文件名
                'title' => "省份老店业绩同比-分经营模式 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // 防止多次创建
            //        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
            //        if(file_exists($file)){
            //            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
            //        }
            // 生成图片
            return $this->create_image_bgcolor($params, [
                // '前年日增长' => 3,
                // '去年日增长' => 4,
                // '前年月增长' => 5,
                // '去年月增长' => 6,
            ]);
        }
    }

    // 加盟
    public function create_table_s103B($date = '')
    {
        // 编号
        $code = 'S103B';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $sql = "select
            省份,
            两年以上老店数 AS 前年店数,
            店铺数 AS 去年店数,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额,
            前年累销递增金额差,
            累销递增金额差
            from old_customer_state  where 更新时间 = '$date' and 经营模式='加盟'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 75;
            $field_width[3] = 75;
            $field_width[4] = 100;
            $field_width[9] = 100;
    
    
            $field_width[11] = 100;
            $field_width[12] = 100;
            $field_width[14] = 150;
            $field_width[15] = 120;
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "【加盟】 今日:" . $week . "  .  去年今日:" . $last_year_week_today . "  .  前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                'file_name' => $code . $dingName . '.jpg',   //保存的文件名
                'title' => "省份老店业绩同比-加盟 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        }
    }

    // 配饰每日销售数量
    public function create_table_s105($date = '')
    {
        // 编号
        $code = 'S105';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $to_1 = date('Y-m-d');
        $to_2 = date('Y-m-d', strtotime('-1day'));
        $to_3 = date('Y-m-d', strtotime('-2day'));
        $to_4 = date('Y-m-d', strtotime('-3day'));
        $to_5 = date('Y-m-d', strtotime('-4day'));
        $to_6 = date('Y-m-d', strtotime('-5day'));
        $to_7 = date('Y-m-d', strtotime('-6day'));
        $sql = "
            SELECT
                ISNULL(EG.CategoryName1,'总计') AS 一级分类,
                ISNULL(EG.CategoryName2,'合计') AS 二级分类,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE(),23) THEN ERG.Quantity END ) AS '{$to_1}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ERG.Quantity END ) AS '{$to_2}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-2,23) THEN ERG.Quantity END ) AS '{$to_3}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-3,23) THEN ERG.Quantity END ) AS '{$to_4}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-4,23) THEN ERG.Quantity END ) AS '{$to_5}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-5,23) THEN ERG.Quantity END ) AS '{$to_6}',
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity END ) AS '{$to_7}'
            FROM ErpCustomer EC
                LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
                LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
                LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
                WHERE EC.MathodId IN (4,7)
                AND EG.CategoryName1='配饰'
                AND CONVERT(VARCHAR(10),ER.RetailDate,23)> CONVERT(VARCHAR(10),GETDATE()-7,23)
                AND ER.CodingCodeText='已审结'
                GROUP BY
                    EG.CategoryName1,
                    EG.CategoryName2
                WITH ROLLUP
        ";
        $list = Db::connect("sqlsrv")->query($sql);
        // dump($list);die;

        $table_header = ['行号'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 180;
        }
        $field_width[0] = 80;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "数据更新时间 （" . date("Y-m-d") . "） - 配饰每日销售数量 表号:S105",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }

    // 业绩校验
    public function create_table_s106($date = '')
    {
        // 编号
        $code = 'S106';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $sql = "
            SELECT
                T.[日期],
                T.[直营每天流水],
                T.[加盟每天流水],
                T.[每日合计],
                SUM(T.[直营每天流水]) OVER (ORDER BY T.[日期]) AS 直营累计流水,
                SUM(T.[加盟每天流水]) OVER (ORDER BY T.[日期]) AS 加盟累计流水,
                SUM(T.[每日合计]) OVER (ORDER BY T.[日期]) AS 合计累计流水
            FROM
            (
            SELECT
                CONVERT(VARCHAR(10),ER.RetailDate,23) AS 日期,
                SUM(CASE WHEN EC.MathodId=4 THEN ERG.Quantity*ERG.DiscountPrice END )/10000 AS 直营每天流水,
                SUM(CASE WHEN EC.MathodId=7 THEN ERG.Quantity*ERG.DiscountPrice END )/10000 AS 加盟每天流水,
                SUM(ERG.Quantity*ERG.DiscountPrice) /10000 AS 每日合计
            FROM ErpCustomer EC
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.MathodId IN (4,7)
                AND ER.CodingCodeText='已审结'
                AND CONVERT(VARCHAR(7),ER.RetailDate,23)=CONVERT(VARCHAR(7),GETDATE(),23)
            GROUP BY CONVERT(VARCHAR(10),ER.RetailDate,23)
            ) T
        ";
        $list = Db::connect("sqlsrv")->query($sql);

        // 入库记录
        $list2 = $list;
        foreach ($list2 as $key => $val) {
            $list2[$key]['更新日期'] = date('Y-m-d H:i:s');
        }
        $status = $this->db_easyA->table('cwl_table_yeji')->strict(false)->insertAll($list2);

        $newList = [];
        foreach ($list as $key => $val) {
            $newList[$key]['日期'] =  date('m-d', strtotime($val['日期'])) . ' ' . date_to_week($val['日期']);
            $newList[$key]['直营天流水'] =  round($val['直营每天流水'], 2);
            $newList[$key]['加盟天流水'] =  round($val['加盟每天流水'], 2);
            $newList[$key]['每日合计']     =  round($val['每日合计'], 2);
            $newList[$key]['直营累计流水'] =  round($val['直营累计流水'], 2);
            $newList[$key]['加盟累计流水'] =  round($val['加盟累计流水'], 2);
            $newList[$key]['合计累计流水'] =  round($val['合计累计流水'], 2);
        }

        // dump($list);die;

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($newList[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 100;
        }
        $field_width[0] = 30;
        $field_width[1] = 90;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 70;


        // dump($table_header);
        // dump($newList);die;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => ''
        ];

        //参数
        $params = [
            'row' => count($newList),          //数据的行数
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "每日业绩 [" . date("Y-m-d") . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $newList,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }

    // 鞋履报表 00:00:00
    public function create_table_s107($date = '')
    {
        // 编号
        $code = 'S107';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));

        $sql2 = "
            WITH T1 AS
            (
            SELECT
                ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS ID,
                ISNULL(EG.TimeCategoryName1,'总计') AS 新老品,
                ISNULL(EG.CategoryName2,'合计') AS 二级分类,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-7,23) THEN ERG.Quantity END) AS 前七天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity END) AS 前六天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-5,23) THEN ERG.Quantity END) AS 前五天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-4,23) THEN ERG.Quantity END) AS 前四天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-3,23) THEN ERG.Quantity END) AS 前三天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-2,23) THEN ERG.Quantity END) AS 前二天,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ERG.Quantity END) AS 前一天,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-1,23) THEN ERG.Quantity END) AS 月销,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-1,23) THEN ERG.Quantity*ERG.DiscountPrice END) AS 月销额,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-1,23) THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END)/(SELECT
                                                                                            SUM(ERG.Quantity*ERG.DiscountPrice)
                                                                                        FROM ErpCustomer EC
                                                                                        LEFT JOIN ErpRetail ER ON EC.CustomerId = ER.CustomerId
                                                                                        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID = ERG.RetailID
                                                                                        LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
                                                                                        WHERE EC.MathodId IN (4,7)
                                                                                        AND EG.CategoryName1 IN ('鞋履','内搭','外套','下装','配饰')
                                                                                        AND ER.CodingCodeText='已审结'
                                                                                        AND CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-1,23))*100 AS 占比
            FROM ErpCustomer EC
            LEFT JOIN ErpRetail ER ON EC.CustomerId = ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN (SELECT
                                        GoodsId,
                                        CASE WHEN TimeCategoryName1=2023 THEN '新品' ELSE '老品' END AS TimeCategoryName1,
                                        CategoryName1,
                                        CASE WHEN CategoryName1='鞋履' AND CategoryName2 NOT IN ('凉鞋','正统皮鞋') THEN '休闲鞋' ELSE CategoryName2 END AS CategoryName2
                                    FROM ErpGoods EG) EG ON ERG.GoodsId=EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EG.CategoryName1 IN ('鞋履')
                AND ER.CodingCodeText='已审结'
                AND CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-31,23) AND CONVERT(VARCHAR(10),GETDATE()-1,23)
            GROUP BY
                EG.TimeCategoryName1,
                EG.CategoryName2
            WITH  ROLLUP
            ),
            T2 AS
            (
            SELECT
                ISNULL(EG.TimeCategoryName1,'总计') AS 新老品,
                ISNULL(EG.CategoryName2,'合计') AS 二级分类,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-366,23) THEN ERG.Quantity END) AS 月销,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-366,23) THEN ERG.Quantity*ERG.DiscountPrice END) AS 月销额,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-366,23) THEN ERG.Quantity*ERG.DiscountPrice END)/(SELECT
                                                                                            SUM(ERG.Quantity*ERG.DiscountPrice)
                                                                                        FROM ErpCustomer EC
                                                                                        LEFT JOIN ErpRetail ER ON EC.CustomerId = ER.CustomerId
                                                                                        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID = ERG.RetailID
                                                                                        LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
                                                                                        WHERE EC.MathodId IN (4,7)
                                                                                        AND EG.CategoryName1 IN ('鞋履','内搭','外套','下装','配饰')
                                                                                        AND ER.CodingCodeText='已审结'
                                                                                        AND CONVERT(VARCHAR(7),ER.RetailDate,23) = CONVERT(VARCHAR(7),GETDATE()-366,23)
                                                                                                                                                                                AND CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-397,23) AND CONVERT(VARCHAR(10),GETDATE()-366,23) )*100 AS 占比
            FROM ErpCustomer EC
            LEFT JOIN ErpRetail ER ON EC.CustomerId = ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN (SELECT
                                        GoodsId,
                                        CASE WHEN TimeCategoryName1=2022 THEN '新品' ELSE '老品' END AS TimeCategoryName1,
                                        CategoryName1,
                                        CASE WHEN CategoryName1='鞋履' AND CategoryName2 NOT IN ('凉鞋','正统皮鞋') THEN '休闲鞋' ELSE CategoryName2 END AS CategoryName2
                                    FROM ErpGoods EG) EG ON ERG.GoodsId=EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EG.CategoryName1 IN ('鞋履')
                AND ER.CodingCodeText='已审结'
                AND CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-397,23) AND CONVERT(VARCHAR(10),GETDATE()-366,23)
            GROUP BY
                EG.TimeCategoryName1,
                EG.CategoryName2
            WITH  ROLLUP
            ),

            T3 AS
            (
            SELECT
                ISNULL(EG.TimeCategoryName1,'总计') AS 新老品,
                ISNULL(EG.CategoryName2,'合计') AS 二级分类,
                COUNT(DISTINCT CASE WHEN T.Quantity>0 THEN T.GoodsNo END) SKC,
                SUM(T.Quantity) Quantity
            FROM
            (
            SELECT
                EG.GoodsNo,
                SUM(EWS.Quantity) AS Quantity
            FROM ErpWarehouseStock EWS
            LEFT JOIN ErpGoods EG ON EWS.GoodsId= EG.GoodsId
            WHERE EG.CategoryName1 IN ('鞋履')
            AND EWS.WarehouseId NOT IN ('K391000003','K391000016','K391000036','K391000053')
            GROUP BY
                EG.GoodsNo
            HAVING SUM(EWS.Quantity)!=0

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(ERG.Quantity) AS 收仓在途
            FROM ErpCustomer EC
            LEFT JOIN ErpReturn ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpReturnGoods  ERG ON ER.ReturnID=ERG.ReturnID
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                    AND EC.ShutOut=0
                    AND ER.CodingCode='EndNode2'
                    AND ER.IsCompleted IS NULL
                    AND EG.CategoryName1 IN ('鞋履')
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(EDG.Quantity) AS Quantity
            FROM ErpCustomer EC
            LEFT JOIN ErpDelivery ED ON EC.CustomerId=ED.CustomerId
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
                AND ED.CodingCode='EndNode2'
                AND ED.IsCompleted=0
                AND ED.DeliveryID NOT IN (SELECT DeliveryId FROM ErpCustReceipt WHERE CodingCodeText='已审结' AND DeliveryId IS NOT NULL )
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(EIG.Quantity) AS Quantity
            FROM ErpCustomer EC
            LEFT JOIN ErpCustOutbound EI ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
                AND EI.CodingCodeText='已审结'
                AND EI.IsCompleted=0
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(ECS.Quantity) AS 店库存数量
            FROM ErpCustomer EC
            LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
            GROUP BY
                EG.GoodsNo
            HAVING SUM(ECS.Quantity)!=0
            ) T
            LEFT JOIN (SELECT
                                        GoodsId,
                                        GoodsNo,
                                        CASE WHEN TimeCategoryName1=2023 THEN '新品' ELSE '老品' END AS TimeCategoryName1,
                                        CategoryName1,
                                        CASE WHEN CategoryName1='鞋履' AND CategoryName2 NOT IN ('凉鞋','正统皮鞋') THEN '休闲鞋' ELSE CategoryName2 END AS CategoryName2
                                    FROM ErpGoods EG) EG ON T.GoodsNo=EG.GoodsNo
            GROUP BY
                EG.TimeCategoryName1,
                EG.CategoryName2
            WITH ROLLUP
            ),



            T4 AS
            (
            SELECT
                ISNULL(EG.TimeCategoryName1,'总计') AS 新老品,
                ISNULL(EG.CategoryName2,'合计') AS 二级分类,
                SUM(T.Quantity) Quantity
            FROM
            (
            SELECT
                EG.GoodsNo,
                SUM(EWS.Quantity) AS Quantity
            FROM ErpWarehouseStock EWS
            LEFT JOIN ErpGoods EG ON EWS.GoodsId= EG.GoodsId
            WHERE EG.CategoryName1 IN ('鞋履')
                AND CONVERT(VARCHAR(10),EWS.StockDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23)
                AND EWS.WarehouseId NOT IN ('K391000003','K391000016','K391000036','K391000053')
            GROUP BY
                EG.GoodsNo
            HAVING SUM(EWS.Quantity)!=0

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(ERG.Quantity) AS 收仓在途
            FROM ErpCustomer EC
            LEFT JOIN ErpReturn ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpReturnGoods  ERG ON ER.ReturnID=ERG.ReturnID
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND ER.CodingCode='EndNode2'
                AND ER.IsCompleted IS NULL
                AND EG.CategoryName1 IN ('鞋履')
                AND CONVERT(VARCHAR(10),ER.ReturnDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23)
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(EDG.Quantity) AS Quantity
            FROM ErpCustomer EC
            LEFT JOIN ErpDelivery ED ON EC.CustomerId=ED.CustomerId
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
                AND ED.CodingCode='EndNode2'
                AND ED.DeliveryID NOT IN (SELECT DeliveryId FROM ErpCustReceipt WHERE CodingCodeText='已审结' AND DeliveryId IS NOT NULL AND CONVERT(VARCHAR(10),ReceiptDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23) )
                AND CONVERT(VARCHAR(10),ED.DeliveryDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23)
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(EIG.Quantity) AS Quantity
            FROM ErpCustomer EC
            LEFT JOIN ErpCustOutbound EI ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
                AND EI.CodingCodeText='已审结'
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' AND CONVERT(VARCHAR(10),ReceiptDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23) GROUP BY ERG.CustOutboundId )
                AND CONVERT(VARCHAR(10),EI.CustOutboundDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23)
            GROUP BY
                EG.GoodsNo

            UNION ALL
            SELECT
                EG.GoodsNo,
                SUM(ECS.Quantity) AS 店库存数量
            FROM ErpCustomer EC
            LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.CategoryName1 IN ('鞋履')
                AND CONVERT(VARCHAR(10),ECS.StockDate,23)<CONVERT(VARCHAR(10),GETDATE()-365,23)
            GROUP BY
                EG.GoodsNo
            HAVING SUM(ECS.Quantity)!=0
            ) T
            LEFT JOIN (SELECT
                                        GoodsId,
                                        GoodsNo,
                                        CASE WHEN TimeCategoryName1=2022 THEN '新品' ELSE '老品' END AS TimeCategoryName1,
                                        CategoryName1,
                                        CASE WHEN CategoryName1='鞋履' AND CategoryName2 NOT IN ('凉鞋','正统皮鞋') THEN '休闲鞋' ELSE CategoryName2 END AS CategoryName2
                                    FROM ErpGoods EG) EG ON T.GoodsNo=EG.GoodsNo
            GROUP BY
                EG.TimeCategoryName1,
                EG.CategoryName2
            WITH ROLLUP
            )

            SELECT
                T1.新老品,
                T1.二级分类,
                T3.SKC,
                T3.Quantity AS 库存量,
                CONCAT(CONVERT(DECIMAL(10,2),T1.占比),'%') AS 本月流水占比,
                CONCAT(CONVERT(DECIMAL(10,2),T2.占比),'%') AS 同期流水占比,
                CONCAT(CONVERT(DECIMAL(10,2),T1.占比 - T2.占比),'%') AS 占比同比,
                T1.月销,
                T1.前一天,
                T1.前二天,
                T1.前三天,
                T1.前四天,
                T1.前五天,
                T1.前六天,
                T1.前七天
            FROM T1
            LEFT JOIN T2 ON T1.新老品=T2.新老品 AND T1.二级分类=T2.二级分类
            LEFT JOIN T3 ON T1.新老品=T3.新老品 AND T1.二级分类=T3.二级分类
            LEFT JOIN T4 ON T1.新老品=T4.新老品 AND T1.二级分类=T4.二级分类
            ORDER BY T1.ID
            ;
        ";
        $list = Db::connect("sqlsrv")->query($sql2);

        // cache('cache_xielv', null);
        // if (!cache('cache_xielv')) {
        //     $list = Db::connect("sqlsrv")->query($sql2);
        //     cache('cache_xielv', $list, 3600);
        // }

        // $list = cache('cache_xielv');

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 60;
        $field_width[2] = 80;
        $field_width[3] = 40;
        $field_width[4] = 60;
        $field_width[5] = 105;
        $field_width[6] = 105;
        $field_width[7] = 70;
        $field_width[8] = 60;
        $field_width[9] = 60;
        $field_width[10] = 60;
        $field_width[11] = 60;
        $field_width[12] = 60;
        $field_width[13] = 60;
        $field_width[14] = 60;
        $field_width[15] = 60;

        // dump($table_header);
        // dump($newList);die;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => ' '
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "鞋履报表 [" . date("Y-m-d") . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }

    // s108 督导挑战目标
    public function create_table_s108A($date = '')
    {
        // 编号
        $code = 'S108A';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');

        $sql2 = "
        select * from (
            SELECT
            IFNULL(SCL.`经营模式`,'总计') AS 经营模式,
            IFNULL(SCL.`督导`,'合计') AS 督导,
            IFNULL(SCL.`省份`,'合计') AS 省份,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            SUM(SCL.`近七天日均`) AS 近七天日均流水,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            GROUP BY
            SCL.`经营模式`,
            SCL.`督导`,
            SCL.`省份`
            WITH ROLLUP
        ) as aa order by 经营模式 desc
        ";

        $sql3 = "
            SELECT
            IFNULL(SCL.`督导`,'总计') AS 督导,
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            where SCL.`经营模式`='直营'
            GROUP BY
            SCL.`督导`,
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 80;
        $field_width[2] = 45;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 80;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 95;
        $field_width[9] = 95;
        $field_width[10] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "【直营】"
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            'file_name' => $code .$dingName . '.jpg',   //保存的文件名
            'title' => "督导挑战目标完成率 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            // 'banben' => '',
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    // s108 督导挑战目标
    public function create_table_s108B($date = '')
    {
        // 编号
        $code = 'S108B';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');
        $sql3 = "
                SELECT
                IFNULL(SCL.`省份`,'合计') AS 省份,
                CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
                CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
                SUM(SCM.`今日目标`) AS 今日目标,
                SUM(SCL.`今天流水`) AS 今天流水,
                SUM(SCM.`本月目标`) 本月目标,
                SUM(SCL.`本月流水`) 本月流水,
                SUM(SCL.`近七天日均`) AS 近七天日均,
                ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
                FROM sp_customer_liushui SCL
                LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
                where SCL.`经营模式`='加盟'
                GROUP BY
                SCL.`督导`,
                SCL.`省份`
                WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        array_pop($list);

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 80;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 95;
        $field_width[8] = 95;
        $field_width[9] = 110;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => "【加盟】",
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'title' => "区域挑战目标完成率 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 2,
            '本月达成率' => 3,
        ]);
    }

    // s109 各省挑战目标完成情况
    public function create_table_s109($date = '')
    {
        // 编号
        $code = 'S109';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');

        $sql2 = "
            SELECT
            IFNULL(SCL.`经营模式`,'总计') AS 经营,
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            COUNT(DISTINCT SCL.`店铺名称`) AS 销售店铺数,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            GROUP BY
            SCL.`经营模式`,
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql2);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        // dump($list); die;

        // cache('cache_xielv', null);
        // if (!cache('cache_xielv')) {
        //     $list = Db::connect("sqlsrv")->query($sql2);
        //     cache('cache_xielv', $list, 3600);
        // }
        // $list = cache('cache_xielv');

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 45;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 90;
        $field_width[11] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟 & 直营】'
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'title' => "各省挑战目标完成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            // 'banben' => '',
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    // s109 各省挑战目标完成情况
    public function create_table_s109B($date = '')
    {
        // 编号
        $code = 'S109B';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');

        $sql2 = "
            SELECT
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            COUNT(DISTINCT SCL.`店铺名称`) AS 销售店铺数,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            where 经营模式='加盟'
            GROUP BY
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql2);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }
        // dump($list); die;

        // cache('cache_xielv', null);
        // if (!cache('cache_xielv')) {
        //     $list = Db::connect("sqlsrv")->query($sql2);
        //     cache('cache_xielv', $list, 3600);
        // }
        // $list = cache('cache_xielv');

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟】'
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'title' => "各省挑战目标完成情况-加盟 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 2,
            '本月达成率' => 3,
        ]);
    }

    // s110 单店目标达成情况
    public function create_table_s110A($date = '')
    {
        // 编号
        $code = 'S110A';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');

        $sql3 = "
            SELECT
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`,
            CONCAT(ROUND(SCL.`今天流水`/SCM.`今日目标`*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SCL.`本月流水`/SCM.`本月目标`*100,2),'%') AS 本月达成率,
            SCM.`今日目标`,
            SCL.`今天流水`,
            SCM.`本月目标`,
            SCL.`本月流水`,
            SCL.`近七天日均`,
            ROUND((SCM.`本月目标` - SCL.`本月流水`) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            WHERE SCL.`经营模式`='直营'
            ORDER BY
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`
            ;
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 75;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;
        $field_width[11] = 100;


        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【直营】',
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'title' => "直营单店目标达成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 4,
            '本月达成率' => 5,
        ]);
    }

    // s116 零售核销单
    public function create_table_s116($date = '')
    {
        // 编号
        $code = 'S116';
        $date = $date ?: date('Y-m-d');
        // $dingName = cache('dingding_table_name');

        $sql = "
            SELECT
                t1.省份,
                concat(round(zy.毛利率 * 100, 1), '%') AS 直营_毛利率,
                zy.毛利 AS 直营_毛利,
                concat(round(jm.毛利率 * 100, 1), '%') AS 加盟_毛利率,
                jm.毛利 AS 加盟_毛利,
                concat(round(hj.毛利率 * 100, 1), '%') AS 合计_毛利率,
                hj.毛利 AS 合计_毛利
            FROM
                `cwl_hexiao_res_day` AS t1
            LEFT JOIN `cwl_hexiao_res_day` AS zy ON zy.省份 = t1.省份 AND zy.经营属性 = '直营' AND zy.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS jm ON jm.省份 = t1.省份 AND jm.经营属性 = '加盟' AND jm.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS hj ON hj.省份 = t1.省份 AND hj.经营属性 = '合计' AND hj.销售日期 = t1.销售日期
            WHERE 
                t1.销售日期 ='{$date}'
            GROUP BY t1.省份
            ORDER BY t1.`index` ASC, t1.省份 ASC
            ;
        ";
        $list = $this->db_easyA->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                if ($val['省份'] != '单日总计' && $val['省份'] != '本月总计') {
                    $list[$key]['省份'] = province2zi($val['省份']);
                }
            }
    
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 95;
            }
            $field_width[0] = 30;
            $field_width[1] = 70;
            // $field_width[2] = 75;
            // $field_width[3] = 90;
    
    
    
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' ',
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "零售核销报表日统计 [" . $date . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '  图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            echo '没有数据,请稍后再试';
        }
 
    }

    // s117 零售核销单
    public function create_table_s117($date = '')
    {
        // 编号
        $code = 'S117';
        $date = $date ?: date('Y-m-d');
        // $dingName = cache('dingding_table_name');
        // 本月开始
        $current_month  = date("Y-m-01", time()); 
        $today  = date("Y-m-d", time()); 
        $sql = "
            SELECT 
            t1.销售日期 as 日期,
            '' as 星期,
            left_zy.毛利率 AS 直营_毛利率,
            left_zy.毛利 AS 直营_毛利,
            left_jm.毛利率 AS 加盟_毛利率,
            left_jm.毛利 AS 加盟_毛利,
            left_hj.毛利率 AS 合计_毛利率,
            left_hj.毛利 AS 合计_毛利,
            
            right_zy.毛利率 AS 直营累计_毛利率,
            right_zy.毛利 AS 直营累计_毛利,
            right_jm.毛利率 AS 加盟累计_毛利率,
            right_jm.毛利 AS 加盟累计_毛利,
            right_hj.毛利率 AS 合计累计_毛利率,
            right_hj.毛利 AS 合计累计_毛利
            
            FROM cwl_hexiao_res_day as t1
            
            LEFT JOIN `cwl_hexiao_res_day` AS left_zy ON left_zy.省份 = '单日总计' AND left_zy.经营属性 = '直营' AND left_zy.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS left_jm ON left_jm.省份 = '单日总计' AND left_jm.经营属性 = '加盟' AND left_jm.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS left_hj ON left_hj.省份 = '单日总计' AND left_hj.经营属性 = '合计' AND left_hj.销售日期 = t1.销售日期
            
            LEFT JOIN `cwl_hexiao_res_day` AS right_zy ON right_zy.省份 = '单日总计' AND right_zy.经营属性 = '直营' AND right_zy.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS right_jm ON right_jm.省份 = '单日总计' AND right_jm.经营属性 = '加盟' AND right_jm.销售日期 = t1.销售日期
            LEFT JOIN `cwl_hexiao_res_day` AS right_hj ON right_hj.省份 = '单日总计' AND right_hj.经营属性 = '合计' AND right_hj.销售日期 = t1.销售日期
            
            where
            t1.销售日期>= '{$current_month}'
            AND t1.销售日期 <= '{$today}'

            group by t1.销售日期
            order by t1.销售日期 asc
            ;
        ";
        $list = $this->db_easyA->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['星期'] = date_to_week2($val['日期']);
                // if ($val['省份'] != '单日总计' && $val['省份'] != '本月总计') {
                //     $list[$key]['省份'] = province2zi($val['省份']);
                // }
            }
    
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 95;
            }
            $field_width[0] = 30;
            $field_width[2] = 70;
            $field_width[8] = 85;
            $field_width[9] = 130;
            $field_width[10] = 130;
            $field_width[11] = 130;
            $field_width[12] = 130;
            $field_width[13] = 130;
            $field_width[14] = 130;
            // $field_width[3] = 90;
    
    
    
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' ',
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "零售核销报表月统计 [" . $date . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '  图片报表编号: ' . $code,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            echo '没有数据,请稍后再试';
        }
 
    }    

    // s110 单店目标达成情况
    public function create_table_s110B($date = '')
    {
        // 编号
        $code = 'S110B';
        $date = $date ?: date('Y-m-d');
        $dingName = cache('dingding_table_name');

        $sql3 = "
            SELECT
            SCL.`省份`,
            SCL.`店铺名称`,
            CONCAT(ROUND(SCL.`今天流水`/SCM.`今日目标`*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SCL.`本月流水`/SCM.`本月目标`*100,2),'%') AS 本月达成率,
            SCM.`今日目标`,
            SCL.`今天流水`,
            SCM.`本月目标`,
            SCL.`本月流水`,
            SCL.`近七天日均`,
            ROUND((SCM.`本月目标` - SCL.`本月流水`) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            WHERE SCL.`经营模式`='加盟'
            ORDER BY
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`
            ;
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;


        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟】'
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'title' => "加盟单店目标达成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    // s101C 51假期4表
    public function create_table_s101C($code = 'S101C', $date = '')
    {
        $date = $date ?: date('Y-m-d', strtotime('+1day'));

        if ($code == 'S101C') {
            $jingyingmoshi = '【加盟】';
            $title = "加盟老店【五一假期】业绩同比 " . date("Y-m-d");
            $map = ['经营模式', '=', '加盟'];
        } else {
            $jingyingmoshi = '【直营】';
            $title = "直营老店【五一假期】业绩同比 " . date("Y-m-d");
            $map = ['经营模式', '=', '直营'];
        }

        $data = Db::connect("mysql2")->table('old_customer_state_detail_jiaqi')
            ->field("
                省份,
                店铺名称,
                同比前年假期同日递增率 AS 前年日增长,
                同比去年假期同日递增率 AS 去年日增长,
                同比前年假期累销递增率 AS 前年月增长,
                同比去年假期累销递增率 AS 去年月增长,
                前年假期同日,
                去年假期同日,
                今日假期销量 as 今日销额,
                前年假期累计 as 前年假期累销额,
                去年假期累计 as 去年假期累销额,
                今年假期累计 as 今年假期累销额
            ")->where([
                $map,
                ['更新时间', '=', $date]
            ])->select()->toArray();

        foreach ($data as $key => $val) {
            $data[$key]['省份'] = province2zi($val['省份']);
        }

        // echo '<pre>';
        // print_r($data);die;
        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 90;
        }
        $field_width[0] = 35;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 100;
        $field_width[8] = 100;
        $field_width[9] = 90;
        $field_width[10] = 120;
        $field_width[11] = 120;
        $field_width[12] = 120;

        // $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
        // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
        // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => "{$jingyingmoshi} 今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
        ];
        //参数
        $params = [
            'row' => count($data),          //数据的行数
            'file_name' =>  $code . '.jpg',      //保存的文件名
            'title' => $title,
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        $this->create_table($params);
    }

    public function create_table_s102C($date = '')
    {
        // 编号
        $code = 'S102C';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $list = Db::connect("mysql2")->table('old_customer_state_2_jiaqi')->field("
            省份,
            同比前年假期同日递增率 AS 前年日增长,
            同比去年假期同日递增率 AS 去年日增长,
            同比前年假期累销递增率 AS 前年累计增长,
            同比去年假期累销递增率 AS 去年累计增长,
            前年假期同日 as 前年同日销额,
            去年假期同日 as 去年同日销额,
            今日假期销量 AS 今日销额,
            前年假期累计 as 前年累计销额,
            去年假期累计 AS 去年累计销额,
            今年假期累计 as 今年累计销额
        ")->where(['更新时间' => $date])->select()->toArray();

        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }
        // dump($list);die;
        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 90;
        }
        $field_width[0] = 35;
        $field_width[1] = 75;

        $field_width[4] = 100;
        $field_width[5] = 100;
        $field_width[6] = 100;
        $field_width[7] = 100;
        $field_width[9] = 100;
        $field_width[10] = 100;
        $field_width[11] = 100;
        // $field_width[14] = 150;
        // $field_width[15] = 120;
        // $field_width[15] = 120;
        // $field_width[16] = 160;

        // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
        // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
        // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "省份老店【五一假期】业绩同比 " . date("Y-m-d"),
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }

    // 加盟
    public function create_table_s103C($date = '')
    {
        // 编号
        $code = 'S103C';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $list = Db::connect("mysql2")->table('old_customer_state_jiaqi')->field("
            经营模式 as 经营,
            省份,
            同比前年假期同日递增率 AS 前年日增长,
            同比去年假期同日递增率 AS 去年日增长,
            同比前年假期累销递增率 AS 前年累计增长,
            同比去年假期累销递增率 AS 去年累计增长,
            前年假期同日 as 前年同日销额,
            去年假期同日 as 去年同日销额,
            今日假期销量 AS 今日销额,

            前年假期累计 as 前年累计销额,
            去年假期累计 AS 去年累计销额,
            今年假期累计 as 今年累计销额
        ")->where(['更新时间' => $date])->select()->toArray();
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }
        // dump($list);die;
        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 90;
        }
        $field_width[0] = 35;
        $field_width[1] = 45;
        $field_width[2] = 45;


        $field_width[5] = 110;
        $field_width[6] = 110;
        $field_width[7] = 110;
        $field_width[8] = 110;
        $field_width[10] = 110;
        $field_width[11] = 110;
        $field_width[12] = 110;
        // $field_width[15] = 150;
        // $field_width[16] = 130;

        // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
        // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
        // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "省份老店【五一假期】业绩同比-分经营模式 " . date("Y-m-d"),
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }

    // 采购顶推1表
    public function create_table_s111($seasion = '春季')
    {
        // 编号
        $code = 'S111';
        if ($seasion == '春季') {
            $str = 'A';
        } elseif ($seasion == '夏季') {
            $str = 'B';
        } elseif ($seasion == '秋季') {
            $str = 'C';
        } elseif ($seasion == '冬季') {
            $str = 'D';
        }
        $date = date('Y-m-d', strtotime('+1day'));

        $sql = "
            SELECT
                供应商,
                SUM(发货总量) AS 发货总量,
                SUM(入库总量) AS 入库总量,
                领型, 
                风格,
                大类,
                中类
            FROM
                `cwl_ErpReceipt_report1`
                WHERE 季节='{$seasion}'
            GROUP BY 	
                风格,
                领型, 
                大类,
                中类,
                供应商
        ";
        $list = $this->db_easyA->query($sql);

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 100;
            }
            $field_width[0] = 30;
            $field_width[1] = 220;
            // $field_width[2] = 45;
            // $field_width[3] = 90;
            // $field_width[4] = 90;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' '
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                'file_name' => $code . $str . '.jpg',   //保存的文件名
                'title' => "{$seasion}新品发货及入库明细 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '           编号: ' . $code . $str,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 采购顶推1表
    public function create_table_s112($seasion = '春季')
    {
        // 编号
        $code = 'S112';
        $date = date('Y-m-d', strtotime('+1day'));
        if ($seasion == '春季') {
            $str = 'A';
        } elseif ($seasion == '夏季') {
            $str = 'B';
        } elseif ($seasion == '秋季') {
            $str = 'C';
        } elseif ($seasion == '冬季') {
            $str = 'D';
        } 

    //     SELECT
    //     IFNULL(风格, '总计') AS 风格,
    //     IFNULL(大类, '合计') AS 大类,
    //     IFNULL(中类, '合计') AS 中类,
    //     IFNULL(领型,'合计') AS 领型,
    //     SUM(发货总量) AS 发货总量,
    //     SUM(入库总量) AS 入库总量
    // FROM
    //     `cwl_ErpReceipt_report1`
    //     WHERE 季节='{$seasion}'
    // GROUP BY 	
    //     风格,
    //     大类,
    //     中类,
    //     领型 
    // WITH ROLLUP
        $sql = "
            SELECT
                IFNULL(中类, '合计') AS 中类,
                IFNULL(领型,'合计') AS 领型,
                SUM(发货总量) AS 发货总量,
                SUM(入库总量) AS 入库总量,
                IFNULL(大类, '合计') AS 大类,
                IFNULL(风格, '总计') AS 风格
            FROM
                `cwl_ErpReceipt_report1`
                WHERE 季节='{$seasion}'
            GROUP BY 	
                风格,
                大类,
                中类,
                领型 
            WITH ROLLUP           
        ";
        $list = $this->db_easyA->query($sql);
        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 120;
            }
            $field_width[0] = 30;
            // $field_width[1] = 220;
            // $field_width[2] = 45;
            // $field_width[3] = 90;
            // $field_width[4] = 90;
    
    
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' '
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . $str . '.jpg',   //保存的文件名
                'title' => "{$seasion}新品发货及入库汇总 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '           编号: ' . $code . $str,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 门店业绩环比报表
    public function create_table_s113($date = '')
    {
        // 编号
        $code = 'S113';
        $date = $date ? $date : date('Y-m-d');
        $sql = "
            SELECT
                IFNULL(经营属性, '总计') AS 性质,
                IFNULL(省份, '合计') AS 省份,
                concat(  round(  (SUM(今日流水)   / SUM( `环比流水` )   - 1) * 100 , 2),    '%') AS 今日环比,
                concat(  round(  (SUM( `本月累计流水` ) / SUM( `环比累计流水`) - 1 ) * 100 , 2),  '%') AS 月度环比,
                ROUND( SUM( 今日流水 ), 2 ) AS 今日流水,
                ROUND( SUM( `环比流水` ), 2 ) AS 环比流水,
                ROUND( SUM( `本月累计流水` ), 2 ) AS 本月累计流水,
                ROUND( SUM( `环比累计流水` ), 2 ) AS 环比累计流水 
            FROM
                cwl_dianpuyejihuanbi_handle 
            WHERE
                `use` = 1 
                AND 更新日期='{$date}'
            GROUP BY
                经营属性,省份 
                WITH ROLLUP
        ";
        $list = $this->db_easyA->query($sql);

        if ($list) {
            // $insertData = $list;
            // foreach ($insertData as $key => $val) {
            //     $insertData[$key]['更新日期'] = $date;
            // }
            // // 清空同一天
            // $this->db_bi->table('sp_ww_s113')->where([
            //     ['更新日期', '=', $date]
            // ])->delete();
            
            // // 入库
            // $this->db_bi->table('sp_ww_s113')->strict(false)->insertAll($insertData);

            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 100;
            }
            $field_width[0] = 30;
            $field_width[1] = 50;
            $field_width[2] = 50;
            $field_width[3] = 80;
            $field_width[4] = 80;
            $field_width[7] = 110;
            $field_width[8] = 110;
            // $field_width[4] = 90;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' '
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . 'B' . '.jpg',   //保存的文件名
                'title' => "门店业绩环比 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '           编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 工厂直发仓库超五天未验收单据
    public function create_table_s114() {
        // 编号
        $code = 'S114';

        $sql = "
            SELECT 
                审批时间 as 单据日期,
                仓库名称 as 收货仓库,
                供应商名称,
                单号 as 采购入库指令单,
                发出天数,
                数量 as 发货数量
            FROM `sp_warehouse_ws_cangjiafahuo` where 
                        YEAR(审批时间) = 2023
                        AND (仓库名称 IN ('广州云仓', '南昌云仓', '武汉云仓', '长沙云仓') OR 仓库名称='贵阳云仓' AND `发出天数`>= 8)
        ";
        $list = $this->db_bi->query($sql);
        // dump($list);die;

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 125;
            }
            $field_width[0] = 30;
            $field_width[1] = 160;
            // $field_width[2] = 80;
            // $field_width[3] = 80;
            // $field_width[4] = 80;
            // $field_width[7] = 110;
            // $field_width[8] = 110;
            // $field_width[4] = 90;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => ' '
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "工厂直发仓库超五天未验收单据 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '           编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 端午节统计 ABCD
    public function create_table_s115A($type = "加盟") {
        // 编号
        $code = 'S115A';
        if (date('Y-m-d') == '2023-06-22') {
            $fday = 1;
        } elseif (date('Y-m-d') == '2023-06-23') {
            $fday = 2;
        } elseif (date('Y-m-d') == '2023-06-24') {
            $fday = 3;
        } else {
            echo '活动已结束';
            die;
        }

        $sql = "
            select 
            省份,店铺名称,
            concat(round(前年日增长 * 100, 1), '%') AS 前年日增长,
            concat(round(去年日增长 * 100, 1), '%') AS 去年日增长,
            concat(round(前年累计增长 * 100, 1), '%') AS 前年累计增长,
            concat(round(去年累计增长 * 100, 1), '%') AS 去年累计增长,
            round(销售金额2021, 1) as 前年同日销额,
            round(销售金额2022, 1) as 去年同日销额,
            round(销售金额2023, 1) as 今日销额,
            round(前年累销额, 1) as 前年累销额,
            round(去年累销额, 1) as 去年累销额,
            round(今年累销额, 1) as 今年累销额
            from cwl_festival_statistics WHERE 节日天数='{$fday}' AND 经营属性='{$type}' order by 省份 DESC
        ";
        $list = $this->db_easyA->query($sql);
        // dump($list);die;

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 100;
            }
            $field_width[0] = 30;
            $field_width[1] = 50;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "【{$type}】"
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "{$type}老店【端午假期】业绩同比 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '          编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 端午节统计 ABCD
    public function create_table_s115B() {
        // 编号
        $code = 'S115B';
        if (date('Y-m-d') == '2023-06-22') {
            $fday = 1;
        } elseif (date('Y-m-d') == '2023-06-23') {
            $fday = 2;
        } elseif (date('Y-m-d') == '2023-06-24') {
            $fday = 3;
        } else {
            echo '活动已结束';
            die;
        }

        $sql = "
            select 
                省份,经营属性 as 性质,
                concat(round(前年日增长 * 100, 1), '%') AS 前年日增长,
                concat(round(去年日增长 * 100, 1), '%') AS 去年日增长,
                concat(round(前年累计增长 * 100, 1), '%') AS 前年累计增长,
                concat(round(去年累计增长 * 100, 1), '%') AS 去年累计增长,
                round(前年同日销额, 1) as 前年同日销额,
                round(去年同日销额, 1) as 去年同日销额,
                round(今日销额, 1) as 今日销额,
                round(前年累销额, 1) as 前年累销额,
                round(去年累销额, 1) as 去年累销额,
                round(今年累销额, 1) as 今年累销额
            from cwl_festival_statistics_province 
            WHERE 
                节日天数='{$fday}' 
        ";
        $list = $this->db_easyA->query($sql);
        // dump($list);die;

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 100;
            }
            $field_width[0] = 30;
            $field_width[1] = 50;
            $field_width[2] = 50;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "  "
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "省份老店【端午假期】业绩同比 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '          编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 端午节统计 ABCD 端午节平均单店流水对比-单日
    public function create_table_s115C() {
        // 编号
        $code = 'S115C';
        if (date('Y-m-d') == '2023-06-22') {
            $fday = 1;
        } elseif (date('Y-m-d') == '2023-06-23') {
            $fday = 2;
        } elseif (date('Y-m-d') == '2023-06-24') {
            $fday = 3;
        } else {
            echo '活动已结束';
            die;
        }

        $sql = "
            select 
                经营属性 as 性质,
                23VS22,
                23VS21,
                今日流水,
                22年同期,
                21年同期
            from cwl_festival_duanwu_table3 
            WHERE 
                节日天数='{$fday}' 
        ";
        $list = $this->db_easyA->query($sql);
        // dump($list);die;

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 125;
            }
            $field_width[0] = 30;
            $field_width[1] = 80;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "  "
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "【端午假期】平均单店流水对比-单日 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '          编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    // 端午节统计 ABCD 端午节平均单店流水对比-累计
    public function create_table_s115D() {
        // 编号
        $code = 'S115D';
        if (date('Y-m-d') == '2023-06-22') {
            $fday = 1;
        } elseif (date('Y-m-d') == '2023-06-23') {
            $fday = 2;
        } elseif (date('Y-m-d') == '2023-06-24') {
            $fday = 3;
        } else {
            echo '活动已结束';
            die;
        }

        $sql = "
            select 
                经营属性 as 性质,
                23VS22,
                23VS21,
                今日流水,
                22年同期,
                21年同期
            from cwl_festival_duanwu_table4 
            WHERE 
                节日天数='{$fday}' 
        ";
        $list = $this->db_easyA->query($sql);
        // dump($list);die;

        if ($list) {
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            
            foreach ($table_header as $v => $k) {
                $field_width[] = 125;
            }
            $field_width[0] = 30;
            $field_width[1] = 80;
    
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "  "
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "【端午假期】平均单店流水对比-累计 [" . date("Y-m-d") . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '          编号: ' . $code ,
                'file_path' => "./img/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        } else {
            return false;
        }
    }

    public function create_image($params)
    {
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_ulr' => app()->getRootPath() . '/public/Medium.ttf', //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
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
        $yellow2 = imagecolorallocate($img, 255, 252, 188); //设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $text_coler2 = imagecolorallocate($img, 255, 255, 255); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $blue2 = imagecolorallocate($img, 141, 193, 247); //设定图片背景色
        $orange = imagecolorallocate($img, 255, 192, 0); //设定图片背景色
        $littleblue = imagecolorallocate($img, 22, 119, 210); //设定图片背景色

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

        foreach ($params['data'] as $key => $item) {
            if (isset($item['省份']) && $item['省份'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            } elseif (isset($item['省份']) && $item['省份'] == '总计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
            }

            if ($params['banben'] == '图片报表编号: S107') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['新老品']) && $item['新老品'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S009') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['风格']) && $item['风格'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S015') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S016') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S018') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S030' || @$params['code'] == 'S031') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S112') {
                if (isset($item['领型']) && $item['领型'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $blue2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S113') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S116') {
                if (isset($item['省份']) && $item['省份'] == '单日总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['省份']) && $item['省份'] == '本月总计') {
                    
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S115B') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['省份']) && $item['省份'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S115C' || @$params['code'] == 'S115D') {
                if (isset($item['性质']) && $item['性质'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
            }
        }
        // create_table_s105
        if ($params['banben'] == '图片报表编号: S105') {
            if (isset($item['一级分类']) && $item['一级分类'] == '总计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            }
        }
        // s106
        if ($params['banben'] == '图片报表编号: S106') {
            imagefilledrectangle($img, 370, $y1, $x2 + 3000, $y2, $yellow);
        }
        
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

        echo '<img src="/' . $save_path . '"/>';
    }

    function create_table($params)
    {
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_url' => realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'), //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
        ];


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
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $yellow = imagecolorallocate($img, 238, 228, 0); //设定图片背景色
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
        foreach ($base['column_x_arr'] as $key => $x) {
            imageline($img, $x, $border_top, $x, $border_bottom, $border_coler); //画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $params['table_header'][$key]); //写入表头文字
            $sum += $params['field_width'][$key];
        }
        
        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $base['font_url'], $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0] / 2 - $first_len / 2 + $base['border'], $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $key + 1); //写入序号
            $sub = 0;
            $sum = $params['field_width'][0] + $base['border'];
            foreach ($item as $k => $value) {
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                if ((isset($item['店铺名称']) && $item['店铺名称']  === '合计') || isset($item['省份']) && $item['省份']  === '合计') {
                    imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $chengse, $font_west, $value);
                    $sum += $params['field_width'][$sub];
                } else {
                    if ($k === "累销递增率" || $k === "昨日递增率") {
                        $value = str_replace('%', "", $value);
                        if ($value < 0) {
                            imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $blue, $base['font_url'], $value . '%'); //写入data数据
                            $sum += $params['field_width'][$sub];
                        } else {
                            imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $red, $base['font_url'], $value . '%'); //写入data数据
                            $sum += $params['field_width'][$sub];
                        }
                    } else {
                        imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $value); //写入data数据
                        $sum += $params['field_width'][$sub];
                    }
                }
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $base['font_url'], $params['title']); //imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
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

        echo '<img src="/' . $save_path . '"/>';
    }


    // 格子带背景色
    public function create_image_bgcolor($params, $set_bgcolor = [])
    {
        // echo '<pre>';
        // print_r($params);die;
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_ulr' => app()->getRootPath() . '/public/Medium.ttf', //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
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
        $yellow2 = imagecolorallocate($img, 250, 233, 84); //设定图片背景色
        $yellow3 = imagecolorallocate($img, 230, 244, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $green2 = imagecolorallocate($img, 75, 234, 32); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $gray = imagecolorallocate($img, 37, 240, 240); //设定图片背景色
        $littleblue = imagecolorallocate($img, 22, 172, 176); //设定图片背景色
        $orange = imagecolorallocate($img, 255, 192, 0); //设定图片背景色

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

        // 1 统计上色
        foreach ($params['data'] as $key => $item) {
            if (isset($item['店铺名称']) && $item['店铺名称'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            }
            if (isset($item['省份']) && $item['省份'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
            }
            if ($params['banben'] == '图片报表编号: S107') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['新老品']) && $item['新老品'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $littleblue);
                }
            }

            if (@$params['code'] == 'S103') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }

            if (@$params['code'] == 'S108A') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['督导']) && $item['督导'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }

            if (@$params['code'] == 'S109') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }
        }

        // 2 单元格上色
        if (! empty($set_bgcolor)) {
             /* 获取开始x1结束x2
             ^ array:2 [▼
                    "今日达成率" => array:2 [▼
                        "start" => 120
                        "end" => 210
                    ]
                    "本月达成率" => array:2 [▼
                        "start" => 210
                        "end" => 300
                    ]
                ]
             */
            foreach ($set_bgcolor as $key => $val) {
                $site_arr = [
                    'x0' => 0,
                    'x1' => 0
                ];
                for ($i = 0; $i <= $val; $i ++) {
                    if ($i < $val) {
                        $site_arr['x0'] += $params['field_width'][$i]; 
                    } else {
                        $site_arr['x1'] = $site_arr['x0'] + $params['field_width'][$i];
                    }
             
                }
                $set_bgcolor[$key] = $site_arr;
            }
            foreach ($params['data'] as $key => $item) {
                foreach ($set_bgcolor as $key2 => $val2) {
                    if (!empty($item[$key2]) && $item[$key2] <= 60) {
                        imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $gray);
                    } elseif (!empty($item[$key2]) && ($item[$key2] > 60 && $item[$key2] <= 99)) {
                        imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $green2);
                    } elseif (!empty($item[$key2]) && $item[$key2] > 99) { 
                        imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                    }
                }
            }
        }

        // create_table_s105
        if ($params['banben'] == '图片报表编号: S105') {
            if (isset($item['一级分类']) && $item['一级分类'] == '总计') {
                imagefilledrectangle($img, 0, $y1 + 30 * ($key + 1), $x2 + 3000 * ($key + 1), $y2 + 30 * ($key + 1), $yellow);
            }
        }
        // s106
        if ($params['banben'] == '图片报表编号: S106') {
            imagefilledrectangle($img, 350, $y1, $x2 + 3000, $y2, $yellow);
        }
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

        echo '<img src="/' . $save_path . '"/>';
    }

    //导购图片生成 晚上10点
    public function table_pic_daogou_night($params, $if_write_num = 1, $if_write_header = 1) {

        $base = [
            'border' => 1,//图片外边框
            'file_path' => $params['file_path'],//图片保存路径
            'title_height' => 35,//报表名称高度
            'title_font_size' => 16,//报表名称字体大小
            'font_ulr' => app()->getRootPath().'/public/Medium.ttf',//字体文件路径
            'text_size' => 12,//正文字体大小
            'row_hight' => 30,//每行数据行高
        ];

        $y1 = 36;
        $x2 = 1542;
        $y2 = 65;
        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf');//字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if(!empty($params['table_explain'])){
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

        $base['img_width'] = $w_sum + $base['border'] * 2-$base['border'];//图片宽度
        $base['img_height'] = ($params['row']+1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height'];//图片高度
        $border_top = $base['border'] + $base['title_height'];//表格顶部高度
        $border_bottom = $base['img_height'] - $base['border'];//表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']);//创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24,98,229);//设定图片背景色



        $yellow = imagecolorallocate($img, 238,228,0);//设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0);//设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150);//设定边框颜色
        $xb  = imagecolorallocate($img, 255,255,255);//设定图片背景色

        $red = imagecolorallocate($img, 255,0,0);//设定图片背景色
        $green = imagecolorallocate($img, 24,98,0);//设定图片背景色
        $chengse = imagecolorallocate($img, 255,72,22);//设定图片背景色
        $blue = imagecolorallocate($img, 0,42,212);//设定图片背景色

        imagefill($img, 0, 0, $bg_color);//填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color);//画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color);//画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];

        foreach($params['data'] as $key => $item){
            if(in_array('合计',$item) || in_array('总计',$item)){
                imagefilledrectangle($img, 0, $y1+30*($key+1), $x2+3000*($key+1), $y2+30*($key+1), $yellow);
            }
        }
        foreach($base['column_x_arr'] as $key => $x){
            imageline($img, $x, $border_top, $x, $border_bottom,$border_coler);//画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            $header_text = '';
            if ($if_write_header == 1) {
                $header_text = $params['table_header'][$key];
            } else {
                if ($key == 0) {
                    $header_text = '';
                } else {
                    $header_text = $params['table_header'][$key];
                }
            }
            imagettftext($img, $base['text_size'], 0, $sum + (($x-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $header_text);//写入表头文字
            $sum += $params['field_width'][$key];
        }

        //画表格横线
        foreach($params['data'] as $key => $item){

            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0]/2 - $first_len/2+$base['border'], $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $if_write_num ? ($key+1) : '');//写入序号
            $sub = 0;
            $sum = $params['field_width'][0]+$base['border'];
            foreach ($item as $k =>$value){
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $value);//写入data数据
                $sum += $params['field_width'][$sub];

            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $font_west, $params['title']);//imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0];//右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7];//左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if(!is_dir($base['file_path']))//判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'],0777,true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width)/2, 30, $xb,$font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if(!empty($params['table_explain'])){
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20+$a_hight, $yellow,$font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20+$a_hight, $xb,$font_west, $params['banben']);
                $a_hight += 20;
            }
        }
//        echo $save_path;die;

        imagepng($img,$save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        return $save_path;
//        echo '<img src="/'.$save_path.'"/>';

    }


}
