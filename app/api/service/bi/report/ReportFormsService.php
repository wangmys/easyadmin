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


    /**
     * 生成
     */
    public function create_table()
    {
        // 标题文本
        $title = "5856456";

        $data = Db::connect("mysql2")->table('old_customer_yearonyear')->select()->toArray();
        
        // 数据
        $data = [
            ["id" => "ID", "username" => "用户名", "score" => "得分"],
            ["id" => 1, "username" => "给你最好的我丶", "score" => 92],
            ["id" => 2, "username" => "抬首轻笑", "score" => 95],
            ["id" => 3, "username" => "45", "score" => 74]
        ];

        // 字体
        $font = realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf');//字体文件路径
        $font_size = 30;
        // 标题长度
        $this_title_box = imagettfbbox($font_size, 0, $font, $title);
        $title_x_len = $this_title_box[2] - $this_title_box[0];
        $title_height = 60;

        // 每行高度
        $row_hight = $title_height - 10;
        $id_x_len = $username_x_len = $score_x_len = 20;
        foreach ($data as $key => $value) {
            # imagettfbbox 获取文字 4 个角的坐标
            $this_id_box = imagettfbbox($font_size, 0, $font, $value["id"]);
            $this_username_box = imagettfbbox($font_size, 0, $font, $value["username"]);
            $this_score_box = imagettfbbox($font_size, 0, $font, $value["score"]);

            // 每列x轴长度
            $data[$key]["this_id_x_len"] = $this_id_x_len = $this_id_box[2] - $this_id_box[0];
            $data[$key]["this_username_x_len"] = $this_username_x_len = $this_username_box[2] - $this_username_box[0];
            $data[$key]["this_score_x_len"] = $this_score_x_len = $this_score_box[2] - $this_score_box[0];

            // 最长宽度作表格宽度
            $id_x_len = $this_id_x_len > $id_x_len ? $this_id_x_len : $id_x_len;
            $username_x_len = $this_username_x_len > $username_x_len ? $this_username_x_len : $username_x_len;
            $score_x_len = $this_score_x_len > $score_x_len ? $this_score_x_len : $score_x_len;
        }
        // 列数
        $column = 3;
        // 文本左右内边距
        $x_padding = 50;
        $y_padding = 10;
        // 图片宽度（每列宽度 + 每列左右内边距）
        $img_width = ($id_x_len + $username_x_len + $score_x_len) + $x_padding * $column * 2;
        // 图片高度（标题高度 + 每行高度 + 每行内边距）
        $img_height = $title_height + count($data) * ($row_hight + $y_padding);

        # 开始画图
        // 创建画布
        $img = imagecreatetruecolor($img_width, $img_height);

        # 创建画笔
        // 背景颜色（蓝色）
        $bg_color = imagecolorallocate($img, 24, 98, 229);
        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        $title_color = imagecolorallocate($img, 255, 255, 255);
        // 内容字体颜色（灰色）
        $text_color = imagecolorallocate($img, 152, 151, 152);


        // 画矩形 （先填充一个黑色的大块背景，小一点的矩形形成外边框）
        imagefill($img, 0, 0, $bg_color);
        imagefilledrectangle($img, 2, $title_height, $img_width - 3, $img_height - 3, $surface_color);

        // 画竖线
        imageline($img, $id_x_len + $x_padding * 2, $title_height, $id_x_len + $x_padding * 2, $img_height, $bg_color);

        // 画竖线
        imageline($img, $id_x_len + $username_x_len + $x_padding * 4, $title_height, $id_x_len + $username_x_len + $x_padding * 4, $img_height, $bg_color);

        // 写入标题
        imagettftext($img, $font_size, 0, $img_width / 2 - $title_x_len / 2, $title_height - $font_size / 2, $title_color, $font, $title);

        // 写入表格
        $temp_height = $title_height;
        foreach ($data as $key => $value) {
            # code...
            $temp_height += $row_hight + $y_padding;
            // 画线
            imageline($img, 0, $temp_height, $img_width, $temp_height, $bg_color);
            // 写入ID
            imagettftext($img, $font_size, 0, $id_x_len / 2 - $value["this_id_x_len"] / 2 + $x_padding, $temp_height - $font_size / 2, $text_color, $font, $value["id"]);
            // 写入username
            imagettftext($img, $font_size, 0, $username_x_len / 2 - $value["this_username_x_len"] / 2 + $x_padding * 3 + $id_x_len, $temp_height - $font_size / 2, $text_color, $font, $value["username"]);
            // 写入score
            imagettftext($img, $font_size, 0, $score_x_len / 2 - $value["this_score_x_len"] / 2 + $x_padding * 5 + $id_x_len + $username_x_len, $temp_height - $font_size / 2, $text_color, $font, $value["score"]);
        }
        $save_path = "./test.jpg";
        imagepng($img, $save_path);
        echo "<img src ='/test.jpg' />";
    }
    
    public function create_table2()
    {
        $code = 'S101';
        $date = date('Y/n/30');
        $sql = "select 经营模式,省份,店铺名称,前年同日,去年同日,昨天销量,前年对比今年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率,累销递增金额差,前年累销递增金额差,累销递增金额差 from Sheet1 where 更新时间 = '$date' and  经营模式 = '加盟'";
        $data = Db::query($sql);
        $table_header = ['行号'];
        $table_header = array_merge($table_header, array_keys($data[0]));
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 130;
        }
        $field_width[0] = 60;
        $field_width[1] = 80;
        $field_width[2] = 160;
        $field_width[7] = 220;
        $field_width[12] = 220;
        $field_width[13] = 160;
        $field_width[14] = 160;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [

        ];
        //参数
        $params = [
            'row' => count($data),          //数据的行数
            'file_name' =>  $code .'.jpg',      //保存的文件名
            'title' => "数据更新时间 （". date("Y-m-d", strtotime("-1 day")) ."）老店同比环比递增及完成率",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $this->create_table3($params);
    }

    function create_table3($params)
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

    /**
     * 发送
     */
    public function send_pic()
    {
        
    }
}