<?php 
set_time_limit(0);
ini_set('memory_limit', '128M');

//include framework
require_once dirname(dirname(__DIR__)) . '/Pyramid/Pyramid.php';
//include config
require_once dirname(__DIR__) . '/config.php';

include '/opt/wwwroot/download/lib/PHPExcel/PHPExcel.php';

$distname = "罗湖区";

$streets = db_query("select street from {shop_shenzheng} where dist='".$distname."' group by street order by id asc")->fetchCol();
$cvskeys = array_flip($streets);
/*
 $cvskeys = array(
    '资讯网' => 0,
    '资讯网2' => 1,
    '资讯网3' => 2,
    '资讯网4' => 3,
    '资讯网5' => 4,
 );
*/
$header = array(
    '0' => array('pos'=>'A','name'=>'点评ID','width'=>'15','key'=>'shop_id'),
    '1' => array('pos'=>'B','name'=>'店铺名','width'=>'30','key'=>'name','Horizontal'=>'PHPExcel_Style_Alignment::HORIZONTAL_LEFT'),
    '2' => array('pos'=>'C','name'=>'菜系','width'=>'20','key'=>'category'),
    '3' => array('pos'=>'D','name'=>'电话','width'=>'20','key'=>'tel'),
    '4' => array('pos'=>'E','name'=>'地址','width'=>'45','key'=>'address','Horizontal'=>'PHPExcel_Style_Alignment::HORIZONTAL_LEFT'),
    '5' => array('pos'=>'F','name'=>'人均','width'=>'10','key'=>'avgprice'),
    '6' => array('pos'=>'G','name'=>'经度','width'=>'20','key'=>'lng'),
    '7' => array('pos'=>'H','name'=>'纬度','width'=>'20','key'=>'lat'),
    '8' => array('pos'=>'I','name'=>'营业时间','width'=>'40','key'=>'businesstime','Horizontal'=>'PHPExcel_Style_Alignment::HORIZONTAL_LEFT'),
    '9' => array('pos'=>'J','name'=>'热门菜式','width'=>'50','key'=>'hotdish'),
    '10' => array('pos'=>'K','name'=>'交通','width'=>'50','key'=>'traffic'),
);
$objcel = new PHPExcel();
$objProps = $objcel->getProperties();   //可以不写这一步 那么后面的调用就要链式调用
$objProps->setCreator('test');
$objProps->setLastModifiedBy('test');
$objProps->setTitle('download for web @ loveqzhi@hotmail.com');
$objProps->setSubject('company data');
$objProps->setKeywords('compayn data loveqzhi@hotmal.com');
$objProps->setCategory('test something');

foreach ($cvskeys as $cname => $ci) {
    echo "正在处理 ".$cname."...\n";
    if ($ci  == 0) {
        $objcel->setActiveSheetIndex($ci);
        $nowSheet = $objcel->getActiveSheet();
    } else {
        $objcel->createSheet();
        $nowSheet = $objcel->getSheet($ci);
    }
    foreach ($header as $k=>$list) {               
        $nowSheet->setCellValue($list['pos']."1",$list['name']);
        if (isset($list['width'])) {
            $nowSheet->getColumnDimension($list['pos'])->setWidth($list['width']);
        }
        $objStyle = $nowSheet->getStyle($list['pos']."1");
        $objStyle->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objFont = $objStyle->getFont();
        $objFont->setBold(true);
        //$objFont->setName('黑体');  
        $objFont->setSize(13);  
        //$nowSheet->freezePane($list['pos']."1");
    }
    $datas = db_select("shop_shenzheng")
                ->fields("shop_shenzheng")
                ->condition("dist",$distname)
                ->condition("street",$cname)
                ->execute()
                ->fetchAll();
    $row = 2;
    if (!empty($datas)) {
        foreach ($datas as $dlist) {
            foreach($header as $list) {
                if(isset($dlist->$list['key'])) {
                    $nowSheet->setCellValueExplicit($list['pos'].$row, $dlist->$list['key'],PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $nowSheet->setCellValue($list['pos'].$row, '');
                }
                if (isset($list['Horizontal'])) {
                    $nowSheet->getStyle($list['pos'].$row)->getAlignment()->setHorizontal($list['Horizontal']);
                } else {
                    $nowSheet->getStyle($list['pos'].$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                $nowSheet->getStyle($list['pos'].$row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
            $nowSheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }
    }
    $nowSheet->freezePane('A2');
    $nowSheet->setTitle($cname);  
    $objWriter = PHPExcel_IOFactory::createWriter($objcel, 'Excel2007');
    $objWriter->save("点评网-".$distname.'.xlsx');

    $nowSheet = $objWriter = $datas = null;
    unset($nowSheet,$objWriter);
}
