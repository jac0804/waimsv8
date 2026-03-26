<?php

namespace App\Http\Classes\modules\waterbilling;

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

class stockcard
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'METER MASTER';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'item';
    public $prefix = 'IT';
    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';

    private $fields = [
        'barcode', 'projectid', 'shortname', 'itemrem', 'clientid', 'isnoninv', 'isinactive'
    ];

    private $except = ['itemid'];
    private $blnfields = ['isinactive'];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = true;

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
            'view' => 4105,
            'edit' => 4106,
            'new' => 4107,
            'save' => 4108,
            'delete' => 4110,
            'print' => 4111
        );

        return $attrib;
    }

    public function createdoclisting($config)
    {

        $getcols = ['action', 'barcode', 'listprojectname', 'shortname', 'listclientname', 'rem'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$barcode]['label'] = 'Meter No';
        $cols[$listprojectname]['label'] = 'Project';
        $cols[$shortname]['label'] = 'Addres';
        $cols[$listclientname]['label'] = 'Name';
        $cols[$rem]['label'] = 'Notes';

        $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listprojectname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$shortname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listclientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$rem]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        return $cols;
        break;
    }

    public function paramsdatalisting($config)
    {
        return [];
    }

    public function loaddoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        $addedfields = "";
        $condition  = "";
        $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
        $joins = "";
        $addparams = '';

        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";

        if (isset($config['params']['search'])) {
            $searchfield = ['pm.name', 'item.barcode', 'item.shortname', 'client.clientname', 'item.itemrem'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        // add others link masterfile
        $qry = "select item.itemid, item.shortname,  client.clientname, item.barcode, item.itemrem as rem, pm.name as projectname
        " . $addedfields . "
        from item
        left join projectmasterfile as pm on item.projectid = pm.line
        left join client on client.clientid = item.clientid
        " . $joins . "
        " . $condition . " " . $filtersearch . $addparams . "
        order by barcode " . $limit;


        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );


        if ($this->companysetup->getbarcodelength($config['params']) != 0) {
            array_push($btns, 'others');
        } else {
            switch ($config['params']['companyid']) {
                case 23: //labsol cebu
                case 41: //labsol manila
                case 52: //technolab
                    array_push($btns, 'others');
                    break;
            }
        }

        $buttons = $this->btnClass->create($btns);

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton


    public function createtab2($access, $config)
    {
        return [];
    }


    public function tabprice($config)
    {
    }

    public function createTab($config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        return [];
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $isserial = $this->companysetup->getserial($config['params']);
        $ispos =  $this->companysetup->getispos($config['params']);

        $fields = ['barcode', 'projectname', 'shortname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
        data_set($col1, 'barcode.required', true);
        data_set($col1, 'barcode.label', 'Meter No');
        data_set($col1, 'shortname.label', 'Address');
        data_set($col1, 'projectname.type', 'lookup');
        data_set($col1, 'projectname.action', 'lookupproject');
        data_set($col1, 'projectname.lookupclass', 'stockcardproject');

        $fields = ['clientname', 'rem', 'isinactive'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'clientname.class', 'csclientname  sbccsreadonly');

        $col3 = $col4 = [];

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newstockcard($config)
    {
        $companyid = $config['params']['companyid'];

        $data[0]['itemid'] = 0;
        $data[0]['barcode'] = $config['newbarcode'];
        $data[0]['shortname'] = '';
        $data[0]['itemrem'] = '';
        $data[0]['rem'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['clientid'] = 0;
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['isnoninv'] = 1;
        $data[0]['isinactive'] = '0';

        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $itemid = $config['params']['itemid'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $filter = '';


        if ($itemid == 0) {
            $itemid = $this->othersClass->readprofile($doc, $config);
            if ($itemid == 0) {
                $itemid = $this->coreFunctions->datareader("select itemid as value from item where isinactive=0 " . $filter . " order by itemid desc limit 1");
            }
            $config['params']['itemid'] = $itemid;
        } else {
            $this->othersClass->checkprofile($doc, $itemid, $config);
        }

        $center = $config['params']['center'];
        $head = [];
        $fields = 'item.itemid, item.barcode as docno';
        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',item.' . $value;
        }
        $qryselect = "select " . $fields . ", 
        ifnull(prj.name,'') as projectname,
        ifnull(cl.clientname,'') as clientname, item.itemrem as rem,
        item.barcode, item.isnoninv
        ";

        $qry = $qryselect . " from item
        left join client as cl on cl.clientid = item.clientid
        left join projectmasterfile as prj on prj.line = item.projectid
        where item.itemid = ? ";

        $head = $this->coreFunctions->opentable($qry, [$itemid]);
        if (!empty($head)) {
            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['itemid' => $itemid]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['itemid']];
        } else {
            $head[0]['itemid'] = 0;
            $head[0]['barcode'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $data = [];
        if ($isupdate) {
            unset($this->fields[0]);
        }
        $itemid = 0;
        $head['itemrem'] = $head['rem'];;
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {

            $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
            $itemid = $head['itemid'];
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            $itemid = $this->coreFunctions->insertGetId('item', $data);
            $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode']);
        }
        return $itemid;
    } // end function

    public function getlastbarcode($pref, $companyid = 0)
    {
        $length = strlen($pref);
        $return = '';
        $filter = '';

        if ($length == 0) {
            $return = $this->coreFunctions->datareader("select barcode as value from item where ''='' " . $filter . " order by barcode desc limit 1");
        } else {
            $return = $this->coreFunctions->datareader("select barcode as value from item where left(barcode,?)=?  " . $filter . " order by barcode desc limit 1", [$length, $pref]);
        }
        return $return;
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'uploadexceltemplate':
                $origdata = $config['params']['data'];
                $data = [];
                foreach ($origdata as $key => $value) {
                    $data[$key] = $value['serial'];
                }
                return ['status' => true, 'msg' => 'Success', 'data' => $data];
                break;
            case 'exportcsv':
                return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
                break;
            case 'readfile':
                $csv = $config['params']['csv'];
                $arrcsv = explode("\r\n", $csv);
                return ['status' => true, 'msg' => 'Readfile Successfully', 'data' => $arrcsv];
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
        }
    }

    public function deletetrans($config)
    {
        $itemid = $config['params']['itemid'];
        $doc = $config['params']['doc'];
        $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
        $qry = "select lastock.trno as value from lastock where itemid=?
            union all
            select glstock.trno from glstock where itemid=?
            union all
            select sostock.trno from sostock where itemid=?
            union all
            select hsostock.trno from hsostock where itemid=?
            union all
            select postock.trno from postock where itemid=?
            union all
            select hpostock.trno from hpostock where itemid=?
             limit 1";
        $count = $this->coreFunctions->datareader($qry, [$itemid, $itemid, $itemid, $itemid, $itemid, $itemid]);
        if (($count != '')) {
            return ['itemid' => $itemid, 'status' => false, 'msg' => 'Already have transaction...'];
        }
        $companyid = $config['params']['companyid'];
        $qry = "select itemid as value from item where itemid<? and isinactive=0 order by itemid desc limit 1 ";

        $itemid2 = $this->coreFunctions->datareader($qry, [$itemid]);
        $this->coreFunctions->execqry('delete from item where itemid=?', 'delete', [$itemid]);
        $this->coreFunctions->execqry('delete from uom where itemid=?', 'delete', [$itemid]);
        $this->coreFunctions->execqry('delete from component where itemid=?', 'delete', [$itemid]);
        $this->coreFunctions->execqry('delete from itemlevel where itemid=?', 'delete', [$itemid]);
        $this->coreFunctions->execqry('delete from pricebracket where itemid=?', 'delete', [$itemid]);
        $this->logger->sbcdel_log($itemid, $config, $barcode);
        return ['itemid' => $itemid2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function openqry($config)
    {
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $filter = '';

        $center = $config['params']['center'];
        $head = [];
        $fields = 'item.itemid, item.barcode as docno';
        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',item.' . $value;
        }
        $qryselect = "select " . $fields . ", ifnull(pmaster.part_name,'') as partname, item.part as partid,
        ifnull(mmaster.model_name,'') as modelname, item.model as model,
        ifnull(itemclass.cl_name,'') as classname,item.class as class,
        ifnull(brand.brand_desc,'') as brandname, ifnull(item.brand,'') as brand,
        ifnull(stockgrp.stockgrp_name,'') as stockgrp, item.groupid as groupid, item.groupid as grid,
        cat.line as category,
        cat.name as categoryname,
        subcat.line as subcat,
        subcat.name as subcatname,
        ifnull(coa1.acnoname,'')  as assetname,
        ifnull(coa2.acnoname,'')  as liabilityname,
        ifnull(coa3.acnoname,'')  as revenuename,
        ifnull(coa4.acnoname,'')  as expensename,
        ifnull(coa5.acnoname,'')  as salesreturnname,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname,
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging,
        ifnull(prj.code, '') as projectcode,
        ifnull(prj.name, '') as projectname,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue,
        '' as dsalesreturn,
        ifnull(dept.clientname,'') as deptname,
        item.linkdept";

        $qry = $qryselect . " from item
        left join part_masterfile as pmaster on pmaster.part_id = item.part
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join item_class as itemclass on itemclass.cl_id = item.class
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join coa as coa1 on coa1.acno = item.asset
        left join coa as coa2 on coa2.acno = item.liability
        left join coa as coa3 on coa3.acno = item.revenue
        left join coa as coa4 on coa4.acno = item.expense
        left join coa as coa5 on coa5.acno = item.salesreturn
        left join client as cl on cl.clientid = item.supplier
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join projectmasterfile as prj on prj.line = item.projectid
        left join client as dept on dept.clientid=item.linkdept
        limit 1";
        return $qry;
    }

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
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
