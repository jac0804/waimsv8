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

class pendingreimbursement
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING REIMBURSEMENT';
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
        $modulename = $config['params']['row']['modulename'];
        $cols = ['action', 'clientname', 'amount', 'appcount'];

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['viewreimbursement'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Created By';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Amount';
        $obj[0][$this->gridname]['columns'][$appcount]['style'] = 'text-align:right;width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return array('col1' => []);
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
        $doc = $config['params']['row']['doc'];
        $trno_list = [];

        $query = "select refx as trno from hdailytask as task 
		left join pendingapp as app on app.trno = task.trno
		where app.approver = 'REIMBURSEMENT' and app.doc = 'DY'";
        $data_list = $this->coreFunctions->opentable($query); // list of reimbursement

        if (!empty($data_list)) {
            foreach ($data_list as $refx) {
                array_push($trno_list, $refx->trno);
            }
            $trno_list = array_unique($trno_list);
            $trno = implode(",", $trno_list);


            $qry = "select sum(task.amt) as amount,client.clientname,count(task.trno) as appcount,task.userid as clientid,'$trno' as rtrno from hdailytask as task 
                    left join client on client.clientid = task.userid
                    where task.statid = 1 and task.trno in ( $trno ) and task.apvtrno = 0
		            group by client.clientname,task.userid";
            return $this->coreFunctions->opentable($qry);
        } else {
            return [];
        }
    }


    public function updateapp($config, $status) {}
}
