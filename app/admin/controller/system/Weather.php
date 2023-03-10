<?php


namespace app\admin\controller\system;

use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Customers as CustomersM;
use app\admin\model\weather\CityUrl;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use think\cache\driver\Redis;


/**
 * Class Weather
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="天气管理")
 */
class Weather extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new WeatherM;
        $this->customers = new CustomersM;
    }

    public function getAllCustomerWeather(){
        $where = input('param.')?input('param.'):[];

        //定义取前3天的天气数据
        $olddate = date("Y-m-d 00:00:00", strtotime("-3 day"));
        $day27 = date("Y-m-d 00:00:00", strtotime("+25 day"));
        $day17 = date("Y-m-d 00:00:00", strtotime("+13 day"));
        if (!empty($where['WeatherDate'])){
            $olddate = date("Y-m-d 00:00:00",strtotime("-3 day",strtotime($where['WeatherDate'])));
            $sortDay = date("d",strtotime($where['WeatherDate']));
        }else{
            $sortDay = date("d");
        }
        //取出所有店铺+区域+关联的省份数据
        $shopRegionArr = $this->customers
            //,w.weather_time,w.img_add,w.temperature,w.text_weather,w.max_c,w.min_c,w.ave_c,cu.province
            ->field('c.CustomerName,c.State,c.City,c.SendGoodsGroup,cr.Region,c.dudao')
            ->field(['cu.City'=>'BdCity'])
            ->alias('c')
           /* ->leftJoin('weather w','c.cid = w.cid')*/
            ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
            ->leftJoin('city_url cu','cu.cid = c.cid')
            ->where(function ($query) use ($where) {
                if (!empty($where['CustomerName'])) $query->where('c.CustomerName','in', $where['CustomerName']);
                if (!empty($where['State'])) $query->where('c.State', $where['State']);
                if (!empty($where['Region'])) $query->where('cr.Region', $where['Region']);
                if (!empty($where['SendGoodsGroup'])) $query->where('c.SendGoodsGroup', $where['SendGoodsGroup']);
                if (!empty($where['City'])) $query->where('c.City', $where['City']);
                if (!empty($where['liable'])) $query->where('c.liable', $where['liable']);
                $query->where(1);
            })
            ->where('c.RegionId','<>',55)
            ->order('State asc,Region asc')
            ->select()
            ->toArray();

            //取出前3天的天气日期并格式化成（天：DATE_FORMAT(weather_time,'%d')）
        $dateArr = $this->model
            ->where('weather_time','>=',$olddate)
            ->group('weather_time')
            ->order('weather_time asc')
            ->limit(0,17)
            ->fieldRaw("DATE_FORMAT(weather_time,'%m-%d') as www")
            ->select()
            ->column("www");
        //前面的天用$riqi1变量保存，$dateArr用到后面来遍历用
        $riqi1 = $dateArr;
        //给前面展示的温度的天加s前缀用于区分后面的天
        foreach ($riqi1 as $rrrkey=>$rrrvalue){
            $riqi1[$rrrkey] = 's'.$rrrvalue;
        }
        //从最前面加province，Region，CustomerName
       array_unshift($riqi1,'Region','CustomerName','State','City','BdCity','SendGoodsGroup');
        //从最后面加tq
       array_push($riqi1,'tq');
       //因为前面有加元素，所以这里用没动过的$dateArr来遍历，添加没动过的天来做后面的天
        foreach ($dateArr as $ditem){
            array_push($riqi1,$ditem);
        }
        //到这里$riqi1里面已经组成了所有的field，通过遍历$riqi1来加上title，showHeaderOverflow，width等用新数组变量来接收
        $columns = [];
        foreach ($riqi1 as $rqitem=>$rqvalue){
            $columns[$rqitem]['field2'] = $rqvalue;
            if($rqvalue=='State'){
                $columns[$rqitem]['field'] = 'State';
                $columns[$rqitem]['title'] = '省份';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['sort'] = '0';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='Region'){
                $columns[$rqitem]['field'] = 'Region';
                $columns[$rqitem]['title'] = '区域';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['sort'] = '1';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='City'){
                $columns[$rqitem]['field'] = 'City';
                $columns[$rqitem]['title'] = '地级市';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['sort'] = '3';
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='BdCity'){
                $columns[$rqitem]['field'] = 'BdCity';
                $columns[$rqitem]['title'] = '绑定的城市';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['sort'] = '4';
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='SendGoodsGroup'){
                $columns[$rqitem]['field'] = 'SendGoodsGroup';
                $columns[$rqitem]['title'] = '温度带';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['sort'] = '5';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='CustomerName'){
                $columns[$rqitem]['field'] = 'CustomerName';
                $columns[$rqitem]['title'] = '店铺';
                $columns[$rqitem]['width'] = '100';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['sort'] = '2';
                $columns[$rqitem]['fixed'] = 'left';
            }else if($rqvalue=='tq'){
                $columns[$rqitem]['title'] = '天气';
                $columns[$rqitem]['width'] = '50';
                $columns[$rqitem]['showHeaderOverflow'] = true;
                $columns[$rqitem]['sort'] = '7';
            }else{
                $asd = explode('s',$rqvalue);
                if(!empty($asd[1])){
                    $columns[$rqitem]['title'] = $asd[1];
                    $columns[$rqitem]['width'] = '80';
                    $columns[$rqitem]['showHeaderOverflow'] = true;
                    $columns[$rqitem]['sort'] = '6';
                }else{
                    $columns[$rqitem]['title'] = $rqvalue;
                    $columns[$rqitem]['width'] = '80';
                    $columns[$rqitem]['showHeaderOverflow'] = true;
                    $columns[$rqitem]['sort'] = '8';

                }
            }
        }

        $columnsSort = array_sort($columns,'sort',SORT_ASC);
        foreach ($columnsSort as $ccckey=>$cccalue){
            if(strpos($cccalue['field2'],'-')){
                $fgarr = explode('-',$cccalue['field2']);

                if(strstr($fgarr[0],'s')){

                    //温度
                    $columnsSort[$ccckey]['field'] = 's'.$fgarr[1];

                    $columnsSort[$ccckey]['title'] = $fgarr[1];
                }else{
                    //天气
                    $columnsSort[$ccckey]['field'] = $fgarr[1];
                    $columnsSort[$ccckey]['title'] = $fgarr[1];
                }

            }
        }
        /**
         * ----------------到这里columns算是完成了，下面开始data-------------------
         */
        /**
         * 天气数据开始
         */
        $weather = $this->model
            ->fieldRaw("DATE_FORMAT(w.weather_time,'%d') as weather_time,text_weather,min_c,max_c,ave_c,CustomerName")
            ->alias('w')
            ->leftJoin('customers c','c.cid = w.cid')
            ->where('weather_time','>=',$olddate)
            ->where('weather_time','<=',$day27)
            ->where('c.cid','>','0')

            ->select()
            ->toArray();


        foreach ($shopRegionArr as $dataItem=>$dataValue){
            //在这里把xx天气预报40天截取成xx，去掉后面的天气预报40天
            foreach ($weather as $weatherItem=>$weatherValue){
                if($dataValue['CustomerName']==$weatherValue['CustomerName']){

                    $shopRegionArr[$dataItem]['s'.$weatherValue['weather_time']] = $weatherValue['min_c'].'~'.$weatherValue['max_c'];
                    $shopRegionArr[$dataItem]['ave'.$weatherValue['weather_time']] = $weatherValue['ave_c'];
                    $shopRegionArr[$dataItem]['min'.$weatherValue['weather_time']] = $weatherValue['min_c'];
                    $shopRegionArr[$dataItem]['max'.$weatherValue['weather_time']] = $weatherValue['max_c'];
                    $shopRegionArr[$dataItem][$weatherValue['weather_time']] = $weatherValue['text_weather'];

                }

            }
        }
        //排序
        if(!empty($where['sort']) && !empty($where['TemperatureSort'])){
            if($where['sort']=='SORT_ASC'){
                $shopRegionArrSort = array_sort($shopRegionArr,$where['TemperatureSort'].$sortDay,SORT_ASC);
                $res['dateSelect'] = ['d1'=>$olddate,'d2'=>$day17];
                $res['columns'] = $columnsSort;
                $res['data'] = $shopRegionArrSort;
            }
            if($where['sort']=='SORT_DESC'){

                $shopRegionArrSort = array_sort($shopRegionArr,$where['TemperatureSort'].$sortDay,SORT_DESC);
                $res['dateSelect'] = ['d1'=>$olddate,'d2'=>$day17];
                $res['columns'] = $columnsSort;
                $res['data'] = $shopRegionArrSort;
            }

        }else{
            $res['dateSelect'] = ['d1'=>$olddate,'d2'=>$day17];
            $res['columns'] = $columnsSort;
            $res['data'] = $shopRegionArr;
        }
        $liablesql = [];

        $liableOption=[];
        foreach ($liablesql as $likey=>$lival){
            $liableOption[] = ['label'=>$lival['商品负责人'],'value'=>$lival['商品负责人']];
        }
        $cascader = $this->storeCascade($shopRegionArr);
        $res['liable'] = $liableOption;
        $res['cascader'] = reform_keys(array_values($cascader));
        return json($res);
    }

    public function storeCascade($shopRegionArr = []){
        if(empty($shopRegionArr)){
            $shopRegionArr = $this->customers
                ->field('c.CustomerName,cr.Region,c.dudao')
                ->alias('c')
                ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
                ->where('c.RegionId','<>',55)
                ->order('Region asc')
                ->select()
                ->toArray();
        }
        //店铺级联选择器数据
        $RegionFields = array_unique(array_column($shopRegionArr, 'Region'));//所有大区
        $dudaoFields = array_unique(array_column($shopRegionArr, 'dudao'));//所有督导
        $cascader=[];
        foreach ($RegionFields as $RegionK=>$RegionV){
            foreach ($dudaoFields as $dudaoK=>$dudaoV){
                foreach ($shopRegionArr as $shopK=>$shopV){
                    if($shopV['Region']==$RegionV && $shopV['dudao']==$dudaoV){
                        $cascader[$RegionK]['label'] = $RegionV;
                        $cascader[$RegionK]['value'] = $RegionV;
                        $cascader[$RegionK]['children'][$dudaoK]['label'] = $dudaoV;
                        $cascader[$RegionK]['children'][$dudaoK]['value'] = $dudaoV;
                        $cascader[$RegionK]['children'][$dudaoK]['children'][] = ['label'=>$shopV['CustomerName'],'value'=>$shopV['CustomerName']];
                    }
                }
            }
        }
        return reform_keys(array_values($cascader));
    }

    /**
     * @NodeAnotation(title="天气列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $count = $this->customers
            ->alias('c')
            ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
            ->leftJoin('city_url cu','cu.cid = c.cid')
            ->where(function ($query) use ($where) {
                if (!empty($where['CustomerName'])) $query->where('c.CustomerName','in', $where['CustomerName']);
                if (!empty($where['State'])) $query->where('c.State', $where['State']);
                if (!empty($where['Region'])) $query->where('cr.Region', $where['Region']);
                if (!empty($where['SendGoodsGroup'])) $query->where('c.SendGoodsGroup', $where['SendGoodsGroup']);
                if (!empty($where['City'])) $query->where('c.City', $where['City']);
                if (!empty($where['liable'])) $query->where('c.liable', $where['liable']);
                $query->where(1);
            })
            ->where('c.RegionId','<>',55)->count();

            $list = $this->customers
            ->field('c.CustomerId,c.CustomerName,c.State,c.City,c.SendGoodsGroup,cr.Region,c.dudao,c.cid')
            ->field(['cu.City'=>'BdCity'])
            ->alias('c')
            ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
            ->leftJoin('city_url cu','cu.cid = c.cid')
            ->where(function ($query) use ($where) {
                if (!empty($where['CustomerName'])) $query->where('c.CustomerName','in', $where['CustomerName']);
                if (!empty($where['State'])) $query->where('c.State', $where['State']);
                if (!empty($where['Region'])) $query->where('cr.Region', $where['Region']);
                if (!empty($where['SendGoodsGroup'])) $query->where('c.SendGoodsGroup', $where['SendGoodsGroup']);
                if (!empty($where['City'])) $query->where('c.City', $where['City']);
                if (!empty($where['liable'])) $query->where('c.liable', $where['liable']);
                $query->where(1);
            })
            ->where('c.RegionId','<>',55)
            ->order('State asc,Region asc')
            ->page($page, $limit)
            ->select();

            // 获取日期列表
            $dateList = $this->getDateList(1);
            $list = $list->toArray();
            if(!empty($list)){
                $cid_list = array_column($list,'cid');
                // 查询天气
                $weather_list = $this->model->field('cid,id,min_c,max_c,weather_time,temperature')->whereIn('cid',$cid_list)->where('weather_time','in',array_values($dateList))->select();
                foreach ($weather_list as $kk => $vv){
                    foreach ($list as $k => $v){
                        if($vv['cid'] == $v['cid']){
                            $key = date('m-d',strtotime($vv['weather_time']));
                            $list[$k][$key] = $vv['min_c'].' ~ '.$vv['max_c'].'℃';
                        }
                    }
                }
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list
            ];
            return json($data);
        }
        return $this->fetch();
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
        for ($i = 0;$i <= 17;$i++){
            $date_list[] = date($str,strtotime($start_date."+{$i}day"));
        }
        return $date_list;
    }

    /**
     * 获取天气日期字段列
     */
    public function getWeatherField()
    {
        $list = $this->getDateList(0);
        $data = [
                'code'  => 1,
                'msg'   => '',
                'data'  => $list
            ];
        return json($data);
    }
    
    public function city()
    {
        // 获取店铺ID
        $id = $this->request->get('CustomerId');
        $city_model = new CityUrl;
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $city_id = $post['city']??0;
            if(empty($city_id)){
                $this->error('请选择绑定城市');
            }
            // 查询城市
            $city_cid = $city_model->where(['cid' => $city_id])->value('cid');
            if(empty($city_cid)){
                $this->error('城市不存在');
            }
            // 执行绑定
            $res = $this->customers->where([
                'CustomerId' => $id
            ])->update(['cid' => $city_cid]);
            $rs = $this->creatWeather40($city_id);
            if($res){
                 // 绑定城市
                $this->success('绑定成功');
            }
            $this->error('绑定失败');
        }

        // 查询店铺记录
        $customer = $this->customers->field('CustomerName,State,City,cid')->where(['CustomerId' => $id])->find();
        if(empty($customer)){
            $this->error('记录不存在');
        }
        // 提取城市关键字
        $keywords = [];
        foreach (['CustomerName','City'] as $k=>$v){
//            if(empty($customer[$v])) continue;
//            switch ($v){
//                case 'CustomerName':
//                    $keywords[$v] = mb_substr($customer[$v],0,-2).'%';
//                    break;
//                case 'City':
//                    $keywords[$v] = mb_substr($customer[$v],0,2).'%';
//                    break;
//            }
        }
        // 查询店铺列表
        $city_list = $city_model->where(function ($q)use($keywords){
            if(!empty($keywords)) $q->where('city', 'like',$keywords,'OR');
        })->order('cid','desc')->column('cid,city,province');
        foreach ($city_list as $kk => $vv){
            $prefix = mb_substr($vv['province'],0,-7);
            $city_list[$kk]['city'] = $prefix.'---'.$city_list[$kk]['city'];
        }
        $this->assign([
            'city_list' => $city_list,
            'cid' => $customer['cid'] ?? 0
        ]);
        return $this->fetch();
    }

    /**
     * 更新天气
     * @param int $cid
     * @return false|\think\Collection
     * @throws \Exception
     */
    public function creatWeather40($cid = 0)
    {
        $url = CityUrl::where([
            'cid' => $cid
        ])->value('url');
        if(empty($url)) return false;
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
            // 判断天气是否为空
            if(!empty($arr)){
                // 查询天气表,大于今天的天气记录
                $weather_log = $this->model->where([
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
                    return $this->model->saveAll($arr);
                }
            }
        }
        return false;
    }
    
    public function getAll()
    {
        $CityUrl = 'https://www.tianqi.com/40tianqi';
        $html = HtmlDomParser::file_get_html($CityUrl);
        if(!$html){
            return false;
        }
        $res = $html->find('div[class=list_box] dl');
        $i =0;
        foreach ($res as $item){
            $ljarr[] = [$res->find('dl',$i)->find('dt')->find('a')->text,['name'=>$res->find('dl',$i)->find('dd')->find('ul')->find('li')->find('a')->text,'href'=>$res->find('dl',$i)->find('dd')->find('ul')->find('li')->find('a')->href]];
            $i++;

        }
        $aaa = [];
        foreach ($ljarr as $key=>$value){
            $aaa[] = array(
                's'=>$value[0][0],
                'c'=>$value[1]
            );
        }
        foreach ($aaa as $bitem){
            $ccc[] = ['s'=>$bitem['s'],'c'=>array_combine($bitem['c']['name'],$bitem['c']['href'])];
        }

        foreach ($ccc as $ditem=>$dvalue){
            foreach ($dvalue as $eitem=>$evalue){
                if(is_array($evalue)){
                    foreach ($evalue as $fitem=>$fvalue){
                        $sqlArr[] = ['province'=>$dvalue['s'],'city'=>$fitem,'url'=>$fvalue];
                    }
                }

            }
        }
        echo '<pre>';
        print_r($sqlArr);
        die;
    }
}
