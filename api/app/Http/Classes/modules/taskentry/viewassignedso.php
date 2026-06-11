<?php

namespace App\Http\Classes\modules\taskentry;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class viewassignedso
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ASSIGNED SO';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = [];
    public $showclosebtn = false;
    private $reporter;
    private $logger;
    private $reportheader;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->reportheader = new reportheader;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['docno', 'barcode', 'itemname'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function loaddata($config)
    {
        $userid = $config['params']['adminid'];
        $trno = $config['params']['tableid'];
        $dtable = $this->coreFunctions->getfieldvalue('dailytask', 'trno', 'trno=?', [$trno]);
        $table = 'dailytask';
        if ($dtable === null || $dtable === '') {
            $table = 'hdailytask';
        }

        $username = $this->coreFunctions->getfieldvalue('client', 'email', 'clientid=?', [$userid]);
        $qry = "select head.docno,head.trno, i.barcode,i.itemname from hsohead as head
                left join hsostock as stock on stock.trno=head.trno
                left join $table as task on task.trno=stock.dytrno
                left join item as i on i.itemid=stock.itemid where stock.dytrno <>0 and stock.tmtrno=0 and head.createby='$username' and task.trno = $trno";
        return $this->coreFunctions->opentable($qry);
    }
} //end class
