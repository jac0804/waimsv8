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

class entryfirearms
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'FIREARMS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'ddfirearms';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['serialno',  'licenseno', 'expiry'];
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
        $columns = ['action', 'code', 'shortname', 'serialno', 'licenseno', 'expiry'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $isposted = $this->isposted($config);
        $stockbuttons = $isposted ? [] : ['delete']; 

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createTab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";

        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Make';
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$shortname]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$shortname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$shortname]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$serialno]['label'] = 'Serial No.';
        $obj[0][$this->gridname]['columns'][$serialno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$serialno]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$licenseno]['label'] = 'License No.';
        $obj[0][$this->gridname]['columns'][$licenseno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$licenseno]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$expiry]['label'] = 'Expiry';
        $obj[0][$this->gridname]['columns'][$expiry]['style'] = "width:180px;whiteSpace: normal;min-width:180px;";
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$expiry]['readonly'] = true;

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'deleteallitem', 'whlog'];

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[$addrecord]['label'] = "ADD";
        $obj[$deleteallitem]['confirm'] = true;
        $obj[$deleteallitem]['confirmlabel'] = 'Do you want to delete all Firearms?';
        $obj[$addrecord]['lookupclass'] = "addfirearms";
        $obj[$addrecord]['action'] = "lookupsetup";
        $obj[$deleteallitem]['label'] = 'Delete all';
        $obj[$deleteallitem]['lookupclass'] = 'loaddata';

        if ($this->isposted($config)) {
            $obj[$addrecord]['visible'] = false;   
            $obj[$deleteallitem]['visible'] = false;
        }

        return $obj;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addfirearms':
                return $this->addfirearms($config);
                break;
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under Firearms'];
                break;
        }
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $params = [$trno];
        $filtersearch = " where dd.trno = ?";
        $searchfield  = ['f.make', 'f.type', 'f.serialno'];

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                $filtersearch .= " and " . $sfield . " like ?";
                $params[] = '%' . $search . '%';
            }
        }

        $qry = "select dd.trno, dd.fireid, dd.rate, f.make as code, f.type as shortname, date(f.expiry) as expiry, f.serialno, f.licenseno, '' as bgcolor
    from ddfirearms as dd
    left join firearms as f on f.line = dd.fireid
    " . $filtersearch . "
    union all
    select dd.trno, dd.fireid, dd.rate, f.make as code, f.type as shortname, date(f.expiry) as expiry, f.serialno, f.licenseno, '' as bgcolor
    from hddfirearms as dd
    left join firearms as f on f.line = dd.fireid
    " . str_replace('dd.trno', 'dd.trno', $filtersearch) . "
    order by fireid";

        $params = array_merge([$trno], array_slice($params, 1), [$trno], array_slice($params, 1));

        $data = $this->coreFunctions->opentable($qry, $params);

        return $data;
    }

    public function add($config)
    {
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['fireid'] = 0;
        $data['rate'] = 0;
        $data['code'] = '';
        $data['make'] = '';
        $data['type'] = '';
        $data['expiry'] = '';
        $data['serialno'] = '';
        $data['licenseno'] = '';
        return $data;
    }

    public function addfirearms($config)
    {
        $trno = $config['params']['tableid'];

        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'line',
            'title' => 'List of Fire Arms',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addfirearms'
        );

        $cols = [
            ['name' => 'expiry', 'label' => 'Expiry', 'align' => 'left', 'field' => 'expiry', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'make', 'label' => 'Make', 'align' => 'left', 'field' => 'make', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select line, code, make, type, serialno, licenseno, cal, expiry
        from firearms
        where line not in (select fireid from ddfirearms where trno = ?)
        order by code";

        $data = $this->coreFunctions->opentable($qry, [$trno]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $rows = $config['params']['rows'];
        $trno = $config['params']['tableid'];
        $returndata = [];
        $errors = [];

        foreach ($rows as $key => $value) {
            $fireid = $rows[$key]['line'];

            $exists = $this->coreFunctions->datareader("select trno as value from ddfirearms where trno=? and fireid=? limit 1", [$trno, $fireid]);
            if ($exists != '') {
                $errors[] = $rows[$key]['shortname'];
                continue;
            }

            $data = [
                'trno' => $trno,
                'fireid' => $fireid,
                'rate' => 0,
                'createby' => $config['params']['user'],
                'createdate' => $this->othersClass->getCurrentTimeStamp()
            ];

            if ($this->coreFunctions->sbcinsert('ddfirearms', $data) == 1) {
                $config['params']['doc'] = 'ENTRYFIREARMS';
                $this->logger->sbcmasterlog($trno, $config, ' ADD - fireid:' . $fireid . ' Type:' . $rows[$key]['type']);
                $returnrow = $this->loaddataperrecord($fireid, $trno);
                array_push($returndata, $returnrow[0]);
            }
        }

        $status = count($returndata) > 0;
        $msg = $status ? 'Successfully added.' : 'No firearms were saved.';

        if (!empty($errors)) {
            $msg .= ' ' . implode(', ', $errors) . ' already added.';
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    }

    private function loaddataperrecord($fireid, $trno)
    {
        $qry = "select dd.trno, dd.fireid, dd.rate, f.make as code, f.type as shortname, date(f.expiry) as expiry, f.serialno, f.licenseno, '' as bgcolor
    from ddfirearms as dd
    left join firearms as f on f.line = dd.fireid
    where dd.trno = ? and dd.fireid = ?
    union all
    select dd.trno, dd.fireid, dd.rate, f.make as code, f.type as shortname, date(f.expiry) as expiry, f.serialno, f.licenseno, '' as bgcolor
    from hddfirearms as dd
    left join firearms as f on f.line = dd.fireid
    where dd.trno = ? and dd.fireid = ?";
        return $this->coreFunctions->opentable($qry, [$trno, $fireid, $trno, $fireid]);
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $fireid = $row['fireid'];

        $qry = "delete from ddfirearms where trno=? and fireid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $fireid]);

        $config['params']['doc'] = 'ENTRYFIREARMS';
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE FIREARM - fireid:' . $fireid . ' Type:' . $row['shortname']);

        $config['params']['tableid'] = $trno;
        $data = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => $data];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];

        $this->coreFunctions->execqry('delete from ddfirearms where trno=?', 'delete', [$trno]);

        $config['params']['doc'] = 'ENTRYFIREARMS';
        $this->logger->sbcdelmaster_log($trno, $config, 'DELETED ALL FIREARMS');

        $data = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => $data, 'reloaddata' => true];
    }

    public function lookuplogs($config)
    {
        $doc = 'ENTRYFIREARMS';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Firearms Logs',
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
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    private function isposted($config)
    {
        $trno = $config['params']['tableid'];
        $postdate = $this->coreFunctions->datareader("select postdate as value from hrisnum where trno = ?", [$trno]);
        return ($postdate != null && $postdate != '');
    }
}
