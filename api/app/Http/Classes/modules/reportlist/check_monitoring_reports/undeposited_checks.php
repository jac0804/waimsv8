<?php

namespace App\Http\Classes\modules\reportlist\check_monitoring_reports;

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


class undeposited_checks
{
  public $modulename = 'Undeposited Checks';
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

    $fields = ['start', 'end', 'dcentername'];

    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'dclientname');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'due.readonly', false);
    data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
    data_set($col2, 'dclientname.label', 'Supplier/Customer');

    $fields = ['radioposttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set(
      $col3,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

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

        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '' as dclientname,'' as client,'' as clientname,'0' as posttype";
        break;

      default:
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as center,
        '' as centername,
        '' as dcentername,
        '' as dclientname,
        '' as client,
        '' as clientname,
        '0' as posttype
        ";
        break;
    }
    return $this->coreFunctions->opentable($paramstr);
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

    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $companyid = $filters['params']['companyid'];
    $filter = "";

    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "'  ";
    }

    if ($client != '') {
      $filter .= " and client.client='" . $client . "'  ";
    }

    switch ($companyid) {
      case 39: //cbbsi
        $dcondition =  "head.doc in ('cr','gj')";
        $docno = "docno,";
        $dfiltercoa = "and left(coa.alias, 2) in ('cr','ca')";
        $orderby = "order by chkdate";
        break;
      default:
        $docno = "concat(left(docno,2),right(docno,5)) as docno,";
        $dcondition =  "head.doc = 'cr'";
        $dfiltercoa = "and left(coa.alias, 2)='cr'";
        $orderby = "order by clientname,chkdate";
        break;
    }

    switch ($isposted) {
      case 1: // unposted 
        $query = "
        select trno, chkdate, clientname, $docno trdate, chkinfo, amount, postdate,notes
        from (select head.trno, detail.postdate as chkdate, head.client, head.clientname, head.docno,head.rem as notes,
        head.dateid as trdate, detail.checkno as chkinfo,abs(detail.db-detail.cr) as amount, date(cntnum.postdate) as postdate
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client = head.client
        where $dcondition $dfiltercoa and date(detail.postdate) between '" . $start . "' and '" . $end . "' " . $filter . " 
        ) as udc 
       $orderby
      ";
        break;
      case 0: // posted
        $query = "
        select head.trno, cr.checkdate as chkdate, head.clientname, head.rem as notes,head.docno, head.dateid as trdate, 
        cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client, date(cntnum.postdate) as postdate
        from ((crledger as cr 
        left join glhead as head on head.trno=cr.trno)
        left join client on client.clientid=head.clientid)
        left join cntnum on cntnum.trno=cr.trno
        where $dcondition and  ifnull(cr.depodate, '')='' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
     $orderby
      ";
        break;

      case 2: //alll
        $query = "  select trno, chkdate, clientname, concat(left(docno,2),right(docno,5)) as docno, trdate, chkinfo, amount, postdate,notes
        from (
        select head.trno, cr.checkdate as chkdate, head.clientname,
              head.rem as notes,
              head.docno, head.dateid as trdate,
              cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client, date(cntnum.postdate) as postdate
              from ((crledger as cr
              left join glhead as head on head.trno=cr.trno)
              left join client on client.clientid=head.clientid)
              left join cntnum on cntnum.trno=cr.trno
              where $dcondition and  ifnull(cr.depodate, '')='' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      union all
      select head.trno, detail.postdate as chkdate,  head.clientname,
              head.rem as notes,
              head.docno, head.dateid as trdate,
              detail.checkno as chkinfo,abs(detail.db-detail.cr) as amount,head.client, date(cntnum.postdate) as postdate
              from ((lahead as head
              left join ladetail as detail on detail.trno=head.trno)
              left join coa on coa.acnoid=detail.acnoid)
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client = head.client
              where $dcondition $dfiltercoa and date(detail.postdate) between '" . $start . "' and '" . $end . "' " . $filter . " 
              ) as udc
            $orderby";
        break;
    } // end switch

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->default_query($config);

    switch ($companyid) {
      case 1: //vitaline
      case 23: // labsol cebu
        $reportdata =  $this->VITALINE_UNDEPOSITED_CHECKS($config, $result);
        break;
      case 10; //afti
        $reportdata =  $this->AFTI_UNDEPOSITED_CHECKS($config, $result);
        break;
      case 39: //cbbsi
        return $reportdata =  $this->CBBSI_UNDEPOSIT_CHECK_LISTING($config, $result);
        break;
      default:
        $reportdata =  $this->VITALINE_UNDEPOSITED_CHECKS($config, $result);
        break;
    }
    return $reportdata;
  }

  private function DEFAULT_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '130', '', false, $border, '', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('' . number_format($c, 2), '130', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function DEFAULT_UNDEPOSITED_CHECKS($params, $data)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 31;
    $page = 30;
    $this->reporter->linecounter = 0;
    $clientname = '';
    $c = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->DEFAULT_UNDEPOSITED_CHECKS_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($clientname != $value->clientname) {
        if ($clientname != '') {
          #subtotal
          $str .= $this->DEFAULT_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          $c = 0;
        }

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->clientname, '800', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'l', $font, '',  '', '', '');
      $str .= $this->reporter->col($value->docno, '150', '', false, $border, '', 'C', $font, '',  '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '125', '', false, $border, '', 'C', $font, '',  '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'R', $font, '',  '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '125', '', false, $border, '', 'R', $font, '',  '', '', '');
      $str .= $this->reporter->endrow();

      $clientname = $value->clientname;
      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;

      $str .= $this->reporter->addline();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_UNDEPOSITED_CHECKS_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end

        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }

      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->DEFAULT_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $value->clientname;
        } #end if

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->endrow();
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '120', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col('', ' 130', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'R', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '130', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '120', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '130', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function DEFAULT_UNDEPOSITED_CHECKS_HEADER($params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $center = $params['params']['dataparams']['center'];
    $isposted = $params['params']['dataparams']['posttype'];
    $companyid = $params['params']['companyid'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    if ($center == '') {
      $center = 'ALL';
    }

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }



    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNDEPOSITED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    # END TABLE
    return $str;
  }


  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '125', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Amount', '125', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }



  private function AFTI_UNDEPOSITED_CHECKS($params, $data)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 31;
    $page = 30;
    $this->reporter->linecounter = 0;
    $clientname = '';
    $c = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->AFTI_UNDEPOSITED_CHECKS_HEADER($params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {

      $cnt1 += 1;

      if ($clientname != $value->clientname) {
        if ($clientname != '') {
          #subtotal
          $str .= $this->AFTI_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          $c = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->clientname, '200', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', '', false, $border, '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '100', '', false, $border, '', 'l', $font, '',  '', '', '', '');

      $qry = "select group_concat(x.ref) as ref from (
      select case left(ref,2) when 'DR' then replace(ref,'DR','SI') else ref end as ref
      from ladetail 
      where trno = '" . $value->trno . "' and ref <> ''
      union all
      select case left(ref,2) when 'DR' then replace(ref,'DR','SI') else ref end as ref
      from gldetail 
      where trno = '" . $value->trno . "' and ref <> '') as x";
      $data = $this->coreFunctions->opentable($qry);

      $ref = str_replace(",", "<br>", $data[0]->ref);
      $str .= $this->reporter->col($ref, '100', '', false, $border, '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '100', '', false, $border, '', 'r', $font, '',  '', '', '', '');
      $clientname = $value->clientname;

      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->AFTI_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $value->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $str .= $this->AFTI_UNDEPOSITED_CHECKS_HEADER($params);
        #header end

        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '');
      $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'r', $font, '',  'b', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function AFTI_UNDEPOSITED_CHECKS_HEADER($params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $center = $params['params']['dataparams']['center'];
    $isposted = $params['params']['dataparams']['posttype'];
    $companyid = $params['params']['companyid'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    if ($center == '') {
      $center = 'ALL';
    }

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }

    $str = '';


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNDEPOSITED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Document #', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Receipt Date', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('INV#', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'r', $font, '',  'b', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE
    return $str;
  }

  private function AFTI_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'r', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 't', 'r', $font, '',  'i', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }


  private function VITALINE_UNDEPOSITED_CHECKS($params, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 20;
    $page = 20;
    $clientname = '';
    $c = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->VITALINE_UNDEPOSITED_CHECKS_HEADER($params);
    $str .= $this->vitaline_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;

      if ($clientname != $value->clientname) {
        if ($clientname != '') {
          #subtotal
          $str .= $this->VITALINE_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          $c = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->postdate, '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col($value->clientname, '150', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->docno, '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '100', '', false, '1px solid', '', 'r', $font, '',  '', '', '', '');
      $clientname = $value->clientname;

      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->VITALINE_UNDEPOSITED_CHECKS_HEADER($params);
        }
        $str .= $this->vitaline_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end

        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }

      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->VITALINE_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $value->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->endrow();
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '');
      $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'r', $font, '',  'b', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function VITALINE_UNDEPOSITED_CHECKS_HEADER($params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $center = $params['params']['dataparams']['center'];
    $isposted = $params['params']['dataparams']['posttype'];

    if ($center == '') {
      $center = 'ALL';
    }

    switch ($isposted) {
      case 0:
        $ispostedstr = 'posted';
        break;
      case 1:
        $ispostedstr = 'unposted';
      case 2:
        $ispostedstr = 'all';
        break;
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNDEPOSITED CHECKS', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    # END TABLE
    return $str;
  }


  private function vitaline_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];


    $str .= $this->reporter->begintable();

    $str .= $this->reporter->col('Post Date', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Customer Name', '150', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Document #', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Info', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Date', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'r', $font, '',  'b', '', '', '');
    $str .= $this->reporter->endtable();

    return $str;
  }


  private function VITALINE_UNDEPOSITED_CHECKS_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'r', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 't', 'r', $font, '',  'i', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
  private function cbbsi_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->begintable('840');
    $str .= $this->reporter->col('CHECK DATE', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('CUSTOMER', '150', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('DOC NO', '190', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('TRANS DATE', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('CHECK INFO', '100', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('NOTES', '125', '', false, '1px dashed', 'B', 'l', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', '', false, '1px dashed', 'B', 'r', $font, '',  'b', '', '', '');
    $str .= $this->reporter->endtable();

    return $str;
  }
  private function UNDEPOSIT_CHECK_HEADER($params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $center = $params['params']['dataparams']['center'];
    $isposted = $params['params']['dataparams']['posttype'];
    $Dcenter = $params['params']['dataparams']['dcentername'];
    if ($center == '') {
      $center = 'ALL';
    }

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('840');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user'], $params);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    $str .= $this->reporter->begintable('840');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNDEPOSITED CHECK LISTING', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Branch: ' . $center, null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('840');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid', 'B', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE
    return $str;
  }
  private function CBBSI_UNDEPOSIT_CHECK_LISTING($params, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 20;
    $page = 20;
    $clientname = '';
    $c = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->UNDEPOSIT_CHECK_HEADER($params);
    $str .= $this->cbbsi_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end
    $str .= $this->reporter->begintable('840');
    foreach ($data as $key => $value) {
      $cnt1 += 1;
      $str .= $this->reporter->addline();

      if ($clientname != $value->clientname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col($value->clientname, '150', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '190', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  'b', '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->docno, '190', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->notes, '125', '', false, '1px solid', '', 'l', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '100', '', false, '1px solid', '', 'r', $font, '',  '', '', '', '');
      $str .= $this->reporter->endrow();
      $clientname = $value->clientname;

      $c2 = $value->amount;
      $total = $total + $c2;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->UNDEPOSIT_CHECK_HEADER($params);
        }
        $str .= $this->cbbsi_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable('840');
        $page = $page + $count;
      }
      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $value->clientname;
        } #end if
        $str .= $this->reporter->endtable();
      } # end if
      $str .= $this->reporter->endrow();
    } // end foreach
    $str .= $this->reporter->endtable();

    $str .= '</br>';
    $str .= $this->reporter->begintable('840');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn
}//end class