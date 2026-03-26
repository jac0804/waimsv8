<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class addquestion
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = 'QUESTION SETUP';
    public $gridname = 'inventory';
    private $fields = [];
    private $head = 'qnhead';
    private $stock = 'qnstock';

    public $style = 'width:100%;max-width:60%;';
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
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $objtype = 0;
        $qid = 0;
        $line = 0;

        if (isset($config['params']['addedparams'][0])) {
            $objtype = $config['params']['addedparams'][0];
        }

        if (isset($config['params']['row'])) {
            $objtype = $config['params']['row']['objtype'];
            $qid = $config['params']['row']['qid'];
            $line = $config['params']['row']['line'];
        }

        $col1 = [];

        switch ($objtype) {
            case 1:
                $this->modulename = 'QUESTION SETUP (Answer in words)';
                $fields = ['section', 'question', 'answord'];
                break;

            case 2:
                $this->modulename = 'QUESTION SETUP (Multiple Select)';
                $fields = ['section', 'question', ['lblchoice', 'lblanswer'], ['a', 'isa'], ['b', 'isb'], ['c', 'isc'], ['d', 'isd'], ['e', 'ise']];
                break;

            case 3:
                $this->modulename = 'QUESTION SETUP (Image Question)';
                $fields = ['section', 'question', 'picture', 'isa', 'isb',  'isc',  'isd', 'ise'];
                break;

            case 4:
                $this->modulename = 'QUESTION SETUP (Spelling)';
                $fields = ['section', ['lblchoice', 'lblanswer'], ['a', 'isa'], ['b', 'isb'], ['c', 'isc'], ['d', 'isd'], ['e', 'ise']];
                break;

            default:
                $this->modulename = 'QUESTION SETUP (Multiple Choice)';
                $fields = ['section', 'question', ['lblchoice', 'lblanswer'], ['a', 'isa'], ['b', 'isb'], ['c', 'isc'], ['d', 'isd'], ['e', 'ise']];
                break;
        }

        if ($qid != 0 && $line != 0) {
            array_push($fields, ['points', 'isinactive']);
        } else {
            array_push($fields, ['points']);
        }
        array_push($fields, 'refresh');

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'section.readonly', false);
        data_set($col1, 'refresh.label', 'SAVE QUESTION');

        if ($objtype == 3) {
            data_set($col1, 'question.type', 'input');
            data_set($col1, 'isa.label', 'A');
            data_set($col1, 'isb.label', 'B');
            data_set($col1, 'isc.label', 'C');
            data_set($col1, 'isd.label', 'D');
            data_set($col1, 'ise.label', 'E');
            data_set($col1, 'picture.addedparams', ['line']);
            data_set($col1, 'picture.table', 'qnstock');
            data_set($col1, 'picture.fieldid', 'qid');
            data_set($col1, 'picture.folder', 'questionnaires');
            data_set($col1, 'viewable', false);
        }
        if ($qid != 0 && $line != 0) {
            data_set($col1, 'isinactive.readonly', false);
        }

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $objtype = 0;
        if (isset($config['params']['addedparams'][0])) {
            $objtype = $config['params']['addedparams'][0];
        }

        $qid = $line = 0;
        if (isset($config['params']['row'])) {
            $qid = $config['params']['row']['qid'];
            $line = $config['params']['row']['line'];

            $data = $this->coreFunctions->opentable("select qid, line, section, question, a, b, c, d, e, ans, answord, points, '0' as isa, '0' as isb, '0' as isc, '0' as isd, '0' as ise, 
                objtype, picture, isinactive from qnstock where qid=? and line=?", [$qid, $line]);
            foreach ($data as $key => $value) {
                switch ($value->ans) {
                    case 'A':
                        $value->isa = "1";
                        break;
                    case 'B':
                        $value->isb = "1";
                        break;
                    case 'C':
                        $value->isc = "1";
                        break;
                    case 'D':
                        $value->isd = "1";
                        break;
                    case 'E':
                        $value->ise = "1";
                        break;
                }
                if ($value->isinactive == 0) {
                    $value->isinactive = "0";
                } else {
                    $value->isinactive = "1";
                }
            }
        } else {
            $data = $this->coreFunctions->opentable("select 0 as qid, 0 as line, '' as section, '' as question, '' as a, '' as b, '' as c, '' as d, '' as e, '' as ans, '' as answord, 
            1 as points, '0' as isa, '0' as isb, '0' as isc, '0' as isd, '0' as ise," . $objtype . " as objtype, '' as picture, '0' as isinactive");
        }
        return $data;
    }


    public function data($config)
    {
        return $this->getheaddata($config);
        // return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
        $status = true;
        $msg = '';
        $trno = $config['params']['clientid'];

        $row = $config['params']['dataparams'];


        if ($row['section'] == '') {
            return ['status' => false, 'msg' => 'Please assign section.'];
        }

        switch ($row['objtype']) {
            case 0:
            case 4:
                $answer = $this->checkans($row, true);
                break;
            case 2:
                $answer = $this->checkans($row, false);
                break;
            case 3:
                $row['a'] = $row['b'] = $row['c'] = $row['d'] = $row['e'] = 'x';
                $answer = $this->checkans($row, true);
                $row['a'] = $row['b'] =  $row['c'] = $row['d'] = $row['e'] = '';
                break;
        }

        $data = [
            'qid' => $trno,
            'line' => $row['line'],
            'section' => $row['section'],
            'question' => $row['question'],
            'points' => $row['points'],
            'objtype' => $row['objtype'],
            'isinactive' => $row['isinactive'],
            'a' => $row['a'],
            'b' => $row['b'],
            'c' => $row['c'],
            'd' => $row['d'],
            'e' => $row['e']
        ];

        if ($answer['status']) {
            $data['ans'] = $answer['ans'];
        } else {
            return $answer;
        }

        if ($data['line'] == 0) {
            $qry = "select line as value from " . $this->stock . " where qid=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno], '', true);
            $line =   $line + 1;
            $data['line'] =  $line;
            $data['sortline'] =  $line;

            $data['createdate'] =  $this->othersClass->getCurrentTimeStamp();
            $data['createby'] =  $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->stock, $data);
        } else {
            $data['editdate'] =  $this->othersClass->getCurrentTimeStamp();
            $data['editby'] =  $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->stock, $data, ['qid' => $trno, 'line' => $data['line']]);
        }

        // $qry = 'select qid, line, sortline, section, question, a, b, c, d, e, ans, answord, points from qnstock where qid=? order by section, sortline, line';
        // $stock = $this->coreFunctions->opentable($qry, [$trno]);
        // return ['status' => $status, 'msg' => $msg, 'data' => [], 'reloadgriddata' => ['inventory' => $stock]];

        return ['status' => $status, 'msg' => $msg, 'data' => [], 'reloadgriddata' => true];
    }

    private function checkans($row, $single)
    {
        $status = true;
        $msg = '';
        $ans = '';
        $count = 0;

        if ($row['isa'] == "0" && $row['isb'] == "0" && $row['isc'] == "0" && $row['isd'] == "0" && $row['ise'] == "0") {
            return ['status' => false, 'msg' => 'Please select one correct answer.1'];
        }

        if ($row['a'] != '') {
            if ($row['isa'] == "1") {
                ($ans == '') ? $ans = 'A' : $ans .= ',A';
                $count += 1;
            }
        }

        if ($row['b'] != '') {
            if ($row['isb'] == "1") {
                ($ans == '') ? $ans = 'B' : $ans .= ',B';
                $count += 1;
            }
        }

        if ($row['c'] != '') {
            if ($row['isc'] == "1") {
                ($ans == '') ? $ans = 'C' : $ans .= ',C';
                $count += 1;
            }
        }

        if ($row['d'] != '') {
            if ($row['isd'] == "1") {
                ($ans == '') ? $ans = 'D' : $ans .= ',D';
                $count += 1;
            }
        }

        if ($row['e'] != '') {
            if ($row['ise'] == "1") {
                ($ans == '') ? $ans = 'E' : $ans .= ',E';
                $count += 1;
            }
        }

        if ($count == 0) {
            return ['status' => false, 'msg' => 'Please select one correct answer.2'];
        }

        if ($single) {
            if ($count > 1) {
                return ['status' => false, 'msg' => 'Please select one correct answer.3'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'ans' => $ans, 'reloadtableentry' => true];
    }
}
