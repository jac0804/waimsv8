<?php

namespace App\Http\Classes\modules\actionlisting;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;
use DateTime;

class timein
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TIME IN';
    public $gridname = 'inventory';
    private $companysetup;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $table = 'timerec';
    public $tablelogs_del = 'payroll_log';
    public $style = 'width:100%;';
    private $coreFunctions;
    private $othersClass;
    private $fields = ['rem', 'isprefer'];
    public $showclosebtn = false;
    public $showfilteroption = true;
    public $showfilter = true;
    public $logger;

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
            'load' => 4646,
            'view' => 4647

        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['timeinout'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $fields = ['mode'];
        $col1 = $this->fieldClass->create($fields);
        $statrem_option = array(
            ['label' => 'TIME IN', 'value' => 'IN'],
            ['label' => 'BREAK OUT', 'value' => 'BO'],
            ['label' => 'BREAK IN', 'value' => 'BI'],
            ['label' => 'LUNCH OUT', 'value' => 'LO'],
            ['label' => 'LUNCH IN', 'value' => 'LI'],
            ['label' => 'TIME OUT', 'value' => 'OUT']
        );
        data_set($col1, 'mode.options', $statrem_option);
        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, "refresh.type", "actionbtn");
        data_set($col2, "refresh.label", "TIME IN");
        $gridheadinput = ['col1' => $col1, 'col2' => $col2];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns,
                'gridheadinput' => $gridheadinput
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$timeinout]['align'] = "left";

        return $obj;
    }

    public function gridheaddata($config)
    {
        $mode = '';
        if (isset($config['params']['gridheaddata']['mode']['label'])) {
            $mode = $config['params']['gridheaddata']['mode']['label'];
        } else {
            if (isset($config['params']['gridheaddata']['mode'])) {
                $mode = $config['params']['gridheaddata']['mode'];
            }
        }

        return $this->coreFunctions->opentable("select '" . $mode . "' as mode");
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $empid = $config['params']['adminid'];
        $timeinout = $this->othersClass->getCurrentTimeStamp();
        $dateid = strtotime($timeinout);

        if (isset($config['params']['gridheaddata']['mode'])) {
            if (isset($config['params']['gridheaddata']['mode']['label']) != '') {
                $dayn = $this->getDay($dateid);
                $mode = $config['params']['gridheaddata']['mode']['value'];
                $modelabel = $config['params']['gridheaddata']['mode']['label'];
                $datas = [
                    'mode' => $mode,
                    'timeinout' => $timeinout,
                    'userid' => $empid

                ];
                $msg = '';
                foreach ($datas as $key => $value2) {
                    $datas[$key] = $this->othersClass->sanitizekeyfield($key, $datas[$key]);
                }
                $dateid = strtotime($timeinout);
                $qry = "select timeinout,mode from timerec where userid = $empid and date(timeinout) = '" . date('Y-m-d', $dateid) . "' and mode = '$mode' limit 1";
                $data2 = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($data2), true);

                $secqry = "select  sd.schedin, sd.schedout, sd.breakin, sd.breakout
                   from tmshifts as tm left join shiftdetail as sd on sd.shiftsid = tm.line where sd.dayn =  $dayn limit 1";
                $secdata = $this->coreFunctions->opentable($secqry);
                $secresultdata =  json_decode(json_encode($secdata), true);

                // checking for breakin/breakout
                $bomode = $this->coreFunctions->getfieldvalue("timerec", "mode", "userid=? and date(timeinout) = ? and mode = 'BO'", [$empid, date('Y-m-d', $dateid)]);
                $botime = $this->coreFunctions->getfieldvalue("timerec", "timeinout", "userid=? and date(timeinout) = ? and mode = 'BO' order by line desc", [$empid, date('Y-m-d', $dateid)]);
                $countbo = $this->coreFunctions->datareader("select count(mode) as value from timerec where userid=? and date(timeinout) = ? and mode = 'BO'", [$empid, date('Y-m-d', $dateid)]);
                $countbi = $this->coreFunctions->datareader("select count(mode) as value from timerec where userid=? and date(timeinout) = ? and mode = 'BI'", [$empid, date('Y-m-d', $dateid)]);
                // checking for lunchin
                $lomode = $this->coreFunctions->getfieldvalue("timerec", "mode", "userid=? and date(timeinout) = ? and mode = 'LO'", [$empid, date('Y-m-d', $dateid)]);

                $sdtimeout = new DateTime($secresultdata[0]['breakout']); //lunch out
                $sdtimein = new DateTime($secresultdata[0]['breakin']); //lunch in
                $datatime = new DateTime($timeinout); //timenow
                $timebo = new DateTime($botime); //timenow

                $amorpm = $datatime->format('Y-m-d h:i A');
                if (!empty($resultdata[0]['mode'])) {
                    if (!empty($resultdata[0]['timeinout'])) {
                        $msg = 'You Have Already ';
                        switch ($resultdata[0]['mode']) {
                            case 'IN':
                            case 'OUT':
                            case 'LO':
                            case 'LI':
                                if ($resultdata[0]['mode'] == $mode) {
                                    return ['status' => false, 'msg' => $msg . $modelabel . ' Time: ' . $amorpm];
                                }
                                break;
                            case 'BO':
                                //checking for second BO
                                if ($bomode == 'BO') {
                                    if ($resultdata[0]['mode'] == $mode) {
                                        if ($datatime->format('H:i:s') < $sdtimein->format('H:i:s')) {
                                            return ['status' => false, 'msg' => 'Time is less than Lunch In ' . ' Time: ' . $amorpm];
                                        }
                                    }
                                    // check count BO 
                                    if ($countbo == 2) {
                                        $msg = "You don't have any ";
                                        if ($resultdata[0]['mode'] == $mode) {
                                            return ['status' => false, 'msg' => $msg . $modelabel . ' Time: ' . $amorpm];
                                        }
                                    }
                                }
                                break;
                            case 'BI':
                                //checking for second BI
                                if ($bomode == 'BO') {
                                    if ($resultdata[0]['mode'] == $mode) {
                                        if ($timebo->format('H:i:s') > $sdtimein->format('H:i:s')) {
                                            if ($datatime->format('H:i:s') < $sdtimein->format('H:i:s')) {
                                                return ['status' => false, 'msg' => 'Time is less than Lunch In ' . ' Time: ' . $amorpm];
                                            }
                                        } else {
                                            return ['status' => false, 'msg' => 'No Break Out'];
                                        }
                                    }
                                }
                                 // check count BI 
                                if ($countbi == 2) {
                                    $msg = "You don't have any ";
                                    if ($resultdata[0]['mode'] == $mode) {
                                        return ['status' => false, 'msg' => $msg . $modelabel . ' Time: ' . $amorpm];
                                    }
                                }
                                break;
                        }
                    }
                } else {
                    switch ($mode) {
                        case 'BI':
                            if ($bomode == "") {
                                if ($mode == 'BI') return ['status' => false, 'msg' => 'No Break Out '];
                            }
                            break;
                        case 'LI':
                            if ($lomode == "") {
                                if ($mode == 'LI') return ['status' => false, 'msg' => 'No Lunch Out'];
                            }
                            break;
                    }
                }
                $this->coreFunctions->sbcinsert($this->table, $datas);
            } else {
                $msg = 'Timemode is Empty';
                return ['status' => false, 'msg' => $msg];
            }
        }

        $qry = "select timeinout,mode from timerec where userid = $empid and date(timeinout) = '" . date('Y-m-d', $dateid) . "' order by line desc";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
    public function getDay($date)
    {
        $dow_numeric = date('N', strtotime($date));
        return $dow_numeric;
    }
} //end class
