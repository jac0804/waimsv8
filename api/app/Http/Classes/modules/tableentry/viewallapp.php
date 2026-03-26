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
use App\Http\Classes\SBCPDF;

class viewallapp
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ALL APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'otapplication';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
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
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['qtype', 'runtime', 'appliedhrs', 'approvedhrs'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['totalfield'] = '';
        $obj[0][$this->gridname]['columns'][$qtype]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$qtype]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$qtype]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$runtime]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$runtime]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$runtime]['label'] = "Time";
        $obj[0][$this->gridname]['columns'][$appliedhrs]['type'] = "label";
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        if (isset($config['params']['sourcerow']['empid'])) {
            $empid = $config['params']['sourcerow']['empid'];
            $dateid = $config['params']['sourcerow']['dateid'];
        } else {
            $empid = $config['params']['row']['empid'];
            $dateid = $config['params']['row']['dateid'];
        }

        $qry = "select ot.empid, ot.dateid,client.clientname,'ot' as ttype, 'OT APPLICATION' as qtype,date_format(ot.ottimein, '%h:%i %p') AS runtime,
                ot.othrs as appliedhrs, ot.apothrs as approvedhrs,'' as bgcolor
                from otapplication as ot
                left join client on client.clientid=ot.empid
                where ot.empid=$empid and date(ot.dateid)='$dateid'

                union all
                select rest.empid, rest.dateid,client.clientname,'rest' as ttype,'REST DAY' as qtype,'' as runtime,
                '' as appliedhrs, '' as approvedhrs,'' as bgcolor
                from changeshiftapp as rest
                left join client on client.clientid=rest.empid
                where  rest.empid=$empid and date(rest.dateid)='$dateid' and rest.isrestday=1

                union all
                select ob.empid, ob.dateid,client.clientname,'ob' as ttype,'OB APPLICATION' as qtype, date_format(ob.dateid, '%h:%i %p') as runtime,
                '' as appliedhrs, '' as approvedhrs,'' as bgcolor
                from obapplication as ob
                left join client on client.clientid=ob.empid
                where ob.empid=$empid and date(ob.dateid)='$dateid' and isitinerary=0

                union all

                select wr.empid, wr.dateid,client.clientname,'word' as ttype,'WORK ON RESTDAY' as qtype, ''  as runtime,
                '' as appliedhrs,
                '' as approvedhrs,'' as bgcolor
                from changeshiftapp as wr
                left join client on client.clientid=wr.empid
                 where wr.empid=$empid and date(wr.dateid)='$dateid' and wr.isword=1

                 union all 

                select ud.empid, ud.dateid,client.clientname,'under' as ttype,'UNDER TIME APPLICATION' as qtype, '' as runtime,
                '' as appliedhrs, '' as approvedhrs,'' as bgcolor
                from undertime as ud
                left join client on client.clientid=ud.empid
                where ud.empid=$empid and date(ud.dateid)='$dateid'
                
                union all
                select itt.empid, itt.dateid,client.clientname,'itt' as ttype,'TRAVEL APPLICATION' as qtype, '' as runtime,
                '' as appliedhrs, '' as approvedhrs,'' as bgcolor
                from itinerary as itt
                left join client on client.clientid=itt.empid
                where itt.empid=$empid and  '$dateid' between date(itt.startdate) and date(itt.enddate)";
        // var_dump($qry);
        //round(timestampdiff(minute, ud.dateid2, ud.dateid) / 60, 2) as appliedhrs
        //round(timestampdiff(minute, wr.schedin, wr.schedout) / 60, 2) as appliedhrs,
        //if(wr.status2 <> 0 and wr.status <> 0,round(timestampdiff(minute, wr.schedin, wr.schedout) / 60, 2), '0' )   as approvedhrs

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];

                if ($data[$key]['ttype'] == 'ot') {
                    $params['params']['doc'] = strtoupper("EMPTIMECARD");
                    $data2['apothrs'] = $data[$key]['approvedhrs'];
                    $this->coreFunctions->sbcupdate('otapplication', $data2, ['empid' => $data[$key]['empid'], 'dateid' => $data[$key]['dateid']]);
                    $this->logger->sbcmasterlog($data[$key]['empid'], $config, ' Update Approved OT Hrs- ' . $data[$key]['approvedhrs']);
                } else {
                    if ($data[$key]['approvedhrs'] != '') {
                        unset($data[$key]['approvedhrs']);
                        $returndata = $this->loaddata($config);
                    }
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' =>  $returndata];
    } // end function  

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'All application Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );
        $doc = strtoupper("EMPTIMECARD");
        $data = $config['params']['data'];
        $empid = $data[0]['empid'];
        $dateid = $data[0]['dateid'];
        $qry = "
            select trno, doc, task, log.user, log.dateid, 
            if(pic='','blank_user.png',pic) as pic
            from " . $this->tablelogs . " as log
            left join useraccess as u on u.username=log.user
            left join otapplication as ot on ot.empid=log.trno
            where log.doc = '" . $doc . "' and log.trno = '" . $empid . "' and ot.dateid='" . $dateid . "'
            union all
            select trno, doc, task, log.user, log.dateid, 
            if(pic='','blank_user.png',pic) as pic
            from  " . $this->tablelogs_del . " as log
            left join useraccess as u on u.username=log.user
            left join otapplication as ot on ot.empid=log.trno
            where log.doc = '" . $doc . "' and log.trno = '" . $empid . "'and ot.dateid='" . $dateid . "'";
        $qry = $qry . " order by dateid desc";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class