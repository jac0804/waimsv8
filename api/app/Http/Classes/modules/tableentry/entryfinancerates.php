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
use App\Http\Classes\reportheader;
use App\Http\Classes\sbcscript\sbcscript;
use DateTime;

class entryfinancerates
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Finance Rates';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'mcfinancerate';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['terms', 'dp', 'interest', 'factor', 'penalty', 'miscfee', 'rebate'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;

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
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action', 'terms', 'dp', 'interest', 'factor', 'penalty', 'miscfee', 'rebate'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'delete'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createTab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$terms]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$terms]['lookupclass'] = 'financeterms';
        $obj[0][$this->gridname]['columns'][$terms]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$terms]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rebate]['label'] = 'Rebate';

        $obj[0][$this->gridname]['columns'][$dp]['align'] = "left";

        $obj[0][$this->gridname]['columns'][$interest]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$interest]['style'] = "width:20px;whiteSpace: normal;min-width:20px; max-width: 20px;";
        $obj[0][$this->gridname]['columns'][$interest]['align'] = "left";

        $obj[0][$this->gridname]['columns'][$factor]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$factor]['align'] = "left";

        $obj[0][$this->gridname]['columns'][$penalty]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$penalty]['style'] = "width:20px;whiteSpace: normal;min-width:20px; max-width: 20px;";
        $obj[0][$this->gridname]['columns'][$penalty]['align'] = "left";

        $obj[0][$this->gridname]['columns'][$miscfee]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$miscfee]['align'] = "left";

        $obj[0][$this->gridname]['columns'][$rebate]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $itemid = $config['params']['tableid'];
        $params = [$itemid];
        $filtersearch = " where itemid = ?";
        $searchfield  = $this->fields;

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                $filtersearch .= " and " . $sfield . " like ?";
                $params[] = '%' . $search . '%';
            }
        }

        $select = $this->selectqry() . ", '' as bgcolor";
        $qry    = "select " . $select . " from " . $this->table . $filtersearch . " order by line";

        $data = $this->coreFunctions->opentable($qry, $params);

        return $data;
    }

    private function selectqry()
    {
        $qry = "line, itemid, terms, format(dp,2) as dp, format(interest, 2) as interest, factor, penalty, format(miscfee,2) as miscfee, format(rebate, 2) as rebate";
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['itemid'] = $config['params']['tableid'];
        $data['terms'] = '';
        $data['dp'] = 0;
        $data['interest'] = 0;
        $data['factor'] = 0;
        $data['penalty'] = 0;
        $data['miscfee'] = 0;
        $data['rebate'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function isTermsDuplicate($itemid, $terms, $excludeLine)
    {
        $existing = $this->coreFunctions->datareader(
            "select line as value from " . $this->table . " where itemid = ? and terms = ? and line != ?",
            [$itemid, $terms, $excludeLine],
            '',
            true
        );
        return $existing != '';
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        $itemid = $config['params']['tableid'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, []);

        $fieldsToSave = $this->fields;


        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($fieldsToSave as $key2 => $value2) {
                    $val = $data[$key][$value2];
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $val, $lookups);
                }

                $data2['itemid'] = $itemid;
                $line = $data[$key]['line'];

                if (empty(trim($data2['terms']))) {
                    return ['status' => false, 'msg' => 'Saving failed. Please select terms.'];
                }

                if ($this->isTermsDuplicate($itemid, $data2['terms'], $line)) {
                    return ['status' => false, 'msg' => 'Saving failed. Terms "' . $data2['terms'] . '" is already used.'];
                }

                if ($line == 0) {
                    $data2['encodedby'] = $config['params']['user'];
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $newline = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = 'ENTRYFINANCERATES';
                    $this->logger->sbcmasterlog($newline, $config, ' CREATE - '
                        . 'Terms -' . $data2['terms']
                        . ' | DP -' . $data2['dp']
                        . ' | Interest -' . $data2['interest']
                        . ' | Factor -' . $data2['factor']
                        . ' | Penalty -' . $data2['penalty']
                        . ' | MiscFee -' . $data2['miscfee']
                        . ' | Rebate -' . $data2['rebate']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $line]);
                    $config['params']['doc'] = 'ENTRYFINANCERATES';
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $itemid = $config['params']['tableid'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, []);

        $doc = 'ENTRYFINANCERATES';

        $fieldsToSave = $this->fields;

        $data = [];
        foreach ($fieldsToSave as $key2 => $value) {
            $val = $row[$value];
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $val, $lookups);
        }

        $data['itemid'] = $itemid;

        if (empty(trim($data['terms']))) {
            return ['status' => false, 'msg' => 'Saving failed. Please select terms.'];
        }

        if ($this->isTermsDuplicate($itemid, $data['terms'], $row['line'])) {
            return ['status' => false, 'msg' => 'Saving failed. Terms "' . $data['terms'] . '" is already used.'];
        }



        if ($row['line'] == 0) {
            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $config['params']['doc'] = 'ENTRYFINANCERATES';
                $this->logger->sbcmasterlog($line, $config, ' CREATE - '
                    . 'Terms -' . $data['terms']
                    . ' | DP -' . $data['dp']
                    . ' | Interest -' . $data['interest']
                    . ' | Factor -' . $data['factor']
                    . ' | Penalty -' . $data['penalty']
                    . ' | MiscFee -' . $data['miscfee']
                    . ' | Rebate -' . $data['rebate']);
                $returnrow = $this->loaddataperrecord($line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
            if ($update) {
                $returnrow = $this->loaddataperrecord($row['line']);
                $config['params']['doc'] = 'ENTRYFINANCERATES';
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    } // end function

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $data = $this->loaddataperrecord($row['line']);

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

        $config['params']['doc'] = 'ENTRYFINANCERATES';
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE LINE: ' . $row['line'] . ' - ' . $row['terms']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'financeterms':
                return $this->lookupfinanceterms($config);
                break;

            case 'whlog':
                return $this->lookuplogs($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under Finance Rates'];
                break;
        }
    }

    public function lookupfinanceterms($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'terms',
            'title' => 'List of Finance Terms',
            'style' => 'width:800px;max-width:800px;'
        );
        $plotting = array('terms' => 'terms');
        $plotsetup = array(
            'plottype' => 'plotgrid',
            'plotting' => $plotting,
        );

        // lookup columns
        $cols = [
            ['name' => 'terms', 'label' => 'Terms', 'align' => 'left', 'field' => 'terms', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $qry = "select '' as terms, 0 as days, 0 as line
                union all
                select terms, days, line from terms order by line";
        $data = $this->coreFunctions->opentable($qry);

        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
    }

    public function lookuplogs($config)
    {
        $doc = 'ENTRYFINANCERATES';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
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
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
}
