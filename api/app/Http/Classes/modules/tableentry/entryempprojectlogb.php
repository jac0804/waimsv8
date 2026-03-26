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

class entryempprojectlogb
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'empprojdetail';
    private $othersClass;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $style = 'width:100%;';
    private $fields = ['line', 'empid', 'dateid',  'tothrs', 'rem', 'dateno', 'compcode', 'pjroxascode1', 'subpjroxascode', 'blotroxascode', 'amenityroxascode', 'subamenityroxascode', 'departmentroxascode'];
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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $row = $config['params']['row'];

        $this->modulename =  $row['emplast'] . ', ' . $row['empfirst'];
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'rem', 'tothrs', 'othrs', 'compcode', 'pjroxascode1', 'subpjroxascode', 'blotroxascode', 'amenityroxascode', 'subamenityroxascode', 'departmentroxascode']]];
        $stockbuttons = ['delete', 'save'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][3]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][6]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][7]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][8]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][9]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][10]['type'] = 'label';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    private function selectqry()
    {
        $qry = " 
    empd.line, empd.empid, empd.dateid, empd.tothrs, empd.othrs, empd.rem, empd.createby, empd.createdate, empd.editby,
    empd.editdate, empd.dateno, '' as bgcolor,
    comp.compcode,
    comp.name as pjroxascode1 ,comp.code as projcode,
    subproj.name as subpjroxascode ,subproj.code as subprojcode,
    concat(blocklot.block,' ',blocklot.lot,' ',blocklot.phase) as blotroxascode,blocklot.code as blocklotcode,
    amnt.name as amenityroxascode ,amnt.code as amntcode,
    subamnt.name as subamenityroxascode ,subamnt.code as subamntcode,
    dept.name as departmentroxascode ,dept.code as deptcode,
    comp.line as projline,  
    subproj.line as subprojline,  
    blocklot.line as blocklotline,
    amnt.line as amntline,
    subamnt.line as subamntline,
    dept.line as deptline";

        return $qry;
    }
    public function loaddata($config)
    {
        $row = $config['params']['row'];
        $qry = "select 
    
    " . $this->selectqry() . " 

    from empprojdetail as empd
    
    left join projectroxas as comp on comp.compcode=empd.compcode and comp.code=empd.pjroxascode1
    left join subprojectroxas as subproj on subproj.code=empd.subpjroxascode and subproj.compcode=empd.compcode
    left join blocklotroxas as blocklot on blocklot.code=empd.blotroxascode and blocklot.compcode=empd.compcode
    left join amenityroxas as amnt on amnt.code=empd.amenityroxascode and amnt.compcode=empd.compcode
    left join subamenityroxas as subamnt on subamnt.code=empd.subamenityroxascode and subamnt.compcode=empd.compcode
    left join departmentroxas as dept on dept.code=empd.departmentroxascode and dept.compcode=empd.compcode 

    where empd.empid=" . $row['empid'] . " and date(empd.dateid)='" . $row['dateid'] . "'";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function delete($config)
    {
        $line = $config['params']['row']['line'];
        $empid = $config['params']['row']['empid'];
        $qry = "delete from empprojdetail  where line=? and empid = ?";
        $this->coreFunctions->execqry($qry, 'delete', [$line, $empid]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];

        $data['othrs'] = $this->othersClass->sanitizekeyfield('othrs', $row['othrs']);
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {

            $returnrow = $this->loaddataperrecord($config);

            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    }

    private function loaddataperrecord($config)
    {
        $row = $config['params']['row'];
        $qry = "select 
    
            " . $this->selectqry() . " 

            from empprojdetail as empd
            
            left join projectroxas as comp on comp.compcode=empd.compcode and comp.code=empd.pjroxascode1
            left join subprojectroxas as subproj on subproj.code=empd.subpjroxascode and subproj.compcode=empd.compcode
            left join blocklotroxas as blocklot on blocklot.code=empd.blotroxascode and blocklot.compcode=empd.compcode
            left join amenityroxas as amnt on amnt.code=empd.amenityroxascode and amnt.compcode=empd.compcode
            left join subamenityroxas as subamnt on subamnt.code=empd.subamenityroxascode and subamnt.compcode=empd.compcode
            left join departmentroxas as dept on dept.code=empd.departmentroxascode and dept.compcode=empd.compcode 

            where empd.empid=" . $row['empid'] . " and empd.line=" . $row['line'] . "";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class
