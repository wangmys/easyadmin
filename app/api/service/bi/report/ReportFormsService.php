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
    protected $config = [

    ];

    protected $code = 0;
    protected $msg = '';

    public function __construct()
    {
        $this->model = new Yinliu();
    }
    
    public function task($number)
    {
        switch ($number){
            case 100:
                
                
                break;
        }
    }

    public function create_table_s101($code = 'S101',$date = '')
    {
        $date = $date?:date('Y-m-d',strtotime('+1day'));

        switch ($code){
            case 'S101':
                $sql = "select 经营模式,省份,店铺名称,首单日期 as 开店日期,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('加盟','加盟合计')";
                $title = "数据更新时间 （". date("Y-m-d") ."）- 加盟老店同比环比递增及完成率";
                break;
            case 'S104':
            default:
                $title = "数据更新时间 （". date("Y-m-d") ."）- 直营老店同比环比递增及完成率";
                $sql = "select 经营模式,省份,店铺名称,首单日期 as 开店日期,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
                break;
        }
        $data = Db::connect("mysql2")->query($sql);
        $table_header = ['行号'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 130;
        }
        $field_width[0] = 60;
        $field_width[1] = 80;
        $field_width[2] = 160;
        $field_width[4] = 120;
        $field_width[7] = 130;
        $field_width[12] = 140;
        $field_width[13] = 160;
        $field_width[14] = 160;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
        ];
        //参数
        $params = [
            'row' => count($data),          //数据的行数
            'file_name' =>  $code .'.jpg',      //保存的文件名
            'title' => $title,
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd',strtotime('+1day')).'/'  //文件保存路径
        ];
        // 防止多次创建
//        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
//        if(file_exists($file)){
//            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
//        }
        $this->create_table($params);
    }

    public function create_table_s102($date = '')
    {
        // 编号
        $code = 'S102';
        $date = $date?:date('Y-m-d',strtotime('+1day'));
        $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state_2 where 更新时间 = '$date'";
        $list = Db::connect("mysql2")->query($sql);
        $table_header = ['行号'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 120;
        }
        $field_width[0] = 60;
        $field_width[1] = 80;
        $field_width[2] = 80;
        $field_width[6] = 140;
        $field_width[7] = 140;
        $field_width[8] = 140;
        $field_width[9] = 140;
        $field_width[12] = 160;
        $field_width[14] = 160;

        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code.'.jpg',   //保存的文件名
            'title' => "数据更新时间 （". date("Y-m-d") ."）- 省份老店业绩同比表号:S102",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd',strtotime('+1day')).'/'  //文件保存路径
        ];
        // 防止多次创建
//        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
//        if(file_exists($file)){
//            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
//        }
        // 生成图片
        return $this->create_image($params);
    }

    public function create_table_s103($date = '')
    {
        // 编号
        $code = 'S103';
        $date = $date?:date('Y-m-d',strtotime('+1day'));
        $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $list = Db::connect("mysql2")->query($sql);
        $table_header = ['行号'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 120;
        }
        $field_width[0] = 80;
        $field_width[1] = 80;
        $field_width[2] = 80;
        $field_width[3] = 80;
        $field_width[6] = 140;
        $field_width[8] = 120;
        $field_width[13] = 125;
        $field_width[15] = 160;
        $field_width[16] = 160;

        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code.'.jpg',   //保存的文件名
            'title' => "数据更新时间 （". date("Y-m-d") ."） - 省份老店业绩同比-分经营模式 表号:S103",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd',strtotime('+1day')).'/'  //文件保存路径
        ];
        // 防止多次创建
//        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
//        if(file_exists($file)){
//            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
//        }
        // 生成图片
        return $this->create_image($params);
    }

    // 配饰每日销售数量
    public function create_table_s105($date = '')
    {
        // 编号
        $code = 'S105';
        $date = $date?:date('Y-m-d',strtotime('+1day'));
        $to_1 = date('Y-m-d');
        $to_2 = date('Y-m-d',strtotime('-1day'));
        $to_3 = date('Y-m-d',strtotime('-2day'));
        $to_4 = date('Y-m-d',strtotime('-3day'));
        $to_5 = date('Y-m-d',strtotime('-4day'));
        $to_6 = date('Y-m-d',strtotime('-5day'));
        $to_7 = date('Y-m-d',strtotime('-6day'));
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

        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => $code.'.jpg',   //保存的文件名
            'title' => "数据更新时间 （". date("Y-m-d") ."） - 配饰每日销售数量 表号:S105",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd',strtotime('+1day')).'/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image($params);
    }


    public function create_image($params)
    {
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
            if(isset($item['省份']) && $item['省份'] == '合计'){
                imagefilledrectangle($img, 0, $y1+30*($key+1), $x2+3000*($key+1), $y2+30*($key+1), $yellow);
            }
        }
        // create_table_s105
        if ($params['banben'] == '图片报表编号: S105') {
            if(isset($item['一级分类']) && $item['一级分类'] == '总计'){
                imagefilledrectangle($img, 0, $y1+30*($key+1), $x2+3000*($key+1), $y2+30*($key+1), $yellow);
            }
        }
        foreach($base['column_x_arr'] as $key => $x){
            imageline($img, $x, $border_top, $x, $border_bottom,$border_coler);//画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $params['table_header'][$key]);//写入表头文字
            $sum += $params['field_width'][$key];
        }

        //画表格横线
        foreach($params['data'] as $key => $item){
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0]/2 - $first_len/2+$base['border'], $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $key+1);//写入序号
            $sub = 0;
            $sum = $params['field_width'][0]+$base['border'];
            foreach ($item as $k =>$value){
                if(empty($value)){
                    $value = '';
                }
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

        imagepng($img,$save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        echo '<img src="/'.$save_path.'"/>';
    }

    function create_table($params)
    {
        $base = [
            'border' => 1,//图片外边框
            'file_path' => $params['file_path'],//图片保存路径
            'title_height' => 35,//报表名称高度
            'title_font_size' => 16,//报表名称字体大小
            'font_url' => realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'),//字体文件路径
            'text_size' => 12,//正文字体大小
            'row_hight' => 30,//每行数据行高
        ];


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
        $text_coler = imagecolorallocate($img, 0, 0, 0);//设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150);//设定边框颜色
        $xb  = imagecolorallocate($img, 255,255,255);//设定图片背景色

        $red = imagecolorallocate($img, 255,0,0);//设定图片背景色
        $green = imagecolorallocate($img, 24,98,0);//设定图片背景色
        $chengse = imagecolorallocate($img, 255,72,22);//设定图片背景色
        $blue = imagecolorallocate($img, 0,42,212);//设定图片背景色
        $yellow = imagecolorallocate($img, 238,228,0);//设定图片背景色
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
        foreach($base['column_x_arr'] as $key => $x){
            imageline($img, $x, $border_top, $x, $border_bottom,$border_coler);//画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $base['font_url'], $params['table_header'][$key]);//写入表头文字
            $sum += $params['field_width'][$key];
        }
        //画表格横线
        foreach($params['data'] as $key => $item){
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $base['font_url'], $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0]/2 - $first_len/2+$base['border'], $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $base['font_url'], $key+1);//写入序号
            $sub = 0;
            $sum = $params['field_width'][0]+$base['border'];
            foreach ($item as $k =>$value){
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                    if( $item['店铺名称']  === '合计'){
                        imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $chengse,$font_west, $value);
                        $sum += $params['field_width'][$sub];
                    }else{
                        if($k ==="累销递增率" || $k ==="昨日递增率"){
                            $value =str_replace('%', "", $value);
                            if($value < 0){
                                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $blue, $base['font_url'], $value.'%');//写入data数据
                                $sum += $params['field_width'][$sub];
                            }else{
                                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $red, $base['font_url'], $value.'%');//写入data数据
                                $sum += $params['field_width'][$sub];
                            }
                        }else{
                            imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $base['font_url'], $value);//写入data数据
                            $sum += $params['field_width'][$sub];

                        }
                }
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $base['font_url'], $params['title']);//imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
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

        imagepng($img,$save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        echo '<img src="/'.$save_path.'"/>';
    }
}