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

namespace app\common\service;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use app\admin\model\weather\Weather;
use app\admin\model\weather\Customers;

/**
 * 天气信息服务
 * Class AuthService
 * @package app\common\service
 */
class WeatherService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];



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
        return $this;
    }

    /**
     * 获取天气数据
     * @param int $cid
     * @return array
     */
    public function getWeather40($cid = 0)
    {
        $url = CityUrl::where([
            'cid' => $cid
        ])->value('url');
        if(empty($url)) return [];
        $html = HtmlDomParser::file_get_html($url);
        $ret = $html->find('ul[class=weaul]');
        //另外一种获取div的
        if(!empty($ret)) {
            foreach ($ret as $item){
                foreach ($item->find('li') as $element){
                    $nowMonth = date('m');
                    $nowYear = date('Y');
                    if($nowMonth==12){
                        $monthArr = explode('-',$element->find('span[class=fl]', 0)->innertext);
                        if($monthArr[0]==1){
                            $nowYear = date('Y')+1;
                        }
                    }
                    $weather_time = $nowYear.'-'.$element->find('span[class=fl]', 0)->innertext.' 00:00:00';
                    $ave_c = (intval($element->find('div[class=weaul_z]', 1)->children(0)->text) + intval($element->find('div[class=weaul_z]', 1)->children(2)->text)) /2;
                    $arr[] = [
                        'cid' => $cid,
                        'weather_time' => $weather_time, // 日期
                        'img_add' => $element->find('img', 0)->src, // 图片
                        'text_weather' => $element->find('div[class=weaul_z]', 0)->innertext, // 文字天气
                        'temperature' => $element->find('div[class=weaul_z]', 1)->innertext, // 温度
                        'min_c'=> $element->find('div[class=weaul_z]', 1)->children(0)->text,
                        'max_c'=> $element->find('div[class=weaul_z]', 1)->children(2)->text,
                        'ave_c'=> $ave_c,
                    ];
                }

            }
            return $arr;
        }
        return [];
    }

    /**
     * 获取城市,天气地址列表
     * @return array|false
     */
    public function getCityUrl()
    {
        $CityUrl = 'https://www.tianqi.com/40tianqi';
        $html = HtmlDomParser::file_get_html($CityUrl);
        if (!$html) {
            return false;
        }
        $res = $html->find('div[class=list_box] dl');
        $i = 0;
        foreach ($res as $item) {
            $ljarr[] = [$res->find('dl', $i)->find('dt')->find('a')->text, ['name' => $res->find('dl', $i)->find('dd')->find('ul')->find('li')->find('a')->text, 'href' => $res->find('dl', $i)->find('dd')->find('ul')->find('li')->find('a')->href]];
            $i++;

        }
        $aaa = [];
        foreach ($ljarr as $key => $value) {
            $aaa[] = array(
                's' => $value[0][0],
                'c' => $value[1]
            );
        }
        foreach ($aaa as $bitem) {
            $ccc[] = ['s' => $bitem['s'], 'c' => array_combine($bitem['c']['name'], $bitem['c']['href'])];
        }

        foreach ($ccc as $ditem => $dvalue) {
            foreach ($dvalue as $eitem => $evalue) {
                if (is_array($evalue)) {
                    foreach ($evalue as $fitem => $fvalue) {
                        $sqlArr[] = ['province' => $dvalue['s'], 'city' => $fitem, 'url' => $fvalue];
                    }
                }

            }
        }
        return $sqlArr;
    }

    /**
     * 更新城市天气
     * @param int $cid
     * @return false|\think\Collection
     * @throws \Exception
     */
    public function updateCityWeather($cid = 0)
    {
        // 判断天气是否为空
        if(!empty($arr)){
            // 查询天气表,大于今天的天气记录
            $weather_log = $this->weather->where([
                'cid' => $cid
            ])->where('weather_time','>=',date('Y-m-d'))->column('weather_time');
            if(count($weather_log) > 0){
                foreach ($arr as $k => $v){
                    if(in_array($v['weather_time'],$weather_log)){
                        unset($arr[$k]);
                    }
                }
            }
            if(!empty($arr)){
                return $this->weather->saveAll($arr);
            }
        }
        return false;
    }
}