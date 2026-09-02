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

class viewpendingso
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PENDING SO';
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
        $columns = ['docno', 'barcode', 'itemname', 'rem', 'remarks',  'yourref', 'ispicked'];
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
        $obj[0][$this->gridname]['columns'][$ispicked]['label'] = 'Select';
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Head Remarks';
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';

        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Item Remarks';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';

        $obj[0][$this->gridname]['columns'][$yourref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$yourref]['label'] = 'PO Reference';
        $obj[0][$this->gridname]['columns'][$yourref]['style'] = 'text-align: left; width:125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $stat = $this->coreFunctions->getfieldvalue('dailytask', 'statid', 'trno=?', [$trno]);
        if ($stat ===  null || $stat === '') {
            $stat = $this->coreFunctions->getfieldvalue('hdailytask', 'statid', 'trno=?', [$trno], '', true);
        }
        $tbuttons = ['saveallentry'];

        if ($stat == 1) { //done
            $tbuttons = [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);

        if ($stat != 1) {
            $obj[0]['label'] = "SAVE ALL";
        }
        return $obj;
    }
    public function loaddata($config)
    {
        $userid = $config['params']['adminid'];
        $trno = $config['params']['tableid'];
        $stat = $this->coreFunctions->getfieldvalue('dailytask', 'statid', 'trno=?', [$trno]);
        $table = 'dailytask';
        if ($stat === null || $stat === '') {
            $table = 'hdailytask';
            $hstat = $this->coreFunctions->getfieldvalue('hdailytask', 'statid', 'trno=?', [$trno], '', true);
            $stat = $hstat;
        }
        //get client
        $dyclientid = $this->coreFunctions->datareader("select dy.clientid as value from $table as dy where dy.trno = ?", [$trno], '', true);

        $getclient = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$dyclientid], '', true);

        if ($stat != 1) {
            $username = $this->coreFunctions->getfieldvalue('client', 'email', 'clientid=?', [$userid]);
            $qry = "select head.docno,head.trno, i.barcode,i.itemname,stock.line,i.itemid,head.rem as remarks, 'false' as ispicked,'' as bgcolor,stock.rem,head.yourref from hsohead as head
                left join hsostock as stock on stock.trno=head.trno
                left join item as i on i.itemid=stock.itemid
                where stock.iss>stock.qa and stock.dytrno=0 and stock.tmtrno=0 and head.createby='$username' and head.client='" . $getclient . "'";
            return $this->coreFunctions->opentable($qry);
        } else {
            return [];
        }
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != ''  && $data[$key]['ispicked'] == 'true') {
                $data2['dytrno'] = $trno;
                $this->coreFunctions->sbcupdate('hsostock', $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function


} //end class
