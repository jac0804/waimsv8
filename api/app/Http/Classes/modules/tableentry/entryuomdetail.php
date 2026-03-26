<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryuomdetail
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'UOM DETAILS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    public $tablenum = 'transnum';
    private $othersClass;
    private $logger;
    public $style = 'width:60%;max-width:80%;';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $showclosebtn = true;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $column = ['docno', 'unit', 'uom', 'uom2', 'uom3'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];
        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = null;

        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";


        $obj[0][$this->gridname]['columns'][$unit]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$unit]['label'] = 'Request UOM';
        $obj[0][$this->gridname]['columns'][$unit]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom]['label'] = 'Canvass UOM';
        $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';


        $obj[0][$this->gridname]['columns'][$uom2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom2]['label'] = 'Ref. Conversion UOM';
        $obj[0][$this->gridname]['columns'][$uom2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$uom3]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom3]['label'] = 'Reference Conversion';
        $obj[0][$this->gridname]['columns'][$uom3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function loaddata($config)
    {
        $reqtrno = $config['params']['row']['cdrefx'];
        $reqline = $config['params']['row']['cdlinex'];
        $qry = " 
        select  head.docno,stock.trno,stock.line,stock.refx,stock.linex,
        stock.uom,si.uom2,si.uom3,ifnull(info.unit,'') as unit
        from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join hstockinfotrans as si on si.trno=stock.trno and si.line=stock.line
        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
        where stock.trno = $reqtrno and stock.line = $reqline order by line";
        $data = $this->coreFunctions->opentable($qry);

        return $data;
    }
}
