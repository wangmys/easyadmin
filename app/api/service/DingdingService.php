<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\api\service;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use app\admin\model\weather\Weather;
use app\admin\model\weather\Customers;
use think\facade\Config;

/**
 * 天气信息服务
 * Class AuthService
 * @package app\common\service
 */
class DingdingService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    // 王氏家族,测试机器人
    protected $hook_test_url = 'https://oapi.dingtalk.com/robot/send?access_token=f892dc03bdf32e94a7332d4a04c8d4328f940a8c6edd2140f3b3a33ab82589de';
    // 索歌测试机器人
    protected $sg_hook_url = '';
    protected $token = '';

    public static function getAccessToken()
    {
        if (empty(cache('dd_access_token'))) {
            $AppKey = 'dinga7devai5kbxij8zr';
            $AppSecret = 'JnQ_2VRvr5BFKlBiTnGf3mnyiNCb3pafnkg5FmB_SkNzyNBPtXCE1vLdHjpNwc1A';
            $url = "https://oapi.dingtalk.com/gettoken?appkey=" . $AppKey . "&appsecret=" . $AppSecret;
            $re = file_get_contents($url);
            $obj = json_decode($re);
            $access_token = $obj->access_token;
            cache('dd_access_token', $access_token, 7200);
        } else {
            $access_token = cache('dd_access_token');
        }
        return $access_token;
    }

    /***
     * 构造方法
     * WeatherService constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        // 实例化天气模型
        $this->weather = new Weather;
        // 店铺模型
        $this->customers = new Customers;
        // 获取token
        $this->token = self::getAccessToken();
    }

    /**
     * 推送
     */
    public function send($title = '数据表格milin',$jpg_url = 'https://bx.babiboy.com/img/20230316/S038.jpg',$robot = '')
    {
        $time = date('Y-m-d H:i:s');
        $arr = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => [ "$title"],
                'text'=>"#### **$title** \n> \n> ![screenshot]($jpg_url)\n> ###### $time \n",
            ]
        ];
        // 推送地址
        if(empty($robot)) $robot = $this->hook_test_url;
        $jsonStr = json_encode($arr); //转换为json格式
        $result = curl_post($robot, $jsonStr);
        return $json = json_decode($result, false);
    }

    /**
     * 创建图片
     * @param $params
     */
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
        $x2 =1542;
        $y2=65;
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
}