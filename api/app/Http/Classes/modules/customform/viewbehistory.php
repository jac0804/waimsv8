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

class viewbehistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BOUNCED CHEQUE HISTORY';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;height:100%;max-height:100%;';
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
        $this->modulename = 'BOUNCED CHEQUE HISTORY - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);

        $cols = ['docno', 'dateid', 'agentname', 'yourref', 'ourref', 'checkdate',  'bank', 'branch', 'checkno', 'amount', 'prref', 'ref', 'notes'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $cols
            ]
        ];

        $stockbuttons = [];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['totalfield'] = [];
        $companyid = $config['params']['companyid'];

        // $obj[0][$this->gridname]['columns'][$bal]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$agentname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$branch]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'text-align: right;width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$amount]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$prref]['label'] = 'Reference(Check / Cash)';
        $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Reference(Replacement)';

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
        data_set($col2, 'refresh.action', 'pdc');


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid');
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
        $companyid = $config['params']['companyid'];

        $rehead = "'' as ref";
        $hrehead = "(select docno from lahead as re where re.trno=p.retrno
               union all select docno from glhead as re where re.trno=p.retrno) as ref";

        $qry = "select head.docno, head.dateid, agent.clientname as agentname, head.yourref, head.ourref,
        date(p.checkdate) as checkdate, p.bank, p.branch, p.checkno, p.amount, rc.docno as prref,  " . $rehead  . ", head.rem as notes
        from lahead as head
        left join client as agent on agent.client = head.agent
        left join particulars as p on p.trno = head.trno
        left join client as customer on customer.clientid = p.clientid
        left join lahead as rc on rc.trno = p.rctrno
        left join cntnum on cntnum.trno = head.trno
        where head.doc = 'BE'  and customer.clientid= '$clientid' and head.dateid>='$date' and cntnum.center = '$center' and p.line is not null

        union all

        select head.docno, head.dateid, agent.clientname as agentname, head.yourref, head.ourref,
        date(p.checkdate) as checkdate, p.bank, p.branch, p.checkno, p.amount,rc.docno as prref,  " . $hrehead  . ", head.rem as notes
        from glhead as head
        left join client as agent on agent.clientid = head.agentid
        left join hparticulars as p on p.trno = head.trno
        left join client as customer on customer.clientid = p.clientid
        left join glhead as rc on rc.trno = p.rctrno
        left join cntnum on cntnum.trno = head.trno
        where head.doc = 'BE'  and customer.clientid= '$clientid' and head.dateid>='$date' and cntnum.center = '$center' and p.line is not null";

        // var_dump($qry);
        $data = $this->coreFunctions->opentable($qry);

        $profile = ['doc' => 'ViewRcHistory', 'psection' => 'StartDate', 'pvalue' => $date, 'puser' => $config['params']['user']];
        $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='ViewRcHistory' and psection='StartDate' and puser=?", [$config['params']['user']]);
        if ($date == '') {
            $this->coreFunctions->sbcinsert("profile", $profile);
        } else {
            $this->coreFunctions->sbcupdate("profile", $profile, ['doc' => 'ViewRcHistory', 'psection' => 'StartDate', 'puser' => $config['params']['user']]);
        }


        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'qry' => $qry];
    }
} //end class
