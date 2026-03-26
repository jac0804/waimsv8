<?php

namespace App\Http\Classes\modules\othersettings;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class pricelist
{
    private $fieldClass;
    private $btnClass;
    private $tabClass;
    public $modulename = 'Price List';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $table = 'pricebracket';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';
    private $fields = ['itemid', 'groupid', 'r', 'w', 'a', 'b', 'c', 'd', 'e', 'f', 'g'];
    public $showclosebtn = false;
    public $issearchshow = false;
    private $reporter;
    private $logger;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
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
        $attrib = array('view' => 2990, 'save' => 2990, 'saveallentry' => 2990);
        return $attrib;
    }

    public function createTab($config)
    {
        $barcode = 0;
        $itemdesc = 1;
        $r = 2;
        $w = 3;
        $a = 4;
        $b = 5;
        $c = 6;
        $d = 7;
        $e = 8;
        $f = 9;
        $g = 10;
        $brand = 11;
        $tab = [
            $this->gridname => [
                'gridcolumns' => ['barcode', 'itemdesc', 'r', 'w', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'brand']
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'LISTING';

        $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:180px;whiteSpace: normal;min-width:180px;max-width:180px;";
        // $obj[0][$this->gridname]['columns'][$barcode]['field'] = "descriptionrow";

        $bracket = $this->coreFunctions->opentable("select name, minimum, maximum from qtybracket order by line");
        if (!empty($bracket)) {
            $i = 2;
            foreach ($bracket as $key => $val) {
                $obj[0][$this->gridname]['columns'][$i]['label'] = $bracket[$i - 2]->name . " (" . $bracket[$i - 2]->minimum . "-" . $bracket[$i - 2]->maximum . ")";
                $obj[0][$this->gridname]['columns'][$i]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";
                $i = $i + 1;
            }
        }

        $obj[0][$this->gridname]['columns'][$brand]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$brand]['label'] = '';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $save = 0;
        $logs = 1;
        $tbuttons = ['saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[$logs]['action'] = 'lookuplogs';
        return $obj;
    }

    public function createHeadbutton($config)
    {
        $btns = [];
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createHeadField($config)
    {
        $fields = ['categoryname', 'itemcategoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'categoryname.label', 'Customer Category');
        data_set($col1, 'itemcategoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemstockcard');
        data_set($col1, 'itemcategoryname.class', 'cscscategocsryname sbccsreadonly');


        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, "shiftcode.type", "input");

        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.action', 'load');

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        $data = $this->coreFunctions->opentable("
      select 0 as category, '' as categoryname, 0 as itemcategory, '' as itemcategoryname");
        if (!empty($data)) {
            return $data[0];
        } else {
            return [];
        }
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

    public function headtablestatus($config)
    {
        $action = $config['params']["action2"];
        switch ($action) {
            case "load":
                return $this->loaddetails($config);
                break;

            case 'saveallentry':
                $this->savechanges($config);
                return $this->loaddetails($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }

    private function loaddetails($config)
    {
        $itemgrp = isset($config['params']['dataparams']['itemcategory']) ? $config['params']['dataparams']['itemcategory'] : 0;
        $customergrp = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : 0;

        if ($customergrp == 0) {
            return ['status' => false, 'msg' => 'Please select valid customer category.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        if ($itemgrp == 0) {
            return ['status' => false, 'msg' => 'Please select valid item category.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        $sql = "select i.itemid, i.barcode, i.itemname as itemdesc, ifnull(p.groupid,0) as groupid, ifnull(p.r,0) as r, ifnull(p.w,0) as w, ifnull(p.a,0) as a, ifnull(p.b,0) as b, 
                ifnull(p.c,0) as c, ifnull(p.d,0) as d, ifnull(p.e,0) as e, ifnull(p.f,0) as f, ifnull(p.g,0) as g, '' as bgcolor, '' as brand
                from item as i left join (select itemid, groupid, r, w, a, b, c, d, e, f, g from pricebracket as p where p.groupid=" . $customergrp . ") as p on p.itemid=i.itemid 
                where i.category=" . $itemgrp . " order by i.barcode";
        $data = $this->coreFunctions->opentable($sql);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }

    private function savechanges($config)
    {
        $customergrp = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : 0;
        $customergrpname = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : 0;

        if ($customergrp == 0) {
            return ['status' => false, 'msg' => 'Please select valid customer category.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        $data = $config['params']['rows'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data2['groupid'] == 0) {
                    $data2['groupid'] = $customergrp;
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['createby'] = $config['params']['user'];
                    $this->coreFunctions->sbcinsert($this->table, $data2);

                    $this->logger->sbcwritelog2($data[$key]['itemid'], $config['params']['user'], 'PRICELIST', $data[$key]['barcode'] . ' - CREATE ' . $customergrpname . ' PRICE', $this->tablelogs);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['itemid' => $data[$key]['itemid'], 'groupid' => $data[$key]['groupid']]);
                }
            } // end if
        } // foreach
    }

    // public function lookuplogs($config)
    // {
    //     $doc = "PRICELIST";
    //     $lookupsetup = array(
    //         'type' => 'show',
    //         'title' => 'Logs',
    //         'style' => 'width:1000px;max-width:1000px;'
    //     );

    //     // lookup columns
    //     $cols = array(
    //         array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
    //         // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
    //         array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
    //         array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    //     );

    //     $trno = $config['params']['tableid'];

    //     $qry = "
    //     select trno, field, oldversion as task, log.userid as user, dateid, 
    //     if(pic='','blank_user.png',pic) as pic
    //     from " . $this->tablelogs . " as log
    //     left join useraccess as u on u.username=log.userid
    //     where log.field = '" . $doc . "' and log.trno = '$trno'";

    //     $qry = $qry . " order by dateid desc";
    //     $data = $this->coreFunctions->opentable($qry);
    //     return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    // }
}
