<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class detachmentcontract
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = "Contract";
    public $gridname = 'inventory';
    private $fields = [];
    private $head = 'client';
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
         $fields = [['lbltotalkg', 'num']];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'lbltotalkg.label', 'No. of Guards');
        data_set($col1, 'lbltotalkg.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
        data_set($col1, 'num.name', 'noguards');
        data_set($col1, 'num.label', '');

        $fields = [['lbldestination', 'basicrate']];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'lbldestination.label', 'Daily Wage');
        data_set($col2, 'lbldestination.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
        data_set($col2, 'basicrate.name', 'dailywage');
        data_set($col2, 'basicrate.label', '');
        data_set($col2, 'basicrate.readonly', false);

        $fields = [['lbldateid', 'leadfrom'], 'refresh'];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'lbldateid.label', 'Retirement');
        data_set($col3, 'lbldateid.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
        data_set($col3, 'leadfrom.name', 'retireamt');
        data_set($col3, 'leadfrom.label', '');
        data_set($col3, 'refresh.label', 'Save');

        return array('col1' => $col1, 'col2'=>$col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $divid= $config['params']['trno'];

        $edit = $this->coreFunctions->getfieldvalue('divinfo', "editdate", "divid=?", [$divid]);

        $noguards = $config['params']['addedparams']['noguards'] ?: 0;
        $dailywage = $config['params']['addedparams']['dailywage'] ?: 0;
        $retireamt = $config['params']['addedparams']['retireamt'] ?: 0;
        $editdate = $edit ?: null;
        return $this->coreFunctions->opentable("select  '' as lbltotalkg, '$noguards' as noguards, '' as lbldestination, '$dailywage' as dailywage, 
                                                             '' as lbldateid, '$retireamt' as retireamt, '$divid' as divid , '$editdate' as editdate"); 
    }


    public function data($config)
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
        $divid = $config['params']['dataparams']['divid'];

        $noguards = $config['params']['dataparams']['noguards'];
        $dailywage = $config['params']['dataparams']['dailywage'];
        $retireamt = $config['params']['dataparams']['retireamt'];

        $editdate = $config['params']['dataparams']['editdate'];


        if($editdate != null){
            $this->coreFunctions->execqry($this->transferhistoryquery(), 'insert', [$divid]);
        }

        $editdate = $this->othersClass->getCurrentTimeStamp();
        $editby = $config['params']['user'];

        $divinfodata = ['noguards' => $noguards,  'dailywage' => $dailywage,
                     'retireamt' => $retireamt, 'editdate' => $editdate,
                     'editby' => $editby  ];
        $this->coreFunctions->sbcupdate('divinfo', $divinfodata, ['divid' => $divid]);



        $msg = '';
        return ['status' => true, 'msg' => $msg, 'closecustomform' => true, 'reloadhead' => true];
   
    }

    public function transferhistoryquery()
    {
        return "insert into hdivinfo (divid,noguards,yrdays,wkdays,dutyhrs,mons,days,hrs,excesshrs,dailywage,salary,excessduty,cola,otamt,amt13th,incentive,uniformamt,retireamt,sssamt,phicamt,hdmfamt,eccamt,agencyfee,agencyvat)
        select dv.divid,dv.noguards,dv.yrdays,dv.wkdays,dv.dutyhrs,dv.mons,dv.days,dv.hrs,dv.excesshrs,dv.dailywage,dv.salary,dv.excessduty,dv.cola,dv.otamt,dv.amt13th,dv.incentive,dv.uniformamt,dv.retireamt,dv.sssamt,dv.phicamt,dv.hdmfamt,dv.eccamt,dv.agencyfee,dv.agencyvat
        from divinfo as dv where dv.divid=?";
    }

}
