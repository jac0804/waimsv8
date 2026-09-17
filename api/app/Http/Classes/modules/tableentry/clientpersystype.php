<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class clientpersystype
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LIST OF PENDING CLIENTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'task_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablelogs_del = 'del_task_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->linkemail = new linkemail;
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
        // $approver = $config['params']['row']['approver'];

        $cols = ['action', 'clientname', 'rem'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        // $stockbuttons = ['approve', 'viewdailytaskattachment'];
        $stockbuttons = ['completetask', 'undone'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['action'] = 'customform';
        $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['lookupclass'] = 'clientcompletetask';
        $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['label'] = 'Completed';
        $obj[0][$this->gridname]['columns'][$action]['btns']['undone']['action'] = 'customform';
        $obj[0][$this->gridname]['columns'][$action]['btns']['undone']['lookupclass'] = 'clientpendingtask';
        $obj[0][$this->gridname]['columns'][$action]['btns']['undone']['label'] = 'Pending';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        // $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        // $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:70%;max-width:100%;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:20%;max-width:100%;';
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:10%;max-width:100%;';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Pending Task';
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        // $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Start';
        // $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Accept';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return [];
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        return [];
    }



    public function loaddata($config)
    {
        $adminid = $config['params']['adminid'];
        $itemid = $config['params']['row']['item'];
    
        $qry = "select t.clientid,
        c.clientname, i.itemid as item,
        sum(t.completed) as completed,
        sum(t.overall)   as overall,
        concat(sum(t.completed), ' / ', sum(t.overall)) as rem
        from (
        select h.trno, h.clientid, h.systype as item,
        (select count(enddate) from tmdetail as tm
        where tm.trno = h.trno and isassigntype = 0) as completed,
        (select count(line) from tmdetail as tm
        where tm.trno = h.trno and isassigntype = 0) as overall
        from tmhead as h
        where h.status = 1 and h.systype = $itemid
        ) as t
        left join client as c on c.clientid = t.clientid
        left join item as i on i.itemid = t.item
        group by t.clientid, c.clientname, i.itemid
        order by c.clientname";
        // var_dump($qry);
    
        $data = $this->coreFunctions->opentable($qry);
    
        // return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
        return $data;
    }


    public function updateapp($config)
    {
        return [];
    }
} //end class
