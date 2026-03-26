<?php

namespace App\Http\Classes\modules\accounting;

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
use App\Http\Classes\builder\helpClass;

class bankrecon
{


    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Bank Reconciliation';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $tablelogs = 'table_log';
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2368,
            'save' => 2369,
            'print' => 3623
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'others'
        );
        $buttons = $this->btnClass->create($btns);

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];

            return $buttons;
        }
    }
    public function createTab($config)
    {
        $isselected = 0;
        $status = 1;
        $dateid = 2;
        $clearday = 3;
        $postdate = 4;
        $checkno = 5;
        $db = 6;
        $cr = 7;
        $bal = 8;
        $docno = 9;
        $clientname = 10;
        $rem = 11;
        $center = 12;

        $fields = ['lblpassbook', 'begbal', 'endbal', 'lblreconcile', 'clearbal', 'difference'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'begbal.readonly', true);

        $fields = ['lblearned', 'interest', 'deduction', 'lblcleared', 'deposit', 'withdrawal'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'deposit.readonly', true);
        data_set($col2, 'withdrawal.readonly', true);

        $fields = ['reconcile'];
        $col3 = $this->fieldClass->create($fields);

        $tab = [
            $this->gridname => ['gridcolumns' => ['isselected', 'status', 'dateid', 'clearday', 'postdate', 'checkno',  'db', 'cr', 'bal', 'docno', 'clientname', 'rem', 'center']],
            'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3], 'label' => 'ADJUSTING and BALANCE'],
        ];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['label'] = 'LIST';
        $obj[0][$this->gridname]['descriptionrow'] = [];

        $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clearday]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$postdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$checkno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$db]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$cr]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$center]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$cr]['label'] = 'Withdrawal';
        $obj[0][$this->gridname]['columns'][$db]['label'] = 'Deposit';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Transaction Date';
        $obj[0][$this->gridname]['columns'][$postdate]['label'] = 'Check Date';

        $obj[0][$this->gridname]['columns'][$isselected]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';

        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$center]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

        return $obj;
    }

    public function createtab2($access, $config)
    {


        $reconsummary = ['customform' => [
            'action' => 'customform',
            'lookupclass' => 'viewbreconbanksummary',
            'addedparams' => ['contra', 'acnoname', 'start', 'end'],
            'totalfield' => []
        ]];

        $bankbook = ['customform' => [
            'action' => 'customform',
            'lookupclass' => 'viewbreconbankbook',
            'addedparams' => ['contra', 'acnoname', 'start', 'end', 'cleardate']
        ]];

        $return = [];
        $return['BANK RECON. SUMMARY'] = ['icon' => 'fa fa-envelope', 'customform' => $reconsummary];
        $return['BANK BOOK'] = ['icon' => 'fa fa-envelope', 'customform' => $bankbook];
        return $return;
    }

    public function tabAdjust($config)
    {

        $fields = ['begbal', 'endbal', 'clearbal', 'difference', 'reconcile'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'begbal.readonly', true);

        $fields = ['interest', 'deduction', 'deposit', 'withdrawal'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'deposit.readonly', true);
        data_set($col2, 'withdrawal.readonly', true);

        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2], 'label' => 'ADJUSTING and BALANCE']
        ];
        $obj = $this->tabClass->createtab($tab, []);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "APPLY CLEAR DATE";
        $obj[0]['access'] = "save";
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['contra', 'acnoname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'contra.lookupclass', 'CB');
        data_set($col1, 'acnoname.readonly', true);

        $fields = [['start', 'end'], 'cleardate'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.action', 'load');

        $fields = [['lblrecondate', 'recondate'], ['lblendingbal', 'endingbal'], ['lblunclear', 'unclear']];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        $data = $this->coreFunctions->opentable("
      select '' as contra, '' as acnoname, curdate() as start, curdate() as end, null as cleardate,
      0.00 as begbal, 0.00 as endbal, 0.00 as clearbal, 0.00 as difference, 0.00 as reconcile,
      0.00 as interest, 0.00 as deduction, 0.00 as deposit, 0.00 as withdrawal,
      date_format(curdate(),'%m/%d/%Y') as recondate, 0.00 as endingbal, 0.00 as unclear
    ");
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
        $action = $config['params']["action2"];
        switch ($action) {
            case "load":
                return $this->loaddetails($config);
                break;

            case 'saveallentry':
                $result = $this->applycleardate($config);
                if ($result["status"]) {
                    return $this->loaddetails($config);
                } else {
                    return $result;
                }
                break;

            case 'reconcile':
                $result = $this->reconcile($config);
                if ($result["status"]) {
                    return $this->loaddetails($config);
                } else {
                    return $result;
                }
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $action . ' is not yet setup in the headtablestatus.'];
                break;
        }
    }

    private function loaddetails($config)
    {
        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];
        $clearday = $config['params']['dataparams']['cleardate'];
        $acno = $config['params']['dataparams']['contra'];

        if ($date1 == null) {
            return ['status' => false, 'msg' => 'Invalid start date', 'action' => 'load', 'griddata' => []];
        }

        if ($date2 == null) {
            return ['status' => false, 'msg' => 'Invalid end date', 'action' => 'load', 'griddata' => []];
        }

        if ($acno == null || $acno === '') {
            return ['status' => false, 'msg' => 'Invalid account', 'action' => 'load', 'griddata' => []];
        }

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);
        $clearday = $this->othersClass->sbcdateformat($clearday);

        $qry = "
        select 'false' as isselected, sort,trno,line,brecon.type,docno,left(dateid,10) as dateid,brecon.acno,date(postdate) as postdate,
        db,cr,checkno,rem,acnoname,0.00 as bal,clientname,clearday,status,center,'' as bgcolor from
        (
            select 'p' as type,1 as sort,gldetail.trno as trno,gldetail.line as line,left(ifnull(gldetail.clearday,''),10)  as clearday,
            glhead.docno as docno,glhead.dateid as dateid,coa.acno as acno,coa.acnoname,gldetail.postdate as postdate,
            gldetail.db as db,gldetail.cr as cr, gldetail.checkno as checkno,
            if(glhead.doc = 'DS', glhead.rem, gldetail.rem) as rem ,client.client as client,glhead.clientname as clientname,'POSTED' as status, concat(center.name,' - ',cntnum.center) as center
            from glhead left join gldetail on gldetail.trno = glhead.trno
            left join coa on coa.acnoid = gldetail.acnoid
            left join client on client.clientid = gldetail.clientid
            left join cntnum on cntnum.trno = glhead.trno
            left join center on center.code = cntnum.center
            where glhead.doc in ('ds','cv','cr','gj','ar','ap', 'pv')
            union all
            select 'u' as type,3 as sort,ladetail.trno as trno,ladetail.line as line,left(ifnull(ladetail.clearday,''),10)  as clearday,
            lahead.docno as docno,lahead.dateid as dateid,coa.acno as acno,coa.acnoname,ladetail.postdate as postdate,
            ladetail.db as db,ladetail.cr as cr, ladetail.checkno as checkno,
            ladetail.rem ,client.client as client,lahead.clientname as clientname,'' as status, concat(center.name,' - ',cntnum.center) as center
            from lahead left join ladetail on ladetail.trno = lahead.trno
            left join coa on coa.acnoid = ladetail.acnoid
            left join client on client.client = ladetail.client
            left join cntnum on cntnum.trno = ladetail.trno
            left join center on center.code = cntnum.center
            where lahead.doc in ('ds','cv','cr','gj','ar','ap', 'pv')
            union all
            select 'p' as type,2 as sort,0 as trno,0 as line,left(ifnull(brecon.dateid,''),10) as clearday,'Recon' as docno,
            brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
            brecon.bal as db,0 as cr,'Recon' as checkno,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,'' as client, '' as clientname,'' as status, '' as center
            from brecon left join coa on coa.acno = brecon.acno where (brecon.line <> 2)
        ) as brecon
        where date(postdate) between '$date1' and '$date2' and acno='\\" . $acno . "' and (clearday ='" . $clearday . "' or ifnull(clearday,'') ='') order by dateid";

        $data = $this->coreFunctions->opentable($qry);

        $result  = [];
        $runningbal = 0;
        foreach ($data as $key => $value) {
            $runningbal = $runningbal + ($value->db - $value->cr);
            $value->bal = number_format($runningbal, 2);
            $value->db = number_format($value->db, 2);
            $value->cr = number_format($value->cr, 2);
            array_push($result, $value);
        }


        if ($this->getfromBrecon2($acno, 'acno') == '') {
            $this->insertBRecon($acno, $date2, 0, 0, 2);
        }

        $config['params']['dataparams'] = $this->getViewData($config);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $result], 'data' => $config['params']['dataparams']];
    }

    private function getViewData($config)
    {

        $acno = $config['params']['dataparams']['contra'];
        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];
        $clearday = $config['params']['dataparams']['cleardate'];

        $interest = $config['params']['dataparams']['interest'];
        $deductions = $config['params']['dataparams']['deduction'];
        $endbal =  $config['params']['dataparams']['endbal'];

        $interest = $this->othersClass->sanitizekeyfield('amt', $interest);
        $deductions = $this->othersClass->sanitizekeyfield('amt', $deductions);
        $endbal = $this->othersClass->sanitizekeyfield('amt', $endbal);

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);
        $clearday = $this->othersClass->sbcdateformat($clearday);

        $brdate = $this->getfromBrecon2($acno, 'dateid');
        if ($brdate === '') {
            $brdate = $date1;
        }

        $bal = 0.00;
        $bal = $this->getfromBrecon($acno, $brdate, 'begbal');
        if ($bal == '') {
            $bal = 0.00;
        }

        $unclear = $this->getunclear($acno, $date2);

        $begbal = $bal;

        $newsdate =  $brdate;
        if ($newsdate > $date2) {
            $date = date_create($newsdate);
            date_add($date, date_interval_create_from_date_string("1 days"));
            $newedate = date_format($date, "Y-m-d");
        } else {
            $newedate =  $date2;
        }

        $arr = $this->openDepwithdraw($newsdate, $date2, $clearday, $acno);

        $deposit = 0.00;
        $withdraw = 0.00;

        if ($arr) {
            $deposit = $arr[0]->dep;
            $withdraw = $arr[0]->withdraw;
        }

        $clearedbal = ($begbal + $interest + $deposit) - ($deductions + $withdraw);
        $difference = $clearedbal - $endbal;

        $config['params']['dataparams']['begbal'] = number_format($begbal, 2);
        $config['params']['dataparams']['endbal'] = number_format($endbal, 2);
        $config['params']['dataparams']['interest'] = number_format($interest, 2);
        $config['params']['dataparams']['deductions'] = number_format($deductions, 2);
        $config['params']['dataparams']['clearbal'] = number_format($clearedbal, 2);
        $config['params']['dataparams']['difference'] = number_format($difference, 2);
        $config['params']['dataparams']['deposit'] = number_format($deposit, 2);
        $config['params']['dataparams']['withdrawal'] = number_format($withdraw, 2);
        $config['params']['dataparams']['recondate'] = $this->othersClass->sbcdateformat($brdate, "/");
        $config['params']['dataparams']['endingbal'] = number_format(0, 2);
        $config['params']['dataparams']['unclear'] = number_format($unclear, 2);
        $config['params']['dataparams']['start'] = $this->othersClass->sbcdateformat($newsdate, "/");
        $config['params']['dataparams']['end'] = $newedate;

        return $config['params']['dataparams'];
    }

    private function applycleardate($config)
    {
        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];
        $clearday = $config['params']['dataparams']['cleardate'];
        $acno = $config['params']['dataparams']['contra'];

        if ($date1 == null) {
            return ['status' => false, 'msg' => 'Invalid start date', 'action' => 'load', 'griddata' => []];
        }

        if ($date2 == null) {
            return ['status' => false, 'msg' => 'Invalid end date', 'action' => 'load', 'griddata' => []];
        }

        if ($clearday == null) {
            return ['status' => false, 'msg' => 'Invalid clear date', 'action' => 'load', 'griddata' => []];
        }

        if ($acno == null || $acno === '') {
            return ['status' => false, 'msg' => 'Invalid account', 'action' => 'load', 'griddata' => []];
        }

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        $rows = $config['params']['rows'];

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Please select accounts that you want to clear'];
        } else {
            foreach ($rows as $key => $val) {
                $data = [];

                if ($val["isselected"] == "true") {
                    if ($val["status"] == 'POSTED' && $val["clearday"] == null) {
                        $data['clearday'] = $clearday;
                        $this->coreFunctions->sbcupdate("gldetail", $data, ['trno' => $val["trno"], 'line' => $val["line"]]);
                        $this->logger->sbcwritelog($val["trno"], $config, 'RECON', $val['docno'] . ', Account: ' . $val['acnoname'] . ', Check#: ' . $val['checkno'] . ', Clear Date: ' . $clearday);
                    }
                }
            }
        }

        return ['status' => true, 'msg' => ''];
    }

    private function reconcile($config)
    {
        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];

        $clearday = $config['params']['dataparams']['cleardate'];
        $acno = $config['params']['dataparams']['contra'];

        $diff = $config['params']['dataparams']['difference'];
        $endbal = $config['params']['dataparams']['endbal'];

        $diff = $this->othersClass->sanitizekeyfield('amt', $diff);
        $endbal = $this->othersClass->sanitizekeyfield('amt', $endbal);

        $clearday = $this->othersClass->sbcdateformat($clearday);
        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        if ($date1 == null) {
            return ['status' => false, 'msg' => 'Invalid start date', 'action' => 'load', 'griddata' => []];
        }

        if ($date2 == null) {
            return ['status' => false, 'msg' => 'Invalid end date', 'action' => 'load', 'griddata' => []];
        }

        if ($clearday == null) {
            return ['status' => false, 'msg' => 'Invalid clear date', 'action' => 'load', 'griddata' => []];
        }

        if ($acno == null || $acno === '') {
            return ['status' => false, 'msg' => 'Invalid account', 'action' => 'load', 'griddata' => []];
        }

        if ($diff == 0) {
            $ac = $this->getfromBrecon($acno, $date2, 'acno');
            if ($ac == '') {
                $this->insertBRecon($acno, $date2, $endbal, $diff, '3');
            } else {
                $this->insertBRecon($acno, $date2, $endbal, $diff, '4');
            }
        }

        $sdate = $this->getfromBrecon2($acno, 'dateid');

        $blnreconcile = false;
        if ($sdate != '') {
            if ($date2 > $sdate) {
                $blnreconcile = true;
            }
        } else {
            $blnreconcile = true;
        }

        if ($blnreconcile) {
            $brprev = strlen($this->getfromBrecon($acno, $date2, 'bal')) == 0 ? 0 : $this->getfromBrecon($acno, $date2, 'bal');

            if ($brprev == 0) {
                $this->insertBRecon($acno, $date2, $endbal, $diff, '3');
            } else {
                $this->insertBRecon($acno, $date2, $endbal, $diff, '5');
            }

            $this->logger->sbcwritelog(0, $config, 'RECON', 'RECONCILE DATE: ' . $date2);
        }

        return ['status' => true, 'msg' => 'Reconcile finished'];
    }

    private function getfromBrecon($acno, $clearday, $field)
    {

        if ($field == 'begbal') {
            $sql = "select ifnull(bal,'') as value from brecon where line=3 and acno='\\" . $acno . "' and date(dateid)<'$clearday' order by dateid desc limit 1";
        } else {
            $sql = "select ifnull($field,'') as value from brecon where line=3 and acno='\\" . $acno . "' and date(dateid)='$clearday'";
        }
        return $this->coreFunctions->datareader($sql);
    }

    public function getfromBrecon2($acno, $field = '')
    {
        $sql = "select ifnull($field,'') as value from brecon where line=2 and acno='\\" . $acno . "'";
        return $this->coreFunctions->datareader($sql);
    }

    public function insertBRecon($acno, $dateid, $bal = 0, $adjust = 0, $mode = '1')
    {
        if ($acno != "") {

            switch ($mode) {
                case '1':
                    break;

                case '2':
                    return $this->coreFunctions->execqry("insert into brecon(line,acno,bal,adjust)values('2','\\" . $acno . "',0,0)");
                    break;

                case '3':
                    $this->coreFunctions->execqry("insert into brecon(line,dateid,acno,bal,adjust)values('$mode','$dateid','\\" . $acno . "',$bal,$adjust)");
                    return $this->coreFunctions->execqry("update brecon set dateid = '$dateid' where line =2 and acno = '\\" . $acno . "'");
                    break;

                case '4':
                    $this->coreFunctions->execqry("update brecon set bal = $bal,adjust=$adjust where line = 3 and date(dateid) = '$dateid' and acno = '\\" . $acno . "'");
                    return $this->coreFunctions->execqry("update brecon set dateid = '$dateid' where line =2 and acno = '\\" . $acno . "'");
                    break;

                case '5':
                    $this->coreFunctions->execqry("update brecon set bal = $bal where line = 3 and date(dateid) = '$dateid' and acno = '\\" . $acno . "'");
                    return $this->coreFunctions->execqry("update brecon set dateid = '$dateid' where line =2 and acno = '\\" . $acno . "'");
                    break;
            }
        }
    }

    public function getUnclear($acno, $date)
    {
        $sql = "select round(ifnull(sum(db)-sum(cr),0),2) as value from(
        select sort,trno,line,brecon.type,docno,left(dateid,10) as dateid,brecon.acno,left(postdate,10) as postdate,db,cr,checkno,rem,acnoname,0.00 as bal,clientname,clearday from
        (select 'p' as `type`,1 as `sort`,`gldetail`.`trno` as `trno`,`gldetail`.`line` as `line`,`gldetail`.`clearday` as `clearday`,
         `glhead`.`docno` as `docno`,`glhead`.`dateid` as `dateid`,`coa`.`acno` as `acno`,coa.acnoname,`gldetail`.`postdate` as `postdate`,
         `gldetail`.`db` as `db`,`gldetail`.`cr` as `cr`, `gldetail`.`checkno` as `checkno`,`gldetail`.`rem` as `rem`,`client`.`client` as `client`,`glhead`.`clientname` as `clientname`
         from (((`glhead` left join `gldetail` on((`gldetail`.`trno` = `glhead`.`trno`))) left join `coa` on((`coa`.`acnoid` = `gldetail`.`acnoid`)))
         left join `client` on((`client`.`clientid` = `gldetail`.`clientid`)))  where (`glhead`.`doc` in ('ds','cv','cr','gj','ar','ap'))
         union all
         select 'p' as `type`,2 as `sort`,0 as `trno`,0 as `line`,`brecon`.`dateid` as `clearday`,'Recon' as `docno`,
        `brecon`.`dateid` as `dateid`,`brecon`.`acno` as `acno`,coa.acnoname,`brecon`.`dateid` as `postdate`,
        `brecon`.`bal` as `db`,0 as `cr`,'Recon' as `checkno`,concat('Recon-' , date_format(`brecon`.`dateid`,'%b %d %Y')) as `rem`,'' as `client`, '' as `clientname`
        from `brecon` left join coa on coa.acno = brecon.acno where (`brecon`.`line` <> 2)) as brecon
        ) as A where acno='\\" . $acno . "' and clearday is null and date(postdate) <='$date'";

        return $this->coreFunctions->datareader($sql);
    }

    public function openDepwithdraw($date1, $date2, $clearday, $acno)
    {

        $sql = "select round(ifnull(sum(db),0),2) as dep, round(ifnull(sum(cr),0),2) as withdraw from(
        select sort,trno,line,brecon.type,docno,left(dateid,10) as dateid,brecon.acno,left(postdate,10) as postdate,db,cr,checkno,rem,acnoname,0.00 as bal,clientname,clearday from
        (select 'p' as `type`,1 as `sort`,`gldetail`.`trno` as `trno`,`gldetail`.`line` as `line`,`gldetail`.`clearday` as `clearday`,
         `glhead`.`docno` as `docno`,`glhead`.`dateid` as `dateid`,`coa`.`acno` as `acno`,coa.acnoname,`gldetail`.`postdate` as `postdate`,
         `gldetail`.`db` as `db`,`gldetail`.`cr` as `cr`, `gldetail`.`checkno` as `checkno`,`gldetail`.`rem` as `rem`,`client`.`client` as `client`,`glhead`.`clientname` as `clientname`
         from (((`glhead` left join `gldetail` on((`gldetail`.`trno` = `glhead`.`trno`))) left join `coa` on((`coa`.`acnoid` = `gldetail`.`acnoid`)))
         left join `client` on((`client`.`clientid` = `gldetail`.`clientid`)))  where (`glhead`.`doc` in ('ds','cv','cr','gj'))
         union all
         select 'p' as `type`,2 as `sort`,0 as `trno`,0 as `line`,`brecon`.`dateid` as `clearday`,'Recon' as `docno`,
        `brecon`.`dateid` as `dateid`,`brecon`.`acno` as `acno`,coa.acnoname,`brecon`.`dateid` as `postdate`,
        `brecon`.`bal` as `db`,0 as `cr`,'Recon' as `checkno`,concat('Recon-' , date_format(`brecon`.`dateid`,'%b %d %Y')) as `rem`,'' as `client`, '' as `clientname`
        from `brecon` left join coa on coa.acno = brecon.acno where (`brecon`.`line` <> 2) and `brecon`.`dateid` < '$date2') as brecon
        ) as A where acno='\\" . $acno . "' and clearDay is not null and sort<>5 and date(clearday) > '$date1' and date(clearday) <= '$date2'";

        return $this->coreFunctions->opentable($sql);
    }

    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
