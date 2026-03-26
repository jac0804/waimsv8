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

class viewloan
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LOAN HISTORY';
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
        $this->modulename = 'LOAN HISTORY - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);

        $column = ['docno','loantype', 'amount', 'amortization', 'interest', 'pfnf', 'bal'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$amortization]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$interest]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$pfnf]['align'] = 'text-left';
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
        $fields = ['dateid'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.readonly', false);
        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'ar');
        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['bal'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $date = "DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
        return $this->coreFunctions->opentable("select " . $date . " as dateid, 0.0 as db, 0.0 as cr,0.0 as bal");
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];
        $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));

        $qry = "select docno,loantype,format(amount,2) as amount,format(amortization,2) as amortization,cr,db,ifnull(format(sum(interest),2),0) as interest ,ifnull(format(sum(pfnf),2),0) as pfnf,
        ifnull(format(sum(bal),2),0) as bal from (
        select ea.docno,ltype.reqtype as loantype,info.amount,info.amortization,
        (case when coa.alias not in ('AR2','AR3') then sum(detail.db) else 0 end ) as hamount,
        (case when coa.alias = 'AR2' then sum(detail.db) else 0 end ) as interest,
        (case when coa.alias = 'AR3' then sum(detail.db) else 0 end ) as pfnf,ifnull(sum(ar.bal),0) as bal,0.0 as db,0.0 as cr
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid
        left join arledger as ar on ar.trno = detail.trno and ar.line = detail.line
        left join transnum as t on t.cvtrno = head.trno
        left join heahead as ea on ea.trno = t.trno
        left join heainfo as info on info.trno = ea.trno
        left join reqcategory as ltype on ltype.line = ea.planid
        where date(head.dateid) >= '$date' and detail.clientid = $clientid and head.doc = 'CV' and coa.alias in ('AR1','AR2','AR3')
        group by ea.docno,coa.alias,ltype.reqtype,detail.db,info.amount,info.amortization order by head.docno
        ) as v
        group by docno,loantype,amount,amortization,cr,db";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' =>  $data, 'qry' => $qry];
    }
} //end class
