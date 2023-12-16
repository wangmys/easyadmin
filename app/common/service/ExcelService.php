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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ExcelService
{


    /**
     *
     * $header = [
     * ['名称', 'name', 'text', '10'],
     * ['图片', 'pic', 'image', '15'],
     * ];
     * $data = [
     * ['name' => '张三', 'pic' => 'img/20230720/S114.jpg'],
     * ['name' => '李四', 'pic' => 'img/20230720/S112B.jpg'],
     * ['name' => '王五', 'pic' => 'img/20230720/S015.jpg'],
     * ];
     *
     * @param $data
     * @param $data [0] 标题
     * @param $data [1] 字段名
     * @param $data [2] 类型：text、number、image
     * @param $data [3] 行宽
     * @param $data [3] image ['图片', 'pic', 'image','15']
     * @param $header   表头
     * @param $fileName 文件名
     * @return string|void
     * @NodeAnotation(title="导出",auth=false)
     */
    public static function export($data, $header, $fileName = '导出EXcel')
    {

        $spreadsheet = new Spreadsheet();
        //获取活动工作簿
        $worksheet = $spreadsheet->getActiveSheet();
        $num = 0;
        foreach ($header as $key => $item) {

            $x = Coordinate::stringFromColumnIndex($key + 1);
            $width = (isset($item[3]) && !empty($item[3])) ? $item[3] : 10;
            //设置要自动调整宽度的行
            $worksheet->getColumnDimension($x)->setWidth($width);
            //设置单元格表头
            $worksheet->setCellValueExplicit($x . 1, $item[0], DataType::TYPE_STRING);
            $arr = array_column($data, $item['1']);
            if ($key > 0 && count($arr) != $num) {
                return '字段数据不全，请检查';
            }
            $num = count($arr);

            foreach ($arr as $s_k => $s_v) {
                //坐标
                $xy = Coordinate::stringFromColumnIndex($key + 1) . ($s_k + 2);
                if (isset($item[2]) && $item[2] == 'number') {
                    $type = DataType::TYPE_NUMERIC;
                    //写入数据
                    $worksheet->setCellValueExplicit($xy, $s_v, $type);
                } else if (isset($item[2]) && $item[2] == 'image') {
                    //设置行高
                    $worksheet->getRowDimension($s_k + 2)->setRowHeight(35);
                    // 从本地路径加载图像文件 img/20230720/S114.jpg
                    $imagePath = $s_v; // 替换成真正的图片路径
                    $drawing = new Drawing();
                    $drawing->setName($imagePath);
                    $drawing->setDescription('Image inserted by PhpSpreadsheet');
                    $drawing->setPath($imagePath);
                    $drawing->setCoordinates($xy);
                      //跨行
//                    $drawing->setOffsetX(0);
//                    $drawing->setOffsetY(5);
                    //设置图片宽高
                    $drawing->setWidthAndHeight(100, 100);
                    $drawing->setResizeProportional(true);
                    $drawing->setWorksheet($worksheet);
                } else {
                    $type = DataType::TYPE_STRING;
                    //写入数据
                    $worksheet->setCellValueExplicit($xy, $s_v, $type);
                }

                //设置颜色
//                $spreadsheet->getActiveSheet()->getStyle($xy)->getFont()->getColor()->setRGB('B8002E');
            }
        }

        ob_end_clean(); //清除缓冲区
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $fileName . '.xlsx');
        header('Cache-Control: max-age=0');

        $write = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $write->save('php://output');
        exit;

    }


}