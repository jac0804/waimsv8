<?php

namespace App\Http\Classes\modules\vehiclescheduling;

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
use App\Http\Classes\lookup\constructionlookup;

class  tabrem
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'REMARKS';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'headrem';
    private $htable = 'hheadrem';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = [];
    public $showclosebtn = true;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->constructionlookup = new constructionlookup;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $rem = 0;
        $createby = 1;
        $createdate = 2;
        $category = 3;

        $tab = [$this->gridname => ['gridcolumns' => ['rem', 'createby', 'createdate', 'category']]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:700px;whiteSpace: normal;min-width:700px;";

        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$createby]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createby]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$category]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "select rem, createby, createdate, (case remtype when 1 then 'FOR REVISION' when 2 then 'RE-SCHEDULE' when 3 then 'DISAPPROVED' else '' end) as category from headrem where trno=?
                union all
                select rem, createby, createdate, (case remtype when 1 then 'FOR REVISION' when 2 then 'RE-SCHEDULE' when 3 then 'DISAPPROVED' else '' end) as category from hheadrem where trno=?
                order by createdate desc";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
}
