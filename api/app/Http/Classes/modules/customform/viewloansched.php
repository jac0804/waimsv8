<?php

namespace App\Http\Classes\modules\customform;

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

class viewloansched
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LOAN SCHEDULE';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = true;



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function createTab($config)
    {
        $this->modulename = 'LOAN SCHEDULE - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);
        $column = ['lblstatus', 'balance', 'processfee', 'principal', 'interest', 'amortization', 'payment', 'bal'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$lblstatus]['label'] = 'Installment';
        $obj[0][$this->gridname]['columns'][$balance]['label'] = 'Beginning Balance';
        $obj[0][$this->gridname]['columns'][$balance]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$processfee]['label'] = 'Processing and Others Fees';
        $obj[0][$this->gridname]['columns'][$amortization]['label'] = 'Total Monthly Amortization';
        $obj[0][$this->gridname]['columns'][$amortization]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$principal]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$amortization]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$bal]['label'] = 'Ending Balance';
        $obj[0][$this->gridname]['columns'][$payment]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$interest]['align'] = 'text-left';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['ledocno'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'ar');
        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select '' as ledocno ,0 as trno,0.0 as db, 0.0 as cr,0.0 as bal");
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];
        $trno = $config['params']['dataparams']['trno'];

        $qry = "select sum(principal + processfee + interest) as amortization,format(principal,2) as principal,format(processfee,2) as processfee,
               format(interest,2) as interest,format(payment,2) as payment,amount,date_format(dateid, '%M %e, %Y') as stat,left(dateid,10) as dateid from (
               select format(tinfo.pfnf,2) as processfee,
               tinfo.interest as interest ,
               tinfo.principal as principal,
               tinfo.payment as payment,
               info.amount,tinfo.dateid
               from htempdetailinfo as tinfo
               left join heainfo as info on info.trno = tinfo.trno
               where info.trno = $trno
               )as v group by principal,processfee,interest,payment,amount,dateid order by dateid";

        $data = $this->coreFunctions->opentable($qry);

        $runningbal = 0;
        $totalending = 0;
        $actualpay = 0;

        $data2 = [];
        $gettotal = $this->getbegbal($trno);
        foreach ($data as $index => $value) {
            $value->amortization = floatval(str_replace(',', '', $value->amortization));
            $value->payment = floatval(str_replace(',', '', $value->payment));
            $gettotal[0]->amount = floatval(str_replace(',', '', $gettotal[0]->amount));
            $gettotal[0]->processfee = floatval(str_replace(',', '', $gettotal[0]->processfee));
            $gettotal[0]->interest = floatval(str_replace(',', '', $gettotal[0]->interest));

            $cvtrno = $this->coreFunctions->getfieldvalue("transnum", "cvtrno", "trno=?", [$trno]);
            $actualpay = $this->coreFunctions->datareader("select sum(cr-db) as value from (select cr,db from ladetail where refx = " . $cvtrno . " and left(postdate,10) ='" . $value->dateid . "' union all 
            select cr,db from gldetail where refx = " . $cvtrno . " and left(postdate,10) ='" . $value->dateid . "') as a", [], '', true);

            if ($index == 0) {
                $runningbal = ($gettotal[0]->amount + $gettotal[0]->processfee + $gettotal[0]->interest);
                $totalending = $runningbal - $value->amortization;
            } else {
                $runningbal = $totalending;
                $totalending = $runningbal - $actualpay;
            }
            array_push($data2, array(
                'stat' => $value->stat,
                'balance' => (string) number_format($runningbal, 2),
                'processfee' => $value->processfee,
                'principal' => $value->principal,
                'interest' => $value->interest,
                'amortization' => (string) $value->amortization,
                'bal' => (string) number_format($totalending, 2),
                'payment' => (string) number_format($actualpay, 2),
                'db' => '0.0',
                'cr' => '0.0'
            ));
        }
        $data2 = json_decode(json_encode($data2), false);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' =>  $data2, 'qry' => $qry];
    }
    public function getbegbal($trno)
    {
        $query = "select format(sum(tinfo.pfnf),2) as processfee,
             format(sum(tinfo.interest),2) as interest ,
             format(sum(tinfo.principal),2) as principal,sum(info.amount / substring_index(head.terms, ' ', 1)) as amount
             from htempdetailinfo as tinfo
             left join heainfo as info on info.trno = tinfo.trno
             left join heahead as head on head.trno = info.trno     
             where tinfo.trno = $trno ";
        return $this->coreFunctions->opentable($query);
    }
} //end class
