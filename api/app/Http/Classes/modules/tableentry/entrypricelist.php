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

class entrypricelist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PRICE LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'pricebracket';
    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';
    private $logger;
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['r', 'w', 'a', 'b', 'c', 'd', 'e', 'f', 'g'];
    public $showclosebtn = false;


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
            'load' => 0
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $cat_name = 0;
        $r = 1;
        $w = 2;
        $a = 3;
        $b = 4;
        $c = 5;
        $d = 6;
        $e = 7;
        $f = 8;
        $g = 9;
        $tab = [
            $this->gridname => [
                'gridcolumns' => ['cat_name', 'r', 'w', 'a', 'b', 'c', 'd', 'e', 'f', 'g']
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'LISTING';

        $obj[0][$this->gridname]['columns'][$cat_name]['label'] = "Customer Category";
        $obj[0][$this->gridname]['columns'][$cat_name]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$cat_name]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";

        $bracket = $this->coreFunctions->opentable("select name, minimum, maximum from qtybracket order by line");
        if (!empty($bracket)) {
            $i = 1;
            foreach ($bracket as $key => $val) {
                $obj[0][$this->gridname]['columns'][$i]['label'] = $bracket[$i - 1]->name . " (" . $bracket[$i - 1]->minimum . "-" . $bracket[$i - 1]->maximum . ")";
                $i = $i + 1;
            }
        }

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    private function selectqry($config)
    {
        $itemid = $config['params']['tableid'];

        $qry = "select c.cat_name, ifnull(r,0) as r, ifnull(w,0) as w, ifnull(a,0) as a, ifnull(b,0) as b, ifnull(c,0) as c, 
        ifnull(d,0) as d, ifnull(e,0) as e, ifnull(f,0) as f, ifnull(g,0) as g
        from category_masterfile as c left join pricebracket as p on p.groupid=c.cat_id and p.itemid=" . $itemid;
        return $qry;
    }


    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $center = $config['params']['center'];
        $qry = $this->selectqry($config);
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $doc = "PRICELIST";
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
        select trno, field, oldversion as task, log.userid as user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.userid
        where log.field = '" . $doc . "' and log.trno = '$trno'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
