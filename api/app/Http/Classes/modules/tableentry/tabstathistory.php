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
use App\Http\Classes\lookup\constructionlookup;

class  tabstathistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STATUS HISTORY';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'headprrem';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = [];
    public $showclosebtn = true;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $logger;
    public $sqlquery;
    public $constructionlookup;

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
        $doc = $config['params']['doc'];

        $rem = 0;
        $status = 1;
        $subcatstatus = 2;
        $requestorstat = 3;
        $deadline = 4;
        $prref = 5;
        $createby = 6;
        $createdate = 7;

        $tab = [$this->gridname => ['gridcolumns' => ['rem', 'stat', 'ref', 'requestorstat', 'deadline', 'prref', 'createby', 'createdate']]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:650px;whiteSpace: normal;min-width:650px;";
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$status]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$subcatstatus]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$deadline]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$createby]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createby]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        

        $obj[0][$this->gridname]['columns'][$deadline]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$subcatstatus]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$requestorstat]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$status]['label'] = 'Status';
        $obj[0][$this->gridname]['columns'][$subcatstatus]['label'] = 'Sub Category Status';
        $obj[0][$this->gridname]['columns'][$createby]['label'] = 'Create By';

        if ($doc == 'CV' || $doc == 'CD' || $doc == 'PO') {
            $obj[0][$this->gridname]['columns'][$status]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$subcatstatus]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$requestorstat]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$deadline]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$prref]['type'] = 'coldel';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
        $doc = $config['params']['doc'];
        $trno = $config['params']['tableid'];

        switch ($doc) {
            case 'CV':
                $qry = 'select rem, createby,createdate from particulars where trno=?
                    union all
                    select rem, createby,createdate from hparticulars where trno=? order by createdate desc';
                $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
                break;
            case 'CD':
            case 'PO':
                $qry = 'select rem, createby,createdate from headrem where trno=?
                    union all
                    select rem, createby,createdate from hheadrem where trno=? order by createdate desc';
                $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
                break;
            default:
                $qry = "select head.trno,head.reqline,head.rem, head.reqstat,stat.status as stat, head.reqstat2, stat2.status as ref, date(head.deadline2) as deadline,
                    head.createby,head.createdate, head.prref, stat3.status as requestorstat
                    from headprrem as head
                    left join trxstatus as stat on stat.line = head.reqstat
                    left join trxstatus as stat2 on stat2.line = head.reqstat2
                    left join trxstatus as stat3 on stat3.line = head.reqstat3
                    where trno = ?
                    order by createdate desc";
                $data = $this->coreFunctions->opentable($qry, [$trno]);
                break;
        }

        return $data;
    }
}
