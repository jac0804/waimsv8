<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;


class pw
{
    private $modulename = 'Power Consumption Entry';

    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }
    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'approved', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }
    public function reportparamsdata($config)
    {
      
        $param = "select
                  'PDFM' as print,
                  '' as prepared,
                  '' as approved,
                  '' as received";
        return $this->coreFunctions->opentable($param);
    }
    public function generateResult($config, $trno)
    {
        return   $this->default_qry($config, $trno);
    }

    public function default_qry($config)
    {

      

        $trno = $config['params']['dataid'];
        $query = "select head.docno,(head.dateid) as date ,head.rem as notes,
ifnull(stock.isqty2,0) as  PreviousR,
ifnull(stock.isqty3,0) as CurrentR,
ifnull(stock.isqty,0) as Consumed,
ifnull(stock.isamt,0) as Rate,
ifnull(stock.isqty * stock.isamt,0) as total,
subcat.name as cat_name, cat.name as categ,
    subcat2.name as subcat_name
from hpwhead as head
    left join powercat as cat on cat.line=head.pwrcat
    left join subpowercat as subcat on subcat.catid=cat.line
    left join subpowercat2 as subcat2 on subcat2.subcatid=subcat.line
    left join hpwstock as stock on stock.trno=head.trno and stock.subcat2=subcat2.line where head.trno = '$trno' and ifnull(subcat2.line,0)<>0
union all
select head.docno,(head.dateid) as date ,head.rem as notes,
ifnull(stock.isqty2,0) as  PreviousR,
ifnull(stock.isqty3,0) as CurrentR,
ifnull(stock.isqty,0) as Consumed,
ifnull(stock.isamt,0) as Rate,
ifnull(stock.isqty * stock.isamt,0) as total,
subcat.name as cat_name, cat.name as categ,
    subcat2.name as subcat_name
from pwhead as head
    left join powercat as cat on cat.line=head.pwrcat
    left join subpowercat as subcat on subcat.catid=cat.line
    left join subpowercat2 as subcat2 on subcat2.subcatid=subcat.line
    left join pwstock as stock on stock.trno=head.trno and stock.subcat2=subcat2.line where head.trno = '$trno'and ifnull(subcat2.line,0)<>0";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return  $result;
    }
    public function reportplotting($params, $data)
    {

        $print =  $params['params']['dataparams']['print'];
        return $print === 'PDFM' ? $this->default_pw_PDF($params, $data) : '';
    }

    public function default_pw_PDF($params, $data)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 11;
        $count = $page = 35;
        if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
        }
        $this->default_pw_header($params, $data);

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(130, 20, "Sub-Category (level 1)", 'TLRB', 'C', false, 0, '',  '');
        PDF::MultiCell(130, 20, "Sub-Category (level 2)", 'TLRB', 'C', false, 0, '',  '');
        PDF::MultiCell(120, 20, "Previous Reading (kwh)", 'TLRB', 'C', false, 0, '',  '');
        PDF::MultiCell(120, 20, "Current Reading (kwh)", 'TLRB', 'C', false, 0, '',  '');
        PDF::MultiCell(90, 20, "Consumed (kwh)", 'TLRB', 'C', false, 0, '',  '');
        PDF::MultiCell(60, 20, "Rate (₱)", 'TLRB', 'R', false, 0, '',  '');
        PDF::MultiCell(72, 20, "Total (₱/kwh)", 'TLRB', 'R', false, 1, '',  '');
       
        $total = 0;
        for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            $cat_name = $data[$i]['cat_name'];
            $subcat_name = $data[$i]['subcat_name'];
            $PreviousR = number_format($data[$i]['PreviousR'], 3);
            $CurrentR = number_format($data[$i]['CurrentR'], 3);
            $Consumed = number_format($data[$i]['Consumed'], 3);
            $Rate =  number_format($data[$i]['Rate'], 4);
            $Total = number_format($data[$i]['total'], 2);

            $arr_catname = $this->reporter->fixcolumn([$cat_name], '15', 0);
            $arr_subcatname = $this->reporter->fixcolumn([$subcat_name], '20', 0);
            $arr_prev = $this->reporter->fixcolumn([$PreviousR], '15', 0);
            $arr_curr = $this->reporter->fixcolumn([$CurrentR], '15', 0);
            $arr_consumed = $this->reporter->fixcolumn([$Consumed], '15', 0);
            $arr_rate = $this->reporter->fixcolumn([$Rate], '15', 0);
            $arr_total = $this->reporter->fixcolumn([$Total], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_catname, $arr_subcatname, $arr_prev, $arr_curr, $arr_consumed, $arr_rate, $arr_total]);

            for ($r = 0; $r < $maxrow; $r++) {
                if (($i + 1) != count($data)) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(130, 0, (isset($arr_catname[$r]) ? $arr_catname[$r] : ''), 'LR', 'C', false, 0, '',  '');
                    PDF::MultiCell(130, 0, (isset($arr_subcatname[$r]) ? $arr_subcatname[$r] : ''), 'LR', 'C', false, 0, '',  '');
                    PDF::MultiCell(120, 0, (isset($arr_prev[$r]) ? $arr_prev[$r] : ''), 'LR', 'R', false, 0, '',  '');
                    PDF::MultiCell(120, 0, (isset($arr_curr[$r]) ? $arr_curr[$r] : ''), 'LR', 'R', false, 0, '',  '');
                    PDF::MultiCell(90, 0, (isset($arr_consumed[$r]) ? $arr_consumed[$r] : ''), 'LR', 'R', false, 0, '',  '');
                    PDF::MultiCell(60, 0, (isset($arr_rate[$r]) ? $arr_rate[$r] : ''), 'LR', 'R', false, 0, '',  '');
                    PDF::MultiCell(72, 0, (isset($arr_total[$r]) ? $arr_total[$r] : ''), 'R', 'R', false, 1, '',  '');
                } else {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(130, 0, (isset($arr_catname[$r]) ? $arr_catname[$r] : ''), 'BLR', 'C', false, 0, '',  '');
                    PDF::MultiCell(130, 0, (isset($arr_subcatname[$r]) ? $arr_subcatname[$r] : ''), 'BLR', 'C', false, 0, '',  '');
                    PDF::MultiCell(120, 0, (isset($arr_prev[$r]) ? $arr_prev[$r] : ''), 'BLR', 'R', false, 0, '',  '');
                    PDF::MultiCell(120, 0, (isset($arr_curr[$r]) ? $arr_curr[$r] : ''), 'BLR', 'R', false, 0, '',  '');
                    PDF::MultiCell(90, 0, (isset($arr_consumed[$r]) ? $arr_consumed[$r] : ''), 'BLR', 'R', false, 0, '',  '');
                    PDF::MultiCell(60, 0, (isset($arr_rate[$r]) ? $arr_rate[$r] : ''), 'BLR', 'R', false, 0, '',  '');
                    PDF::MultiCell(72, 0, (isset($arr_total[$r]) ? $arr_total[$r] : ''), 'BR', 'R', false, 1, '',  '');
                }

               

                if (intVal($i) + 1 == $page) {
                    $this->default_pw_header($params, $data);
                    $page += $count;
                }
            }
            //THIS WILL BE ADDED IF ALL TOTAL IS NEEDED 
            $total += $data[$i]['total'];
        }
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(625, 0, 'GRAND TOTAL: (₱/kwh)', '', 'R', false, 0);
        PDF::MultiCell(95, 0, number_format($total, 2), 'B', 'R', false, 1, '', '');
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(720, 0,  '', '', 'L', false, 1, '',  '900');
        PDF::MultiCell(100, 0,  'Prepared By:', '', 'L', false, 0, '',  '');
        PDF::MultiCell(100, 0,  '' . $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '105',  '');
        PDF::MultiCell(320, 0,  '', '', '', false, 0, '',  '');
        PDF::MultiCell(100, 0,  'Approved By:', '', 'R', false, 0, '',  '');
        PDF::MultiCell(100, 0,  '' . $params['params']['dataparams']['approved'], 'B', 'L', false, 1, '',  '');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
    public function default_pw_header($params, $data)
    {
      
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
        }
        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::SetFont($font, 'B', 14);
        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 16);
        PDF::MultiCell(720, 0, $this->modulename, '', 'L', false, 1, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 0, "Document #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(480, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(60, 0, "", '', '', false, 0, '',  '');
        PDF::MultiCell(30, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        $dateid  =  date('m-d-Y', strtotime($data[0]['date']));
        PDF::MultiCell(80, 0, (isset($dateid) ? $dateid : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 0, "Category: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(480, 0, (isset($data[0]['categ']) ? $data[0]['categ'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(170, 0, "", '', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 0, "Notes: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(480, 0, (isset($data[0]['notes']) ? $data[0]['notes'] : ''), 'B', 'L', false, 0, '110',  '');
        PDF::MultiCell(170, 0, "", '', '', false, 1, '',  '');
    }
}
