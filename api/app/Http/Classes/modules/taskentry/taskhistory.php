<?php

namespace App\Http\Classes\modules\taskentry;

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
use DateTime;

class taskhistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TASK HISTORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger; //supplierlist
    private $table = 'credithead';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'task_log';
    public $tablelogs_del = 'del_task_log';
    // private $fields = ['ctag'];
    public $showclosebtn = false;
    private $reporter;


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
        $attrib = array('load' => 0);
        return $attrib;
    }
    //aa
    public function createTab($config)
    {
        $columns = ['dateid2', 'usertype', 'status', 'username', 'rem1', 'dateid', 'donedate', 'tothrs'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$dateid2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateid2]['label'] = "Date";
        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Create Date";
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$donedate]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$donedate]['type'] = "label";


        $obj[0][$this->gridname]['columns'][$username]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        $obj[0][$this->gridname]['columns'][$tothrs]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$tothrs]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";

        $obj[0][$this->gridname]['columns'][$status]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$status]['style'] = "width:80px;whiteSpace: normal;min-width:80px;align:text-center;";

        $obj[0][$this->gridname]['columns'][$usertype]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$usertype]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$rem1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$rem1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rem1]['label'] = "Solution Remarks";
        return $obj;
    }


    public function createtabbutton($config)
    {

        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    //aa
    public function loaddata($config)
    {
        // var_dump($config['params']['row']['tmtrno']);
        // break;
        $trno = isset($config['params']['row']['tmtrno']) ? $config['params']['row']['tmtrno'] : (isset($config['params']['row']['tasktrno']) ? $config['params']['row']['tasktrno'] : 0);
        $line = isset($config['params']['row']['tmline']) ? $config['params']['row']['tmline'] : (isset($config['params']['row']['taskline']) ? $config['params']['row']['taskline'] : 0);

        // $trno = $config['params']['row']['tmtrno'];
        // $line = $config['params']['row']['tmline'];
        $filter = " where dt.tasktrno=$trno and dt.taskline=$line";

        if ($trno == 0 && $line == 0) {
            $dytrno = isset($config['params']['row']['refx']) ? $config['params']['row']['refx'] :  0;
            $filter = " where dt.trno=$dytrno";
        }



        $qry = "  select c.clientname as customer, dt.createdate as dateid, userr.clientname as username,dt.userid, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours,
        dt.ischecker,dt.statid,dt.rem1, dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.taskline,
        round(timestampdiff(second,dt.createdate,if(dt.donedate is null, current_timestamp, dt.donedate)) / 3600,2) as tothrs,
        case when dt.statid='0' and dt.isprev=1 then 'on-going (continuation)' when dt.statid='0' then 'on-going' when dt.statid='1' then 'completed' when dt.statid='2' then 'undone' when dt.statid='4' then 'neglect' when dt.statid='5' then 'cancelled' when dt.statid='6' then 'return' end as status,
        case dt.ischecker when '1' then 'Checker' when '0' then 'User' end as usertype,dt.rem1, date_format(dt.createdate, '%m/%d/%Y') as dateid2
        from dailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid=dt.userid
        $filter
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate,  dt.ischecker,
        dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.taskline,dt.userid,dt.isprev,dt.rem1

        union all

        select c.clientname as customer, dt.createdate as dateid, userr.clientname as username,dt.userid, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours,
        ischecker, dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.taskline,
        round(timestampdiff(second,dt.createdate,if(dt.donedate is null, current_timestamp, dt.donedate)) / 3600,2) as tothrs,
        case when dt.statid='0' and dt.isprev=1 then 'on-going (continuation)' when dt.statid='0' then 'on-going' when dt.statid='1' then 'completed' when dt.statid='2' then 'undone' when dt.statid='4' then 'neglect' when dt.statid='5' then 'cancelled' when dt.statid='6' then 'return' end as status,
        case dt.ischecker when '1' then 'Checker' when '0' then 'User' end as usertype,dt.rem1, date_format(dt.createdate, '%m/%d/%Y') as dateid2
        from hdailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid=dt.userid
        $filter
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate,  dt.ischecker,
        dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.taskline,dt.userid,dt.isprev,dt.rem1
        order by dateid asc";
        // var_dump($qry);
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        return [];
    }

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $doc = strtoupper("SUPPLIERLIST");
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
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
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
    }
} //end class
