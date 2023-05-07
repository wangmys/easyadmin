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

namespace app\common\logic\execl;

use app\common\constants\AdminConstant;
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 逻辑层
 * Class AuthService
 * @package app\common\logic
 */
class PHPExecl
{


    /***
     * 构造方法
     * DressLogic constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {

    }

    /**
     * 导出
     * @param $header
     * @param $data
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($header,$data)
    {
        // 创建一个新的Excel文件
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();


        // 写入头部
        $hk = 1;
        foreach ($header as $k => $v) {
            switch ($k){
                case 'column_1':
                    foreach ($v as $kk => $vv){
                        $sheet_column = Coordinate::stringFromColumnIndex($hk);
                        $sheet->setCellValue( $sheet_column. '1', $kk);
                        $sheet->mergeCells($sheet_column . '1'.':'.$sheet_column.'2');
                        $hk += 1;
                    }
                    break;
                case 'column_2':
                    foreach ($v as $kk => $vv){
                        $sheet_column_1 = Coordinate::stringFromColumnIndex($hk);
                        $hk += 1;
                        $sheet_column_2 = Coordinate::stringFromColumnIndex($hk);
                         // 设置第一级表头
                        $sheet->mergeCells($sheet_column_1 . '1'.':'.$sheet_column_2.'1');
                        $sheet->setCellValue($sheet_column_1 . '1', $vv);

                        // 设置第二级表头
                        $sheet->setCellValue($sheet_column_1.'2', '库存');
                        $sheet->setCellValue($sheet_column_2.'2', '周转');
                        $hk += 1;
                    }
                    break;
            }
        }

        $column = 3;

        foreach ($data as $dk => $dv){
            $span = 1;
            foreach ($header as $k => $v) {
                switch ($k){
                    case 'column_1':
                        foreach ($v as $kk => $vv){
                            $sheet_column = Coordinate::stringFromColumnIndex($span);
                            $sheet->setCellValue( $sheet_column.$column, $dv[$vv]);
                            $span += 1;
                        }
                        break;
                    case 'column_2':
                        foreach ($v as $kk => $vv){
                            // 写入excel
                            $sheet_column_1 = Coordinate::stringFromColumnIndex($span);
                            $span += 1;
                            $sheet_column_2 = Coordinate::stringFromColumnIndex($span);
                            if(($textValue = $this->is_hasHtml($dv[$vv])) !== false){
                                $this->setColor($sheet,$sheet_column_1.$column);
                            }
                            // 设置第二级表头
                            $sheet->setCellValue($sheet_column_1.$column, $textValue!==false?$textValue:$dv[$vv]);
                            if(($textValue2 = $this->is_hasHtml($dv["_$vv"])) !== false){
                                $this->setColor($sheet,$sheet_column_2.$column);
                            }
                            $sheet->setCellValue($sheet_column_2.$column,$textValue2!==false?$textValue2:$dv["_$vv"]);
                            $span += 1;
                        }
                        break;
                }
            }
            $column++;
        }
        // 保存文件
        $writer = new Xlsx($spreadsheet);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;");
        header("Content-Disposition: inline;filename=\"6.xlsx\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 是否包含html
     * @param $value
     * @return bool
     */
    public function is_hasHtml($value)
    {
        $str = strip_tags($value);
        if($value == $str){
            return false;
        }
        return $str;
    }

    /**
     * 设置单元格背景颜色
     * @param $sheet
     * @param $key
     * @param $val
     */
    public function setColor($sheet,$index)
    {
       $sheet->getStyle($index)
        ->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFC0CB');
    }
}