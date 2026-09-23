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

use Carbon\Carbon;


class trucking
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Delivery Trucking Details';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';
    private $table = 'particulars';
    private $htable = 'hparticulars';
    private $othersClass;
    public $style = 'width:100%;max-width:70%;';
    private $fields = [];
    public $showclosebtn = true;


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

        $columns = ['action', 'rem', 'amt'];

        foreach ($columns as $key => $value) {
            $$value = $key; //declare
        }

        $isposted = $this->isposted($config);
        $stockbuttons = $isposted ? [] : ['save', 'delete'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Breakdown Of Expenses';
        $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog']; // tab button

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        if ($this->isposted($config)) {
            $obj[$addrecord]['visible'] = false;
            $obj[$saveallentry]['visible'] = false;
            $obj[$whlog]['visible'] = false;
        }

        return $obj;
    }

    public function selectqry($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "select tdtrno as trno, tdline as line, rem, format(ifnull(amount,0),2) as amt, '' as bgcolor
                from " . $this->table . " where tdtrno = ? order by tdline";
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['rem'] = '';
        $data['amt'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function loaddata($config)
    {
        $qry = $this->selectqry($config);
        $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
        return $data;
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];

        $rem = $row['rem'];
        $amt = str_replace(',', '', $row['amt']);

        if ((int) $row['line'] === 0) {
            $nextline = $this->coreFunctions->datareader(
                "select ifnull(max(tdline),0) as value from " . $this->table . " where tdtrno=?",
                [$trno]
            );
            $nextline = ($nextline == '' || $nextline === null) ? 0 : (int) $nextline;
            $nextline++;
            $line = $nextline;

            $data = [
                'trno' => $this->getnextrunningtrno(),
                'line' => 1,
                'tdtrno' => $trno,
                'tdline' => $line,
                'rem' => $rem,
                'amount' => $amt,
                'createby' => $config['params']['user'],
                'createdate' => $this->othersClass->getCurrentTimeStamp()
            ];
            $update = $this->coreFunctions->sbcinsert($this->table, $data);
            $logmsg = 'ADD - line:' . $line . ' rem:' . $rem . ' amt:' . $amt;
        } else {
            $line = $row['line'];
            $data = [
                'rem' => $rem,
                'amount' => $amt
            ];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['tdtrno' => $trno, 'tdline' => $line]);
            $logmsg = 'UPDATE - line:' . $line . ' rem:' . $rem . ' amt:' . $amt;
        }

        if ($update) {
            $this->logger->sbcwritelog($trno, $config, 'DELIVERYTRUCKING', $logmsg);
            $returnrow = $this->openstockline($trno, [$line]);
            return ['row' => $returnrow, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['status' => false, 'msg' => 'Update failed.'];
        }
    }

    private function openstockline($trno, $lines = [])
    {
        $qry = "select tdtrno as trno, tdline as line, rem, format(ifnull(amount,0),2) as amt, '' as bgcolor
                from " . $this->table . " where tdtrno = ?";
        $params = [$trno];

        if (!empty($lines)) {
            $placeholders = implode(',', array_fill(0, count($lines), '?'));
            $qry .= " and tdline in ($placeholders)";
            $params = array_merge($params, $lines);
        }

        $qry .= " order by tdline";

        return $this->coreFunctions->opentable($qry, $params);
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $line = $row['line'];

        $qry = "delete from " . $this->table . " where tdtrno=? and tdline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

        $config['params']['doc'] = 'DELIVERYTRUCKING';
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE TRUCKING EXPENSE - trno:' . $trno . ' line:' . $line);

        $config['params']['tableid'] = $trno;
        $data = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => $data];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];

        $this->coreFunctions->execqry('delete from ' . $this->table . ' where tdtrno=?', 'delete', [$trno]);

        $config['params']['doc'] = 'DELIVERYTRUCKING';
        $this->logger->sbcdelmaster_log($trno, $config, 'DELETED ALL TRUCKING EXPENSES');

        $data = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => $data, 'reloaddata' => true];
    }

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $rows = $config['params']['data'];

        $nextline = $this->coreFunctions->datareader(
            "select ifnull(max(tdline),0) as value from " . $this->table . " where tdtrno=?",
            [$trno]
        );
        $nextline = ($nextline == '' || $nextline === null) ? 0 : (int) $nextline;

        $saved = 0;
        foreach ($rows as $row) {
            if (!isset($row['bgcolor']) || $row['bgcolor'] == '') {
                continue;
            }

            $rem = $row['rem'];
            $amt = str_replace(',', '', $row['amt']);

            if ((int) $row['line'] === 0) {
                $nextline++;
                $data = [
                    'trno' => $this->getnextrunningtrno(),
                    'line' => 1,
                    'tdtrno' => $trno,
                    'tdline' => $nextline,
                    'rem' => $rem,
                    'amount' => $amt,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp()
                ];
                $this->coreFunctions->sbcinsert($this->table, $data);
                $this->logger->sbcwritelog($trno, $config, 'DELIVERYTRUCKING', 'ADD - line:' . $nextline . ' rem:' . $rem . ' amt:' . $amt);
            } else {
                $data = [
                    'rem' => $rem,
                    'amount' => $amt
                ];
                $this->coreFunctions->sbcupdate($this->table, $data, ['tdtrno' => $trno, 'tdline' => $row['line']]);
                $this->logger->sbcwritelog($trno, $config, 'DELIVERYTRUCKING', 'UPDATE - line:' . $row['line'] . ' rem:' . $rem . ' amt:' . $amt);
            }
            $saved++;
        }

        if ($saved == 0) {
            return ['status' => false, 'msg' => 'No edited items to save.'];
        }

        $config['params']['tableid'] = $trno;
        $data = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data, 'reloaddata' => true];
    }

    public function lookuplogs($config)
    {
        $doc = 'DELIVERYTRUCKING';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Trucking Expense Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $trno = $config['params']['tableid'];
        $qry = "
        select trno, doc, task, log.user, dateid,
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = ? and log.trno = ?
        order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $trno]);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    private function isposted($config)
    {
        $trno = $config['params']['tableid'];
        $postdate = $this->coreFunctions->datareader("select postdate as value from transnum where trno = ?", [$trno]);
        return ($postdate != null && $postdate != '');
    }

    private function getnextrunningtrno()
    {
        $next = $this->coreFunctions->datareader(
            "select ifnull(max(trno),0)+1 as value from " . $this->table
        );
        return ($next == '' || $next === null) ? 1 : (int) $next;
    }
}
