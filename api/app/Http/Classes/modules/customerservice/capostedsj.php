<?php

namespace App\Http\Classes\modules\customerservice;

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


class capostedsj
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'REFERENCE DOCUMENTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;

    public $table = '';
    public $htable = 'glhead';

    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['catrno'];
    public $tablelogs = 'transnum_log';
    public $showclosebtn = true;
    private $enrollmentlookup;
    private $logger;

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $isposted = $this->othersClass->isposted2($config['params']['tableid'], "transnum");
        $column = ['action', 'docno', 'dateid'];
        $stockbuttons = ['delete'];
        if ($isposted) {
            array_shift($column);
            $stockbuttons = [];
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;

        return $obj;
    }
    public function createtabbutton($config)
    {
        $isposted = $this->othersClass->isposted2($config['params']['tableid'], "transnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = ['addrefdoc', 'deleteallitem'];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = 'Delete all';
        $obj[1]['lookupclass'] = 'loaddata';
        return $obj;
    }
    public function add($config)
    {
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['docno'] = 0;
        $data['dateid'] = '';
        $data['catrno'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        if ($this->coreFunctions->sbcupdate($this->htable, ['catrno' => $row['catrno']], ['trno' => $row['trno']]) == 1) {
            $returnrow = $this->loaddataperrecord($row['trno']);

            for ($i = 0; $i < count($returnrow); $i++) {
                $this->logger->sbcwritelog(
                    $trno,
                    $config,
                    'ADD REFERENCE DOCUMENT',
                    "DOC NO. : " . $returnrow[$i]->docno . ' DATE: ' . $returnrow[$i]->dateid
                );
            }

            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $user = $config['params']['user'];
                $data2['createdate'] = $current_timestamp;
                $data2['createby'] = $user;
                $data2['trno'] = $config['params']['tableid'];
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
                    $checkline = $this->coreFunctions->datareader($qry, [$config['params']['tableid']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        $this->coreFunctions->execqry("update glhead set catrno=0 where trno=?", 'update', [$row['trno']]);

        $this->logger->sbcwritelog(
            $trno,
            $config,
            'DELETE REFERENCE DOCUMENT',
            "DOC NO. : " . $row['docno'] . ' DATE: ' . $row['dateid']
        );

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        $this->coreFunctions->execqry("update glhead set catrno=0 where catrno=?", 'update', [$trno]);

        $this->logger->sbcwritelog($trno, $config, 'REFERENCE DOCUMENTS', 'DELETED ALL REFERENCE SJ DOCUMENTS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($trno)
    {
        $qry = "select sj.trno,sj.docno, sj.dateid
                from csstickethead as head
                left join glhead as sj on sj.catrno= head.trno
                where sj.trno=? and sj.catrno <> 0
                union all
                select sj.trno,sj.docno, sj.dateid
                from hcsstickethead as head
                left join glhead as sj on sj.catrno= head.trno
                where sj.trno=? and sj.catrno <> 0
                order by docno";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];

        $qry = "select sj.trno,sj.docno, sj.dateid
                from csstickethead as head
                left join glhead as sj on sj.catrno= head.trno
                where head.trno=? and sj.catrno <> 0
                union all
                select sj.trno,sj.docno, sj.dateid
                from hcsstickethead as head
                left join glhead as sj on sj.catrno= head.trno
                where head.trno=? and sj.catrno <> 0
                order by docno";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addrefdoc':
                return $this->addrefdoc($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function addrefdoc($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'keyid',
            'title' => 'List of Posted SJ',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = array(
            array('name' => 'docno', 'label' => 'Document #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select trno as keyid,trno,docno,dateid
            from glhead
            where doc='SJ' and catrno=0
            order by docno";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        $returndata = [];

        foreach ($row  as $key2 => $value) {
            $config['params']['row']['catrno'] = $id;
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $config['params']['row']['trno'] = $row[$key2]['trno'];
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            }
        }
        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
    } // end function


} //end class
