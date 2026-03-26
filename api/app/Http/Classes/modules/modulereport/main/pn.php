<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class pn
{
    private $modulename = "Project Completion";
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

        $fields = ['radioprint', 'prepared', 'approved', 'received'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '' as prepared,
            '' as approved,
            '' as received"
        );
    }

    public function report_default_query($trno)
    {
        // $trno = $config['params']['dataid'];
        $query = "select  head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref, coa.acnoname,
                    ifnull(project.name,'') as projectname, phase.code as phase,model.model as housemodel,
                    bl.blk as blklot,bl.lot,head.rem,
                    ifnull(i.barcode,'') as barcode,
                    ifnull(i.itemname,'') as itemname,stock.uom,stock.rrqty,stock.qty,
                     stock.rrcost,stock.cost,ifnull(amen.description,'') as amenityname,
                     ifnull(subamen.description,'') as subamenityname,
                    left(head.voiddate,10) as voiddate, num.center,stock.line,cl.clientname,warehouse.clientname as whname

            from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join projectmasterfile as project on project.line=head.projectid
                    left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                    left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
                    left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                    left join coa on coa.acno=head.contra
                    left join amenities as amen on amen.line= stock.amenityid
                    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
                    left join transnum as num on num.trno = head.trno
                    left join client as cl on cl.client=head.client
                    left join client as warehouse on warehouse.client = head.wh where head.trno= '$trno'
                
                    union all

        select  head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                            head.rem as note, head.yourref, coa.acnoname,
                            ifnull(project.name,'') as projectname, phase.code as phase,model.model as housemodel,
                            bl.blk as blklot,bl.lot,head.rem,
                            ifnull(i.barcode,'') as barcode,
                            ifnull(i.itemname,'') as itemname,stock.uom,stock.rrqty,stock.qty,
                            stock.rrcost,stock.cost,ifnull(amen.description,'') as amenityname,
                            ifnull(subamen.description,'') as subamenityname,
                            left(head.voiddate,10) as voiddate, num.center,stock.line,cl.clientname,warehouse.clientname as whname

                    from glhead as head
                            left join glstock as stock on stock.trno=head.trno
                            left join item as i on i.itemid=stock.itemid
                            left join projectmasterfile as project on project.line=head.projectid
                            left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                            left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
                            left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                            left join coa on coa.acno=head.contra
                            left join amenities as amen on amen.line= stock.amenityid
                            left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
                            left join transnum as num on num.trno = head.trno
                            left join client as cl on cl.clientid=head.clientid
                            left join client as warehouse on warehouse.clientid = head.whid  where head.trno= '$trno' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        return $this->default_pn_PDF($params, $data);
    }

    public function default_pn_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 11;
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

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, "", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        // PDF::SetFont($font, '', $fontsize);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "House Model : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['housemodel']) ? $data[0]['housemodel'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Clientname : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Yourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Warehouse : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Ourref : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Project : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Block : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['blklot']) ? $data[0]['blklot'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 20, "Phase : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 20, (isset($data[0]['phase']) ? $data[0]['phase'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 20, "Lot : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, (isset($data[0]['lot']) ? $data[0]['lot'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(150, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(250, 0, "ITEMNAME", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "AMENITY", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "SUBAMENITY", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_pn_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
        $totalext = 0;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_pn_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;
                $barcode = $data[$i]['barcode'];
                $itemname = $data[$i]['itemname'];
                $qty = number_format($data[$i]['qty'], 2);
                $uom = $data[$i]['uom'];
                $amenity = $data[$i]['amenityname'];
                $subamenity = $data[$i]['subamenityname'];


                $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
                $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
                $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
                $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
                $arr_amenity = $this->reporter->fixcolumn([$amenity], '35', 0);
                $arr_subamenity = $this->reporter->fixcolumn([$subamenity], '35', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amenity, $arr_subamenity]);
                for ($r = 0; $r < $maxrow; $r++) {

                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(150, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_amenity[$r]) ? $arr_amenity[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 15, ' ' . (isset($arr_subamenity[$r]) ? $arr_subamenity[$r] : ''), '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
                if (PDF::getY() > 900) {
                    $this->default_pn_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['note'], '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
