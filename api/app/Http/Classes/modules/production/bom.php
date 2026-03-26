<?php

namespace App\Http\Classes\modules\production;

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

class bom
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'FINISHED GOODS - BOM';
    public $gridname = 'inventory';
    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $showfilter = false;
    public $issearchshow = false;
    public $showclosebtn = false;
    public $showfilteroption = false;
    public $showcreatebtn = false;
    private $head = 'bom';
    private $fields = ['itemid', 'dateid', 'bclientid', 'bclientname', 'uom2', 'batchsize', 'yield', 'rem'];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
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
            'view' => 3815,
            'new' => 3815,
            'edit' => 3816,
            'print' => 3816,
            'save' => 3817,
            'saveallentry' => 3817
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'save',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createdoclisting($config)
    {
        $getcols = ['action', 'barcode', 'itemname', 'uom'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['itemid', 'barcode', 'itemname', 'uom'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }
        $qry = "select itemid, itemid as clientid, barcode as client, barcode, itemname, uom from item where fg_isfinishedgood=1 $filtersearch";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadField($config)
    {
        $fields = ['client', 'itemname', 'bclient', 'bclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.name', 'barcode');
        data_set($col1, 'client.label', 'Barcode');
        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.lookupclass', 'lookupbarcode');
        data_set($col1, 'client.action', 'lookupbarcode');
        data_set($col1, 'client.readonly', false);
        data_set($col1, 'bclient.lookupclass', 'bomclient');

        $fields = [['dateid', 'uom'], 'batchsize', 'uom2', 'yield'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'uom.readonly', true); //
        data_set($col2, 'uom.class', 'csuom2 sbccsreadonly');
        data_set($col2, 'uom2.action', 'lookupuom');
        data_set($col2, 'uom2.lookupclass', 'bomuom');


        if ($config['params']['companyid'] == 36) { //rozlab
            data_set($col2, 'batchsize.label', 'Size');
        } else {
            data_set($col2, 'batchsize.label', 'Batch Size');
        }


        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);

        $fields = [['lbltotal', 'ext'], ['lblcostuom', 'costuom'], ['lblearned', 'qty'], ['lbltotalkg', 'tqty']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'ext.label', '');
        data_set($col4, 'costuom.label', '');
        data_set($col4, 'qty.label', '');
        data_set($col4, 'tqty.label', '');
        data_set($col4, 'lbltotal.label', 'Grand total');
        data_set($col4, 'lblearned.label', 'Total Quantity');
        data_set($col4, 'ext.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'costuom.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'tqty.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'lbltotal.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'lblearned.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'lblcostuom.style', 'font-weight:bold;font-size:20px;');
        data_set($col4, 'lbltotalkg.style', 'font-weight:bold;font-size:20px;');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createTab($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycomponent', 'label' => 'INVENTORY']];
        // $tab = [];
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

    public function getlastclient($pref)
    {
        return '';
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['itemid'] = 0;
        $data[0]['barcode'] = '';
        $data[0]['itemname'] = '';
        $data[0]['uom'] = '';
        return $data;
    }

    public function loadheaddata($config)
    {
        $companyid = $config['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

        $itemid = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['itemid'];

        $costuomdiv = "ifnull(bom.batchsize,0)";
        if ($companyid == 36) { //rozlab
            $costuomdiv = "ifnull(bom.yield,0)";
        }

        $qry =  " select i.itemid, i.itemid as clientid, i.itemid as trno, i.barcode as client, i.barcode as docno, i.barcode, i.itemname, i.uom, ifnull(bom.dateid,'" . $this->othersClass->getCurrentDate() . "') as dateid,
            ifnull(client.client,'') as bclient, ifnull(bom.bclientname,'') as bclientname, format(ifnull(bom.batchsize,0)," . $decimalprice . ") as batchsize, format(ifnull(bom.yield,0)," . $decimalprice . ") as yield, ifnull(bom.rem,'') as rem, ifnull(bom.uom2,'') as uom2,
            FORMAT(ifnull((select sum(isqty * cost) from component where itemid=i.itemid),0),2) as ext, 
            FORMAT(ifnull((select sum(isqty * cost)/" . $costuomdiv . " from component where itemid=i.itemid),0),6) as costuom, 
            FORMAT(ifnull((select sum(isqty) from component where itemid=i.itemid),0)," . $decimalqty . ") as qty, 
            FORMAT(ifnull((select sum(isqty) from component where itemid=i.itemid and left(uom,1) in ('K','L')),0)," . $decimalqty . ") as tqty
            from item as i left join bom on bom.itemid=i.itemid left join client on client.clientid=bom.bclientid where i.itemid=? ";

        $head = $this->coreFunctions->opentable($qry, [$itemid]);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $itemid, 'reloadtableentry' => true];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];

        $data = [];

        $clientid = $config['params']['head']['clientid'];
        $msg = "";

        if ($head['dateid'] == 'Invalid date') {
            return ['status' => false, 'msg' => 'Invalid date', 'clientid' => $clientid];
        };

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            }
        }

        $data['editby'] = $config['params']['user'];
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

        $exists = $this->coreFunctions->getfieldvalue($this->head, "itemid", "itemid=?", [$clientid]);
        if ($exists == '') {
            $exists = 0;
        }
        if ($exists == 0) {
            $this->coreFunctions->insertGetId($this->head, $data);
        } else {
            $this->coreFunctions->sbcupdate($this->head, $data, ['itemid' => $clientid]);
        }

        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        // auto lock
        $date = date("Y-m-d H:i:s");
        $user = $config['params']['user'];
        $trno = $config['params']['dataid'];
        $this->coreFunctions->sbcupdate($this->head, ['lockdate' => $date, 'lockuser' => $user], ['trno' => $trno]);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
