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

class entrypendingso
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SALES ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hsohead';
    private $othersClass;
    public $style = 'width:100%;';


    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';

    public $htablelogs = 'htable_log';
    public $tablelogs = 'table_log';
    public  $tablelogs_del = 'del_table_log';

    private $fields = ['task', 'title', 'userid', 'startdate', 'enddate', 'percentage', 'taskcatid', 'isassigntype'];
    public $showclosebtn = false;
    private $reporter;
    public $logger;
    private $reportheader;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
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
        $columns = ['action', 'docno', 'client', 'clientname', 'rem', 'barcode', 'itemname', 'ref'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';

        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name';
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';

        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

        $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Document No.';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tableid = $config['params']['tableid'];
        $tbuttons = ['additem', 'saveallentry', 'whlog'];
        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }
        $isclose = $this->isclose($config);
        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($isclose || $tableid == 0) {
            $obj[0]['visible'] = false;
            $obj[1]['visible'] = false;
        }
        $obj[$additem]['label'] = 'ADD PENDING SO';
        $obj[$additem]['lookupclass'] = 'addpendingso';
        $obj[$additem]['action'] = 'lookupsetup';

        $obj[$saveallentry]['label'] = 'GENERATE SJ';
        return $obj;
    }


    public function add($config)
    {
        $trno = $config['params']['tableid'];

        $data = [];
        return $data;
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['tableid'];
        $return = $this->generatesj($config);
        $returndata = $this->loaddata($config);
        return ['status' => $return['status'], 'msg' => $return['msg'], 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $qry = "update hsostock set tmtrno=? where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'update', [$trno, $row['sotrno'], $row['soline']]);
        $config['params']['doc'] = 'ENTRYPENDINGSO';
        $this->tablelogs = 'masterfile_log';
        $this->logger->sbcmasterlog($trno, $config, 'ADD - Line : ' . $row['soline'] . ' Item Name: ' . $row['itemname'] . ' Docno : ' . $row['sodocno']);
        $returnrow = $this->loaddataperrecord($trno, $row['sotrno'], $row['soline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } //end function
    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        if ($row['ref'] != "") {
            return ['status' => false, 'msg' => 'cannot be deleted; already have SJ'];
        }
        $qry = "update hsostock set tmtrno = 0 where trno=?  and line=? and tmtrno=?";
        $this->coreFunctions->execqry($qry, 'update', [$row['sotrno'], $row['soline'], $trno]);
        $config['params']['doc'] = 'ENTRYPENDINGSO';
        $this->tablelogs_del = 'del_masterfile_log';
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE - Line : Task : ' . $row['itemname'] . ' Docno : ' . $row['docno']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function selectqry()
    {
        $qry = "head.client,head.clientname,head.docno,head.rem,item.itemname,item.barcode,head.forex,head.cur,head.agent,head.address,head.tax,head.vattype,head.wh,head.terms,head.due,head.projectid,head.ourref,head.yourref,head.pono,head.sano,head.shipto,head.rem,stock.line as soline,stock.trno as sotrno,stock.tmtrno,stock.itemid,
        wh.client as stockwh,stock.uom,stock.disc,stock.amt,stock.isamt,stock.iss,stock.isqty,stock.ext,stock.void,stock.loc,stock.expiry,stock.kgs,
        (select h.docno from glstock as s
		  left join glhead as h on h.trno = s.trno where s.refx = stock.trno and s.linex = stock.line limit 1 ) as ref";

        return $qry;
    }


    private function loaddataperrecord($trno, $sotrno, $soline)
    {
        $select = $this->selectqry();
        $qry = "select " . $select . ",'' as bgcolor
         from hsohead as head
         left join hsostock as stock on stock.trno=head.trno
         left join item on item.itemid=stock.itemid
         left join client as wh on wh.clientid = stock.whid
         where stock.tmtrno = ? and stock.trno=? and stock.line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $sotrno, $soline]);
        return $data;
    }


    public function loaddata($config)
    {
        $select = $this->selectqry();
        $company = $config['params']['companyid'];
        $trno = $config['params']['tableid'];
        $limit = '';
        $filtersearch = "";
        $searcfield = ['docno', 'clientname'];
        $search = '';

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            if (!empty($search)) {
                foreach ($searcfield as $key => $sfield) {
                    if ($filtersearch == "") {
                        $filtersearch .= " and (head." . $sfield . " like '%" . $search . "%'";
                    } else {
                        $filtersearch .= " or head." . $sfield . " like '%" . $search . "%'";
                    } //end if
                }
            }
            $filtersearch .= ")";
        }

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }
        $qry = "select " . $select . ",'' as bgcolor from " . $this->table . " as head
        left join hsostock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        where stock.tmtrno  = ?" . $filtersearch . " order by ref";
        return  $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'addpendingso':
                return $this->addpendingso($config); //addpendingso
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function lookupcallback($config)
    {
        $row = $config['params']['rows'];
        $tableid = $config['params']['tableid'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';
        foreach ($row  as $key2 => $value) {
            $config['params']['row']['sotrno'] = $row[$key2]['trno'];
            $config['params']['row']['itemid'] = $row[$key2]['itemid'];
            $config['params']['row']['itemname'] = $row[$key2]['itemname'];
            $config['params']['row']['sodocno'] = $row[$key2]['docno'];
            $config['params']['row']['soline'] = $row[$key2]['line'];
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $errors[] = $return['msg'];
                $status = false;
            }
        }


        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    }
    public function addpendingso($config)
    {
        $trno = $config['params']['tableid'];
        $client = $this->coreFunctions->datareader("select client.client as value from tmhead as head left join client on client.clientid = head.clientid where head.trno =? ", [$trno], '');
        $user = $config['params']['user'];
        $lookupsetup = array(
            'type' => 'multi', //single
            'rowkey' => 'trnoln',
            'title' => 'List of Pending SO',
            'style' => 'width:1200px;max-width:1200px;height:60%;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'entrypendingso',
        );

        // lookup columns
        $cols = [
            ['name' => 'docno', 'label' => 'Document No.', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'client', 'label' => 'Customer', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'rem', 'label' => 'Notes', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'itemname', 'label' => 'Item Name', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;']

        ];
        $qry = "select  head.client,head.clientname,head.docno,head.rem,item.itemname,item.barcode,concat(stock.trno,'~',stock.line) as trnoln,stock.line,stock.tmtrno,stock.trno,stock.itemid
         from hsostock as stock
         left join hsohead as head on head.trno=stock.trno
         left join item on item.itemid=stock.itemid
         where stock.tmtrno = 0 and head.client = '$client' and head.createby = '$user' order by head.docno asc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function generatesj($config)
    {
        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        $user = $config['params']['user'];
        $sj_line = [];
        $sjtrno = 0;
        $defaultContra = 'AR1';
        $docno = "";
        $msg = '';
        $status = true;
        $path = 'App\Http\Classes\modules\sales\sj';

        $sjdate = $this->coreFunctions->datareader("select sjdate as value from tmhead where trno = $trno", [], '');
        if ($sjdate == null) {
            return ['status' => false, 'msg' => 'please encode SJ Date field.'];
        }
        foreach ($data as $key => $value) {

            // foreach ($this->fields as $key2 => $value2) {
            //     $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
            // }
            // check existing head

            if ($data[$key]['ref'] == "") {

                array_push($sj_line, $data[$key]['soline']);
                $date = $this->othersClass->getCurrentDate();
                $datestamp = $this->othersClass->getCurrentTimeStamp();


                if ($sjtrno != 0) {
                    $headdata = $this->coreFunctions->opentable("select trno,docno from lahead where doc = 'SJ' and client ='" . $data[$key]['client'] . "' and trno = '" . $sjtrno . "'");
                    if (!empty($headdata)) {
                        $docno = $headdata[0]->docno;
                        $sjtrno = $headdata[0]->trno;
                        $config['params']['doc'] = 'SJ';
                    }
                } else {
                    //generate header
                    $doc = 'SJ';
                    $sjref = 'SJ';
                    $asjref = 'SJ'; //bref
                    $table = 'cntnum';
                    $center = $config['params']['center'];
                    $docnolength =  $this->companysetup->getdocumentlength($config['params']);

                    $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$asjref]);
                    $seq = $this->othersClass->getlastseq($asjref, $config, $table);
                    if ($getdoc == '') {
                        $seq = $this->othersClass->getlastseq($sjref, $config, $table);
                    }
                    $mrseq = $asjref . $seq;
                    $newdocno = $this->othersClass->PadJ($mrseq, $docnolength);
                    $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $asjref, 'center' => $center];
                    $insert = $this->coreFunctions->insertGetId($table, $col);

                    $cntdata =  $this->coreFunctions->opentable("select trno,docno from cntnum where doc = ? and docno = ? and center = ?", [$doc, $newdocno, $center]);

                    $docno = $cntdata[0]->docno;
                    $sjtrno = $cntdata[0]->trno;
                    $this->coreFunctions->logconsole($sjtrno . '---' . $docno);
                    $head = [
                        'trno' => $sjtrno,
                        'doc' => $doc,
                        'docno' => $docno,
                        'client' => $data[$key]['client'],
                        'clientname' => $data[$key]['clientname'],
                        'address' => $data[$key]['address'],
                        'shipto' => $data[$key]['shipto'],
                        'agent' => $data[$key]['agent'],
                        'ourref' => $data[$key]['ourref'],
                        'yourref' => $data[$key]['yourref'],
                        'cur' => $data[$key]['cur'],
                        'forex' => $data[$key]['forex'],
                        'dateid' => $sjdate,
                        'due' => $data[$key]['due'],
                        'terms' => $data[$key]['terms'],
                        'wh' => $data[$key]['wh'],
                        'vattype' => $data[$key]['vattype'],
                        'tax' => $data[$key]['tax'],
                        'projectid' => $data[$key]['projectid'],
                        'contra' => $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$defaultContra]),
                        'rem' => $data[$key]['rem'],
                        'sano' => $data[$key]['sano'],
                        'pono' => $data[$key]['pono']
                    ];



                    foreach ($head as $key2 => $val) {
                        $head[$key2] = $this->othersClass->sanitizekeyfield($key2, $head[$key2]);
                    }
                    $head['createdate'] = $date;
                    $head['createby'] = $config['params']['user'];
                    $lahead = $this->coreFunctions->sbcinsert('lahead', $head);
                    $config['params']['doc'] = $doc;

                    if ($lahead) {
                        $msg = ' New Transaction Generated ' . $docno;
                        $this->logger->sbcwritelog($sjtrno, $config, 'CREATE', 'GENERETED SJ - ' . $docno . ' - ' . $data[$key]['client'] . ' - ' . $data[$key]['clientname']);
                    } else {
                        $msg = ' Creating Head Failed. ';
                        $config['params']['doc'] = 'ENTRYPENDINGSO';
                        $this->tablelogs = 'masterfile_log';
                        $this->logger->sbcmasterlog($trno, $config, 'FAILED TO GENERATE HEADER');
                        $status = false;
                        goto end;
                    }
                }

                //stock
                $config['params']['trno'] = $sjtrno;
                $config['params']['data']['uom'] = $data[$key]['uom'];
                $config['params']['data']['disc'] = $data[$key]['disc'];
                $config['params']['data']['qty'] = $data[$key]['isqty'];
                $config['params']['data']['amt'] = $data[$key]['isamt'];
                // $config['params']['data']['ext'] = $data[$key]['ext'];
                $config['params']['data']['itemid'] = $data[$key]['itemid'];
                $config['params']['data']['refx'] = $data[$key]['sotrno'];
                $config['params']['data']['linex'] = $data[$key]['soline'];
                $config['params']['data']['ref'] = $data[$key]['docno'];
                $config['params']['data']['loc'] = $data[$key]['loc'];
                $config['params']['data']['expiry'] = $data[$key]['expiry'];
                $config['params']['data']['kgs'] = $data[$key]['kgs'];

                $config['params']['data']['wh']  = $data[$key]['stockwh'];

                $config['params']['doc'] = 'SJ';
                $return = app($path)->additem('insert', $config, true);
                if (!$return['status']) {
                    $msg = ' generate stock failed!';
                    $config['params']['doc'] = 'ENTRYPENDINGSO';
                    $this->tablelogs = 'masterfile_log';
                    $this->logger->sbcmasterlog($trno, $config, 'FAILED TO GENERATE STOCK');
                    end:
                    $status = false;
                    $this->coreFunctions->execqry("delete from lahead where trno=? and doc = 'sj'", 'delete', [$sjtrno]);
                    $this->coreFunctions->execqry("delete from lastock where trno=?", 'delete', [$sjtrno]);
                    $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$sjtrno]);
                    break;
                }
            }
        }
        if ($status && $msg != "") {
            $config['params']['tableid'] = $sjtrno;
            $postrans = app($path)->posttrans($config);
            $config['params']['trno'] = $trno;
            if ($postrans['status']) {
                $config['params']['doc'] = 'ENTRYPENDINGSO';
                $this->tablelogs = 'masterfile_log';
                $this->tablelogs_del = 'del_masterfile_log';
                $this->logger->sbcmasterlog($trno, $config, 'GENERETE - ' . $docno);
            } else {
                $msg = $postrans['msg'];
                $status = false;
                $this->coreFunctions->execqry("delete from lahead where trno=? and doc = 'sj'", 'delete', [$sjtrno]);
                $this->coreFunctions->execqry("delete from lastock where trno=?", 'delete', [$sjtrno]);
                $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$sjtrno]);
            }
        }
        if (empty($msg)) {
            $msg = 'No Data Found!';
        }

        return ['status' => $status, 'msg' => $msg];
    }

    public function lookuplogs($config)
    {
        $doc = 'ENTRYPENDINGSO';
        $this->tablelogs = 'masterfile_log';
        $this->tablelogs_del = 'del_masterfile_log';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Task Master Logs',
            'style' => 'width:100%;max-width:90%;height:50%;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = $config['params']['tableid'];

        $qry = "
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
    public function isclose($config)
    {
        $tableid = $config['params']['tableid'];
        $status = $this->coreFunctions->datareader("select status as value from tmhead where trno= ?", [$tableid]);
        if ($status != 2) {
            return false;
        } else {
            return true;
        }
    }
} //end loantype
