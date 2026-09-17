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

class viewclientpendingtask
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LIST OF PENDING TASK';
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

        $cols = ['action', 'dateid', 'clientname', 'empname', 'title', 'startdate', 'enddate', 'assignto'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        $stockbuttons = [];


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$action]['label'] = '';
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:5px;max-width:5px;';

        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$startdate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;max-width:120px';

        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Date';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;max-width:120px';

        $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'End Date';
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$enddate]['readonly'] = true; 
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;max-width:120px';

        $obj[0][$this->gridname]['columns'][$assignto]['readonly'] = true; 
        $obj[0][$this->gridname]['columns'][$assignto]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;max-width:200px';
        
        $obj[0][$this->gridname]['columns'][$title]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;max-width:200px';

        $obj[0][$this->gridname]['columns'][$empname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;max-width:200px';
        $obj[0][$this->gridname]['columns'][$empname]['label'] = 'Project Head';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Reseller';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;max-width:200px';

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
        // $fields = ['dateid'];
        // $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dateid.readonly', false);
    
        // $fields = ['refresh'];
        // $col2 = $this->fieldClass->create($fields);
        // data_set($col2, 'refresh.action', 'pdc');
    
        // return array('col1' => $col1, 'col2' => $col2);
        return [];
    }

    public function paramsdata($config)
    {
        // $dateid = $this->othersClass->getCurrentDate();
        // // $trno = $config['params']['row']['trno'];
        // $paramstr = "select '" . $dateid . "' as dateid";
        // return $this->coreFunctions->opentable($paramstr);
        return [];
    }

    public function data($config)
    {
        return [];
    }



    public function loaddata($config)
    {
        $adminid = $config['params']['adminid'];
        $clientid = $config['params']['row']['clientid'];
        $item = $config['params']['row']['item'];
    
        $qry = "select
                head.dateid, ifnull(detail.startdate, '') as startdate,
                ifnull(detail.enddate, '') as enddate,
                head.trno, ifnull(head.dateid, '') as dateid,
                head.clientid as cust, u.clientname as assignto, head.reseller as clientname,
                detail.task as tm,
                detail.title,
                head.requestby as head,
                e.clientname as empname
                from tmhead as head
                left join tmdetail as detail on detail.trno = head.trno
                left join client as e on e.clientid = head.requestby
                left join client as c on c.clientid = head.clientid
                left join client as u on u.clientid = detail.userid
                where head.clientid = $clientid and head.status = 1 and head.systype = $item and detail.status in (1, 2, 3, 4)
                order by detail.line";
    
        $data = $this->coreFunctions->opentable($qry);
    
        // return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
        return $data;
    }


    public function updateapp($config)
    {
        return [];
    }
} //end class
