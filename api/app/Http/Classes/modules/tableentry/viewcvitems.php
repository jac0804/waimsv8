<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewcvitems
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'PO ITEMS';
    public $gridname = 'inventory';
    private $fields = ['paid'];
    private $cvfields = ['surcharge', 'acnoid', 'isapproved', 'ispartialpaid', 'amt'];
    private $table = 'stockrem';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 117,
            'surcharge' => 4195
        );
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
        }

        return $this->getheaddata($trno, $config['params']['doc']);
    }

    public function getheaddata($trno, $doc)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $admin = $this->othersClass->checkAccess($config['params']['user'], 4387);

        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        $docno = 0;
        $ctrlno = 1;
        $pono = 2;
        $barcode = 3;
        $itemdesc = 4;
        $rrqty = 5;
        $rrcost = 6;
        $disc = 7;
        $ext = 8;
        $amt1 = 9;
        $amt2 = 10;
        $amt3 = 11;
        $amt4 = 12;
        $amt5 = 13;
        $specs = 14;
        $ispartialpaid = 15;
        $paid = 16;
        $isapproved = 17;
        $category = 18;
        $clientname = 19;
        $department  = 20;
        $requestorname = 21;
        $amt = 22;
        $issc = 23;
        $surcharge = 24;
        $acnoexpense = 25;
        $rem = 26;

        $column = ['docno', 'ctrlno', 'pono', 'barcode', 'itemdesc', 'rrqty', 'rrcost', 'disc', 'ext', 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'specs', 'ispartialpaid', 'paid', 'isapproved', 'category', 'clientname', 'department', 'requestorname', 'amt', 'issc', 'surcharge', 'acnoexpense', 'rem'];
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$ext]['style'] = 'width:140px;whiteSpace: normal;min-width:140px;max-width:140px;';
        $obj[0][$this->gridname]['columns'][$paid]['style'] = 'width:140px;whiteSpace: normal;min-width:140px;max-width:140px;';
        $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$surcharge]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$acnoexpense]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
        $obj[0][$this->gridname]['columns'][$ctrlno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$disc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$pono]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$department]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'input';

        $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$amt1]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt3]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt4]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt5]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt1]['label'] = 'Delivery Fee';
        $obj[0][$this->gridname]['columns'][$amt2]['label'] = 'Diagnostic Fee';
        $obj[0][$this->gridname]['columns'][$amt3]['label'] = 'Installation Fee';
        $obj[0][$this->gridname]['columns'][$amt4]['label'] = 'Consultation Fee';
        $obj[0][$this->gridname]['columns'][$amt5]['label'] = 'Misc. Fee';

        $obj[0][$this->gridname]['columns'][$paid]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$paid]['label'] = 'Paid Amount';
        $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Receipts w/ Issue Amount';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Project';

        $obj[0][$this->gridname]['columns'][$surcharge]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Additional Remarks';

        if (!$admin) {
            $obj[0][$this->gridname]['columns'][$isapproved]['type'] = "coldel";
        }

        if ($isposted) {
            $obj[0][$this->gridname]['columns'][$paid]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$acnoexpense]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$ispartialpaid]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$acnoexpense]['type'] = 'label';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = ['saveallentry', 'generatesurcharge'];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $table = 'cvitems';
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
            $table = 'hcvitems';
        }


        $data = $this->coreFunctions->opentable("select s.trno, s.line, h.docno, item.barcode, item.itemname, 
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost, 
        FORMAT(s.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
        FORMAT(s.paid," . $this->companysetup->getdecimal('currency', $config['params']) . ") as paid, 
        FORMAT(cvs.amt,2) as amt, 
        info.itemdesc, info.specs, pr.clientname, ifnull(cat.category,'') as category,h.yourref as pono, info.requestorname, dept.clientname as department,
        cvs.surcharge,cvs.acnoid,ifnull(c.acnoname,'') as acnoexpense,ifnull(c.acno,'') as acno, cvs.trno as cvtrno, cvs.line as cvline,
        if(cvs.surcharge<>0,'true','false') as issc,  if(s.void=1,'bg-red-2','') as bgcolor,s.void, if(cvs.isapproved<>0,'true','false') as isapproved,
        if(cvs.ispartialpaid<>0,'true','false') as ispartialpaid,info.ctrlno,s.rem,
        poinfo.amt1,poinfo.amt2,poinfo.amt3,poinfo.amt4,poinfo.amt5
        from " . $table . " as cvs 
        left join hpostock as s on s.trno=cvs.refx and s.line=cvs.linex 
        left join item on item.itemid=s.itemid
        left join hpohead as h on h.trno=s.trno
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
        left join hprhead as pr on pr.trno=info.trno
        left join reqcategory as cat on cat.line=pr.ourref
        left join client as dept on dept.clientid=pr.deptid
        left join coa as c on c.acnoid=cvs.acnoid
        left join hstockinfotrans as poinfo on poinfo.trno=s.trno and poinfo.line=s.line
        where cvs.trno=? and cvs.line=?", [$trno, $line]);

        return $data;
    }


    public function saveallentry($config)
    {
        $data = [];
        $data2 = [];
        $row = $config['params']['data'];


        $surcharge = $this->coreFunctions->getfieldvalue("profile", "pvalue",  "doc=? and psection=?", ['SYS', 'SURCHARGE']);
        if ($surcharge == '') {
            return ['status' => false, 'msg' => 'Please setup surcharge rate first in other settings', 'reloadhead' => true];
        }

        foreach ($config['params']['data'] as $key => $row) {
            if ($row['bgcolor'] == 'bg-blue-2') {

                if ($row['void'] == 1) continue;

                if ($row['issc'] == 'true') {
                    $row['surcharge'] = $surcharge;
                } else {
                    $row['surcharge'] = 0;
                    $row['scamt'] = 0;
                }

                foreach ($this->fields as $key => $value) {
                    $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
                }

                foreach ($this->cvfields as $key => $value) {
                    $data2[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
                }

                if ($row['surcharge'] == 0) $data2['scamt'] = 0;

                $this->coreFunctions->LogConsole(json_encode($data));
                $this->coreFunctions->sbcupdate("hpostock", $data, ['trno' => $row['trno'], 'line' => $row['line']]);
                $this->coreFunctions->sbcupdate("cvitems", $data2, ['trno' => $row['cvtrno'], 'line' => $row['cvline'], 'refx' => $row['trno'], 'linex' => $row['line']]);
            }
        }
        $returnrow = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadhead' => true];
    }
    public function tableentrystatus($config)
    {
        switch ($config['params']['action2']) {
            case 'generatesurcharge':
                return $this->generatesurcharge($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in tableentrystatus'];
                break;
        }
    }

    public function generatesurcharge($config)
    {
        $status = true;
        $msg = '';
        $trno = $config['params']['tableid'];
        $acctg = [];

        $qry = "select sum(round((cv.amt * (cv.surcharge/100)),6)) as amt, cv.acnoid, h.client, h.dateid, h.forex, h.cur
                    from cvitems as cv left join lahead as h on h.trno=cv.trno 
                    where cv.trno=" . $trno . " and cv.surcharge<>0 group by cv.acnoid, h.client, h.dateid, h.forex, h.cur ";

        $cvitems = $this->coreFunctions->opentable($qry);
        $line = 0;
        if (!empty($cvitems)) {
            foreach ($cvitems as $key => $chkaccnt) {
                if ($chkaccnt->acnoid == 0) {
                    return ['status' => false, 'msg' => 'Select account.', 'reloadgriddata' => true];
                } else {
                    $this->coreFunctions->execqry("update cvitems as cv left join lahead as h on h.trno=cv.trno left join coa on coa.acnoid=cv.acnoid
                            set cv.scamt=round((cv.amt * (cv.surcharge/100)),2) where cv.trno=" . $trno . " and cv.surcharge<>0 and left(coa.alias,3)='SCE'");

                    foreach ($cvitems as $key => $value) {
                        $this->coreFunctions->execqry("delete from ladetail where trno=" . $trno . " and acnoid=" . $value->acnoid);
                    }

                    $qry = "select line as value from ladetail where trno=? order by line desc limit 1";
                    $line = $this->coreFunctions->datareader($qry, [$trno]);
                    if (
                        $line == ''
                    ) {
                        $line = 0;
                    }
                    $line = $line + 1;
                    foreach ($cvitems as $key => $value) {
                        $forex = $value->forex;
                        if ($forex == 0)   $forex = 1;

                        $entry = [
                            'line' => $line,
                            'acnoid' => $value->acnoid,
                            'client' => $value->client,
                            'cr' => $value->amt,
                            'db' => 0,
                            'postdate' => $value->dateid,
                            'fdb' => 0,
                            'fcr' => 0,
                            'rem' => "Auto entry",
                            'cur' => $value->cur,
                            'forex' => $forex
                        ];


                        $config['params']['trno'] = $trno;
                        $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
                        $line = $line + 1;
                    }


                    if (!empty($acctg)) {
                        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                        foreach ($acctg as $key => $value) {

                            foreach ($value as $key2 => $value2) {
                                $acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                            }

                            $acctg[$key]['editdate'] = $current_timestamp;
                            $acctg[$key]['editby'] = $config['params']['user'];
                            $acctg[$key]['encodeddate'] = $current_timestamp;
                            $acctg[$key]['encodedby'] = $config['params']['user'];
                            $acctg[$key]['trno'] = $config['params']['trno'];
                            $acctg[$key]['sortline'] = $acctg[$key]['line'];

                            $sc_amount = $acctg[$key]['cr'];
                            $acctg[$key]['cr'] = 0;

                            if ($this->coreFunctions->sbcinsert("ladetail", $acctg) == 1) {

                                $this->coreFunctions->execqry("delete from detailinfo where trno=" . $acctg[$key]['trno'] . " and line=" . $acctg[$key]['line']);
                                $detailinfo = [
                                    'trno' => $acctg[$key]['trno'],
                                    'line' => $acctg[$key]['line'],
                                    'payment' => $sc_amount
                                ];
                                if ($this->coreFunctions->sbcinsert("detailinfo", $detailinfo) == 1) {
                                    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'GENERATE SURCHARGE ENTRY');
                                    $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
                                } else {
                                    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED (detailinfo)');
                                    $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED (detailinfo)";
                                    $status = false;
                                }
                            } else {
                                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
                                $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
                                $status = false;
                            }
                        }
                    }
                }
            }
        } else {
            $msg = 'Nothing to compute';
        }

        if ($msg == '') {
            $msg = 'Surcharge succesfully created';
        }

        $path = 'App\Http\Classes\modules\ati\cv';
        $config['params']['trno'] = $trno;
        $detail = app($path)->opendetail($trno, $config);

        return ['status' => $status, 'msg' => $msg, 'reloadgriddata' => ['accounting' => $detail]];
    }

    public function lookupexpense($config)
    {
        $plotting = array('acnoid' => 'acnoid', 'acnoexpense' => 'acnoname');
        $plottype = 'plotgrid';
        $title = 'List of Surcharge';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );

        $cols = [
            ['name' => 'acno', 'label' => 'Code', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'acnoname', 'label' => 'Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select '' as acno, '' as acnoname, 0 as acnoid union all select acno, acnoname, acnoid from coa where left(alias,3)='SCE'";

        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        $table = isset($config['params']['table']) ? $config['params']['table'] : "";

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookupexpense':
                return $this->lookupexpense($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }
}
