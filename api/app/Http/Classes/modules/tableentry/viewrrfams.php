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

class viewrrfams
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ASSET TAG';
    public $gridname = 'inventory';
    public $tablenum = 'cntnum';
    public $tablelogs = 'table_log';
    private $logger;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    private $fields = ['barcode', 'serialno', 'isnsi', 'sku', 'fline'];
    private $fieldsiteminfo = ['serialno'];


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
        $attrib = array('load' => 79, 'view' => 79);
        return $attrib;
    }

    public function createTab($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $posted = $this->othersClass->isposted($config);
        $doc = $config['params']['doc'];

        $columns = ['action', 'itemdesc', 'barcode', 'serialno', 'sku', 'isnsi', 'poref'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];
        if (!$posted) {
            $stockbuttons = ['delete'];
        }


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$serialno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';


        $obj[0][$this->gridname]['columns'][$isnsi]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$poref]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$sku]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        if ($config['params']['companyid'] == 16 && $doc == 'RR') { //ati
        } else {
            $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$sku]['type'] = 'coldel';
        }
        $obj[0][$this->gridname]['columns'][$poref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Asset Tag';

        $obj[0][$this->gridname]['columns'][$serialno]['label'] = 'Serial No.';
        if ($posted) {
            $obj[0][$this->gridname]['columns'][$serialno]['type'] = 'label';
        }
        if ($doc == 'FC') {
            $obj[0][$this->gridname]['columns'][$isnsi]['type'] = 'coldel';
        }

        if ($config['params']['companyid'] != 16) { //not ati
            $obj[0][$this->gridname]['columns'][$isnsi]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $posted = $this->othersClass->isposted($config);
        $tbuttons = ['saveallentry'];
        if (!$posted) {
            array_push($tbuttons, 'deleteallitem');
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'SAVE ALL ASSET TAG CHANGES';
        $obj[1]['label'] = 'DELETE ALL';
        return $obj;
    }

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = $this->loaddataperrecord($tableid, $row['line'], $row['fline']);

        $qry = "delete from rrfams where trno=? and line =? and fline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$tableid, $row['line'], $row['fline']]);
        $this->logger->sbcwritelog($tableid, $config, 'DELETE', 'REMOVE: ' . $row['itemdesc'] . ' - Line: ' . $row['line']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $this->coreFunctions->execqry('delete from rrfams where trno=?', 'delete', [$tableid]);

        $this->logger->sbcwritelog($tableid, $config, 'DELETE', "DELETE ALL ASSET TAG");
        // return ['status' => true, 'msg' => 'Successfully deleted.'];
        // $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadgrid' => []];
    }

    private function loaddataperrecord($trno, $line, $fline)
    {
        $select = "select rr.trno, rr.line, item.itemname as itemdesc, rr.itemid, rr.barcode, rr.serialno, 
                       case when rr.isnsi=0 then 'false' else 'true' end as isnsi, '' as bgcolor,s.ref as poref,
                       rr.sku,rr.fline";
        $select = $select . ",'' as bgcolor ";
        $qry = "" . $select . " 
                from rrfams as rr 
                left join lastock as s on s.trno=rr.trno and s.line=rr.line 
                left join item on item.itemid=s.itemid 
                left join item as fa on fa.itemid=rr.itemid
                where rr.trno=? and rr.line=? and rr.fline=?
                union all " . $select . " 
                from hrrfams as rr 
                left join lastock as s on s.trno=rr.trno and s.line=rr.line 
                left join item on item.itemid=s.itemid 
                left join item as fa on fa.itemid=rr.itemid
                where rr.trno=? and rr.line=? and rr.fline=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line, $fline, $trno, $line, $fline]);
        return $data;
    }


    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        return $this->getdata($trno, $config);
    }

    public function getdata($trno, $config)
    {
        $companyid = $config['params']['companyid'];

        $barcode = 'fa.barcode';
        if ($companyid == 16) { //ati
            $barcode = 'rr.barcode';
        }

        $qry = "select rr.trno, rr.line, item.itemname as itemdesc, rr.itemid, " . $barcode . ", rr.serialno, 
                       case when rr.isnsi=0 then 'false' else 'true' end as isnsi, '' as bgcolor,s.ref as poref,
                       rr.sku,rr.fline
                from rrfams as rr 
                left join lastock as s on s.trno=rr.trno and s.line=rr.line 
                left join item on item.itemid=s.itemid 
                left join item as fa on fa.itemid=rr.itemid
                where rr.trno=? 
                union all
                select rr.trno, rr.line, item.itemname as itemdesc, rr.itemid, " . $barcode . ", rr.serialno, 
                       case when rr.isnsi=0 then 'false' else 'true' end as isnsi, '' as bgcolor,s.ref as poref,
                       rr.sku,rr.fline
                from hrrfams as rr 
                left join glstock as s on s.trno=rr.trno and s.line=rr.line 
                left join item on item.itemid=s.itemid 
                left join item as fa on fa.itemid=rr.itemid
                where rr.trno=? order by line,fline,itemid";
        return $this->coreFunctions->opentable($qry, [$trno, $trno]);
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            $iteminfo = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                foreach ($this->fieldsiteminfo as $key2 => $value2) {
                    $iteminfo[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if ($data[$key]['itemid'] != 0) {
                    $this->coreFunctions->sbcupdate('rrfams', $data2, ['trno' => $tableid, 'line' => $data[$key]['line'], 'itemid' => $data[$key]['itemid']]);
                } else {
                    $this->coreFunctions->sbcupdate('rrfams', $data2, ['trno' => $tableid, 'line' => $data[$key]['line'], 'fline' => $data[$key]['fline']]);
                }

                $this->coreFunctions->sbcupdate('iteminfo', $iteminfo, ['itemid' => $data[$key]['itemid']]);
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    }
}
