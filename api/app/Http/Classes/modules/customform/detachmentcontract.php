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
        $fields = ['lblinvreq', ['lbltotalkg', 'num'],['lblshipping', 'year'],['lblbilling', 'numdays'],['lblacquisition', 'hours'],
               ['lbldepreciation', 'monthsno'],['lbllocation','nodays'],['lblvehicleinfo', 'othrs'],['lblrem', 'workloc']];
         $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'lblinvreq.label', '.');
        data_set($col1, 'lblinvreq.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);color:white;');
        data_set($col1, 'lbltotalkg.label', 'No. of Guards');
        data_set($col1, 'lbltotalkg.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'num.name', 'noguards');
        data_set($col1, 'num.label', '');

        data_set($col1, 'lblshipping.label', 'Days in a Year');
        data_set($col1, 'lblshipping.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'year.name', 'yrdays');
        data_set($col1, 'year.label', '');

        data_set($col1, 'lblbilling.label', 'Days in a Week');
        data_set($col1, 'lblbilling.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'numdays.name', 'wkdays');
        data_set($col1, 'numdays.label', '');
        data_set($col1, 'numdays.class', 'csnumdays');
        data_set($col1, 'numdays.readonly', false);


        data_set($col1, 'lblacquisition.label', 'Duty Hours');
        data_set($col1, 'lblacquisition.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'hours.name', 'dutyhrs');
        data_set($col1, 'hours.label', '');

        data_set($col1, 'lbldepreciation.label', 'No. of Months');
        data_set($col1, 'lbldepreciation.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'monthsno.name', 'mons');
        data_set($col1, 'monthsno.label', '');


        data_set($col1, 'lbllocation.label', 'No. of Days');
        data_set($col1, 'lbllocation.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'nodays.name', 'days');
        data_set($col1, 'nodays.label', '');
        data_set($col1, 'nodays.readonly', false);
        data_set($col1, 'nodays.type', 'input');


        data_set($col1, 'lblvehicleinfo.label', 'No. of Hours');
        data_set($col1, 'lblvehicleinfo.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'othrs.name', 'hrs');
        data_set($col1, 'othrs.label', '');
        data_set($col1, 'othrs.readonly', false);

        data_set($col1, 'lblrem.label', 'Excess Duty');
        data_set($col1, 'lblrem.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col1, 'workloc.name', 'excesshrs');
        data_set($col1, 'workloc.label', '');


        $fields = ['lblsource',['lbldestination', 'basicrate'],['lblpassbook', 'tbasicrate'],['lblreconcile', 'othrsextra'],
            ['lblearned', 'cola'],['lblcleared', 'apothrs'],['lblrecondate', 'paymentname'],['lblendingbal', 'opincentive'],
            ['lblunclear', 'tallowrate']];
        $col2 = $this->fieldClass->create($fields);


        data_set($col2, 'lblsource.label', 'AMOUNT DIRECTLY TO GUARDS');
        data_set($col2, 'lblsource.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');

        data_set($col2, 'lbldestination.label', 'Daily Wage');
        data_set($col2, 'lbldestination.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'basicrate.name', 'dailywage');
        data_set($col2, 'basicrate.label', '');
        data_set($col2, 'basicrate.class', 'csbasicrate');
        data_set($col2, 'basicrate.readonly', false);

        data_set($col2, 'lblpassbook.label', 'Basic Salary');
        data_set($col2, 'lblpassbook.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'tbasicrate.name', 'salary');
        data_set($col2, 'tbasicrate.label', '');

        data_set($col2, 'lblreconcile.label', 'Excess Hours Duty');
        data_set($col2, 'lblreconcile.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'othrsextra.name', 'excessduty');
        data_set($col2, 'othrsextra.label', '');
        data_set($col2, 'othrsextra.readonly', false);

        data_set($col2, 'lblearned.label', 'Night Pay / Cola');
        data_set($col2, 'lblearned.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'cola.label', '');
        data_set($col2, 'cola.readonly', false);

        data_set($col2, 'lblcleared.label', 'Overtime Pay');
        data_set($col2, 'lblcleared.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'apothrs.name', 'otamt');
        data_set($col2, 'apothrs.label', '');
        data_set($col2, 'apothrs.class', 'csapothrs');
        data_set($col2, 'apothrs.readonly', false);


        data_set($col2, 'lblrecondate.label', '13th Month Pay');
        data_set($col2, 'lblrecondate.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'paymentname.type', 'input');
        data_set($col2, 'paymentname.name', 'amt13th');
        data_set($col2, 'paymentname.class', 'cspaymentname');
        data_set($col2, 'paymentname.label', '');
        data_set($col2, 'paymentname.readonly', false);


        data_set($col2, 'lblendingbal.label', '5 Days Incentive');
        data_set($col2, 'lblendingbal.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'opincentive.name', 'incentive');
        data_set($col2, 'opincentive.label', '');

        data_set($col2, 'lblunclear.label', 'Uniform Allowance');
        data_set($col2, 'lblunclear.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col2, 'tallowrate.name', 'uniformamt');
        data_set($col2, 'tallowrate.label', '');

       $fields = ['lblbranch',['lbldateid', 'leadfrom'],['lblreceived', 'sss'],['lblattached', 'phic'],['lblinvreq', 'hdmf'],
              ['lblforapproval', 'escalation'],['lblapproved', 'agentcno'],['lbllocked', 'taxdef'],['lblitemdesc', 'fcontractprice'],
              'refresh'];
       $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'lblbranch.label', 'AMOUNT TO GOV\'T IN FAVOR OF GUARD');
        data_set($col3, 'lblbranch.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');

        data_set($col3, 'lbldateid.label', 'Retirement');
        data_set($col3, 'lbldateid.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'leadfrom.name', 'retireamt');
        data_set($col3, 'leadfrom.label', '');

        data_set($col3, 'lblreceived.label', 'SSS Cont.');
        data_set($col3, 'lblreceived.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'sss.name', 'sssamt');
        data_set($col3, 'sss.label', '');
        data_set($col3, 'sss.readonly', false);

        data_set($col3, 'lblattached.label', 'PHIC Cont.');
        data_set($col3, 'lblattached.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'phic.name', 'phicamt');
        data_set($col3, 'phic.label', '');
        data_set($col3, 'phic.readonly', false);

        data_set($col3, 'lblinvreq.label', 'HDMF Cont.');
        data_set($col3, 'lblinvreq.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'hdmf.name', 'hdmfamt');
        data_set($col3, 'hdmf.label', '');
        data_set($col3, 'hdmf.readonly', false);

        data_set($col3, 'lblforapproval.label', 'ECC Cont.');
        data_set($col3, 'lblforapproval.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'escalation.name', 'eccamt');
        data_set($col3, 'escalation.label', '');

        data_set($col3, 'lblapproved.label', 'Agency Fee');
        data_set($col3, 'lblapproved.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'agentcno.name', 'agencyfee');
        data_set($col3, 'agentcno.label', '');
        data_set($col3, 'agentcno.readonly', false);

        data_set($col3, 'lbllocked.label', '12% VAT');
        data_set($col3, 'lbllocked.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'taxdef.name', 'agencyvat');
        data_set($col3, 'taxdef.label', '');
        data_set($col3, 'taxdef.readonly', true);

        data_set($col3, 'lblitemdesc.label', 'TOTAL CONTRACT');
        data_set($col3, 'lblitemdesc.style', 'font-weight:bold; font-size:15px; display:flex; justify-content:center; transform:translateY(8px);');
        data_set($col3, 'fcontractprice.label', '');
        
        data_set($col3, 'refresh.label', 'Save');

        return array('col1' => $col1, 'col2'=>$col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $divid= $config['params']['trno'];

        $edit = $this->coreFunctions->getfieldvalue('divinfo', "editdate", "divid=?", [$divid]);
        //1st col
        $noguards = $config['params']['addedparams']['noguards'] ?: 0;
        $yrdays = $config['params']['addedparams']['yrdays'] ?: 0;
        $wkdays = $config['params']['addedparams']['wkdays'] ?: 0;
        $dutyhrs = $config['params']['addedparams']['dutyhrs'] ?: 0;
        $mons = $config['params']['addedparams']['mons'] ?: 0;
        $days = $config['params']['addedparams']['days'] ?: 0;
        $hrs = $config['params']['addedparams']['hrs'] ?: 0;
        $excesshrs = $config['params']['addedparams']['excesshrs'] ?: 0;

        //2nd col
        $dailywage = $config['params']['addedparams']['dailywage'] ?: 0;
     
        $salary = $config['params']['addedparams']['salary'] ?: 0;
        $excessduty = $config['params']['addedparams']['excessduty'] ?: 0;
        $cola = $config['params']['addedparams']['cola'] ?: 0;
        $otamt = $config['params']['addedparams']['otamt'] ?: 0;
        $amt13th = $config['params']['addedparams']['amt13th'] ?: 0;
        $incentive = $config['params']['addedparams']['incentive'] ?: 0;
        $uniformamt = $config['params']['addedparams']['uniformamt'] ?: 0;

        //3rd col
        $retireamt = $config['params']['addedparams']['retireamt'] ?: 0;
        $sssamt = $config['params']['addedparams']['sssamt'] ?: 0;
        $phicamt = $config['params']['addedparams']['phicamt'] ?: 0;
        $hdmfamt = $config['params']['addedparams']['hdmfamt'] ?: 0;
        $eccamt = $config['params']['addedparams']['eccamt'] ?: 0;
        $agencyfee = $config['params']['addedparams']['agencyfee'] ?: 0;
        $agencyvat = $config['params']['addedparams']['agencyvat'] ?: 0;

        $agencyvat = number_format($agencyvat, 2);
        $fcontractprice = $salary + $excessduty + $cola + $otamt + $amt13th + $incentive + $uniformamt + $retireamt + $sssamt + $phicamt + $hdmfamt + $eccamt + $agencyfee + $agencyvat;
        $fcontractprice = number_format($fcontractprice, 2);



        $editdate = $edit ?: null;

        return $this->coreFunctions->opentable("select  '' as lbltotalkg, '$noguards' as noguards, '$yrdays' as yrdays, '$wkdays' as wkdays, '$dutyhrs' as dutyhrs, 
                                                             '$mons' as mons, '$days' as days, '$hrs' as hrs, '$excesshrs' as excesshrs,
                                                            '' as lblsource, '' as lbldestination,
                                                             '$dailywage' as dailywage, '$salary' as salary,'$excessduty' as excessduty,'$cola' as cola,
                                                             '$otamt' as otamt,'$amt13th' as amt13th,'$incentive' as incentive,'$uniformamt' as uniformamt,
                                                             '' as lblbranch, '' as lbldateid,
                                                            '$retireamt' as retireamt,'$sssamt' as sssamt,'$phicamt' as phicamt,'$hdmfamt' as hdmfamt,
                                                             '$eccamt' as eccamt,'$agencyfee' as agencyfee,'$agencyvat' as agencyvat, '$fcontractprice' as fcontractprice,
                                                             '$divid' as divid , '$editdate' as editdate"); 
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
        $msg = '';

        $divid = $config['params']['dataparams']['divid'];

        // NEW / CURRENT VALUES
        //1st col
        $noguards = $config['params']['dataparams']['noguards'];
        $yrdays = $config['params']['dataparams']['yrdays'];
        $wkdays = $config['params']['dataparams']['wkdays'];
        $dutyhrs = $config['params']['dataparams']['dutyhrs'];
        $mons = $config['params']['dataparams']['mons'];
        $days = $config['params']['dataparams']['days'];
        $hrs = $config['params']['dataparams']['hrs'];
        $excesshrs = $config['params']['dataparams']['excesshrs'];

        //2nd col
        $dailywage = $config['params']['dataparams']['dailywage'];
        $salary = $config['params']['dataparams']['salary'];
        $excessduty = $config['params']['dataparams']['excessduty'];
        $cola = $config['params']['dataparams']['cola'];
        $otamt = $config['params']['dataparams']['otamt'];
        $amt13th = $config['params']['dataparams']['amt13th'];
        $incentive = $config['params']['dataparams']['incentive'];
        $uniformamt = $config['params']['dataparams']['uniformamt'];

        //3rd col
        $retireamt = $config['params']['dataparams']['retireamt'];
        $sssamt = $config['params']['dataparams']['sssamt'];
        $phicamt = $config['params']['dataparams']['phicamt'];
        $hdmfamt = $config['params']['dataparams']['hdmfamt'];
        $eccamt = $config['params']['dataparams']['eccamt'];
        $agencyfee = $config['params']['dataparams']['agencyfee'];
        $agencyvat = $config['params']['dataparams']['agencyvat'];
    

        if($agencyfee !=0 ){
            $agencyvat = $agencyfee * 0.12;
        }

        $existingdate = $config['params']['dataparams']['editdate'];

        if ($existingdate != null) {

            // GET OLD DATA FROM divinfo
            $olddata = $this->coreFunctions->opentable("select * from divinfo where divid=?",[$divid]);

            // GET NEXT LINE
            $getline = $this->coreFunctions->getfieldvalue("hdivinfo","line","1=1 order by line desc",[],'',true );
            $line = $getline + 1;

            // SAVE OLD DATA TO hdivinfo
            $hdivinfodata = [
                'line' => $line,
                'divid' => $divid,
                'noguards' => $olddata[0]->noguards,
                'yrdays' => $olddata[0]->yrdays,
                'wkdays' => $olddata[0]->wkdays,
                'dutyhrs' => $olddata[0]->dutyhrs,
                'mons' => $olddata[0]->mons,
                'days' => $olddata[0]->days,
                'hrs' => $olddata[0]->hrs,
                'excesshrs' => $olddata[0]->excesshrs,

                'dailywage' => $olddata[0]->dailywage,
                'salary' => $olddata[0]->salary,
                'excessduty' => $olddata[0]->excessduty,
                'cola' => $olddata[0]->cola,
                'otamt' => $olddata[0]->otamt,
                'amt13th' => $olddata[0]->amt13th,
                'incentive' => $olddata[0]->incentive,
                'uniformamt' => $olddata[0]->uniformamt,

                'retireamt' => $olddata[0]->retireamt,
                'sssamt' => $olddata[0]->sssamt,
                'phicamt' => $olddata[0]->phicamt,
                'hdmfamt' => $olddata[0]->hdmfamt,
                'eccamt' => $olddata[0]->eccamt,
                'agencyfee' => $olddata[0]->agencyfee,
                'agencyvat' => $olddata[0]->agencyvat,


                'editdate' => $olddata[0]->editdate,
                'editby' => $olddata[0]->editby,
                'encodedby' => $olddata[0]->encodedby,
                'encodeddate' => $olddata[0]->encodeddate,
                'viewby' => $olddata[0]->viewby,
                'viewdate' => $olddata[0]->viewdate
            ];
            $this->coreFunctions->sbcinsert('hdivinfo', $hdivinfodata);

            // VERIFY HISTORY INSERT
            $getsavedhistory = $this->coreFunctions->getfieldvalue("hdivinfo","line","line=? and divid=?",[$line, $divid],'',true );

            if ($getsavedhistory == 0) {
                $msg = 'Failed to transfer.';
                return [
                    'status' => false,
                    'msg' => $msg,
                    'closecustomform' => false,
                    'reloadhead' => false
                ];
            }
        }

        // UPDATE divinfo USING NEW VALUES
        $editdate = $this->othersClass->getCurrentTimeStamp();
        $editby = $config['params']['user'];

        $divinfodata = [
            'noguards' => $noguards,
            'yrdays' => $yrdays,
            'wkdays' => $wkdays,
            'dutyhrs' => $dutyhrs,
            'mons' => $mons,
            'days' => $days,
            'hrs' => $hrs,
            'excesshrs' => $excesshrs,

            'dailywage' => $dailywage,
            'salary' => $salary,
            'excessduty' => $excessduty,
            'cola' => $cola,
            'otamt' => $otamt,
            'amt13th' => $amt13th,
            'incentive' => $incentive,
            'uniformamt' => $uniformamt,

            'retireamt' => $retireamt,
            'sssamt' => $sssamt,
            'phicamt' => $phicamt,
            'hdmfamt' => $hdmfamt,
            'eccamt' => $eccamt,
            'agencyfee' => $agencyfee,
            'agencyvat' => $agencyvat,

            'editdate' => $editdate,
            'editby' => $editby
        ];
        $updatediv = $this->coreFunctions->sbcupdate('divinfo',$divinfodata,['divid' => $divid] );

        if ($updatediv == 0) {
            $msg = 'Failed to save.';
            return [
                'status' => false,
                'msg' => $msg,
                'closecustomform' => false,
                'reloadhead' => false
            ];
        }

        $msg = 'Successfully saved.';
        return ['status' => true,  'msg' => $msg, 'closecustomform' => true,  'reloadhead' => true ];
    }

}
