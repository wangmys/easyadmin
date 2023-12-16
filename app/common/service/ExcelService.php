<?php


namespace app\common\service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


/**
 * 支持设置单元格文本格式
 * 支持图片的导入导出  图片是本地路径  是远程需要自行下载到本地
 * Class ExcelService
 * @package app\common\service
 * @ControllerAnnotation(title="EXCEL导入导出",auth=true)
 */
class ExcelService
{

    /**
     * 导入
     *
     * @param $filePath     excel的服务器存放地址 可以取临时地址
     * @param int $startRow 开始和行数
     * @param bool $hasImg 导出的时候是否有图片
     * @param string $suffix 格式
     * @param string $imageFilePath 作为临时使用的 图片存放的地址
     * @return array|mixed
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function import($filePath, $startRow = 1, $hasImg = false, $suffix = 'Xlsx', $imageFilePath = null)
    {
        if ($hasImg) {
            if ($imageFilePath == null) {
                $imageFilePath = './excel_img/' . date('Ymd') . '/';
            }
            if (!file_exists($imageFilePath)) {
                //如果目录不存在则递归创建
                mkdir($imageFilePath, 0777, true);
            }
        }
        $reader = IOFactory::createReader($suffix);
        if (!$reader->canRead($filePath)) {
            throw new Exception('不能读取Excel');
        }

        $spreadsheet = $reader->load($filePath);
        $sheetCount = $spreadsheet->getSheetCount();// 获取sheet(工作表)的数量

        // 获取所有的sheet表格数据
        $excleDatas = [];
        $emptyRowNum = 0;
        for ($i = 0; $i < $sheetCount; $i++) {
            $objWorksheet = $spreadsheet->getSheet($i); // 读取excel文件中的第一个工作表
            $data = $objWorksheet->toArray();
            if ($hasImg) {
                foreach ($objWorksheet->getDrawingCollection() as $drawing) {
                    list($startColumn, $startRow) = Coordinate::coordinateFromString($drawing->getCoordinates());
                    $imageFileName = $drawing->getCoordinates() . mt_rand(1000, 9999);

                    $imageFileName .= '.' . $drawing->getExtension();
                    //获取图片信息
                    $image_info = getimagesize($drawing->getPath());
                    switch ($image_info['mime']) {
                        case 'image/png':
                            // 处理 PNG 文件
                            $source = imagecreatefrompng($drawing->getPath());
                            imagepng($source, $imageFilePath . $imageFileName);
                            break;
                        case 'image/jpeg':
                            // 处理 JPEG 文件
                            $source = imagecreatefromjpeg($drawing->getPath());
                            imagejpeg($source, $imageFilePath . $imageFileName);
                            break;
                        case 'image/gif':
                            // 处理 GIF 文件
                            $source = imagecreatefromgif($drawing->getPath());
                            imagegif($source, $imageFilePath . $imageFileName);
                            break;
                        default:
                            // 其他类型的文件
                            throw new \Exception('Unsupported image format: ' . $image_info['mime']);
                    }

                    $startColumn = self::ABC2decimal($startColumn);
                    $data[$startRow - 1][$startColumn] = $imageFilePath . $imageFileName;
                }
            }
            $excleDatas[$i] = $data; // 多个sheet的数组的集合
        }

        // 这里我只需要用到第一个sheet的数据，所以只返回了第一个sheet的数据
        $returnData = $excleDatas ? array_shift($excleDatas) : [];

        // 第一行数据就是空的，为了保留其原始数据，第一行数据就不做array_fiter操作；
        $returnData = $returnData && isset($returnData[$startRow]) && !empty($returnData[$startRow]) ? array_filter($returnData) : $returnData;

        return $returnData;
    }


    /**
     *
     * $header = [
     * ['名称', 'name', 'text', '10'],
     * ['图片', 'pic', 'image', '10'],
     * ['性别', 'sex', 'text', '10'],
     * ];
     * $data = [
     * ['name' => '张三', 'pic' => 'https://sha.babiboy.com/m/images/dindin_template/test.jpg', 'sex' => '男'],
     * ['name' => '李四', 'pic' => 'static/dingding/s101.png', 'sex' => '女'],
     * ['name' => '王五', 'pic' => 'static/dingding/s114.png', 'sex' => '男'],
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

                if (mb_strpos($s_v, '://') !== false) {  //网络链接
                    $path = 'download/' . date('Ymd') . '/';
                    $fileName = urldecode(basename($s_v));
                    self::downloadFile($s_v, $path, $fileName);

                    $s_v = $path . $fileName;
                }
                //坐标
                $xy = Coordinate::stringFromColumnIndex($key + 1) . ($s_k + 2);
                if (isset($item[2]) && $item[2] == 'number') {
                    $type = DataType::TYPE_NUMERIC;
                    //写入数据
                    $worksheet->setCellValueExplicit($xy, $s_v, $type);
                } else if (isset($item[2]) && $item[2] == 'image') {
                    //设置行高
                    $worksheet->getRowDimension($s_k + 2)->setRowHeight(38);
                    // 从本地路径加载图像文件 img/20230720/S114.jpg
                    $imagePath = $s_v; // 替换成真正的图片路径
                    if (!file_exists($imagePath)) {
                        continue;
                    }
                    $size = getimagesize($imagePath);
                    $imgWidthMul=$size[0]/50;
                    $imgHeightMul=$size[1]/50;
                    $drawing = new Drawing();
                    $drawing->setName($imagePath);
                    $drawing->setDescription('Image inserted by PhpSpreadsheet');
                    $drawing->setPath($imagePath);
                    $drawing->setCoordinates($xy);
                    //跨行
//                    $drawing->setOffsetX(0);
//                    $drawing->setOffsetY(5);
                    //设置图片宽高
                    $drawing->setWidthAndHeight(floor($size[0]/$imgWidthMul), floor($size[1]/$imgHeightMul));
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

    private static function ABC2decimal($abc)
    {
        $ten = 0;
        $len = strlen($abc);
        for ($i = 1; $i <= $len; $i++) {
            $char = substr($abc, 0 - $i, 1);//反向获取单个字符

            $int = ord($char);
            $ten += ($int - 65) * pow(26, $i - 1);
        }

        return $ten;
    }


    /**
     *
     * @param $url
     * @param $path
     * @return bool
     * @NodeAnotation(title="下载文件",auth=false)
     */

    public static function downloadFile($url, $path = 'download/', $fileName = null): bool
    {

        if (!file_exists($path)) {
            //如果目录不存在则递归创建
            mkdir($path, 0777, true);
        }
        if (!$fileName) {
            $fileName = urldecode(basename($url));
        }

        $fp = @fopen($path . iconv("UTF-8", "GB2312", $fileName), 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //设置链接超时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3600); //10分钟
//         设置请求超时时间，单位是秒
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($http_code == 200) {
            return $path . $fileName;
        } else {
            unlink($path . basename($url));
            return false;
        }
    }


}