<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\facade\Db;
use app\api\service\dingding\Sample;
use voku\helper\HtmlDomParser;
use think\cache\driver\Redis;

class Test
{
    public function run()
    {

        // 设置目标URL
        $url = "https://www.qweather.com/weather30d/shangrao-101110801.html";
        $url2 = "http://www.tianqi.com/suining1/40/";
        $html = HtmlDomParser::file_get_html($url);
        $ret = $html->find('div[class="calendar__month d-flex flex-wrap"]');
        $arr = [];
        // 是否跨月
        $isStep = 0;
        foreach ($ret as $item){
                foreach ($item->find('a[class="calendar__date jsWeather30CalendarItem"]') as $element){
                    $temperature = trim($element->find('p')[0]->nodeValue);
                    $weather_time = $element->find('span')[0]->nodeValue;
                    $weather_info = explode('~',$temperature);
                    $max_c = $weather_info[0];
                    $min_c = $weather_info[1];
                    if(!strpos($weather_time,'月') === false){
                        $isStep = 1;
                    }
                    switch ($isStep){
                        // 本月
                        case 0:
                             $Date = date("Y-m-{$weather_time}");
                             break;
                        // 下月一号
                        case 1:
                            $month = floor($weather_time);
                            $weather_time = 1;
                            $Date = date("Y-{$month}-1");
                            $isStep = 2;
                            break;
                        // 下月
                        case 2:
                            $month = date('m',strtotime('+30day'));
                            $Date = date("Y-{$month}-{$weather_time}");
                            break;
                    }
                    $arr[] = [
                        'weather_time' => $weather_time,
                        'temperature' => $temperature,
                        'max_c' => $max_c,
                        'min_c' => $min_c,
                        'date' => $Date
                    ];
                }
        }
        echo '<pre>';
        print_r($arr);
        die;
    }

    /**
     * 获取天气
     */
    public function getWeather()
    {
        $url = "https://tianqi.2345.com/wea_forty/57516.htm";
        $url = "http://www.weather.com.cn/weather40d/101280101.shtml";
        $html = HtmlDomParser::file_get_html($url);
        $el = $html->find('div[class="W_left"] table');
        echo '<pre>';
        print_r($el);
        die;
        foreach ($el as $k){
            echo '<pre>';
            print_r($el);
            die;
        }
    }

    /**
     * 测试redis
     */
    public function testRedis()
    {
        $redis = new Redis;
        echo '<pre>';
        print_r($redis);
        die;
    }
}
