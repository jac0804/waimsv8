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
use DateTime;

class entrytripob
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TRIP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    public $tablelogs = 'payroll_log';
    public $detail = 'obdetail';
    private $logger;
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['trno', 'line', 'purpose', 'destination', 'leadfrom', 'leadto', 'contact'];
    private $lead = ['leadfrom', 'leadto'];
    public $showclosebtn = false;
    public $showsearch = true;


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
        $tableid = $config['params']['tableid'];
        $isapproved = $this->isapproved($config);
        $gridcolumns = ['action', 'purpose', 'destination', 'leadfrom', 'leadto', 'contact'];
        $stockbuttons = ['save', 'delete'];

        if ($isapproved) {
            $stockbuttons = [];
        }
        foreach ($gridcolumns as $key => $value) {
            $$value = $key;
        }



        $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);



        $obj[0][$this->gridname]['columns'][$purpose]['label'] = 'Purpose of Travel';

        $obj[0][$this->gridname]['columns'][$leadfrom]['label'] = 'Time From';
        $obj[0][$this->gridname]['columns'][$leadto]['label'] = 'Time To';

        $obj[0][$this->gridname]['columns'][$leadfrom]['type'] = 'time';
        $obj[0][$this->gridname]['columns'][$leadto]['type'] = 'time';

        $obj[0][$this->gridname]['columns'][$leadto]['style'] =  'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:right;';
        $obj[0][$this->gridname]['columns'][$leadfrom]['style'] =  'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:right;';

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:left;';
        $obj[0][$this->gridname]['columns'][$destination]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;text-align:left;';
        $obj[0][$this->gridname]['columns'][$purpose]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;text-align:left;';

        if ($isapproved) {
            $obj[0][$this->gridname]['columns'][$leadto]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$leadfrom]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$purpose]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$destination]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$action]['style'] = 'display:none;';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from obdetail where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
    }

    public function createtabbutton($config)
    {
        $tableid = $config['params']['tableid'];
        $tbuttons = ['saveallentry', 'addrecord'];
        $isapproved = $this->isapproved($config);
        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($isapproved || $tableid == 0) {
            $obj[0]['visible'] = false;
            $obj[1]['visible'] = false;
        }
        $obj[0]['label'] = 'SAVE DETAILS';
        $obj[1]['label'] = 'ADD ROW';
        return $obj;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $qry = "select detail.trno,detail.line,detail.purpose,detail.destination,
        date_format(detail.leadfrom,'%H:%i') as leadfrom,date_format(detail.leadto,'%H:%i') as leadto,contact,
        '' as bgcolor  from obdetail as detail 
        where detail.trno = ? and detail.trno <> 0 order by line asc ";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }

    public function lookupsetup($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'saveallentry':
                return $this->saveallentry($config);
                break;
            case 'addrecored':
                return $this->add($config);
                break;
        }
    } //end function

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $table = $this->detail;
        $row = $config['params']['data'];
        $fromdate = null;
        $todate = null;
        if ($trno == 0) {
            return ['status' => false, 'msg' => 'Please save header before adding details.', 'data' => [], 'reloadledgerdata' => true];
        }
        $date = $this->othersClass->getCurrentDate();
        if (!empty($row)) {

            foreach ($row as $key => $rows) {
                $data = [];
                foreach ($this->fields as $key2 => $value) {
                    if (in_array($value, $this->lead)) {
                        if ($value == 'leadfrom') {
                            $f = new DateTime($date . " " . $row[$key][$value]);
                            $fromdate  =  $f->format("Y-m-d H:i:s");
                        }
                        if ($value == 'leadto') {
                            $t = new DateTime($date . " " . $row[$key][$value]);
                            $todate  =  $t->format("Y-m-d H:i:s");
                        }
                    }
                    $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$key][$value]);
                }

                $data['leadfrom'] = $fromdate;
                $data['leadto'] = $todate;
                if ($data['line'] == 0) {
                    if (isset($rows['bgcolor'])) {
                        if ($rows['bgcolor'] != '') {
                            $qry = "select line as value from $table where trno=$trno order by line desc limit 1";
                            $line = $this->coreFunctions->datareader($qry);
                            if ($line == '') {
                                $line = 0;
                            }
                            $line = $line + 1;
                            $data['line'] = $line;
                            $data['encodedate'] = $this->othersClass->getCurrentTimeStamp();
                            $data['encodedby'] = $config['params']['user'];
                            $this->coreFunctions->sbcinsert($table, $data);
                        }
                    }
                } else {
                    if (isset($rows['bgcolor']) && $data['line'] != 0) {
                        if ($rows['bgcolor'] != '') {
                            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                            $data['editby'] = $config['params']['user'];

                            $this->coreFunctions->sbcupdate($table, $data, ['trno' => $data['trno'], 'line' => $data['line']]);
                        }
                    }
                }
            }
        }



        $config['params']['trno'] = $trno;
        $txtdata = app('App\Http\Classes\modules\payroll\\obapplication')->getheadqry($config, $trno);

        $returnrow = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadledgerdata' => true, 'txtdata' => $txtdata];
    }
    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $date = $this->othersClass->getCurrentDate();
        foreach ($this->fields as $key => $value) {
            if (in_array($value, $this->lead)) {
                if ($value == 'leadfrom') {
                    $f = new DateTime($date . " " . $row[$value]);
                    $fromdate  =  $f->format("Y-m-d H:i:s");
                }
                if ($value == 'leadto') {
                    $t = new DateTime($date . " " . $row[$value]);
                    $todate  =  $t->format("Y-m-d H:i:s");
                }
            }
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['leadfrom'] = $fromdate;
        $data['leadto'] = $todate;
        if ($row['line'] == 0) {

            $qry = "select line as value from $this->detail where trno= " . $row['trno'] . " order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            $data['encodedate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->detail, $data);
            if ($insert != 0) {
                $returnrow = $this->loaddataperrecord($config, $line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . 'OB-DETAIL' . $row['purpose']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($config, $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function
    public function loaddataperrecord($config, $line)
    {
        $trno = $config['params']['tableid'];
        $query = "select detail.trno,detail.line,detail.purpose,detail.destination,
        date_format(detail.leadfrom,'%H:%i') as leadfrom,date_format(detail.leadto,'%H:%i') as leadto,contact,
        '' as bgcolor  from obdetail as detail 
        where detail.trno = $trno and line = $line order by line desc";
        return $this->coreFunctions->opentable($query);
    }
    public function add($config)
    {
        $data = [];
        $trno = $config['params']['tableid'];
        $data['trno'] = $trno;
        $data['line'] = 0;
        $data['purpose'] = '';
        $data['destination'] = '';
        $data['leadfrom'] = '00:00';
        $data['leadto'] = '00:00';
        $data['contact'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }
    public function isapproved($config)
    {
        $tableid = $config['params']['tableid'];
        $adminid = $config['params']['adminid'];
        $status = $this->coreFunctions->datareader("select status as value from obapplication where line = ? and empid = ?", [$tableid, $adminid]);
        if (in_array($status, ['A', 'D'])) {
            return true;
        } else {
            return false;
        }
    }
} //end class
