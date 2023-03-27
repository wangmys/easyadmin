<?php
// 这是系统自动生成的公共文件

/**
 * 生成引流库存筛选条件
 * @return array|mixed
 */
function crateStockWhere()
{
    // 获取判断标准
    $stock_warn = sysconfig('stock_warn');
    // 组合筛选条件
    $_where = [];
    foreach ($stock_warn as $k => $v){
        $_where[] = [$k,'<',$v];
    }
    return $_where;
}