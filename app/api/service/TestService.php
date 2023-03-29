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
class TestService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    /**
     * 生成
     */
    public function create_test()
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
}