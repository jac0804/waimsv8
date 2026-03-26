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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class viewbreconbankbook
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BANK BOOK';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%px;';
    public $issearchshow = true;
    public $showclosebtn = false;
    public $reporter;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1150'];

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
    }

    public function createTab($config)
    {
        $status = 0;
        $dateid = 1;
        $clearday = 2;
        $postdate = 3;
        $checkno = 4;
        $db = 5;
        $cr = 6;
        $bal = 7;
        $docno = 8;
        $clientname = 9;
        $rem = 10;

        $tab = [
            $this->gridname => ['gridcolumns' => ['status', 'dateid', 'clearday', 'postdate', 'checkno',  'db', 'cr', 'bal', 'docno', 'clientname', 'rem']]
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$cr]['label'] = 'Withdrawal';
        $obj[0][$this->gridname]['columns'][$db]['label'] = 'Deposit';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Transaction Date';
        $obj[0][$this->gridname]['columns'][$postdate]['label'] = 'Check Date';

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
        $fields = ['contra', 'acnoname', ['start', 'end']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'contra.lookupclass', 'BANKBOOK');
        data_set($col1, 'acnoname.readonly', true);

        $fields = ['optiongatherby'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['optionstatus', ['refresh', 'print']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.width', '100%');
        data_set($col3, 'print.width', '100%');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $acno = $config['params']['addedparams'][0];
        if ($acno != '') {
            $acno = '\\' . $acno;
        }
        $acnoname = $config['params']['addedparams'][1];
        $date1 = $config['params']['addedparams'][2];
        $date2 = $config['params']['addedparams'][3];
        $cleardate = $config['params']['addedparams'][4];
        $qry = "select '" . $acno . "' as contra, '" . $acnoname . "' as acnoname,
        '" . $date1 . "' as `start`, '" . $date2 . "' as `end`, '" . $cleardate . "' as cleardate, 0 as gatherby, 1 as `status` ";
        return $this->coreFunctions->opentable($qry);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $acno = $config['params']['dataparams']['contra'];
        if ($acno != '') {
            $acno = '\\' . $acno;
        }
        $acnoname = $config['params']['dataparams']['acnoname'];
        $gatherby = $config['params']['dataparams']['gatherby'];
        $status = $config['params']['dataparams']['status'];

        $start = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
        $end = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);
        $cleardate = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['cleardate']);

        $txtqry = "select '" . $acno . "' as contra, '" . $acnoname . "' as acnoname,
        '" . $start . "' as `start`, '" . $end . "' as `end`, '" . $cleardate . "' as cleardate, " . $gatherby . " as gatherby, " . $status . " as `status` ";
        $txtdata = $this->coreFunctions->opentable($txtqry);

        if ($acno == '') {
            return ['status' => false, 'msg' => 'Select valid account', 'data' => [], 'txtdata' => $txtdata];
        }

        if ($start == '') {
            return ['status' => false, 'msg' => 'Select valid start date', 'data' => [], 'txtdata' => $txtdata];
        }

        if ($end == '') {
            return ['status' => false, 'msg' => 'Select valid end date', 'data' => [], 'txtdata' => $txtdata];
        }

        if ($start > $end) {
            return ['status' => false, 'msg' => 'Invalid date range. Start date must not be greater then end date', 'data' => [], 'txtdata' => $txtdata];
        }

        $data = $this->openBankBook($config);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
    } //end function

    public function openBankBook($config)
    {
        $strfilter = '';
        $sorting = '';
        $center = $config['params']['center'];
        $status = $config['params']['dataparams']['status'];
        $acno = $config['params']['dataparams']['contra'];
        $gatherby = $config['params']['dataparams']['gatherby'];

        $date1 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
        $date2 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        switch ($gatherby) {
            case 0:
                $strfilter = "date(head.dateid)";
                $sorting = "dateid,docno";
                break;
            case 1:
                $strfilter = "date(detail.postdate)";
                $sorting = "postdate";
                break;
            case 2:
                $strfilter = "date(detail.clearday)";
                $sorting = "clearday";
                break;
        }
        $statusfilter = '';
        switch ($status) {
            case 1:
                $statusfilter = " and cntnum.postdate is not null ";
                break;

            case 0:
                $statusfilter = " and cntnum.postdate is null ";
                break;
        }

        $begbalqry = "select 0 as sort,0 as trno,0 as line,'' as status,'Beginning Balance' as docno,'' as dateid,
                            '' as acno,'' as postdate,0 as db,0 as cr,'' as checkno,'' as rem,'' as acnoname,ifnull(sum(db-cr),0) as bal,
                            '' as clientname,'' as clearday from
                    (";

        if ($gatherby == 2) {
            $begbalqry .= "select 'POSTED' as type,1 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'')
                                                as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,
                                                detail.postdate as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno,
                                                if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,
                                                client.client as client,head.clientname as clientname
                                    from (((glhead as head 
                                    left join gldetail as detail on((detail.trno = head.trno))) 
                                    left join coa on((coa.acnoid = detail.acnoid)))
                                    left join client on((client.clientid = detail.clientid))) 
                                    left join cntnum on cntnum.trno=head.trno 
                                    where cntnum.center='" . $center . "' and " . $strfilter . " <'$date1' and 
                                           detail.clearday is not null and coa.acno ='\\" . $acno . "'";
        } else {
            $begbalqry .= "select 'POSTED' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') 
                                                as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,
                                                detail.postdate as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno, 
                                                if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,
                                                client.client as client,head.clientname as clientname
                                    from (((glhead as head 
                                    left join gldetail as detail on((detail.trno = head.trno))) 
                                    left join coa on((coa.acnoid = detail.acnoid)))
                                    left join client on((client.clientid = detail.clientid))) 
                                    left join cntnum on cntnum.trno=head.trno 
                                    where cntnum.center='" . $center . "' and " . $strfilter . "  <'$date1' and coa.acno ='\\" . $acno . "'";
        }

        $begbalqry .= "union all
                        select 'POSTED' as type,2 as sort,0 as trno,0 as line,brecon.dateid as clearday,'Recon' as docno,
                                brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
                                if(brecon.adjust<0,abs(brecon.adjust),0) as db,if(brecon.adjust>0,brecon.adjust,0) as cr,
                                'Recon' as checkno,concat('Recon-' ,
                                date_format(brecon.dateid,'%b %d %Y')) as rem,'' as client, '' as clientname
                        from brecon 
                        left join coa on coa.acno = brecon.acno where (brecon.line <> 2) and 
                                brecon.dateid <'$date1' and coa.acno ='\\" . $acno . "') as brecon
                        union all ";

        $sql = " " . $begbalqry . " select sort,trno,line,brecon.type as status,docno,left(dateid,10) as dateid,
                        brecon.acno,left(postdate,10) as postdate,db,
                        cr,checkno,rem,acnoname,0.00 as bal,clientname,ifnull(date(clearday),'') as clearday from
                        (";

        if ($gatherby == 2) {
            $sql .= "select 'POSTED' as type,1 as sort,detail.trno as trno,detail.line as line,detail.clearday as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,head.clientname as clientname
                    from (((glhead as head left join gldetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and detail.clearday  is not null and coa.acno ='\\" . $acno . "'";
        } else {
            $sql .= "select 'POSTED' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,head.clientname as clientname
                    from (((glhead as head left join gldetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and coa.acno ='\\" . $acno . "'" . $statusfilter . "
                    union all
                    select '' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,head.clientname as clientname
                    from (((lahead as head left join ladetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.client = detail.client))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and coa.acno ='\\" . $acno . "'" . $statusfilter;
        }

        $sql .= "union all
                        select 'POSTED' as type,2 as sort,0 as trno,0 as line,brecon.dateid as clearday,'Recon' as docno,
                                brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
                                brecon.bal as db,0 as cr,'Recon' as checkno,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,
                                '' as client, '' as clientname
                        from brecon 
                        left join coa on coa.acno = brecon.acno 
                        where (brecon.line <> 2) and date(brecon.dateid) between '$date1' and '$date2' and coa.acno ='\\" . $acno . "' 
                                and brecon.adjust<>0";

        $sql .= ") as brecon order by $sorting";
        $this->coreFunctions->LogConsole($sql);
        $result = $this->coreFunctions->openTable($sql);
        $data  = [];
        $runningbal = 0;
        foreach ($result as $key => $value) {
            switch ($value->docno) {
                case 'Beginning Balance':
                    $runningbal = $runningbal + $value->bal;
                    $value->bal = number_format($runningbal, 2);
                    break;

                case 'Recon':
                    $runningbal = ($value->db - $value->cr);
                    $value->bal = number_format($value->db - $value->cr, 2);
                    break;

                default:
                    $runningbal = $runningbal + ($value->db - $value->cr);
                    $value->bal = number_format($runningbal, 2);
                    break;
            }

            $value->db = number_format($value->db, 2);
            $value->cr = number_format($value->cr, 2);
            //array_push($result, $value);
        }

        return $result;
    }



    public function reportsetup($config)
    {
        $style = 'width:500px;max-width:500px;';
        $str = $this->default_layout($config);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => $str, 'style' => $style, 'directprint' => true];
    }

    public function default_query($config)
    {
        $strfilter = '';
        $sorting = '';

        $center = $config['params']['center'];
        $status = $config['params']['dataparams']['status'];
        $acno = $config['params']['dataparams']['contra'];
        $gatherby = $config['params']['dataparams']['gatherby'];

        $date1 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
        $date2 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        switch ($gatherby) {
            case 0:
                $strfilter = "date(head.dateid)";
                $sorting = "dateid,docno";
                break;
            case 1:
                $strfilter = "date(detail.postdate)";
                $sorting = "postdate";
                break;
            case 2:
                $strfilter = "date(detail.clearday)";
                $sorting = "clearday";
                break;
        }
        $statusfilter = '';
        switch ($status) {
            case 1:
                $statusfilter = " and cntnum.postdate is not null ";
                break;

            case 0:
                $statusfilter = " and cntnum.postdate is null ";
                break;
        }

        $begbalqry = "select 0 as sort,0 as trno,0 as line,'' as status,'Beginning Balance' as docno,'' as dateid,'' as acno,
                            '' as postdate,0 as db,0 as cr,'' as checkno,'' as rem,'' as acnoname,ifnull(sum(db-cr),0) as bal,
                            '' as clientname,'' as clearday from 
            (";

        if ($gatherby == 2) {
            $begbalqry .= "select 'POSTED' as type,1 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'')
                     as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,
                     detail.db as db,detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) 
                     as rem,client.client as client,head.clientname as clientname from (((glhead as head left join gldetail as detail 
                     on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
            left join client on((client.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno 
            where cntnum.center='" . $center . "' and " . $strfilter . " <'$date1' and detail.clearday is not null and coa.acno ='\\" . $acno . "'";
        } else {
            $begbalqry .= "
            select 'POSTED' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') 
            as clearday,head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate 
            as postdate,detail.db as db,detail.cr as cr, detail.checkno as checkno, if(head.doc in ('DS','GJ'),
             head.rem, detail.rem) as rem,client.client as client,head.clientname as clientname
            from (((glhead as head left join gldetail as detail on((detail.trno = head.trno))) 
            left join coa on((coa.acnoid = detail.acnoid))) left join client on((client.clientid = detail.clientid))) 
            left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " 
             <'$date1' and coa.acno ='\\" . $acno . "'";
        }

        $begbalqry .= "union all
            select 'POSTED' as type,2 as sort,0 as trno,0 as line,brecon.dateid as clearday,'Recon' as docno,
            brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
            if(brecon.adjust<0,abs(brecon.adjust),0) as db,if(brecon.adjust>0,brecon.adjust,0) as cr,'Recon' 
            as checkno,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,'' as client, '' as clientname
            from brecon left join coa on coa.acno = brecon.acno where (brecon.line <> 2) and brecon.dateid <'$date1' and coa.acno ='\\" . $acno . "'
            ) as brecon
            union all ";

        $sql = " " . $begbalqry . "
            select sort,trno,line,brecon.type as status,docno,left(dateid,10) as dateid,brecon.acno,left(postdate,10) as postdate,
            db,cr,checkno,rem,acnoname,0.00 as bal,clientname,ifnull(date(clearday),'') as clearday from
            (";

        if ($gatherby == 2) {
            $sql .= "select 'POSTED' as type,1 as sort,detail.trno as trno,detail.line as line,detail.clearday as clearday,
                    head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,
                    detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,
                    head.clientname as clientname
                    from (((glhead as head left join gldetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and detail.clearday  is not null and coa.acno ='\\" . $acno . "'";
        } else {
            $sql .= "select 'POSTED' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') as clearday,
                    head.docno as docno,head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,
                    detail.cr as cr, detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,
                    head.clientname as clientname
                    from (((glhead as head left join gldetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and coa.acno ='\\" . $acno . "'" . $statusfilter . "
                union all
                select '' as type,3 as sort,detail.trno as trno,detail.line as line,ifnull(detail.clearday,'') as clearday,head.docno as docno,
                head.dateid as dateid,coa.acno as acno,coa.acnoname,detail.postdate as postdate,detail.db as db,detail.cr as cr, 
                detail.checkno as checkno,if(head.doc in ('DS','GJ'), head.rem, detail.rem) as rem,client.client as client,head.clientname as clientname
                from (((lahead as head left join ladetail as detail on((detail.trno = head.trno))) left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.client = detail.client))) left join cntnum on cntnum.trno=head.trno where cntnum.center='" . $center . "' and " . $strfilter . " between '$date1' and '$date2' and coa.acno ='\\" . $acno . "'" . $statusfilter;
        }

        $sql .= "union all
            select 'POSTED' as type,2 as sort,0 as trno,0 as line,brecon.dateid as clearday,'Recon' as docno,
            brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
            brecon.bal as db,0 as cr,'Recon' as checkno,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,'' as client,
             '' as clientname
            from brecon left join coa on coa.acno = brecon.acno where (brecon.line <> 2) and date(brecon.dateid) between '$date1' and 
            '$date2' and coa.acno ='\\" . $acno . "' and brecon.adjust<>0";
        $sql .= ") as brecon order by $sorting";

        $data = json_decode(json_encode($this->coreFunctions->opentable($sql)), true);
        return $data;
    }

    public function default_layout($config)
    {
        $data = $this->default_query($config);
        $acno = $config['params']['dataparams']['contra'];
        $acnoname = $config['params']['dataparams']['acnoname'];
        $gatherby = $config['params']['dataparams']['gatherby'];

        $start = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
        $end = date("m/d/Y", strtotime($config['params']['dataparams']['end']));


        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $style = 'width:500px;max-width:500px;';

        $border = "1px solid ";
        $font =  "Century Gothic";
        $fontsize = "11";
        $str = '';

        $str .= $this->reporter->beginreport();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("BANK STATEMENT", '1000', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Date Range: " . $start . " to " . $end, '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $datefield = 'dateid';
        if ($gatherby == 0) {
            $gatherby = 'Transaction Date';
        } elseif ($gatherby == 1) {
            $gatherby = 'Check Date';
            $datefield = 'postdate';
        } else {
            $gatherby = 'Clear Date';
            $datefield = 'clearday';
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Account #:', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($acno, '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('Account Name:', '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($acnoname, '390', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('Gather By:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($gatherby, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($gatherby), '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAYEE', '180', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PARTICULARS', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHECK NO', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPOSIT', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WITHRAWAL', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $acno = $config['params']['dataparams']['contra'];

        if ($acno == "") {
            $acno = "ALL";
        }

        $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
        for ($i = 0; $i < count($data); $i++) {
            $debit = number_format($data[$i]['db'], 2);
            if ($debit < 1) {
                $debit = '-';
            }
            $credit = number_format($data[$i]['cr'], 2);
            if ($credit < 1) {
                $credit = '-';
            }

            $bal = 0;
            if ($data[$i]['docno'] == 'Beginning Balance') {
                $bal = $data[$i]['bal'];
            } else {
                switch ($cat) {
                    case 'L':
                    case 'R':
                    case 'C':
                    case 'O':
                        $bal += ($data[$i]['cr'] - $data[$i]['db']);
                        break;
                    default:
                        $bal += ($data[$i]['db'] - $data[$i]['cr']);
                        break;
                } // end switch
                $data[$i]['bal'] = $bal;
            }


            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i][$datefield], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['docno'], '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['clientname'], '180', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['acnoname'], '250', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data[$i]['checkno'], '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($debit, '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($credit, '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');

            $str .= $this->reporter->col(number_format($data[$i]['bal'], 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->endrow();
        }


        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        for ($i = 0; $i < count($data); $i++) {
            $debit = number_format($data[$i]['db'], 2);
            if ($debit < 1) {
                $debit = '-';
            }
            $credit = number_format($data[$i]['cr'], 2);
            if ($credit < 1) {
                $credit = '-';
            }

            if ($data[$i]['docno'] == 'Beginning Balance') {
                $bal = $data[$i]['bal'];
            } else {
                switch ($cat) {
                    case 'L':
                    case 'R':
                    case 'C':
                    case 'O':
                        $bal += ($data[$i]['cr'] - $data[$i]['db']);
                        break;
                    default:
                        $bal += ($data[$i]['db'] - $data[$i]['cr']);
                        break;
                } // end switch
                $data[$i]['bal'] = $bal;
            }

            $totaldb += $data[$i]['db'];
            $totalcr += $data[$i]['cr'];
            $totalbal = $data[$i]['bal'];
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '100', null, false, '1px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('', '180', null, false, '1px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('', '250', null, false, '1px solid ', 'TB', 'C', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '80', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '80', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '11', '', '', '2px');

        $str .= $this->reporter->col(number_format($totalbal, 2), '80', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '11', '', '', '2px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();


        return $str;
    } //end fn



}
