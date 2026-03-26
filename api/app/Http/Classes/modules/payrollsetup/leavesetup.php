<?php

namespace App\Http\Classes\modules\payrollsetup;

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

class leavesetup
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LEAVE SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'leavesetup';
    public $prefix = 'LS';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [
        'docno', 'dateid', 'empid', 'remarks', 'prdstart', 'prdend', 'days', 'bal', 'acnoid', 'isnopay'
    ];
    // 'remarks','acno','days','bal',
    private $except = ['clientid', 'client', 'acno'];
    private $blnfields = ['isnopay'];
    public $showfilteroption = true;
    public $showfilter = false;
    public $showcreatebtn = true;
    private $reporter;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'With Balance', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Without Balance', 'color' => 'primary']
    ];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 1545,
            'new' => 1541,
            'save' => 1542,
            'delete' => 1543,
            'print' => 1544,
            'edit' => 1728,
            'payrollaccounts' => 1490,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $listdocument = 1;
        $startdate = 2;
        $enddate = 3;
        $listcodename = 4;
        $empcode = 5;
        $empname = 6;
        $adays = 7;
        $bal = 8;

        $getcols = ['action', 'listdocument', 'startdate', 'enddate', 'listcodename', 'empcode', 'empname', 'adays', 'bal'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$listcodename]['label'] = 'Leave Type';

        $cols[$adays]['label'] = 'Entitled';
        $cols[$adays]['align'] = 'text-left';
        $cols[$bal]['align'] = 'text-left';

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$startdate]['style'] = 'text-align:left;width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $cols[$enddate]['style'] = 'text-align:left;width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $cols[$listcodename]['style'] = 'text-align:left;width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $cols[$empcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$empname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$adays]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $cols[$bal]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filteroption = '';
        $option = $config['params']['itemfilter'];
        if ($option == 'draft') {
            $filteroption = " and ls.bal<>0";
        } else {
            $filteroption = " and ls.bal=0";
        }
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['ls.docno', 'e.empid', 'client.client', 'e.emplast', 'e.empfirst', 'e.empmiddle', 'p.codename'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        $qry = "select ls.trno as clientid, ls.docno, date(ls.dateid) as dateid, e.empid, client.client as empcode, CONCAT(emplast,', ',empfirst,' ',empmiddle) AS empname,
        p.codename,ls.days as adays,ls.bal, date(ls.prdstart) as startdate, date(ls.prdend) as enddate
        from leavesetup AS ls left join employee AS e ON e.empid = ls.empid left join paccount as p on p.line = ls.acnoid
        left join client on  client.clientid=e.empid where client.isemployee=1 and ifnull(e.empid,0)<>0 " . $filteroption . " $filtersearch
        order by ls.docno";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        $tab = [
            'jobdesctab' => [
                'action' => 'payrollentry',
                'lookupclass' => 'entryleavesetup',
                'label' => 'LEAVE SETUP'
            ]
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        return [];
    }

    public function createHeadField($config)
    {
        $leavelabel = $this->companysetup->getleavelabel($config['params']);
        $fields = ['client', 'dateid', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.label', 'Docno #');
        data_set($col1, 'client.action', 'lookupledger');
        data_set($col1, 'client.lookupclass', 'lookupleavesetup');

        data_set($col1, 'remarks.type', 'ctextarea');

        $fields = ['empid', 'empcode', 'empname', 'acno', 'acnoname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'empname.action', 'lookupclient');
        data_set($col2, 'empname.lookupclass', 'employee');
        data_set($col2, 'acnoname.action', 'lookuppacno');
        data_set($col2, 'acnoname.lookupclass', 'lookuppacno');
        data_set($col2, 'acnoname.readonly', true);
        data_set($col2, 'acnoname.class', 'csacnoname sbccsreadonly');

        $fields = ['prdstart', 'days'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'days.type', 'cinput');
        data_set($col3, 'days.label', 'Entitled (' . $leavelabel . ')');

        $fields = ['prdend', 'bal', 'isnopay'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'bal.label', 'Remaining (' . $leavelabel . ')');
        data_set($col4, 'bal.readonly', true);
        data_set($col4, 'bal.class', 'csbal sbccsreadonly');

        data_set($col4, 'bal.type', 'cinput');


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['docno'] = '';
        $data[0]['remarks'] = '';
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['prdstart'] = $this->othersClass->getCurrentDate();
        $data[0]['prdend'] = $this->othersClass->getCurrentDate();
        $data[0]['empid'] = '';
        $data[0]['empname'] = '';
        $data[0]['empcode'] = '';
        $data[0]['acno'] = '';
        $data[0]['acnoname'] = '';
        $data[0]['days'] = 0;
        $data[0]['bal'] = 0;
        $data[0]['acnoid'] = 0;
        $data[0]['isnopay'] = '0';

        return $data;
    }


    public function loadheaddata($config)
    {
        $clientid = $config['params']['clientid'];

        $clientid = $this->othersClass->val($clientid);
        if ($clientid == 0) $clientid = $this->getlastclient();

        $qryselect = "select ls.trno as clientid, ls.docno as client, ls.docno, ls.dateid, ls.empid, ls.remarks, ls.prdstart, ls.prdend, 
                    pac.code as acno, concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname, pac.codename as acnoname, client.client as empcode, ls.days, ls.bal,ls.acnoid,
                    ls.isnopay";

        $qry = $qryselect . " from leavesetup as ls
        left join employee as e on ls.empid = e.empid
        left join client on client.clientid = e.empid
        left join paccount as pac on pac.line = ls.acnoid
        where trno = ? ";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
        } else {
            $data['docno'] = $head['client'];
            $head['docno'] = $head['client'];
        }
        $clientid = 0;
        $msg = '';
        $head['acnoid'] = $this->coreFunctions->getfieldvalue("paccount", "line", "code = '" . $head['acno'] . "'");

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        if ($isupdate) {
            $checking = $this->coreFunctions->getfieldvalue("leavetrans", "trno", "trno = '" . $head['clientid'] . "'");
            $checking_status = $this->coreFunctions->getfieldvalue("leavetrans", "status", "trno = '" . $head['clientid'] . "'");

            if (!empty($checking)) {
                if ($checking_status == "A") {
                    unset($data['dateid']);
                    unset($data['empid']);
                    unset($data['days']);
                    unset($data['bal']);
                    unset($data['acnoid']);

                    $msg = "Can't modified, Already Transaction!";
                }
            }

            $data['bal'] = $head['days'];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
            $clientid = $head['clientid'];

            // $this->logger->sbcmasterlog(
            //   $clientid,
            //   $config,
            //   'UPDATE' . ' - ' .$head['client'].' - '.$head['empcode'].' - '.$head['empname']. '- ' .'ENTITLED: '. $data['days']. ' - ' . 'BAL: '.$data['bal']); 
        } else {
            $data['bal'] = $head['days'];
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);

            $this->logger->sbcmasterlog(
                $clientid,
                $config,
                'CREATE' . ' - ' . $head['client'] . ' - ' . $head['empcode'] . ' - ' . $head['empname'] . '- ' . 'ENTITLED: ' . $data['days'] . ' - ' . 'BAL: ' . $data['bal']
            );
        }
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function getlastclient()
    {
        $last_id = $this->coreFunctions->datareader("select trno as value 
        from " . $this->head . " 
        order by trno DESC LIMIT 1");

        return $last_id;
    }



    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];

        $qry = "select refno as value from Leavetrans where refno=?";
        $count = $this->coreFunctions->datareader($qry, [$clientid]);

        if ($count != '') {
            return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
        }

        $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
        return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function reportsetup($config)
    {
        // $txtfield = $this->createreportfilter();
        // $txtdata = $this->reportparamsdata($config);

        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        // $data = $this->report_default_query($config['params']['dataid']);
        // $str = $this->reportplotting($config, $data);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    // -> print function
    // public function reportsetup($config)
    // {
    //     $txtfield = $this->createreportfilter();
    //     $txtdata = $this->reportparamsdata($config);
    //     $modulename = $this->modulename;
    //     $data = [];
    //     $style = 'width:500px;max-width:500px;';
    //     return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    // }

    // public function reportdata($config)
    // {
    //     $data = $this->report_default_query($config);
    //     $str = $this->rpt_leavesetup_masterfile_layout($data, $config);
    //     return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    // }

    // public function createreportfilter()
    // {
    //     $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    //     $col1 = $this->fieldClass->create($fields);
    //     return array('col1' => $col1);
    // }

    // public function reportparamsdata($config)
    // {
    //     return $this->coreFunctions->opentable(
    //         "select 
    //     'default' as print,
    //     '' as prepared,
    //     '' as approved,
    //     '' as received
    //     "
    //     );
    // }

    // private function report_default_query($config)
    // {
    //     $trno = $config['params']['dataid'];
    //     $query = "select ls.empid, ls.docno, date(ls.dateid) as headdateid, ls.remarks,
    //             concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
    //             date(lt.dateid) as leavedate, lt.daytype, lt.batch,
    //             pac.code as acno, pac.codename as acnoname, ls.bal, ls.days,
    //             date(ls.prdstart) as prdstart, date(ls.prdend) as prdend,
    //             lt.adays as leavehours, lt.remarks as leaverem,
    //             case 
    //               when lt.status = 'A' then 'APPROVED'
    //               when lt.status = 'E' then 'ENTRY'
    //               when lt.status = 'O' then 'ON-HOLD'
    //               when lt.status = 'P' then 'PROCESSED'
    //             end as leavestatus
    //             from leavesetup as ls
    //             left join leavetrans as lt on ls.trno = lt.trno
    //             left join employee as e on ls.empid = e.empid
    //             left join paccount as pac on pac.line = ls.acnoid
    //             where ls.trno = $trno
    //             order by lt.dateid";

    //     $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    //     return $result;
    // } //end fn


    // private function rpt_default_header($data, $filters)
    // {
    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];

    //     $str = '';
    //     $layoutsize = '1000';
    //     $font = "Century Gothic";
    //     $fontsize = "11";
    //     $border = "1px solid ";
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->letterhead($center, $username);
    //     $str .= $this->reporter->endtable();
    //     $str .= '<br/><br/>';

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('LEAVE SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //     $str .= '<br/>';

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Employee Name: ' . $data[0]['empname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= '<br/>';
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Document No.', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Account No.', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Account Name', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Entitled (Hours)', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Remaining (Hours)', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Period Start', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     $str .= $this->reporter->col('Period End', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    //     // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    //     $str .= $this->reporter->endrow();
    //     return $str;
    // }

    // private function rpt_leavesetup_masterfile_layout($data, $filters)
    // {
    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];

    //     $str = '';
    //     $layoutsize = '1000';
    //     $font = "Century Gothic";
    //     $fontsize = "11";
    //     $border = "1px solid ";
    //     $count = 35;
    //     $page = 35;

    //     $str .= $this->reporter->beginreport();
    //     $str .= $this->rpt_default_header($data, $filters);
    //     $totalext = 0;
    //     // for($i=0;$i<count($data);$i++){
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->addline();
    //     $str .= $this->reporter->col($data[0]['docno'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col($data[0]['acno'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col($data[0]['acnoname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col(number_format($data[0]['days'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col(number_format($data[0]['bal'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col($data[0]['prdstart'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->col($data[0]['prdend'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    //     $str .= $this->reporter->endrow();

    //     // if($this->reporter->linecounter==$page){
    //     //     $str .= $this->reporter->endtable();
    //     //     $str .= $this->reporter->page_break();
    //     //     $str .= $this->rpt_default_header($data,$filters);
    //     //     $str .= $this->reporter->printline();
    //     //     $page=$page + $count;
    //     //     }
    //     // }   

    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->printline();

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Applied Leaves: ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('Date Applied', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('No of Hours', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('Remarks', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('Status', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    //     $str .= $this->reporter->endrow();
    //     // for ($i=0; $i < count($data); $i++) { 
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col($data[0]['leavedate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col(number_format($data[0]['leavehours'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col($data[0]['leaverem'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col($data[0]['leavestatus'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->endrow();
    //     // }
    //     $str .= $this->reporter->endtable();

    //     $str .=  '<br/>';
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .=  '<br/>';
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->endreport();
    //     return $str;
    // } //end fn



} //end class
