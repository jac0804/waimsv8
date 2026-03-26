<?php

namespace App\Http\Classes\modules\cdo;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entryclosingmccollection
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CLOSING MC COLLECTION';
    public $gridname = 'inventory';
    private $companysetup;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'hmchead';
    public $hstock = 'hmcdetail';
    public $style = 'width:100%;';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $coreFunctions;
    private $othersClass;
    private $fields = ['isok']; // update na fields
    public $showclosebtn = false;
    public $showfilteroption = true;
    public $showfilter = true;
    public $issearchshow = false;

    public $logger;

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
            'load' => 4728,
            'view' => 4729

        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['isok', 'dateid', 'docno',  'clientname', 'sicsino', 'amt', 'yourref', 'ourref'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$isok]['label'] = 'Select';
        $obj[0][$this->gridname]['columns'][$isok]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$amt]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$yourref]['label'] = 'CR#';
        $obj[0][$this->gridname]['columns'][$yourref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$ourref]['label'] = 'RF#';
        $obj[0][$this->gridname]['columns'][$ourref]['type'] = 'label';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry', 'saveallentry', 'unmarkall'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'CLOSE MC';
        $obj[1]['label'] = 'MARK ALL';
        $obj[1]['action'] = 'markall';
        return $obj;
    }

    private function selectqry($config)
    {
        $qry = "head.docno,date(head.dateid) as dateid,head.clientname,client.client,head.sicsino,head.clientid,format(sum(detail.amount),2) as amt,head.ourref,head.yourref,head.trno,'false' as isok,'' as bgcolor";
        return $qry;
    }
    public function tableentrystatus($config)
    {
        switch ($config['params']['action2']) {
            case 'unmarkall':
                return $this->unmarkall($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in tableentrystatus'];
                break;
        }
    }
    public function markall($config)
    {
        foreach ($config['params']['data'] as $key => $value) {
            $config['params']['data'][$key]['isok'] = 'true';
        }
        return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => []];
    }
    public function unmarkall($config)
    {
        foreach ($config['params']['data'] as $key => $value) {
            if ($config['params']['data'][$key]['isok'] == 'false') {
                continue;
            } else {
                $config['params']['data'][$key]['isok'] = 'false';
            }
        }
        return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => []];
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data2['isok'] = ($data[$key]['isok'] == 'true' ? 1 : 0);
            } else {
                if ($data[$key]['bgcolor'] == '' &&  $data[$key]['isok'] == 'true') {
                    $data2['isok'] = ($data[$key]['isok'] = 1);
                }
            }
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data2, ['trno' => $data[$key]['trno']]);
            $path = 'App\Http\Classes\modules\cdo\mc';
            $this->logger->sbcwritelog($data[$key]['trno'], $config, 'UPDATE', $data[$key]['docno'] . ' - IS CLOSE ', app($path)->tablelogs);
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } //end function
    public function loaddata($config)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry($config);
        $query = "select " . $select . " 
           from hmchead as head
           left join hmcdetail as detail on detail.trno = head.trno
           left join transnum as num on num.trno = head.trno
           left join coa on coa.acnoid = detail.acnoid
           left join client on client.clientid = head.clientid
           where head.isok = 0 and  head.trnxtype = 'Spareparts' and client.client ='WALK-IN' and num.center ='" . $center . "'
           group by head.docno,date(head.dateid),client.clientname,head.clientid,head.ourref,head.yourref,head.trno,client.client,head.sicsino,head.clientname
           order by head.dateid, head.docno";
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }
    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                // return $this->lookuplogs($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
} //end class
