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

class entrytruckdocuments
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DOCUMENTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'whdoc';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'wh_log';
    public $tablelogs_del = 'del_wh_log';
    private $fields = ['docno', 'expiry', 'expiry2', 'days', 'whid', 'rem'];
    public $showclosebtn = false;
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
        // $tab = [
        //     $this->gridname => [
        //         'gridcolumns' => ['action', 'docno', 'expiry', 'days', 'rem']
        //     ]
        // ];

        $columns = ['action', 'docno', 'expiry', 'days', 'rem'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$expiry]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][$docno]['label'] = "Reference";
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = "date";
        $obj[0][$this->gridname]['columns'][$expiry]['readonly'] = false;
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['docno'] = '';
        $data['expiry'] = ''; //date('Y-m-d')
        $data['expiry2'] = ''; //date('Y-m-d')
        $data['days'] = 0;
        $data['whid'] = $config['params']['tableid'];
        $data['rem'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line,docno,date(expiry) as expiry,date(expiry2) as expiry2,days,rem,whid, '" . $this->table . "' as tabtype";

        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $data['editdate'] = $current_timestamp;
                $data['editby'] = $config['params']['user'];

                $days = $data[$key]['days'];
                $expiry = $data[$key]['expiry'];

                $exp2 = $this->coreFunctions->opentable("select DATE_SUB('" . $expiry . "', INTERVAL $days DAY) as expiry2");
                if ($data2['expiry'] == '') {
                    $data2['expiry'] = NULL;
                    $data2['expiry2'] = NULL;
                }

                foreach ($exp2 as $key2 => $value) {

                    $data2['expiry2'] = $exp2[$key2]->expiry2;

                    if ($data[$key]['line'] == 0) {
                        $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data2['createby'] = $config['params']['user'];
                        $data2['rem'] = '';

                        $this->logger->sbcwritelog($tableid, $config, 'WH DOCUMENTS', ' CREATE - ' . $data[$key]['docno']);
                        $this->coreFunctions->sbcinsert($this->table, $data2);
                    } else {

                        $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    }
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $tableid = $config['params']['tableid'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];

        $days = $data['days'];
        $expiry = $data['expiry'];
        $exp2 = $this->coreFunctions->opentable("select DATE_SUB('" . $expiry . "', INTERVAL $days DAY) as expiry2");
        $data['expiry2'] = $exp2[0]->expiry2;

        if ($data['expiry'] == '') {
            $data['expiry'] = NULL;
            $data['expiry2'] = NULL;
        }

        if ($row['line'] == 0) {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['rem'] = '';
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcwritelog($tableid, $config, 'WH DOCUMENTS', ' CREATE - ' . $data['docno']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = $this->loaddataperrecord($row['line']);

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

        if (!empty($data)) {
            $this->logger->sbcwritelog($tableid, $config, 'WH DOCUMENTS', 'REMOVE - ' . $data[0]->docno);
        }
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " 
    where whid = " . $tableid . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookupoicemployee1':
            case 'lookupoicemployee2':
                return $this->lookupemployee($config);
                break;

            case 'whlog':
                return $this->lookuplogs($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookupemployee($config)
    {
        $rowindex = $config['params']['index'];
        $lookupclass2 = $config['params']['lookupclass2'];
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Employee',
            'style' => 'width:900px;max-width:900px;'
        );

        switch ($lookupclass2) {
            case 'lookupoicemployee1':
                $plotsetup = array(
                    'plottype' => 'plotgrid',
                    'plotting' => array(
                        'oic1' => 'clientname',
                    )
                );
                break;

            default:
                $plotsetup = array(
                    'plottype' => 'plotgrid',
                    'plotting' => array(
                        'oic2' => 'clientname',
                    )
                );
                break;
        }

        $cols = array(
            array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "select e.empid, client.client as empcode, 
        concat(e.empfirst,' ',e.empmiddle, ' ', e.emplast) as clientname
        from employee as e 
        left join client on client.clientid=e.empid";

        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Warehouse Documents Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'userid', 'label' => 'User', 'align' => 'left', 'field' => 'userid', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'field', 'label' => 'Level', 'align' => 'left', 'field' => 'field', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'oldversion', 'label' => 'Activity', 'align' => 'left', 'field' => 'oldversion', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = $config['params']['tableid'];

        $qry = "select trno, field, oldversion, log.userid, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.userid
    where log.trno= ? and log.field = 'WH DOCUMENTS'
    union all
    select trno, concat('DELETE',' ',field), docno, log.userid, dateid, if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.userid
    where log.trno= ? and log.field = 'WH DOCUMENTS' ";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    // -> Print Function
    public function reportsetup($config)
    {
        return [];
    }


    public function createreportfilter()
    {
        return [];
    }

    public function reportparamsdata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        return [];
    }
} //end class
