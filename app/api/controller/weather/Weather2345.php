<?php
declare (strict_types = 1);

namespace app\api\controller\weather;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Weather2345Model;
use app\admin\model\weather\Customers as CustomersM;

/**
 * 天气2345接口
 */
class Weather2345
{
    protected $model;
    protected $customers;
    public function __construct()
    {
        $this->model = new Weather2345Model;
        $this->customers = new CustomersM;
    }

    public function get_weather2345() {

        $page = 1;
        $limit = 1000;

        $list = $this->customers
        ->field('c.State as 省份,c.CustomItem30 as 温带,c.CustomItem36 as 温区,c.CustomerName as 店铺,c.liable as 商品负责人,c.City,c.cid,c.url_2345_cid')
        ->field(['cu.City'=>'绑定城市'])
        ->alias('c')
        ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
        ->leftJoin('city_url cu','cu.cid = c.cid')
        ->where(['c.ShutOut' => 0])
        ->where('c.RegionId','<>',55)
        ->order('c.State asc,c.CustomItem30 asc,c.CustomItem36 asc,c.CustomerName asc,c.CustomerCode asc')
        ->page($page, $limit)
        ->select();

        // 获取日期列表
        $dateList = $this->getDateList(1);
        $list = $list->toArray();
        if(!empty($list)){
            foreach ($list as &$v_list) {
                $v_list['省份'] = mb_substr($v_list['省份'], 0, 2);
                $v_list['cid'] = $v_list['url_2345_cid'] ?: $v_list['cid']; 
            }
            $cid_list = array_column($list,'cid');
            // 查询天气
            $weather_list = $this->model->field('cid,id,min_c,max_c,weather_time,temperature')->whereIn('cid',$cid_list)->where('weather_time','in',array_values($dateList))->select();
            // print_r($weather_list->toArray());die;
            foreach ($weather_list as $kk => $vv){
                foreach ($list as $k => $v){
                    if($vv['cid'] == $v['cid']){
                        $key = date('m-d',strtotime($vv['weather_time']));

                        $list[$k][$key] = $vv['min_c'].'~'.$vv['max_c'];
                    }
                }
            }
        }

        //最终转换
        foreach ($list as &$vv_list) {
            unset($vv_list['City']);
            unset($vv_list['cid']);
            unset($vv_list['url_2345_cid']);
        }
        return json($list);

    }



    /**
     * @param int $type 返回格式 0 天数 1 Y-m-d
     * @return array
     */
    public function getDateList($type = 0)
    {
        $str = 'Y-m-d';
        if($type == 0){
            $str = 'm-d';
        }
        // 开始日期
        $start_date = date('Y-m-d',strtotime(date('Y-m-d').'-3day'));
        // 日期列表
        $date_list = [];
        for ($i = 0;$i <= 23;$i++){
            $date_list[] = date($str,strtotime($start_date."+{$i}day"));
        }
        return $date_list;
    }

}
