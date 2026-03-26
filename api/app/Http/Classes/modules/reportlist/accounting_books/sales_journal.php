<?php

namespace App\Http\Classes\modules\reportlist\accounting_books;

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
use PhpParser\Node\Stmt\Switch_;

class sales_journal
{
  public $modulename = 'Sales Journal';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['start', 'end', 'dprojectname', 'dcentername'];

    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'dclientname');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);
    data_set($col2, 'dclientname.lookupclass', 'customers');
    data_set($col2, 'dclientname.label', 'Customers');

    $fields = ['radioreporttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'radioreporttype.options', array(
      ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Sales Summary', 'value' => '1', 'color' => 'orange'],
      ['label' => 'Detailed', 'value' => '2', 'color' => 'orange']
    ));

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable(
          "select 'default' as print,
          adddate(left(now(),10),-360) as start,
          left(now(),10) as end,
          '' as dprojectname,
          '' as projectname,
          '' as projectcode,
          0 as projectid,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
          '0' as reporttype,
          '0' as posttype,
          '' as contra,
          '' as acnoname,
          '' as dacnoname,
          '' as dclientname,
          '' as client,
          '' as clientname"
        );
        break;
      default:
        return $this->coreFunctions->opentable(
          "select 'default' as print,
          adddate(left(now(),10),-360) as start,
          left(now(),10) as end,
          '' as dprojectname,
          '' as projectname,
          '' as projectcode,
          0 as projectid,
          '' as center,
          '' as centername,
          '' as dcentername,
          '0' as reporttype,
          '0' as posttype,
          '' as contra,
          '' as acnoname,
          '' as dacnoname,
          0 as clientid,
          '' as dclientname,
          '' as client,
          '' as clientname"
        );
        break;
    }
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function default_query($filters)
  {
    $center = $filters['params']['dataparams']['center'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $client = $filters['params']['dataparams']['client'];
    $companyid = $filters['params']['companyid'];

    $filter = "";
    if ($center != "") {
      $filter .= "and cntnum.center = '" . $center . "'  ";
    } //end if

    $projectcode = $filters['params']['dataparams']['projectcode'];
    $projectid = $filters['params']['dataparams']['projectid'];
    if ($companyid != 10 && $companyid != 12) { //not afti, not afti usd
      if ($projectcode != "") {
        $filter .= " and head.projectid=" . $projectid;
      }
    }

    $leftjoinclient = 'left join client on head.clientid = client.clientid';
    switch ($companyid) {
      case 6: //mitsukoshi
        $condition = " head.doc in ('SD','SE','SF')";
        break;
      case 10: //afti
      case 12: //afti usd
        $condition = " (head.doc = 'SJ' or head.doc = 'AI') ";
        if ($projectcode != "") {
          $filter .= " and detail.projectid=" . $projectid;
        }
        break;
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $condition = " head.doc in ('SJ','CM','AR')";
        break;
      case 35: //aquamax
        $leftjoinclient = 'left join client on client.clientid = detail.clientid';
        $condition = " head.doc in ('WM','AR') ";
        break;
      default:
        $condition = " head.doc in ('SJ','MJ') "; // default
        break;
    }

    if ($client) {
      $clientid = $filters['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }

    switch ($reporttype) {
      case 0:
        $query = "select 'u' as tr, 'sj' as bk, coa.acno, coa.acnoname as description, 
        sum(detail.db) as debit, sum(detail.cr) as credit
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno)
        left join cntnum on cntnum.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid
        " . $leftjoinclient . "
        where " . $condition . " $filter
        and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "'
        group by coa.acno, coa.acnoname
        order by credit";
        break;

      case 1:
        switch ($companyid) {
          case 10: //ati
          case 12: //afti usd
            $query = "select left(head.dateid,10) as dateid,head.dateid as dateid2, head.docno, head.clientname, head.rem, head.doc,
            case when lower(head.rem ) != 'cancelled' then ifnull(sum(stock.ext),0) else 0 end as amt, head.tax
            from ((glhead as head 
            left join glstock as stock on stock.trno=head.trno)
            left join cntnum on cntnum.trno=head.trno)
            left join client on head.clientid = client.clientid
            where " . $condition . " $filter
            and head.dateid between '" . $startdate . "' and '" . $enddate . "'
            group by head.trno, head.dateid, head.docno, head.clientname, head.rem, head.tax, head.doc
            order by dateid2 , docno";
            break;

          case 32: //3m
            $query = "select left(head.dateid,10) as dateid, head.docno, head.clientname, head.rem,
            case when lower(head.rem ) != 'cancelled' then ifnull(sum(detail.cr),0) else 0 end as amt
            from ((glhead as head 
            left join gldetail as detail on detail.trno=head.trno)
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid
            left join client on head.clientid = client.clientid
            where left(coa.alias,2)='SA' and " . $condition . " $filter
            and head.dateid between '" . $startdate . "' and '" . $enddate . "'
            group by head.trno, head.dateid, head.docno, head.clientname, head.rem
            order by dateid, docno";
            break;

          default:
            $query = "select left(head.dateid,10) as dateid, head.docno, client.clientname, head.rem,
            case when lower(head.rem ) != 'cancelled' then ifnull(sum(detail.db),0) else 0 end as amt
            from ((glhead as head 
            left join gldetail as detail on detail.trno=head.trno)
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid
            " . $leftjoinclient . "
            where " . $condition . " $filter
            and head.dateid between '" . $startdate . "' and '" . $enddate . "' and coa.alias = 'AR1'
            group by head.trno, head.dateid, head.docno, client.clientname, head.rem
            order by dateid, docno";
            break;
        }
        break;

      case 2:
        switch ($companyid) {
          case 48: //seastar
            $query = "select trno,tr,bk,dateid,docno,type,clientname,sum(regdb) as regdb,sum(regcr) as regcr,
                            (case when group_concat(sunacctname,'') <> '' then 'OUTPUT TAX' else '' end) as sunacctname, 
                            sum(sundb) as sundb,sum(suncr) as suncr, postdate,doc
                      from(select trno, tr, bk, dateid, docno, type, clientname,sum(regdb) as regdb, 
                                  sum(regcr) as regcr,sunaccno,sunacctname, sundb, suncr, postdate, doc
                           from (select head.trno, 'p' as tr, 'sj' as bk, left(head.dateid,10) as dateid, 
                                       concat(left(head.docno,3),right(head.docno,5)) as docno, 'regular' as type, client.clientname,
                                  case when left(coa.alias, 2) in ('sa') then ifnull((detail.cr-detail.db), 0) else 0 end as regcr,
                                  case when left(coa.alias, 2) in ('ar') then ifnull((detail.db), 0) else 0 end as regdb,
                                  '' as sunaccno, '' as sunacctname, 0 as sundb, 0 as suncr, date(cntnum.postdate) as postdate, head.doc
                            from ((glhead as head
                            left join gldetail as detail on detail.trno=head.trno)
                            left join coa on coa.acnoid=detail.acnoid)
                            left join cntnum on cntnum.trno=head.trno
                            " . $leftjoinclient . "
                            where " . $condition . "  $filter
                                  and head.dateid between '" . $startdate . "' and '" . $enddate . "' and left(coa.alias, 2) in ('ar','sa')) as c
                      group by trno, tr,bk,type,dateid, docno, clientname,sunaccno,sunacctname,sundb,suncr, postdate, doc
                      UNION ALL
                      select head.trno, 'p' as tr, 'sj' as bk,left(head.dateid,10) as dateid, concat(left(head.docno,3),
                            right(head.docno,5)) as docno, 'regular' as type, client.clientname,
                            0 as regdb, 0 as regcr, ifnull(coa.acno, '') as sunaccno, ifnull(coa.acnoname, '') as sunacctname,
                            ifnull(sum(detail.db), 0) as sundb, ifnull(sum(detail.cr), 0) as suncr, date(cntnum.postdate) as postdate, head.doc
                      from ((glhead as head
                      left join gldetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid)
                      left join cntnum on cntnum.trno=head.trno
                       " . $leftjoinclient . "
                      where " . $condition . " $filter
                              and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' and left(coa.alias, 2) not in ('ar','sa')
                      group by head.trno, head.dateid, head.docno, coa.acno, coa.acnoname, head.rem, client.clientname,
                                      cntnum.postdate, head.doc) as a

                      group by trno, tr, bk, dateid, docno, type, clientname,postdate, doc

                      order by docno";
            break;

          default:
            $query = "select trno, tr, bk, dateid, docno, type, clientname,sum(regdb) as regdb, sum(regcr) as regcr, sunaccno, sunacctname, sundb, suncr, postdate, doc
                      from (
                      select head.trno, 'p' as tr, 'sj' as bk, left(head.dateid,10) as dateid, concat(left(head.docno,3),
                      right(head.docno,5)) as docno, 'regular' as type, client.clientname,
                      case when left(coa.alias, 2) in ('sa') then ifnull((detail.cr-detail.db), 0) else 0 end as regcr, 
                      case when left(coa.alias, 2) in ('ar') then ifnull((detail.db), 0) else 0 end as regdb, 
                      '' as sunaccno, '' as sunacctname, 0 as sundb, 0 as suncr, date(cntnum.postdate) as postdate, head.doc
                      from ((glhead as head left join gldetail as detail on detail.trno=head.trno) left join coa on coa.acnoid=detail.acnoid)
                      left join cntnum on cntnum.trno=head.trno
                      " . $leftjoinclient . "
                      where " . $condition . "  $filter
                      and head.dateid between '" . $startdate . "' and '" . $enddate . "' and left(coa.alias, 2) in ('ar','sa')) as c
                      group by trno, tr,bk,type,dateid, docno, clientname,sunaccno,sunacctname,sundb,suncr, postdate, doc

                      UNION ALL

                      select head.trno, 'p1' as tr, 'sj' as bk,left(head.dateid,10) as dateid, concat(left(head.docno,3),
                      right(head.docno,5)) as docno, 'sundries' as type, client.clientname,
                      0 as regdb, 0 as regcr, ifnull(coa.acno, '') as sunaccno, ifnull(coa.acnoname, '') as sunacctname,
                      ifnull(sum(detail.db), 0) as sundb, ifnull(sum(detail.cr), 0) as suncr, date(cntnum.postdate) as postdate, head.doc
                      from ((glhead as head left join gldetail as detail on detail.trno=head.trno) left join coa on coa.acnoid=detail.acnoid)
                      left join cntnum on cntnum.trno=head.trno
                      " . $leftjoinclient . "
                      where " . $condition . " $filter
                      and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' and left(coa.alias, 2) not in ('ar','sa')
                      group by head.trno, head.dateid, head.docno, coa.acno, coa.acnoname, head.rem, client.clientname, cntnum.postdate, head.doc
                      order by suncr,regcr";
            break;
        }

        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    switch ($reporttype) {
      case 0:
        switch ($companyid) {
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //cbbsi
            $reportdata = $this->MSJOY_SALES_JOURNAL_SUMMARIZED($result, $config);
            break;
          default:
            $reportdata = $this->MSJOY_SALES_JOURNAL_SUMMARIZED($result, $config);
            break;
        }
        break;

      case 1:
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $reportdata = $this->AFTI_SALES_SUMMARY($result, $config);
            break;
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //cbbsi
            $reportdata =  $this->MSJOY_SALES_SUMMARY($result, $config);
            break;
          default:
            $reportdata =  $this->MSJOY_SALES_SUMMARY($result, $config);
            break;
        }
        break;

      case 2:
        switch ($companyid) {
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //cbbsi
            $reportdata = $this->MSJOY_SALES_JOURNAL_DETAILED($result, $config);
            break;
          default:
            $reportdata = $this->MSJOY_SALES_JOURNAL_DETAILED($result, $config);
            break;
        }

        break;
    }
    return $reportdata;
  }

  private function MSJOY_table_cols($layoutsize, $border, $font, $fontsize10, $params)
  {
    $str = '';
    $companyid = $params['params']['companyid'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 0:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;

      case 1:
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '70', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DOC#', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('&nbsp;&nbsp;CLIENTNAME', '300', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;

      case 2:
        $str .= $this->reporter->begintable('800');
        switch ($params['params']['companyid']) {
          case 1: //vitaline
          case 23: //labsol cebu
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            break;
          default:
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            break;
        }
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(' ', '100', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(' ', '175', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(' ', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(' ', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('SUNDRIES', '75', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->col('DATE', '100', null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('POSTED <br> DATE', '75', null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('COSTUMER NAME', '175', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('A/R DEBIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('SALES CREDIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        switch ($params['params']['companyid']) {
          case 1: //vitaline
          case 23: //labsol cebu
            $str .= $this->reporter->col('REBATE', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
            break;
        }
        $str .= $this->reporter->col('ACCOUNT DESCRIPTION', '150', null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DEBIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    } // end switch
    return $str;
  }

  // CURRENTLY FOR UNIHOME/NATHINA START
  private function MSJOY_DEFAULT_HEADER($params)
  {
    $str = '';
    $center = $params['params']['dataparams']['center'];
    $centername = $params['params']['dataparams']['centername'];
    $startdate = $params['params']['dataparams']['start'];
    $enddate = $params['params']['dataparams']['end'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $font = $this->companysetup->getrptfont($params['params']);
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    switch ($params['params']['companyid']) {
      case 17: //unihome
      case 39: //cbbsi
        $project = $params['params']['dataparams']['projectname'];
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    $qry = "select name,address,tel from center where code = '" . $center1 . "'";

    switch ($reporttype) {
      case 0:

       
          $str .= $this->reporter->begintable('800');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->letterhead($center1, $username, $params);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

      
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARIZED SALES JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        }

        switch ($params['params']['companyid']) {
          case 17: //unihome
          case 39: //cbbsi
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }

        $str .= $this->reporter->pagenumber('Page');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        break;

      case 1:

       
         $str .= $this->reporter->begintable('800');
         $str .= $this->reporter->startrow();
         $str .= $this->reporter->letterhead($center1, $username, $params);
         $str .= $this->reporter->endrow();
         $str .= $this->reporter->endtable();
        

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALES JOURNAL SUMMARY', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        }

        switch ($params['params']['companyid']) {
          case 17: //unihome
          case 39: //cbbsi
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }
        $str .= $this->reporter->pagenumber('Page');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        break;

      case 2:

       
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center1, $username, $params);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
      

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DETAILED SALES JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Center: ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        }
        $str .= $this->reporter->col('Transaction: ' . strtoupper($reporttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        switch ($params['params']['companyid']) {
          case 17: // UNIHOME
          case 39: //CBBSI
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
    } // end switch

    return $str;
  } //end fn

  private function MSJOY_SALES_JOURNAL_SUMMARIZED($data, $params)
  {
    $str = '';
    $count = 45;
    $page = 45;

    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_DEFAULT_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, 2);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, 2);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_DEFAULT_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end for each

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function MSJOY_SALES_SUMMARY($data, $params)
  {
    $str = '';
    $count = 61;
    $page = 60;

    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_DEFAULT_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $totalamt = 0;

    foreach ($data as $key => $value) {
      $amt = number_format($value->amt, 2);

      if ($amt == 0) {
        $amt = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->dateid, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('&nbsp;&nbsp;' . $value->clientname, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->rem, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($amt, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt += $value->amt;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_DEFAULT_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function MSJOY_SALES_JOURNAL_DETAILED($data, $params)
  {
    $count = 41;
    $page = 40;
    $str = '';

    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_DEFAULT_HEADER($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalrdb = 0;
    $totalrcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $totalsundb = 0;
    $totalsuncr = 0;
    $totalrebate = 0;
    $rebate = "-";

    foreach ($data as $key => $value) {
      $getrebate = $this->coreFunctions->opentable(
        "
          select sum(detail.cr) as rebate 
          from gldetail as detail 
          left join coa as coa on coa.acnoid = detail.acnoid
          where trno = '" . $value->trno . "' and coa.alias = 'AR3'
        "
      );

      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $postdate = "";
        if ($params['params']['companyid'] == 35) { //aquamax
          $clname = $value->clientname;
        } else {
          $clname = "";
        }

        $rebate = "-";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          switch ($params['params']['companyid']) {
            case 1: //vitaline
            case 23: // labsol cebu
              $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
              break;
          }
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $postdate = $value->postdate;
        $clname = $value->clientname;

        $rebate = number_format($getrebate[0]->rebate, 2);
        if ($rebate == 0) {
          $rebate = '-';
        }
        $totalrebate = $totalrebate + $getrebate[0]->rebate;
      }

      $regdb = number_format($value->regdb, 2);
      if ($regdb == 0) {
        $regdb = '-';
      }

      $regcr = number_format($value->regcr, 2);
      if ($regcr == 0) {
        $regcr = '-';
      }

      $sundb = number_format($value->sundb, 2);
      if ($sundb == 0) {
        $sundb = '-';
      }

      $suncr = number_format($value->suncr, 2);
      if ($suncr == 0) {
        $suncr = '-';
      }

      switch ($params['params']['companyid']) {
        case 10: //afti
        case 12: //afti usd
          $docno = $value->doc == 'SJ' ? substr($value->docno, -5) : 'BS' . substr($value->docno, -8);
          break;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, '75', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($postdate, '100', null, '', '1px solid ', '', 'CT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, '', '1px solid ', '', 'CT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($clname, '175', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($regdb, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($regcr, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      switch ($params['params']['companyid']) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($rebate, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
          break;
      }
      $str .= $this->reporter->col($value->sunacctname, '150', null, '', '1px solid ', '', 'CT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($sundb, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($suncr, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');

      $totalsundb = $totalsundb + $value->sundb;
      $totalsuncr = $totalsuncr + $value->suncr;
      $totalrdb = $totalrdb + $value->regdb;
      $totalrcr = $totalrcr + $value->regcr;

      $docno = $value->docno;
      $date = $value->dateid;
      $clname = $value->clientname;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_DEFAULT_HEADER($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    switch ($params['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', '175', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrdb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrcr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrebate, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsundb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsuncr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', '175', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrdb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrcr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsundb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsuncr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn
  // CURRENTLY FOR UNIHOME/NATHINA END

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $fontsize10 = 10;
    $str = '';
    $companyid = $params['params']['companyid'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 0:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;

      case 1:
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $str .= $this->reporter->begintable('1200');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Date', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('Invoice', '200', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('Customer', '400', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('Net Amount', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('Vat Amount', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('Grand Total', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->endrow();
            break;
          default:
            $str .= $this->reporter->printline();
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE', '70', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('DOC#', '100', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('&nbsp;&nbsp;CLIENTNAME', '300', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('REMARKS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('AMOUNT', '100', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->endrow();
            break;
        }
        break;

      case 2:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow('', null, '', '1px solid ', '', '', $font, '11', 'B', '', '');
        switch ($params['params']['companyid']) {
          case 1: //vitaline
          case 23: //labsol cebu
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            break;
          default:
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, 'B', '', '');
            break;
        }
        switch ($params['params']['companyid']) {
          case 10: //afti
          case 12: //afti usd
            break;
          default:
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col(' ', '100', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col(' ', '175', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col(' ', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col(' ', '75', null, '', '1px solid ', '', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('SUNDRIES', '75', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, 'B', '', '');
            break;
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->col('DATE', '100', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('POSTED <br> DATE', '75', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('COSTUMER NAME', '175', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('A/R DEBIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('SALES CREDIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        switch ($params['params']['companyid']) {
          case 1: //vitaline
          case 23: //labsol cebu
            $str .= $this->reporter->col('REBATE', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
            break;
        }
        $str .= $this->reporter->col('ACCOUNT DESCRIPTION', '150', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('DEBIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    } // end switch
    return $str;
  }

  private function GENERATE_DEFAULT_HEADER($params)
  {
    $str = '';
    $center = $params['params']['dataparams']['center'];
    $centername = $params['params']['dataparams']['centername'];
    $startdate = $params['params']['dataparams']['start'];
    $enddate = $params['params']['dataparams']['end'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    $font = $this->companysetup->getrptfont($params['params']);
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    switch ($params['params']['companyid']) {
      case 17: // UNIHOME
      case 39: //CBBSI
        $project = $params['params']['dataparams']['projectname'];
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    switch ($reporttype) {
      case 0:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARIZED SALES JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        }

        switch ($params['params']['companyid']) {
          case 17: // UNIHOME
          case 39: //CBBSI
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }

        $str .= $this->reporter->pagenumber('Page');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        break;

      case 1:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALES JOURNAL SUMMARY', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '', 0, '', 0, 5);
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '', 0, '', 0, 5);

        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '', 0, '', 0, 5);
        } else {
          $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '', 0, '', 0, 5);
        }

        switch ($params['params']['companyid']) {
          case 17: // UNIHOME
          case 39: //CBBSI
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }
        $str .= $this->reporter->pagenumber('Page');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        break;

      case 2:
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DETAILED SALES JOURNAL', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        if ($center != '') {
          $str .= $this->reporter->col('Center: ' . $center . ' - ' . $centername, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Center: ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        }
        $str .= $this->reporter->col('Transaction: ' . strtoupper($reporttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');

        switch ($params['params']['companyid']) {
          case 17: // UNIHOME
          case 39: //CBBSI
            $str .= $this->reporter->col('Project: ' . $project, null, null, '', '1px solid', '', 'l', '', '10', '', '', '');
            break;
        }

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        break;
    } // end switch

    return $str;
  } //end fn

  private function VITALINE_SALES_JOURNAL_DETAILED($data, $params)
  {
    $count = 41;
    $page = 40;
    $str = '';
    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalrdb = 0;
    $totalrcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $totalsundb = 0;
    $totalsuncr = 0;
    $totalrebate = 0;
    $rebate = "-";

    foreach ($data as $key => $value) {
      $getrebate = $this->coreFunctions->opentable(
        "
        select sum(detail.cr) as rebate 
        from gldetail as detail 
        left join coa as coa on coa.acnoid = detail.acnoid
        where trno = '" . $value->trno . "' and coa.alias = 'AR3'
      "
      );

      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $postdate = "";
        $clname = "";
        $rebate = "-";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          switch ($params['params']['companyid']) {
            case 1: //vitaline
            case 23: //labsol cebu
              $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
              break;
          }
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $postdate = $value->postdate;
        $clname = $value->clientname;

        $rebate = number_format($getrebate[0]->rebate, 2);
        if ($rebate == 0) {
          $rebate = '-';
        }
        $totalrebate = $totalrebate + $getrebate[0]->rebate;
      }

      $regdb = number_format($value->regdb, 2);
      if ($regdb == 0) {
        $regdb = '-';
      }

      $regcr = number_format($value->regcr, 2);
      if ($regcr == 0) {
        $regcr = '-';
      }

      $sundb = number_format($value->sundb, 2);
      if ($sundb == 0) {
        $sundb = '-';
      }

      $suncr = number_format($value->suncr, 2);
      if ($suncr == 0) {
        $suncr = '-';
      }

      switch ($params['params']['companyid']) {
        case 10: //afti 
        case 12: //afti usd
          $docno = $value->doc == 'SJ' ? substr($value->docno, -5) : 'BS' . substr($value->docno, -8);
          break;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($postdate, '100', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($clname, '175', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($regdb, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', 0, '',  1);
      $str .= $this->reporter->col($regcr, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', 0, '',  1);
      switch ($params['params']['companyid']) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($rebate, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
          break;
      }
      $str .= $this->reporter->col($value->sunacctname, '150', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($sundb, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', 0, '',  1);
      $str .= $this->reporter->col($suncr, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', 0, '',  1);

      $totalsundb = $totalsundb + $value->sundb;
      $totalsuncr = $totalsuncr + $value->suncr;
      $totalrdb = $totalrdb + $value->regdb;
      $totalrcr = $totalrcr + $value->regcr;

      $docno = $value->docno;
      $date = $value->dateid;
      $clname = $value->clientname;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    switch ($params['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
        break;
    }
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', '175', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrdb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', 0, '',  1);
    $str .= $this->reporter->col(number_format($totalrcr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', 0, '',  1);
    switch ($params['params']['companyid']) {
      case 1: //vitaline
      case 23: // labsol cebu
        $str .= $this->reporter->col(number_format($totalrebate, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', 0, '',  1);
        break;
    }
    $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsundb, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', 0, '',  1);
    $str .= $this->reporter->col(number_format($totalsuncr, 2), '75', null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', 0, '',  1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function VITALINE_SALES_JOURNAL_SUMMARIZED($data, $params)
  {
    $str = '';
    $count = 45;
    $page = 45;
    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, 2);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, 2);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end for each

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function VITALINE_SALES_SUMMARY($data, $params)
  {
    $str = '';
    $count = 61;
    $page = 60;
    $border = '1px solid';
    $fontsize10 = 10;
    $fontsize12 = 12;
    $font = $this->companysetup->getrptfont($params['params']);
    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totalamt = 0;

    foreach ($data as $key => $value) {
      $amt = number_format($value->amt, 2);

      if ($amt == 0) {
        $amt = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->dateid, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('&nbsp;&nbsp;' . $value->clientname, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->rem, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($amt, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt += $value->amt;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        $str .= $this->reporter->begintable('800');
        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function AFTI_SALES_SUMMARY($data, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = 10;
    $this->reporter->linecounter = 0;
    if (empty($data)) return $this->othersClass->emptydata($params);

    $str .= $this->reporter->beginreport();
    $str .= $this->GENERATE_DEFAULT_HEADER($params);


    $totalamt = 0;
    $totalnet = 0;
    $totalvatamt = 0;
    $invoice = '';

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Invoice', '200', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Customer', '400', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Net Amount', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Vat Amount', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Grand Total', '150', null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();

    foreach ($data as $key => $value) {
      $amt = $value->amt;
      $netamt = 0;
      $vatamt = 0;
      if ($value->tax > 0) {
        $vatamt = $amt * 0.12;
        $netamt = $vatamt + $amt;
      } else {
        $netamt = $amt;
      }
      $invoice = $value->doc === 'SJ' ? substr($value->docno, -5) : 'BS' . substr($value->docno, -8);


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->dateid, null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($invoice, null, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->clientname, null, null, '', '1px solid', 'LTRB', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($amt == 0 ? '-' : number_format($amt, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($vatamt == 0 ? '-' : number_format($vatamt, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($netamt == 0 ? '-' : number_format($netamt, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();

      $totalamt += $amt;
      $totalnet += $netamt;
      $totalvatamt += $vatamt;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid', 'LTRB', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Total', null, null, '', '1px solid', 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalvatamt, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalnet, 2), null, null, '', '1px solid', 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class