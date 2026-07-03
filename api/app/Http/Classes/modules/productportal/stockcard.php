<?php

namespace App\Http\Classes\modules\productportal;

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
use App\Http\Classes\lookup\productportallookup;
use PDO;

class stockcard
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCKCARD';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'item';
    public $prefix = 'IT';
    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';
    private $stockselect;
    private $productportallookup;

    private $fields = ['barcode', 'picture', 'itemname', 'model', 'brand', 'category', 'sizeid', 'partno', 'othcode', 'disc', 'itemrem', 'amt', 'carid', 'uom'];
    private $iteminfo = ['fyear', 'kind', 'positionid'];

    private $except = ['itemid', 'itemrem'];
    private $blnfields = [];
    private $acctg = [];
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
        $this->productportallookup = new productportallookup;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 12,
            'edit' => 13,
            'new' => 14,
            'save' => 15,
            'change' => 16,
            'delete' => 17,
            'print' => 18
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {

        $getcols = ['action', 'barcode', 'itemname', 'activestat', 'amt'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$amt]['label'] = 'Price';
        $cols[$amt]['align'] = 'text-left';

        return $cols;
    }

    public function paramsdatalisting($config)
    {

        return [];
    }

    public function loaddoclisting($config)
    {
        ini_set('memory_limit', '-1');
        $searchfield = [];
        $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
        $filtersearch = "";
        $search = '';
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            $search = str_replace('"', "”", $search);
            if ($search != "") {
                $limit = '';
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        $qry = "select item.itemid, ifnull(model.model_name,'') as model_name, item.itemname, item.barcode, item.partno,
        format(item.amt, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,item.model,
        cat.name as cat_name,
        item.othcode,if(item.isinactive=1,'Inactive','Active') as activestat,item.sizeid
        from item
        left join model_masterfile as model on model.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join itemcategory as cat on cat.line = item.category
        where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##') " . $filtersearch . "
        order by barcode " . $limit;

        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $companyid = $config['params']['companyid'];
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
            'toggledown',
            'others'
        );
        $buttons = $this->btnClass->create($btns);

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        return [];
    }

    public function tabprice($config) {}

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
        $fields = ['barcode', 'itemname', 'brandname', 'categoryname', 'partno'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
        data_set($col1, 'barcode.required', true);
        data_set($col1, 'itemname.type', 'cinput');
        data_set($col1, 'itemname.required', true);
        data_set($col1, 'brandname.label', 'Item Brand');
        data_set($col1, 'categoryname.label', 'Item Category');
        data_set($col1, 'partno.label', 'Part No.');

        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
        data_set($col1, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

        $fields = ['carbrand', 'modelname', 'fyear', 'othcode', 'position', 'sizeid'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'othcode.label', 'Equivalent No.');
        data_set($col2, 'modelname.label', 'Car Model');
        data_set($col2, 'modelname.class', 'csmodelname');
        data_set($col2, 'position.type', 'lookup');
        data_set($col2, 'position.lookupclass', 'lookupposition');
        data_set($col2, 'position.action', 'lookupposition');
        data_set($col2, 'position.class', 'csposition sbccsreadonly');
        data_set($col2, 'sizeid.label', 'Size/Type');
        data_set($col2, 'fyear.label', 'Year Model');


        $fields = ['postion', 'amt', 'disc', 'kind', 'rem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'amt.type', 'cinput');
        data_set($col3, 'amt.label', 'Price');
        data_set($col3, 'disc.label', 'Discount');

        data_set($col3, 'rem.name', 'itemrem');
        data_set($col3, 'rem.label', 'Remark');

        $fields = ['picture'];

        $col4 = $this->fieldClass->create($fields);

        data_set($col4, 'picture.folder', 'product');
        data_set($col4, 'picture.table', 'item');
        data_set($col4, 'picture.fieldid', 'itemid');
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newstockcard($config)
    {
        $companyid = $config['params']['companyid'];
        $data[0]['itemid'] = 0;
        $data[0]['barcode'] = $config['newbarcode'];
        $data[0]['itemname'] = '';
        $data[0]['brand'] = 0;
        $data[0]['brandname'] = '';
        $data[0]['category'] = 0;
        $data[0]['categoryname'] = '';
        $data[0]['partno'] = '';

        $data[0]['carbrand'] = '';
        $data[0]['carid'] = 0;
        $data[0]['modelname'] = '';
        $data[0]['model'] = 0;
        $data[0]['fyear'] = '';
        $data[0]['othcode'] = '';
        $data[0]['position'] = '';
        $data[0]['positionid'] = 0;

        $data[0]['sizeid'] = '';
        $data[0]['amt'] = 0;
        $data[0]['disc'] = '';
        $data[0]['kind'] = '';

        $data[0]['rem'] = '';
        $data[0]['picture'] = '';

        $data[0]['uom'] = 'PCS';
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
        $head = [];

        $fields = 'item.itemid, item.barcode as docno';

        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',item.' . $value;
        }

        foreach ($this->iteminfo as $key => $value) {
            $fields = $fields . ',info.' . $value;
        }

        $qryselect = "select " . $fields . ",
        ifnull(mmaster.model_name,'') as modelname, item.model as model,
        ifnull(brand.brand_desc,'') as brandname, ifnull(item.brand,'') as brand,
        cat.line as category,item.uom,
        cat.name as categoryname,item.partno,car.brand as carbrand,car.id as carid,pos.positions as position,pos.id as positionid";

        $qry = $qryselect . " from item
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join itemcategory as cat on cat.line = item.category
        left join iteminfo as info on info.itemid=item.itemid
        left join carbrand as car on car.id = item.carid
        left join positions as pos on pos.id = info.positionid
        where item.itemid = ? ";

        $head = $this->coreFunctions->opentable($qry, [$itemid]);
        if (!empty($head)) {
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
            $head[0]['itemname'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
        $data = [];
        $iteminfo = [];

        if ($isupdate) {
            unset($this->fields[0]);
            unset($this->fields[1]);
        }

        $itemid = 0;
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
                } //end if
            }
        }

        foreach ($this->iteminfo as $key) {
            if (!in_array($key, $this->except)) {
                if (array_key_exists($key, $head)) {
                    $iteminfo[$key] = $head[$key];
                    $iteminfo[$key] = $this->othersClass->sanitizekeyfield($key, $iteminfo[$key]);
                }
            } //end if    
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
            $itemid = $head['itemid'];
            array_push($this->fields, 'barcode');
            array_push($this->fields, 'picture');
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $default1 = 0;
            $itemid = $this->coreFunctions->insertGetId('item', $data);
            $this->coreFunctions->execqry('insert into uom(itemid,uom,factor,isdefault) values(?,?,1,?)', 'INSERT', [$itemid, $data['uom'], $default1]);
            $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode'] . ' - ' . $head['itemname']);
        }

        $exist = $this->coreFunctions->getfieldvalue("iteminfo", "itemid", "itemid=?", [$itemid], '', true);
        if ($exist == 0) {
            $iteminfo['itemid'] = $itemid;
            $this->coreFunctions->sbcinsert("iteminfo", $iteminfo);
        } else {
            $this->coreFunctions->sbcupdate('iteminfo', $iteminfo, ['itemid' => $head['itemid']]);
        }
        return $itemid;
    } // end function

    public function getlastbarcode($pref, $companyid = 0, $sort = 'barcode')
    {
        $length = strlen($pref);
        $return = '';
        $filter = '';
        if ($length == 0) {
            $return = $this->coreFunctions->datareader("select barcode as value from item where ''='' " . " order by " . $sort . " desc limit 1");
        } else {
            $return = $this->coreFunctions->datareader("select barcode as value from item where left(barcode,?)=? " . " order by " . $sort . " desc limit 1", [$length, $pref]);
        }

        $this->coreFunctions->LogConsole($return);

        return $return;
    }
    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
        }
    }

    public function deletetrans($config)
    {
        $itemid = $config['params']['itemid'];
        $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
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

        $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
