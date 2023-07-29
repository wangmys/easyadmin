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
use voku\helper\HtmlDomParser;
use app\admin\model\weather\CityUrl;
use app\admin\model\weather\Weather;
use app\admin\model\weather\Weather2345Model;
use app\admin\model\weather\Customers;
use app\admin\model\weather\Capital;
use think\facade\Log;

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

    protected $weather2345=null;

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
        $this->weather2345 = new Weather2345Model();
        // 店铺模型
        $this->customers = new Customers;
        // 省会天气数据
        $this->capital = new Capital;
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
                    $weather_time = $nowYear.'-'.$element->find('span[class=fl]', 0)->innertext;
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
     * 获取天气数据(2345天气)
     * @param int $cid
     * @return array
     */
    public function getWeather40_2345($cid = 0)
    {
        $url = CityUrl::where([
            'cid' => $cid
        ])->value('url_2345');
        if(empty($url)) return [];

        $res = [];

        try {
            $html = HtmlDomParser::file_get_html($url);

            $res = $html->find('script', 28)->textContent;
            $res = $res ? str_replace('window.statisticsReportModule.init();var fortyData=', '', $res) : '';
            $res = $res ? json_decode($res, true) : [];
            $res = $res ? $res['data'] : [];
        } catch (\Exception $e) {
            //记录失败日志：
            Log::write(json_encode($e->getMessage()), 'crawl2345 error（绑定城市，保存后抓取不到数据）:cid='.$cid.',weather_url='.$url);
        }

        $add_data = [];
        if ($res) {
            foreach ($res as $v_res) {

                $add_data[] = [
                    'cid' => $cid,
                    'weather_time' => date('Y-m-d', $v_res['time']),
                    'text_weather' => $v_res['weather'],
                    'temperature' => $v_res['weather'],
                    'min_c' => $v_res['night_temp'],
                    'max_c' => $v_res['day_temp'],
                    'ave_c' => round( ( $v_res['night_temp'] + $v_res['day_temp'] ) / 2 , 1),
                ];

            }

            return $add_data;

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
        // 拉取近40天的天气
        $arr = $this->getWeather40($cid);
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

    /**
     * 更新城市天气（天气2345）
     * @param int $cid
     * @return false|\think\Collection
     * @throws \Exception
     */
    public function updateCityWeather2345($cid = 0)
    {
        // 拉取近40天的天气
        $arr = $this->getWeather40_2345($cid);
        // 判断天气是否为空
        if(!empty($arr)){

            $if_exist_cid = $this->weather2345::where([['cid', '=', $cid]])->field('id')->find();
            Db::startTrans();
            try {

                if (!$if_exist_cid) {
                    $this->weather2345::insertAll($arr);
                } else {
                    foreach ($arr as $vv_data) {
                        $if_exist_weather = $this->weather2345::where([['cid', '=', $cid], ['weather_time', '=', $vv_data['weather_time']]])->field('id')->find();
                        if (!$if_exist_weather) {
                            $this->weather2345::create($vv_data);
                        } else {
                            $this->weather2345::where([['id', '=', $if_exist_weather['id']]])->update($vv_data);
                        }
                    }
                }

                Db::commit();
    
            } catch (\Exception $e) {
                //记录失败日志：
                Log::write(json_encode($e->getMessage()), 'crawl2345 error(WeatherService-->updateCityWeather2345方法天气入库或更新失败):cid='.$cid);
                Db::rollback();
            }

            return true;
        }
        return false;
    }

    /**
     * 获取省会城市列表
     */
    public function getCapitalList()
    {
        // 实例化城市列表模型
        $cityModel = new CityUrl;
        // 查询省会城市
        return $cityModel->field('*,LEFT(province,2) as province_name')->group('province')->order('cid','asc')->select()->toArray();
    }

    /**
     * 设置省会城市
     * @return array
     */
    public function setCapitalProvince()
    {
        // 省会列表
        $list = $this->getCapitalList();
        foreach ($list as $k => $v){
            $res[] = $v->where(['cid' => $v['cid']])->update([
                'is_capital' => 1
            ]);
        }
        return $res;
    }

    /**
     * 保存省会城市天气数据
     */
    public function saveWeatherData($data)
    {
        return $this->capital->saveAll($data);
    }
}