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
use App\Http\Classes\modules\calendar\em;
use App\Http\Classes\sqlquery;
use DateTime;

class entrysuspension
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TRIP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    public $tablelogs = 'payroll_log';
    public $detail = 'datesuspension';
    private $logger;
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['trno', 'line', 'startdate', 'enddate'];
    private $lead = ['startdate', 'enddate'];
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
        $isposted = $this->othersClass->isposted2($tableid, 'hrisnum');
        $gridcolumns = ['action', 'startdate', 'enddate'];
        $stockbuttons = ['save', 'delete'];
        foreach ($gridcolumns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';
        $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'Time To';

        // $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'time';
        // $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'time';
        $obj[0][$this->gridname]['columns'][$enddate]['style'] =  'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;text-align:right;';
        $obj[0][$this->gridname]['columns'][$startdate]['style'] =  'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;text-align:right;';

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:left;';

        if ($isposted) {
            $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$action]['style'] = 'display:none;';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from datesuspension where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
    }

    public function createtabbutton($config)
    {
        $tableid = $config['params']['tableid'];
        $tbuttons = ['saveallentry', 'addrecord'];
        $isposted = $this->othersClass->isposted2($tableid, 'hrisnum');
        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($isposted || $tableid == 0) {
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
        $qry = "select sus.trno,date(sus.startdate) as startdate,date(sus.enddate) as enddate,sus.line,sus.empid,'' as bgcolor from datesuspension as sus 
        where trno = ? order by line asc ";
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
        $startdate = null;
        $enddate = null;
        if ($trno == 0) {
            return ['status' => false, 'msg' => 'Please save header before adding details.'];
        }
        $date = $this->othersClass->getCurrentDate();

        if (!empty($row)) {

            foreach ($row as $key => $rows) {
                $data = [];
                foreach ($this->fields as $key2 => $value) {

                    if ($value == 'startdate') {
                        $f = new DateTime($row[$key][$value]);
                        $startdate  =  $f->format("Y-m-d");
                    }
                    if ($value == 'enddate') {
                        $t = new DateTime($row[$key][$value]);
                        $enddate  =  $t->format("Y-m-d");
                    }

                    $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$key][$value]);
                }
                $empid = $this->coreFunctions->datareader("select empid as value from disciplinary where trno= " . $trno . "");
                if ($empid == 0) {
                    return ['status' => false, 'msg' => 'Please add employee in header first.'];
                }
                $data['empid'] = $empid;
                $data['trno'] = $trno;
                $data['startdate'] = $startdate;
                $data['enddate'] = $enddate;

                if (isset($rows['bgcolor'])) {
                    if ($rows['bgcolor'] != '') {
                        $query = "select line from $this->detail where trno= " . $trno . " 
                        and (date(startdate) between '" . $startdate . "' and '" . $enddate . "' 
                        or 
                             date(enddate) between '" . $startdate . "' and '" . $enddate . "') ";
                        $date = $this->coreFunctions->opentable($query);

                        if (!empty($date)) {
                            $same_date = false;
                            foreach ($date as $key2 => $checkdate) {
                                if ($checkdate->line != $rows['line']) {
                                    $same_date = true;
                                }
                            }
                            if ($same_date) {
                                return ['status' => false, 'msg' => 'Suspension date overlaps with existing suspension for this employee.'];
                            }
                        }
                    }
                }

                if ($rows['line'] == 0) {
                    if (isset($rows['bgcolor'])) {
                        if ($rows['bgcolor'] != '') {
                            $qry = "select line as value from $table where trno= $trno order by line desc limit 1";
                            $line = $this->coreFunctions->datareader($qry);
                            if ($line == '') {
                                $line = 0;
                            }
                            $line = $line + 1;
                            $data['line'] = $line;
                            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                            $data['createby'] = $config['params']['user'];
                            $this->coreFunctions->sbcinsert($table, $data);
                            $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . 'SUSPENSION - DETAIL' . $startdate . ' to ' . $enddate);
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


        $returnrow = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => $returnrow];
    }
    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        if ($trno == 0) {
            return ['status' => false, 'msg' => 'Please save header before adding details.'];
        }
        foreach ($this->fields as $key => $value) {

            if ($value == 'startdate') {
                $f = new DateTime($row[$value]);
                $startdate  =  $f->format("Y-m-d");
            }
            if ($value == 'enddate') {
                $t = new DateTime($row[$value]);
                $enddate  =  $t->format("Y-m-d");
            }
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }


        $empid = $this->coreFunctions->datareader("select empid as value from disciplinary where trno= " . $row['trno'] . "");
        if ($empid == 0) {
            return ['status' => false, 'msg' => 'Please add employee in header first.'];
        }

        // $query_date = "select empid from disciplinary where trno= " . $trno . " 
        //                 and (date(startdate) between '" . $startdate . "' and '" . $enddate . "' 
        //                 or 
        //                      date(enddate) between '" . $startdate . "' and '" . $enddate . "')";
        // $check_date = $this->coreFunctions->opentable($query_date);


        if (isset($row['bgcolor'])) {
            if ($row['bgcolor'] != '') {
                $query = "select line from $this->detail where trno= " . $trno . " 
                        and (date(startdate) between '" . $startdate . "' and '" . $enddate . "' 
                        or 
                             date(enddate) between '" . $startdate . "' and '" . $enddate . "')";
                $date = $this->coreFunctions->opentable($query);

                if (!empty($date)) {
                    $same_date = false;
                    foreach ($date as $key2 => $checkdate) {
                        if ($checkdate->line != $row['line']) {
                            $same_date = true;
                        }
                    }
                    if ($same_date) {
                        return ['status' => false, 'msg' => 'Suspension date overlaps with existing suspension for this employee.'];
                    }
                }
            }
        }

        $data['empid'] = $empid;
        $data['trno'] = $trno;
        $data['startdate'] = $startdate;
        $data['enddate'] = $enddate;
        if ($row['line'] == 0) {
            $qry = "select line as value from $this->detail where trno= " . $row['trno'] . " order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->detail, $data);
            if ($insert != 0) {
                $returnrow = $this->loaddataperrecord($config, $line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . 'SUSPENSION - DETAIL' . $startdate . ' to ' . $enddate);
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

        // return ['status' => false, 'msg' => 'Suspension date overlaps to Start Date and End Date set to header.'];

    } //end function
    public function loaddataperrecord($config, $line)
    {
        $trno = $config['params']['tableid'];
        $query = "select sus.trno,sus.line,date(sus.startdate) as startdate,date(enddate) as enddate, sus.empid,'' as bgcolor from datesuspension as sus
        where sus.trno = $trno and line = $line order by line desc";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }
    public function add($config)
    {
        $data = [];
        $trno = $config['params']['tableid'];
        $data['trno'] = $trno;
        $data['line'] = 0;
        $data['startdate'] = $this->othersClass->getCurrentDate();
        $data['enddate'] = $this->othersClass->getCurrentDate();
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }
} //end class
