<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\common\logic\inventory\DressLogic;


/**
 * Class Dress
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰库存")
 */
class Dress extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Accessories;
        // 实例化逻辑类
        $this->logic = new DressLogic;
    }

    /**
     * 数据筛选配置
     */
    public function config()
    {
        if ($this->request->isAjax()) {
            // 查询所有的省份
            $provinceList = $this->logic->getProvince();
            // 字段
            $field = AdminConstant::YINLIU_COLUMN;
            $data = [
                'provinceList' => $provinceList,
                'field' => $field
            ];
            return json($data);
        }
        return $this->fetch();
    }
}
