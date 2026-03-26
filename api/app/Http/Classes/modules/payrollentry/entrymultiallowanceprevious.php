<?php

namespace App\Http\Classes\modules\payrollentry;

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

class entrymultiallowanceprevious
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ALLOWANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'allowsetup';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $fields = ['dateid', 'dateeffect', 'dateend', 'empid', 'allowance', 'acnoid', 'remarks', 'refx', 'isliquidation'];
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
        $columns = ['action', 'code', 'codename', 'dateeffect', 'dateend', 'allowance', 'isliquidation', 'remarks'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$dateend]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$allowance]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$codename]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateeffect]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateend]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$allowance]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$isliquidation]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$isliquidation]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$remarks]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Allowance Code';
        $obj[0][$this->gridname]['columns'][$codename]['label'] = 'Allowance Type';
        $obj[0][$this->gridname]['columns'][$dateeffect]['label'] = 'Period Start';
        $obj[0][$this->gridname]['columns'][$dateend]['label'] = 'Period End';
        $obj[0][$this->gridname]['columns'][$allowance]['label'] = 'Amount';
        return $obj;
    }


    public function createtabbutton($config)
    {
        return [];
    }

    private function selectqry()
    {
        $qry = "a.trno,date(a.dateeffect) as dateeffect, date(a.dateend) as dateend,date(a.dateid) as dateid,
                a.empid,a.remarks,a.allowance,a.acnoid,a.refx,(case when a.isliquidation = 0 then 'NO' else 'YES' end) as isliquidation";

        return $qry;
    }

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];

        $row = $config['params']['row'];
        $dateeffect = $this->coreFunctions->getfieldvalue("eschange", "effdate", "trno=?", [$tableid]);

        $codename = $this->coreFunctions->getfieldvalue("paccount", "codename", "line=?", [$row['acnoid']]);

        $qry = "update " . $this->table . " set dateend=DATE_SUB('" . $dateeffect . "', INTERVAL 1 DAY), voiddate='" . $this->othersClass->getCurrentTimeStamp() . "',voidby='" . $config['params']['user'] . "' where acnoid=? and refx=?";
        if ($this->coreFunctions->execqry($qry, 'update', [$row['acnoid'], $row['refx']])) {
            $this->logger->sbcwritelog($tableid, $config, 'UPDATE', 'END ALLOWANCE: ' . $codename . ', Amount: ' . number_format($row['allowance'], 2));
        }

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($trno, $acnoid)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,p.codename,p.code ";
        $qry = "select " . $select . " 
                from " . $this->table . " as a
                left join paccount as p on p.line=a.acnoid where a.acnoid=? and a.refx=?";
        $data = $this->coreFunctions->opentable($qry, [$acnoid, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $filter = '';
        if ($config['params']['doc'] == 'HS') {
            $empid = $this->coreFunctions->datareader("select empid as value from eschange where trno=? union all select empid as value from heschange where trno=?", [$tableid, $tableid], '', true);
            if ($empid != 0) {
                $filter = " and a.empid= $empid ";
            }
        }
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,p.codename,p.code ";
        $qry = "select " . $select . " from " . $this->table . " as a
                left join paccount as p on p.line=a.acnoid
                where a.refx<>0 and a.refx<>" . $tableid . " $filter and a.voiddate is null order by a.dateeffect,codename and a.allowance";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }


    public function lookupcallback($config)
    {
        $row = $config['params']['row'];
        $data = $this->save('insert', $config);

        if ($data['status']) {
            return ['status' => true, 'msg' => $data['msg'], 'data' => $data['data'][0]];
        } else {
            return ['status' => false, 'msg' => $data['msg']];
        }
    }
} //end class
