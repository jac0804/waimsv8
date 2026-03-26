<?php

namespace App\Http\Classes\modules\modulereport\ati;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\VarDumper\VarDumper;

class mm
{
    private $modulename = "Merging Barcode";
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $reporter;
    private $logger;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
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
    public function  createreportfilter($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'approved', 'checked', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }
    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
      'PDFM' as print,
      '' as approved,
       '' as checked,
       '' as received
      "
        );
    }
    public function report_default_query($trno)
    {

        $query = "select date(head.dateid) as dateid ,head.trno,head.docno,item.itemname,item.barcode,item.itemid,item.subcode as specs
                    ,item.othcode,cat.name as category,subcat.name as subcategory,item.uom,
                    (case when item.isgeneric = 1 then 'Yes' else 'No' end) as isgeneric
                    from mmhead as head
                    left join mmstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join iteminfo as info on info.itemid=item.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat
                    where head.doc = 'MM' and stock.trno='$trno'
                    union all 
                    select date(head.dateid) as dateid ,head.trno,head.docno,item.itemname,item.barcode,item.itemid,item.subcode as specs
                    ,item.othcode,cat.name as category,subcat.name as subcategory,item.uom,
                    (case when item.isgeneric = 1 then 'Yes' else 'No' end) as isgeneric
                    from hmmhead as head
                    left join hmmstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join iteminfo as info on info.itemid=item.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat
                    where head.doc = 'MM' and stock.trno='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }
    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_MM_PDF($params, $data);
        }
    }

    public function default_MM_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
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
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(540, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(540, 0, '', '', '', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "BARCODE NAME", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "ITEMNAME", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "UOM", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "SPECIFICATION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "CATEGORY", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "SUBCATEGORY", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }
    public function default_MM_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 30;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_MM_header_PDF($params, $data);

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $othcode =   trim($data[$i]['othcode']);
                $barcode =   trim($data[$i]['barcode']);
                $itemname =   trim($data[$i]['itemname']);
                $uom =   $data[$i]['uom'];
                $specs =   $data[$i]['specs'];
                $category =   $data[$i]['category'];
                $subcategory =   $data[$i]['subcategory'];

                $maxrow = 1;
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '32', 0);
                $arr_othcode = $this->reporter->fixcolumn([$barcode], '21', 0);
                $arr_barcode = $this->reporter->fixcolumn([$othcode], '21', 0);
                $arr_specs = $this->reporter->fixcolumn([$specs], '35', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
                $arr_category = $this->reporter->fixcolumn([$category], '34', 0);
                $arr_subcategory = $this->reporter->fixcolumn([$subcategory], '33', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode, $arr_othcode, $arr_specs, $arr_uom, $arr_category, $arr_subcategory]);;
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(100, 15,   isset($arr_barcode[$r]) ? $arr_barcode[$r] : '', '', 'C', false, 0, '', '', true, 1);
                    PDF::MultiCell(100, 15, isset($arr_othcode[$r]) ? $arr_othcode[$r] : '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(120, 15, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 15, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 15,  isset($arr_specs[$r]) ? $arr_specs[$r] : '', '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 15, isset($arr_category[$r]) ? $arr_category[$r] : '', '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 15, isset($arr_subcategory[$r]) ? $arr_subcategory[$r] : '', '', 'L', false, 1, '', '', false, 1);
                }
                if (intVal($i) + 1 == $page) {
                    $this->default_MM_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::MultiCell(240, 0, 'Checked By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(240, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['received'], '', 'L');
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
