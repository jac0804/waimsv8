<?php

namespace App\Http\Classes\modules\autoserv;

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

class carmakesetup
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CAR MAKE SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'cmake';
    public $detail = 'cmodel';
    public $prefix = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';
    private $stockselect;
    private $tablenum;

    private $fields = [
        'carname',
        'picture',
    ];

    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = true;
    private $reporter;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5836,
            'view' => 5837,
            'edit' => 5838,
            'new' => 5839,
            'save' => 5840,
            'delete' => 5841,
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['name', 'client'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'name.label', 'Car Make');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'client.label', '');
        data_set($col1, 'client.required', false);

        $fields = ['picture'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'picture.lookupclass', 'client');
        data_set($col2, 'picture.folder', 'carmakesetup');
        data_set($col2, 'picture.fieldid', 'id');
        data_set($col2, 'picture.table', 'cmake');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $tab = [];

        $tab = [
            'tableentry' => ['action' => 'autoserventry', 'lookupclass' => 'entrycarmodel', 'label' => 'CAR MODEL']
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {

        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }
    public function createdoclisting($config)
    {
        $getcols = ['action', 'name', 'jobtitle'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[2]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
        $cols[2]['type'] = 'hidden';
        $cols[2]['label'] = '';
        $cols[1]['align'] = 'text-left';
        $cols[1]['label'] = 'Car Make';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['id', 'carname'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $qry = "select id as clientid, carname as name from cmake 
        where 1=1 " . $filtersearch . "
        order by id";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function loadheaddata($config)
    {
        $clientid = $this->othersClass->val($config['params']['clientid']);

        $fields = "id as clientid, carname as name, carname as client, picture";

        $qry = "select " . $fields . "
                from cmake
                where id = ?";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);

        if (!empty($head)) {
            $stock = $this->openstock($clientid, $config);
            $msg = isset($config['msg']) ? $config['msg'] : 'Data Fetched Success';

            return ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'griddata' => ['cmodel' => $stock]];
        } else {
            return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $this->resetdata(), 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function openstock($id, $config)
    {
        $qry = 'select line, carid, year, model, type, sub_model, other_info, "" as bgcolor 
            from cmodel 
            where carid = ?';

        return $this->coreFunctions->opentable($qry, [$id]);
    }

    private function resetdata($carname = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['name'] = $carname;
        $data[0]['client'] = $carname;
        $data[0]['picture'] = '';

        return $data;
    }

    public function getlastclient($pref = '')
    {
        $length = strlen($pref);
        if ($length == 0) {
            $last_id = $this->coreFunctions->datareader(
                "select carname as value from " . $this->head . " order by id desc limit 1"
            );
        } else {
            $last_id = $this->coreFunctions->datareader(
                "select carname as value from " . $this->head . " where left(carname, ?) = ? order by id desc limit 1",
                [$length, $pref]
            );
        }
        return $last_id;
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        $clientid = 0;
        $msg = '';

        if (isset($head['name'])) {
            $head['carname'] = $head['name'];
        }

        foreach ($this->fields as $key) {
            if (isset($head[$key])) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                }
            }
        }

        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['id' => $head['clientid']]);
            $clientid = $head['clientid'];
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        }

        $stock = $this->openstock($clientid, $config);
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['cmodel' => $stock]];
    }

    public function newclient($config)
    {
        $data = $this->resetdata();
        return ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Car Make'];
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];

        $qry1 = "select carid as value from cmodel where carid=? limit 1";
        $count = $this->coreFunctions->datareader($qry1, [$clientid]);

        if ($count != '') {
            return ['clientid' => $clientid, 'status' => false, 'msg' => 'Car Make already has Car Models attached...'];
        }

        $this->coreFunctions->execqry('delete from ' . $this->head . ' where id=?', 'delete', [$clientid]);

        $this->logger->sbcmasterlog($clientid, $config, 'DELETE CAR MAKE - ID: ' . $clientid);

        return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } // end function
}