<?php

namespace app\admin\controller\system\code;


use app\admin\model\code\SizePurchaseStock;
use app\admin\model\Stocks as StocksM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\Size7DaySale;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;
use app\admin\model\code\SizeAllRatio;
use app\admin\model\code\SizeWarehouseRatio;

/**
 * Class Ratio
 * @package app\admin\controller\system\code
 * @ControllerAnnotation(title="码比")
 */
class Ratio extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
    }



    /**
     * 页面
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        // 筛选
        $filters = $this->request->get();
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 指定货号
            $goodsno = $filters['货号']??'B32503010';
            $arr = [$goodsno];
            $list = [];
            foreach ($arr as $k => $v){

                // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods')
                        ->field('GoodsId,GoodsNo,GoodsName,UnitPrice,CategoryName,CategoryName1,CategoryName2,TimeCategoryName1,TimeCategoryName2,StyleCategoryName,StyleCategoryName2,LEFT(CategoryName,2) as Collar')
                    ->where([
                    'GoodsNo' => $v
                ])->find();

                // 查询尺码信息
                $size = Db::connect('sqlsrv')->table('ErpGoodsSize')->where([
                    'GoodsId' => $info['GoodsId'],
                    'IsEnable' => 1
                ])->select()->toArray();
                // 分离尺码列
                $size_list = array_column($size,'Size');
                // 图片信息
                $thumb = Db::connect('sqlsrv')->table('ErpGoodsImg')->where([
                    'GoodsId' => $info['GoodsId']
                ])->value('Img');

                // 单款累销
                $all_total = SizeAccumulatedSale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款7天销量
                $all_day7_total = Size7DaySale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款店铺预计库存
                $all_shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓可用库存
                $all_warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓在途库存
                $all_warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款当前总库存量 = 店铺预计库存 + 云仓可用库存 + 云仓在途库存
                $all_thisTotal = intval($all_shop_stock) + intval($all_warehouse_stock) + intval($all_warehouse_transit_stock);
                // 单款采购数量
                $all_purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款未入量 = 采购库存 - 累销 - 当前总库存
                $all_unearnedQuantity = intval($all_purchase_stock) - intval($all_total) - intval($all_thisTotal);

                // 货品上柜数
                $cabinets_num = SizeRanking::where(['货号' => $goodsno])->value("上柜家数");

                $total_item = [
                    '风格' => $info['StyleCategoryName'],
                    '一级分类' => $info['CategoryName1'],
                    '二级分类' => $info['CategoryName2'],
                    '领型' => $info['Collar'],
                    '近三天折率' => '100%',
                    '货品等级' => $info['StyleCategoryName2'],
                    '上柜数' => $cabinets_num,
                    '货号' => $v,
                    '尺码情况' => '合计',
                    '图片' => $thumb,
                    '周销' => $all_day7_total,
                    '累销' => $all_total,
                    '店铺库存' => $all_shop_stock,
                    '云仓库存' => $all_warehouse_stock??0,
                    '云仓在途库存' => $all_warehouse_transit_stock??0,
                    '当前总库存量' => $all_thisTotal,
                    '未入量' => $all_unearnedQuantity,
                    '周转' => '',
                    '单码售罄' => '',
                    '累销尺码比' => '',
                    '总库存' => '',
                    '当前库存' => '',
                    '单码售罄比' => '',
                    '当前单店均深' => bcadd($all_thisTotal / $cabinets_num,0,2)
                ];


                if(!empty($all_shop_stock) && !empty($all_total)){
                    $total_item['周转'] = bcadd($all_shop_stock / $all_total,0,2);
                }

                if(!empty($all_total) && !empty($all_total + $all_thisTotal)){
                    $total_item['单码售罄'] = bcadd($all_total / ($all_total + $all_thisTotal) * 100,0,2) . '%';
                }

                // 货品等级
                $config = sysconfig('site');
                // 单码缺量判断数值
                $level_rate = 0;
                if($info['StyleCategoryName2']=='B级'){
                    $level_rate = $config['level_b'];
                }else{
                    $level_rate = $config['level_other'];
                }

                // 总尺码
                $size_list = [
                    '库存_00/28/37/44/100/160/S',
                    '库存_29/38/46/105/165/M',
                    '库存_30/39/48/110/170/L',
                    '库存_31/40/50/115/175/XL',
                    '库存_32/41/52/120/180/2XL',
                    '库存_33/42/54/125/185/3XL',
                    '库存_34/43/56/190/4XL',
                    '库存_35/44/58/195/5XL',
                    '库存_36/6XL',
                    '库存_38/7XL',
                    '库存_40/8XL'
                ];

                foreach ($size_list as $key =>$value){

                    // 根据货号尺码获取周销尺码字段
                    $sum_key = Size7DaySale::getSizeKey($value);
                    // 周销
                    $day7_total = Size7DaySale::where(['货号' => $goodsno])->value("sum( `$sum_key` )");
                    // 根据尺码获取累销尺码字段
                    $total_key = SizeAccumulatedSale::getSizeKey($value);
                    // 累销
                    $total = SizeAccumulatedSale::where(['货号' => $goodsno])->value("sum( `$total_key` )");
                    // 店铺预计库存尺码字段
                    $stock_key = SizeShopEstimatedStock::getSizeKey($value);
                    // 店铺预计库存
                    $shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->value("sum( `$stock_key` )");
                    // 云仓可用库存尺码字段
                    $warehouse_key = SizeWarehouseAvailableStock::getSizeKey($value);
                    // 云仓可用库存
                    $warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_key` )");

                    // 云仓在途库存尺码字段
                    $warehouse_transit_key = SizeWarehouseTransitStock::getSizeKey($value);
                    // 云仓在途库存
                    $warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_transit_key` )");

                    // 当前总库存量
                    $thisTotal = intval($shop_stock) + intval($warehouse_stock) + intval($warehouse_transit_stock);
                    // 采购库存尺码字段
                    $purchase_key = SizePurchaseStock::getSizeKey($value);
                    // 采购数量
                    $purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `$purchase_key` )");
                    // 未入量 = 采购库存 - 累销 - 当前总库存
                    $unearnedQuantity = intval($purchase_stock) - intval($total) - intval($thisTotal);
                    // 当前单店均深

                    $item = [
                        '风格' => $info['StyleCategoryName'],
                        '一级分类' => $info['CategoryName1'],
                        '二级分类' => $info['CategoryName2'],
                        '领型' => $info['Collar'],
                        '近三天折率' => '100%',
                        '货品等级' => $info['StyleCategoryName2'],
                        '上柜数' => $cabinets_num,
                        '货号' => $v,
                        '尺码情况' => $value,
                        '图片' => $thumb,
                        '周销' => $day7_total,
                        '累销' => $total,
                        '店铺库存' => $shop_stock,
                        '云仓库存' => $warehouse_stock??0,
                        '云仓在途库存' => $warehouse_transit_stock??0,
                        '当前总库存量' => $thisTotal,
                        '未入量' => $unearnedQuantity,
                        '周转' => '',
                        '单码售罄' => '',
                        '累销尺码比' => '',
                        '总库存' => '',
                        '当前库存' => '',
                        '单码售罄比' => '',
                        '当前单店均深' => bcadd($thisTotal / $cabinets_num,0,2),
                    ];
                    // 周转 = 店铺库存 / 累销
                    if(!empty($shop_stock) && !empty($total)){
                        $item['周转'] = bcadd($shop_stock / $total,0,2);
                    }
                    // 单码售罄 = 累销 / (累销 + 当前总库存量)
                    if(!empty($total) && !empty($total + $thisTotal)){
                        $item['单码售罄'] = bcadd($total / ($total + $thisTotal) * 100,0,2).'%';
                    }
                    // 累销尺码比 = 单尺码累销 / 单款累销
                    if(!empty($total) && !empty($all_total)){
                        $item['累销尺码比'] = bcadd($total / $all_total * 100,0,2).'%';
                    }
                    // 总库存比 = 单码总库存(未入量 + 当前总库存量) / 单款总库存(未入量 + 当前总库存量)
                    if(!empty($unearnedQuantity + $thisTotal) && !empty($all_unearnedQuantity + $all_thisTotal)){
                        $item['总库存'] = bcadd(($unearnedQuantity + $thisTotal) / ($all_unearnedQuantity + $all_thisTotal) * 100,0,2).'%';
                    }
                    // 当前库存比 = 单码当前库存 / 单款当前库存
                    if(!empty($thisTotal) && !empty($all_thisTotal)){
                        $item['当前库存'] = bcadd($thisTotal / $all_thisTotal * 100,0,2).'%';
                    }
                    $item['单码售罄比'] = (floatval($item['单码售罄']) - floatval($total_item['单码售罄'])).'%';

                    if(intval($item['单码售罄比']) > $level_rate){
//                        $total_item['单码售罄比'] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px;' >单码缺量</span>";
                        $total_item['单码售罄比'] = "单码缺量";
//                        $item['单码售罄比'] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px;' >{$item['单码售罄比']}</span>";
                    }

                    $list[] = $item;
                }

                // 提取偏码判断数据
                $ranking_data = [];
                // 单码售罄比
                $sell_out_ratio = [];
                foreach ($list as $lk => $lv){
                    $size_k = Size7DaySale::getSizeKey($lv['尺码情况']);
                    $sell_out_ratio[$size_k] = floatval($lv['单码售罄比']);
                    $ranking_data['当前库存'][$size_k] = floatval($lv['当前库存']);
                    $ranking_data['总库存'][$size_k] = floatval($lv['总库存']);
                    $ranking_data['累销尺码比'][$size_k] = floatval($lv['累销尺码比']);
                }
                // 对数据进行排序
                foreach ($ranking_data as $kk => $vv){
                    asort($ranking_data[$kk]);
                }
                // 判断尺码个数,如果大于等于配置数,则使用配置数,如果小于配置数,则使用尺码数
                $_count = 3;
                if($n = count($ranking_data['累销尺码比']) < $_count){
                    $_count = $n;
                }
                // 获取指定前几名的尺码数据
                $ranking_arr = [];
                foreach ($ranking_data as $rk => $rv){
                    $item = array_slice($rv,-$_count,null,true);
                    $ranking_arr[$rk] = $item;
                }
                // 总库存偏码对比
                $total_inventory_1 = array_diff_key($ranking_arr['总库存'],$ranking_arr['累销尺码比']);
                $total_inventory_2 = array_diff_key($ranking_arr['累销尺码比'],$ranking_arr['总库存']);
                $total_inventory = $total_inventory_1 + $total_inventory_2;
                // 当前库存偏码对比
                $current_inventory_1 = array_diff_key($ranking_arr['当前库存'],$ranking_arr['累销尺码比']);
                $current_inventory_2 = array_diff_key($ranking_arr['累销尺码比'],$ranking_arr['当前库存']);
                $current_inventory = $current_inventory_1 + $current_inventory_2;

                // 判断总库存是否偏码
                foreach ($total_inventory as $total_key => $total_val){
                    // 单码售罄比是否高于设定偏码参数
                    if(isset($sell_out_ratio[$total_key]) && $sell_out_ratio[$total_key] > $level_rate){
                        // 高于则提示总库存偏码
//                        $total_item['总库存'] =  "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px;' >偏码</span>";
                        $total_item['总库存'] =  "偏码";
                    }
                }

                // 判断当前库存是否偏码
                foreach ($current_inventory as $current_key => $current_val){
                    // 单码售罄比是否高于设定偏码参数
                    if(isset($sell_out_ratio[$current_key]) && $sell_out_ratio[$current_key] > $level_rate){
                        // 高于则提示当前库存偏码
//                        $total_item['当前库存'] =  "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px;' >偏码</span>";
                        $total_item['当前库存'] =  "偏码";
                    }
                }
                $list[] = $total_item;

                $field = [
                    '尺码情况',
                    '单码售罄比',
                    '当前库存',
                    '总库存',
                    '累销尺码比',
                    '单码售罄',
                    '周转',
                    '当前总库存量',
                    '未入量',
                    '累销',
                    '周销',
                    '店铺库存',
                    '云仓库存',
                    '云仓在途库存',
                    '当前单店均深'
                ];
                // 表数据
                $res = [];
                // 表头
                $head = [];
                // 公众字段
                $common = ['GoodsNo' => $v];
                foreach ($field as $kk => $vv){
                    if($kk=='尺码情况'){
                        $head[] = ['field' => '字段','width' => 115,'title' => '字段','search' => false];
                        foreach ($size_list as $k_1 => $v_1){
                            $head[] = [
                                'field' => $v_1,
                                'width' => 115,
                                'title' => $v_1,
                                'search' => false,
                            ];
                        }
                        $head[] = ['field' => '合计','width' => 115,'title' => '合计','search' => false];
                    }else{
                        $item = ['字段' => $vv];
                        $list_data = array_column($list,$vv);
                        foreach ($list_data as $k_2 => $v_2){
                            $size_key = $size_list[$k_2]??'合计';
                            $item[$size_key] = $v_2;
                        }
                        $res[] = $common + $item;
                    }
                }
            }

            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($res),
                    'data'  => $res
                ];
            return json($data);
        }

        return $this->fetch('',[
            'where' => $where,
            'cols' => [
                ['field' => 'GoodsNo','width' => 180,'title' => '货号','search' => false,'fixed' => 'left'],
                ['field' => '字段','width' => 180,'title' => '字段','search' => false,'fixed' => 'left'],
                ['field' => '合计','width' => 115,'title' => '合计','search' => false,'fixed' => 'left'],
                ['field' => '库存_00/28/37/44/100/160/S','width' => 180,'title' => '库存_00/28/37/44/100/160/S','search' => false],
                ['field' => '库存_29/38/46/105/165/M','width' => 180,'title' => '库存_29/38/46/105/165/M','search' => false],
                ['field' => '库存_30/39/48/110/170/L','width' => 180,'title' => '库存_30/39/48/110/170/L','search' => false],
                ['field' => '库存_31/40/50/115/175/XL','width' => 180,'title' => '库存_31/40/50/115/175/XL','search' => false],
                ['field' => '库存_32/41/52/120/180/2XL','width' => 180,'title' => '库存_32/41/52/120/180/2XL','search' => false],
                ['field' => '库存_33/42/54/125/185/3XL','width' => 180,'title' => '库存_33/42/54/125/185/3XL','search' => false],
                ['field' => '库存_34/43/56/190/4XL','width' => 180,'title' => '库存_34/43/56/190/4XL','search' => false],
                ['field' => '库存_35/44/58/195/5XL','width' => 180,'title' => '库存_35/44/58/195/5XL','search' => false],
                ['field' => '库存_36/6XL','width' => 115,'title' => '库存_36/6XL','search' => false],
                ['field' => '库存_38/7XL','width' => 115,'title' => '库存_38/7XL','search' => false],
                ['field' => '库存_40/8XL','width' => 115,'title' => '库存_40/8XL','search' => false]
            ]
        ]);
    }

    /**
     * @NodeAnotation(title="展示所有货号整体偏码")
     */
    public function alllist()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);

        // 获取参数
        $get = $this->request->get();
        if ($this->request->isAjax()) {

            $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
            $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;

            $where = [];

            $list = new SizeRanking;
            $model = new SizeRanking;
            if(isset($filters['风格']) && !empty($filters['风格'])){
                $list = $list->where(['s.风格' => $filters['风格']]);
                $model = $model->where(['s.风格' => $filters['风格']]);
            }

            if(isset($filters['cate']) && !empty($filters['cate'])){
                $list = $list->where(['s.一级分类' => $filters['cate']]);
                $model = $model->where(['s.一级分类' => $filters['cate']]);
            }

            if(isset($filters['cate2']) && !empty($filters['cate2'])){
                $list = $list->where(['s.二级分类' => $filters['cate2']]);
                $model = $model->where(['s.二级分类' => $filters['cate2']]);
            }

            if(isset($filters['collar']) && !empty($filters['collar'])){
                $list = $list->where(['s.领型' => $filters['collar']]);
                $model = $model->where(['s.领型' => $filters['collar']]);
            }

            if(isset($filters['货号']) && !empty($filters['货号'])){
                $list = $list->where(['s.货号' => $filters['货号']]);
                $model = $model->where(['s.货号' => $filters['货号']]);
            }

            if(isset($filters['总库存']) && !empty($filters['总库存'])){
                $list = $list->where(' CAST(r.合计 AS UNSIGNED) >=  '.$filters['总库存']);
                $model = $model->where(' CAST(r.合计 AS UNSIGNED) >=  '.$filters['总库存']);
            }

            // 是否偏码
            if(isset($filters['isDanger']) && !empty($filters['isDanger'])){
                $goodsnoList = SizeAllRatio::where(['合计'=>'偏码','Date' => date('Y-m-d')])->where(function ($q){
                    $q->where(['字段' => '当前库存尺码比'])->whereOr(['字段' => '总库存尺码比']);
                })->group('GoodsNo')->column('GoodsNo');
                $list = $list->whereIn('s.货号',$goodsnoList);
                $model = $model->whereIn('s.货号',$goodsnoList);
            }

            // 查询货号列表排名
            $list = $list->alias('s')->field('s.*')->leftJoin('size_all_ratio r','s.货号=r.GoodsNo and s.Date=r.Date and r.字段="当前总库存量"')->where(['s.Date' => date('Y-m-d')])->group('s.货号')->order('日均销','desc')->page($page, $limit)->select();
            // 分组后的数量
            $count = $model->alias('s')->leftJoin('size_all_ratio r','s.货号=r.GoodsNo and s.Date=r.Date and r.字段="当前总库存量"')->where(['s.Date' => date('Y-m-d')])->group('s.货号')->count();

            $allList = [];
            $init = ($page - 1) * $limit;

            $size = [
                '库存_00/28/37/44/100/160/S',
                '库存_29/38/46/105/165/M',
                '库存_30/39/48/110/170/L',
                '库存_31/40/50/115/175/XL',
                '库存_32/41/52/120/180/2XL',
                '库存_33/42/54/125/185/3XL',
                '库存_34/43/56/190/4XL',
                '库存_35/44/58/195/5XL',
                '库存_36/6XL',
                '库存_38/7XL',
                '库存_40/8XL',
                '合计'
            ];

            // 货品等级
            $config = sysconfig('site');
            foreach ($list as $key => &$value){
                  $value['近三天折率'] = '100%';
                  $value['图片'] = $value['图片']?:'https://ff211-1254425741.cos.ap-guangzhou.myqcloud.com/B31101454.jpg';
                  $value['全国排名'] = $init + $key+1;
                  $item = $value->alias('r')
                      ->leftJoin('ea_size_all_ratio ra','r.`货号`=ra.GoodsNo and r.Date = ra.Date')
                      ->where(['ra.GoodsNo' => $value['货号'],'ra.Date' => date('Y-m-d')])
                      ->where(function ($q)use($filters){
                          // 全部展示-部分展示
                          if(isset($filters['showType']) && !empty($filters['showType'])){
                             if($filters['showType'] == 2){
                                 $q->whereIn('字段',['单码售罄比','当前库存尺码比','总库存尺码比','累销尺码比','单码售罄','周转','当前总库存量','未入量']);
                             }
                          }
                      })->order('ra.id')
                      ->select()
                      ->toArray();
                  if(isset($value['上柜家数'])) unset($value['上柜家数']);
                  // 单码缺量判断数值
                  $level_rate = 0;
                  if($value['货品等级']=='B级'){
                      $level_rate = $config['level_b'];
                  }else{
                      $level_rate = $config['level_other'];
                  }

                  foreach ($item as $k => &$v){

                      if($v['合计'] == '单码缺量'){
                          $v['合计'] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >单码缺量</span>";
                      }

                      if($v['合计'] == '偏码'){
                          $v['合计'] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >偏码</span>";
                      }

                      if($v['合计'] == '偏码'){
                          $v['合计'] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >偏码</span>";
                      }

                      $item2 = $value->toArray() + $v;

                      if(in_array($item2['字段'],['当前库存尺码比','总库存尺码比','累销尺码比','单码售罄','单码售罄比'])){
                        $r_size = array_slice($size,0,-1);
                        $temp_arr = [];
                        foreach ($r_size as $k => $v){
                            $temp_arr[$v] = $item2[$v];

//                            if($item2['字段'] == '单码售罄' && $item2[$v] > $item2['合计']){
//                                $item2[$v] = "<span style='width: 100%;display: block; background:rgb(255,199,206);color:white;margin: 0px;padding: 0px' >{$item2[$v]}%</span>";
//                            }

                            if($item2['字段'] == '单码售罄比' && $item2[$v] > $level_rate){
                                $item2[$v] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >{$item2[$v]}%</span>";
                            }

                        }
                        if(in_array($item2['字段'],['当前库存尺码比','总库存尺码比','累销尺码比'])){
                            rsort($temp_arr);
                            $_count = 3;
                            if($item2['一级分类'] == '下装' && (strpos($item2['二级分类'],'松紧') !== false)){
                                $_count = 4;
                            }elseif($item2['一级分类'] == '下装'){
                                $_count = 5;
                            }
                            // 获取数组前三的元素值
                            $in_arr = getArray($temp_arr,$_count);
                            // 值等于前三的元素都标红
                            foreach ($r_size as $k => $v){
                                if(in_array($item2[$v],$in_arr)){
                                    $color = getColor($item2['字段']);
                                    $item2[$v] = "<span style='width: 100%;display: block; background:{$color};color:brown;margin: 0px;padding: 0px' >{$item2[$v]}%</span>";
                                }
                            }
                        }
                      }

                      foreach ($item2 as $vk => $val){
                          if(empty($val) && $vk!='上柜家数'){
                              $item2[$vk] = '';
                          }else if(!empty($val) && is_numeric($val) && in_array($vk,$size) && in_array($item2['字段'],['单码售罄比','当前库存尺码比','总库存尺码比','累销尺码比','单码售罄'])){
                              $item2[$vk] = $val.'%';
                          }
                      }
                      
                      $allList[] = $item2;
                      unset($v);
                  }
                  unset($value);
            }
            // 返回数据
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'showType' => $filters['showType']??0,
                'data'  => $allList
            ];
            return json($data);
        }
        $Style = ['引流款'=> '引流款', '基本款'=> '基本款'];
        $CategoryName1 = SizeRanking::group('一级分类')->column('一级分类','一级分类');
        $CategoryName2 = SizeRanking::group('二级分类')->column('二级分类','二级分类');
        $Collar = SizeRanking::group('领型')->column('领型','领型');
        return $this->fetch('',[
            'Style' => $Style,
            'CategoryName1' => $CategoryName1,
            'CategoryName2' => $CategoryName2,
            'Collar' => $Collar
        ]);
    }


    /**
     * 总体偏码导出
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function alllist_export()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);

        // 获取参数
        $get = $this->request->get();

        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15000;

        $where = [];

        $list = new SizeRanking;
        $model = new SizeRanking;
        if(isset($filters['风格']) && !empty($filters['风格'])){
            $list = $list->where(['风格' => $filters['风格']]);
            $model = $model->where(['风格' => $filters['风格']]);
        }

        if(isset($filters['cate']) && !empty($filters['cate'])){
            $list = $list->where(['一级分类' => $filters['cate']]);
            $model = $model->where(['一级分类' => $filters['cate']]);
        }

        if(isset($filters['cate2']) && !empty($filters['cate2'])){
            $list = $list->where(['二级分类' => $filters['cate2']]);
            $model = $model->where(['二级分类' => $filters['cate2']]);
        }

        if(isset($filters['collar']) && !empty($filters['collar'])){
            $list = $list->where(['领型' => $filters['collar']]);
            $model = $model->where(['领型' => $filters['collar']]);
        }


        // 查询货号列表排名
        $list = $list->where(['Date' => date('Y-m-d')])->withoutField('上柜家数')->order('日均销','desc')->page($page, $limit)->select();
        $count = $model->where(['Date' => date('Y-m-d')])->count();
        $allList = [];
        $init = ($page - 1) * $limit;

        $size = [
            '库存_00/28/37/44/100/160/S',
            '库存_29/38/46/105/165/M',
            '库存_30/39/48/110/170/L',
            '库存_31/40/50/115/175/XL',
            '库存_32/41/52/120/180/2XL',
            '库存_33/42/54/125/185/3XL',
            '库存_34/43/56/190/4XL',
            '库存_35/44/58/195/5XL',
            '库存_36/6XL',
            '库存_38/7XL',
            '库存_40/8XL',
            '合计'
        ];

        foreach ($list as $key => &$value){
              $value['近三天折率'] = '100%';
              $value['图片'] = $value['图片']?:'https://ff211-1254425741.cos.ap-guangzhou.myqcloud.com/B31101454.jpg';
              $value['全国排名'] = $init + $key+1;
              $item = $value->alias('r')
                  ->leftJoin('ea_size_all_ratio ra','r.`货号`=ra.GoodsNo and r.Date = ra.Date')
                  ->where(['ra.GoodsNo' => $value['货号'],'ra.Date' => date('Y-m-d')])
                  ->order('ra.id')
                  ->select()
                  ->toArray();
              foreach ($item as $k => &$v){

                  foreach (['偏码','单码缺量'] as $kk => $vv){
                      if(($v_key = array_search($vv,$v)) !== false){
                        $v[$v_key] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >{$vv}</span>";
                      }
                  }


                  $item2 = $value->toArray() + $v;
                  foreach ($item2 as $vk => $val){
                      if(empty($val)){
                          $item2[$vk] = '';
                      }else if(!empty($val) && is_numeric($val) && in_array($vk,$size) && in_array($item2['字段'],['单码售罄比','当前库存尺码比','总库存尺码比','累销尺码比','单码售罄'])){
                          $item2[$vk] = $val.'%';
                      }
                  }
                  $allList[] = $item2;
              }
        }


    }


    /**
     * @NodeAnotation(title="展示所有货号云仓偏码")
     */
    public function warehouseList()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);

        // 获取参数
        $get = $this->request->get();
        if ($this->request->isAjax()) {

            $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
            $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;

            $where = [];

            $list = new SizeRanking;
            $model = new SizeRanking;
            if(isset($filters['风格']) && !empty($filters['风格'])){
                $list = $list->where(['风格' => $filters['风格']]);
                $model = $model->where(['风格' => $filters['风格']]);
            }

            if(isset($filters['cate']) && !empty($filters['cate'])){
                $list = $list->where(['一级分类' => $filters['cate']]);
                $model = $model->where(['一级分类' => $filters['cate']]);
            }

            if(isset($filters['cate2']) && !empty($filters['cate2'])){
                $list = $list->where(['二级分类' => $filters['cate2']]);
                $model = $model->where(['二级分类' => $filters['cate2']]);
            }

            if(isset($filters['collar']) && !empty($filters['collar'])){
                $list = $list->where(['领型' => $filters['collar']]);
                $model = $model->where(['领型' => $filters['collar']]);
            }

            if(isset($filters['货号']) && !empty($filters['货号'])){
                $list = $list->where(['货号' => $filters['货号']]);
                $model = $model->where(['货号' => $filters['货号']]);
            }

            $size = [
                '00/28/37/44/100/160/S',
                '29/38/46/105/165/M',
                '30/39/48/110/170/L',
                '31/40/50/115/175/XL',
                '32/41/52/120/180/2XL',
                '33/42/54/125/185/3XL',
                '34/43/56/190/4XL',
                '35/44/58/195/5XL',
                '36/6XL',
                '38/7XL',
                '40/8XL',
                '总计'
            ];
            // 偏码判断设置
            $config = sysconfig('site');
            // 查询货号列表排名
            $list = $list->where(['Date' => date('Y-m-d')])->order('日均销','desc')->page($page, $limit)->select();
            $count = $model->where(['Date' => date('Y-m-d')])->count();
            $allList = [];
            $init = ($page - 1) * $limit;
            foreach ($list as $key => &$value){
                  $value['近三天折率'] = '100%';
                  $value['图片'] = $value['图片']?$value['图片']."?id={$value['id']}":'https://ff211-1254425741.cos.ap-guangzhou.myqcloud.com/B31101454.jpg'."?id={$value['id']}";
                  $value['全国排名'] = $init + $key+1;
                  // 查询码比数据
                  $item = SizeWarehouseRatio::selectWarehouseRatio($value['货号'],$filters);
                  // 单码缺量判断数值
                  $level_rate = 0;
                  if($value['货品等级']=='B级'){
                      $level_rate = $config['level_b'];
                  }else{
                      $level_rate = $config['level_other'];
                  }

                  foreach ($item as $k =>$v){
                      foreach (['偏码','单码缺量','偏码','单码缺量','偏码','单码缺量','偏码','单码缺量','偏码','单码缺量'] as $kk => $vv){
                          if(($v_key = array_search($vv,$v)) !== false){
                            $v[$v_key] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >{$vv}</span>";
                          }
                      }


                      $item2 = $value->toArray() + $v;

                      // 云仓判断
                      foreach (['广州','南昌','武汉','长沙','贵阳'] as $wk => $wv){

                          // 给部分记录设置背景标红
                          if(in_array($item2["{$wv}_".'字段'],['当前库存尺码比','累销尺码比','单码售罄','单码售罄比'])){
                            $r_size = array_slice($size,0,-1);
                            $temp_arr = [];
                            foreach ($r_size as $k => $v){
                                $temp_arr["{$wv}_".$v] = $item2["{$wv}_".$v];

//                                if($item2["{$wv}_".'字段'] == '单码售罄' && $item2["{$wv}_".$v] > $item2["{$wv}_".'总计']){
//                                    $item2["{$wv}_".$v] = "<span style='width: 100%;display: block; background:rgb(255,199,206);color:white;margin: 0px;padding: 0px' >{$item2["{$wv}_".$v]}%</span>";
//                                }

                                if($item2["{$wv}_".'字段'] == '单码售罄比' && $item2["{$wv}_".$v] > $level_rate){
                                    $item2["{$wv}_".$v] = "<span style='width: 100%;display: block; background:red;color:white;margin: 0px;padding: 0px' >{$item2["{$wv}_".$v]}%</span>";
                                }

                            }
                            if(in_array($item2["{$wv}_".'字段'],['当前库存尺码比','累销尺码比'])){
                                rsort($temp_arr);
                                $_count = 3;
                                if($item2['一级分类'] == '下装' && (strpos($item2['二级分类'],'松紧') !== false)){
                                    $_count = 4;
                                }elseif($item2['一级分类'] == '下装'){
                                    $_count = 5;
                                }
                                // 获取数组前三的元素值
                                $in_arr = getArray($temp_arr,$_count);
                                // 值等于前三的元素都标红
                                foreach ($r_size as $k => $v){
                                    if(in_array($item2["{$wv}_".$v],$in_arr)){
                                        $color = getColor($item2["{$wv}_".'字段']);
                                        $item2["{$wv}_".$v] = "<span style='width: 100%;display: block; background:{$color};color:brown;margin: 0px;padding: 0px' >{$item2["{$wv}_".$v]}%</span>";
                                    }
                                }
                            }
                          }

                      }


                      foreach ($item2 as $vk => $val){

                          $vk_key = mb_substr($vk , 3);
                          if(empty($val) || $val == '0.00'){
                              $item2[$vk] = '';
                          }else if(!empty($val) && is_numeric($val) && in_array($vk_key,$size) && in_array($item2['广州_字段'],['单码售罄比','当前库存尺码比','总库存尺码比','累销尺码比','单码售罄'])){
                              $item2[$vk] = $val.'%';
                          }
                      }

                      $allList[] = $item2;
                  }
            }
            // 返回数据
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $allList,
                'showType' => $filters['showType']??0
            ];
            return json($data);
        }
        $Style = ['引流款'=> '引流款', '基本款'=> '基本款'];
        $CategoryName1 = SizeRanking::group('一级分类')->column('一级分类','一级分类');
        $CategoryName2 = SizeRanking::group('二级分类')->column('二级分类','二级分类');
        $Collar = SizeRanking::group('领型')->column('领型','领型');
        return $this->fetch('',[
            'Style' => $Style,
            'CategoryName1' => $CategoryName1,
            'CategoryName2' => $CategoryName2,
            'Collar' => $Collar
        ]);
    }

    /**
     * 获取二级分类
     */
    public function getCate2()
    {
        $cate1 = $this->request->param('cate1');
        $cate = SizeRanking::group('二级分类')->where([
            '一级分类' => $cate1
        ])->column('二级分类','二级分类');
        return $this->success('成功',$cate);
    }

    /**
     * 获取领型
     */
    public function getCollar()
    {
        $cate2 = $this->request->param('cate2');
        $collar = SizeRanking::group('领型')->where([
            '二级分类' => $cate2
        ])->column('领型','领型');
        return $this->success('成功',$collar);
    }
}
