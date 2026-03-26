<?php

namespace App\Http\Classes\modules\payrollcustomform;

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



class timerec
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TIME IN/OUT';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4624,
            'edit' => 4625,
            'save' => 4625,
            'deleteitem' => 4625,
            'saveallentry' => 4625,
        );
        return $attrib;
    }


    public function createHeadbutton($config)
    {
        $btns = []; //actionload - sample of adding button in header - align with form/module name
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => [
            'gridcolumns' => [
                'action', 'dateid', 'schedin'
            ]
        ]];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'Logs';

        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['label'] = 'Time in/out';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['dateid', 'empcode', 'empname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.readonly', false);

        $fields = ['refresh', ''];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'load');

        $fields = ['returndate'];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['create'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'create.label', 'Add logs');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("select date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as dateid");
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
        // should return action
        $action = $config['params']["action2"];

        switch ($action) {
            case "load":
                return $this->loaddetails($config);
                break;
            case "create":
                $this->create($config);
                return $this->loaddetails($config);
                break;
            case "saveallentry":
                $this->saveallentry($config);
                return $this->loaddetails($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            case 'deleteitem':
                return $this->delete($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "delete from timerec where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function saveallentry($config)
    {
        $rows = $config['params']['rows'];
        foreach ($rows as $key => $val) {
            if ($val["bgcolor"] != "") {
                unset($val["bgcolor"]);
                foreach ($val as $k => $v) {
                    $val[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
                }

                $timerec = [
                    'curdate' => $val['dateid'],
                    'timeinout' => $val['schedin'],
                    'userid' => $val['userid']
                ];

                $this->coreFunctions->sbcupdate("timerec", $timerec, ['line' => $val["line"]]);
            }
        }

        $data = $this->loaddetails($config);
        return ['status' => true, 'msg' => 'Successfully updated.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }

    private function create($config)
    {
        $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $idbarcode = $config['params']['dataparams']['idbarcode'];

        $timerec = [
            'curdate' => $dateid,
            'timeinout' => $dateid,
            'userid' => $idbarcode
        ];
        $this->coreFunctions->sbcinsert("timerec", $timerec);
    }

    private function loaddetails($config)
    {
        $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $data = $this->coreFunctions->opentable("select t.line, e.idbarcode as userid, timeinout as schedin, date(t.curdate) as dateid, '' as bgcolor
                from timerec as t left join employee as e on e.idbarcode=t.userid 
                where e.empid=" . $config['params']['dataparams']['empid'] . " and date(t.curdate)='" . $dateid . "' order by t.curdate, t.timeinout");
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
} //end class
