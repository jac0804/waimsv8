<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;
use DateTime;

class statement_of_account
{
  public $modulename = 'Statement of Accounts';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  private $balance;
  private $acurrent;
  private $a30days;
  private $a60days;
  private $a90days;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->balance = 0;
    $this->acurrent = 0;
    $this->a30days = 0;
    $this->a60days = 0;
    $this->a90days = 0;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'dateid', 'dclientname'];

    if ($companyid == 59) { //roosevelt
      array_push($fields, 'area');
    }
    $col1 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        data_set($col1, 'radioprint.options', [
          ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        break;
    }

    data_set($col1, 'dateid.label', 'Balance as of');
    data_set($col1, 'dateid.readonly', false);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    switch ($companyid) {
      case 3: // conti
        $fields = ['radioreportcustomerfilter', 'radioreporttype', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'attention.readonly', false);
        break;

      case 10: //afti
      case 12: //afti usd
        $fields = ['attention', 'interestrate', 'radiosjaftilogo', 'radioreportcustomerfilter'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'attention.readonly', false);
        break;
      case 37: //mega crystal
        $fields = ['radioreportcustomerfilter', 'attention', 'certifby', 'received'];
        $col2 = $this->fieldClass->create($fields);
        break;
      case 39: //cbbsi
        $fields = ['radioreportcustomerfilter', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreportcustomerfilter.options', [
          ['label' => 'Per Customer', 'value' => '0', 'color' => 'orange'],
          ['label' => 'By Customer Group', 'value' => '1', 'color' => 'orange'],
          ['label' => 'By Project', 'value' => '2', 'color' => 'orange']
        ]);
        break;
      case 52: //technolab
        $fields = ['radiotechlabcomp', 'radioreportcustomerfilter', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);
        break;
      case 59: //roosevelt
        $fields = ['radioreportcustomerfilter'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreportcustomerfilter.options', [
          ['label' => 'By Customer', 'value' => '0', 'color' => 'orange']
          // ['label' => 'By Customer Group', 'value' => '1', 'color' => 'orange'],
          // ['label' => 'By Project', 'value' => '2', 'color' => 'orange']
        ]);
        break;
      case 29: //sbc
        $fields = ['radioreportcustomerfilter', 'radioreporttype', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.options', [
          ['label' => 'Default', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Sbc Format', 'value' => '0', 'color' => 'orange']
        ]);
        data_set($col2, 'attention.readonly', false);
        break;
      default:
        $fields = ['radioreportcustomerfilter', 'attention', 'certifby'];
        $col2 = $this->fieldClass->create($fields);
        break;
    }


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $username = $config['params']['user'];
        $type = 'PDFM';
        break;

      default:
        $type = 'default';
        $username = '';
        break;
    }

    $paramstr = "select 
    '" . $type . "' as print,
    left(now(),10) as dateid,
    0 as clientid,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as attention,
    '" . $username . "' as certifby,
    '' as received,
    '0' as customerfilter,
    '0' as reporttype,
     '' as interestrate,'wlogo' as radiosjaftilogo,
     'c0' as radiotechlabcomp,
     '' as area";

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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    switch ($companyid) {
      case 3: //conti
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
          case '0': // summarized
            return $this->SOA_SUMMARIZED_LAYOUT($config);
            break;
          case '1': // detailed
            return $this->GPC_SOA_DETAILED_LAYOUT($config);
            break;
        }
        break;
      case 32: //3m
        return $this->mmm_defaultLayout($config);
        break;
      case 10: //afti
      case 12: //afti usd
        return $this->report_soa_afti($config);
        break;
      case 37: //mega crystal
        return $this->report_megacrystal($config);
        break;
      case 39: //cbbsi
        switch ($config['params']['dataparams']['customerfilter']) {
          case '2':
            return $this->report_cbbsi_project_layout($config);
            break;
          default:
            return $this->report_cbbsi_layout($config);
            break;
        }
        break;
      case 59: //roosevelt
        return $this->reportDefaultLayout_roosevelt($config);
        break;
      case 29: //sbc
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
          case '1': // default
            return $this->reportDefaultLayout($config);
            break;
          case '0': // sbc format
            return $this->sbc_layout($config);
            break;
        }
        break;
      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }

  private function afti_query($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $attention = $config['params']['dataparams']['attention'];
    $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $customerfilter = $config['params']['dataparams']['customerfilter'];
    $filter     = "";

    if ($client != '') {
      $filter .= "and client.client='$client'";
    }

    switch ($customerfilter) {
      case '0':
        if ($client != "") {
          $filter = "and client.client='$client'";
        }
        break;
      case '1':
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    // Statement of Account to Customer : The uncollected CWT AR must be reflected in the SOA
    $query = "select head.trno, 'p' as tr, 1 as trsort, client.client, client.clientname,
    date(ar.dateid) as ardate,  ar.ref as applied, ar.db as debit,
    ar.cr as credit,case ar.cr when 0 then (ar.bal) else (ar.bal)*-1 end as balance, ag.client as agent, head.due,
    client.terms, client.tel, client.start as startdate, cp.email,
    concat(cp.fname,' ',cp.mname,' ',cp.lname) as attention, 
    case
      when head.doc = 'AR' then detail.poref
      else head.yourref
    end as yourref,
    concat(billadd.addrline1,' ',billadd.addrline2,' ',billadd.city,' ',billadd.province,' ',billadd.country,' ',billadd.zipcode) as addr,
    detail.rem as remarks, cp.contactno, head.dateid,

    case
      when head.doc = 'AR' then datediff(now(), detail.podate)
      when head.doc = 'CR' then datediff(now(), head.dateid)
      else datediff(now(), head.due)
    end as elapse,

    case 
      when left(ar.docno,2) = 'DR' then concat('SI',right(ar.docno,10))
      when left(ar.docno,2) = 'AR' then detail.rem
      else ar.docno
    end as invoiceno,coa.alias

    from glhead as head 
    left join arledger as ar on ar.trno=head.trno
    left join gldetail as detail on head.trno = detail.trno and ar.line = detail.line
    left join client on client.clientid=head.clientid
    left join billingaddr as billadd on billadd.line = client.billid
    left join contactperson cp on cp.line = client.billcontactid
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar' and ar.bal<>0 
    and ifnull(client.client,'')<>'' and date(head.dateid)<='" . $asof . "'  " . $filter . "
    order by client, clientname, ardate 

    ";
    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $attention = $config['params']['dataparams']['attention'];
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code = "";
    $addfield = "";

    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }
    // if ($companyid == 59) { //roosevelt
    //   $addfield .= ", client.area, datediff('" . $asof . "', head.dateid) as elapse ";
    // }

    $query = "select head.trno,'p' as tr, 1 as trsort, client.client, client.clientname, client.addr,head.terms,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, client.tel,
    ar.cr as credit, (ar.bal) as balance, ag.client as agent, ag.clientname as agentname, head.due, head.yourref, head.rem,
    (case when head.doc='sj' then 'sales' else (case when head.doc='cm' then 'return' else 'adjustment' end) end) as trcode $addfield
    from (((glhead as head 
    left join arledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)
    left join coa on coa.acnoid=ar.acnoid)
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar'
    and num.center = '$center' 
    and date(ar.dateid)<='$asof' and ar.bal<>0
    $code $filter
    order by clientname, docdate, refno";
    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }


  public function vitalineqry($config)
  {
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code = "";
    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }
    $query = "select head.trno,'p' as tr, 1 as trsort, head.clientname, head.address as addr,head.terms,
      date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, head.tel,
      ar.cr as credit, (ar.bal) as balance, ag.client as agent, ag.clientname as agentname, head.due, head.yourref, head.rem,
      (case when head.doc='sj' then 'sales' else (case when head.doc='cm' then 'return' else 'adjustment' end) end) as trcode 
      from glhead as head 
      left join arledger as ar on ar.trno=head.trno
      left join coa on coa.acnoid=ar.acnoid
      left join client as ag on ag.clientid=ar.agentid
      left join cntnum as num on num.trno = head.trno  
      left join client on client.clientid=head.clientid
      where left(coa.alias,2)='ar'
      and num.center = '$center' 
      and date(ar.dateid)<='$asof' and ar.bal<>0 $code
      and num.doc IN ('AR','SJ','CM') $filter 
      order by clientname, docdate, refno";

    return $this->coreFunctions->opentable($query);
  }


  public function cbbsiqry($config)
  {

    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code = "";

    switch ($customerfilter) {
      case '0':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':

        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;

      case '2':
        $filter = "and head.projectid <> 0";
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
    }

    $query = "select head.trno,'p' as tr, 1 as trsort, client.client,head.clientname, head.address as addr,head.terms,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, head.tel,
    ar.cr as credit, (ar.bal) as balance, ag.clientname as agentname, head.due, head.yourref, head.rem,
    proj.name as project, datediff('" . $asof . "', head.dateid) as elapse
    from glhead as head 
    left join arledger as ar on ar.trno=head.trno
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join client on client.clientid=head.clientid
    left join cntnum as num on num.trno = head.trno
    left join projectmasterfile as proj on proj.line=head.projectid
    where left(coa.alias,2)='ar'
    and num.center = '$center' 
    and date(ar.dateid)<='$asof' and ar.bal<>0
    $code $filter
    order by clientname, docdate, refno";
    return $this->coreFunctions->opentable($query);
  }


  public function megacrystal($config)
  {
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];
    $clientid    = $config['params']['dataparams']['clientid'];

    $filter = "";
    $code = "";
    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    $query = "select head.trno,'p' as tr, 1 as trsort, client.client, head.clientname, head.address as addr,head.terms,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, head.tel,
    ar.cr as credit, (ar.bal) as balance, ag.client as agent, ag.clientname as agentname, head.due, head.yourref, head.rem,
    (case when head.doc='sj' then 'SJ' else (case when head.doc='cm' then 'SR' else 'GJ' end) end) as trcode
    from glhead as head 
    left join arledger as ar on ar.trno=head.trno
    left join client on client.clientid=head.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join client as ag on ag.clientid=ar.agentid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar'
    and num.center = '$center' 
    and date(ar.dateid)<='$asof' and ar.bal<>0
    $code $filter
    order by clientname, docdate, refno";
    return $this->coreFunctions->opentable($query);
  }


  public function reportDefault_DETAILED_QUERY($config)
  {

    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code = "";
    switch ($customerfilter) {
      case '0':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    $query = "
    select head.trno,'p' as tr, 1 as trsort,  head.clientname, head.address  as addr,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit,
    ar.cr as credit, (ar.bal) as balance, 
     ifnull(item.itemname,dritem.itemname) as itemname,
    CASE WHEN IFNULL(stock.isqty,drstock.isqty) = 0 THEN IFNULL(stock.rrqty,drstock.rrqty) ELSE IFNULL(stock.isqty,drstock.isqty) END AS isqty,
    ifnull(ifnull(stock.isamt,drstock.isamt),0) as isamt
    from (((glhead as head left join arledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)left join coa on coa.acnoid=ar.acnoid)
    left join client as ag on ag.clientid=ar.agentid
    left join glstock as stock on head.trno = stock.trno
    left join glhead AS drhead ON drhead.invtagging = head.trno
    left join glstock AS drstock ON drstock.trno = drhead.trno
    left join item as item on item.itemid = stock.itemid
    left join item as dritem on dritem.itemid = drstock.itemid
    where left(coa.alias,2)='ar'
    and head.dateid<='$asof' and ar.bal<>0 $code $filter 
    UNION ALL
    select head.trno,'p' as tr, 1 as trsort,
    head.clientname, head.address as addr, date(ar.dateid) as docdate,
    ar.docno as refno, ar.ref as applied, ar.db as debit, ar.cr as credit,
    (ar.bal) as balance,
    '' as itemname, 0 as isqty,0 as isamt 
    from (((glhead as head
    left join apledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)
    left join coa on coa.acnoid=ar.acnoid)
    left join glstock as stock on head.trno = stock.trno
    where head.doc = 'CR'
    and left(coa.alias,2)='AP' 
    and head.dateid<='$asof' and ar.bal<>0 $code $filter 
    order by clientname";

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_SUMMARIZED_QUERY($config)
  {
    $attention = $config['params']['dataparams']['attention'];
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $filter = "";
    $code = "";
    switch ($customerfilter) {
      case '0':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    $query = "
    select head.trno,'p' as tr, 1 as trsort,  head.clientname, head.address as addr,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit,
    ar.cr as credit, (ar.bal) as balance, ag.client as agent, head.due,
    (case when head.doc='sj' then 'SALES' else (case when head.doc='cm' then 'RETURN' when head.doc='cr' then 'ADVANCE/OVER'  else 'ADJUSTMENT' end) end) as trcode
    from (((glhead as head 
    left join arledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)
    left join coa on coa.acnoid=ar.acnoid)
    left join client as ag on ag.clientid=ar.agentid
    where left(coa.alias,2)='ar'
    and head.dateid<='$asof' and ar.bal<>0 $code $filter
    UNION ALL
    select head.trno,'p' as tr, 2 as trsort,
     head.clientname, head.address as addr,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit,
    ar.cr as credit, (ar.bal) as balance, '' as agent, head.due, 'ADVANCE PAYMENT' as trcode
    from (((glhead as head
    left join apledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)
    left join coa on coa.acnoid=ar.acnoid)
    where head.doc = 'CR' and left(coa.alias,2)='ap' 
    and head.dateid<='$asof' and ar.bal<>0 $code $filter 
    order by clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }
    // if ($companyid == 40) { //cdo
    $width = '1000';
    // } else {
    //   $width = '800';
    // }

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($width);
    $str .= $this->reporter->startrow();
    if ($companyid == 52) { //technolab
      $compfilter = $config['params']['dataparams']['radiotechlabcomp'];
      $qry = "select code,name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);

      if (isset($config['params'])) {
        $font = $this->companysetup->getrptfont($config['params']);
      } else {
        $font = 'Century Gothic';
      }

      $reporttimestamp = $this->setreporttimestamp($config, $username, $headerdata);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '13', '', '', '', 0, '', 0, 5);
      $str .= $this->reporter->endrow();

      if ($compfilter == 'c0') {
        $comp = 'Technolab Diagnostic Solutions Inc.';
      } else {
        $comp = 'LabSolution Technology Inc.';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($comp, null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->letterhead($center, $username, $config);
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable($width);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($width);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($width);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function setreporttimestamp($config, $user, $headerdata)
  {
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $username = $user;
    $companyid = 0;
    $resellerid = 0;

    if (!empty($config)) {
      $username = $config['params']['user'];
      $companyid = $config['params']['companyid'];
      $resellerid = $config['params']['resellerid'];
    }

    switch ($resellerid) {
      case 2:
        return strtoupper($username) . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->code . ' RSSC');
        break;
      default:
        return $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name);
        break;
    }
  }

  public function mmm_defaultLayout($config)
  {
    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->displayHeader($config);
    $customer = '';
    $customersub = '';
    $balance = 0;
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '80', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('AGENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '150', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '80', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '170', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '80', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '170', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }
          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $customer = $data->clientname;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '80', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '170', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
      } else {
        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', '', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
          $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '80', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('AGENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '150', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '80', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '170', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '80', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '170', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '80', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('AGENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '150', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '80', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '170', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '80', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '170', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '150', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function displayHeader2($asof, $border)
  {
    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function report_cbbsi_layout($config)
  {
    $result     = $this->cbbsiqry($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->displayHeader($config);
    $customer = '';
    $customersub = '';
    $balance = 0;
    $acurrent = 0;
    $a30days = 0;
    $a60days = 0;
    $a90days = 0;
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->client . ' - ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TELEPHONE NO.   : ' . $data->tel, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '140', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('YOURREF', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NOTES', '140', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col($data->yourref, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->rem, '140', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->balance, 2), '120', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          if ($data->elapse < 30) {
            if ($data->debit != 0) {
              $acurrent += $data->balance;
            } else {
              $acurrent -= $data->balance;
            }
          } else if ($data->elapse >= 30 && $data->elapse < 60) {
            if ($data->debit != 0) {
              $a30days += $data->balance;
            } else {
              $a30days -= $data->balance;
            }
          } else if ($data->elapse >= 60 && $data->elapse < 90) {
            if ($data->debit != 0) {
              $a60days += $data->balance;
            } else {
              $a60days -= $data->balance;
            }
          } else {
            if ($data->debit != 0) {
              $a90days += $data->balance;
            } else {
              $a90days -= $data->balance;
            }
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $customer = $data->clientname;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col($data->yourref, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->rem, '140', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '120', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          if ($data->elapse < 30) {
            if ($data->debit != 0) {
              $acurrent += $data->balance;
            } else {
              $acurrent -= $data->balance;
            }
          } else if ($data->elapse >= 30 && $data->elapse < 60) {
            if ($data->debit != 0) {
              $a30days += $data->balance;
            } else {
              $a30days -= $data->balance;
            }
          } else if ($data->elapse >= 60 && $data->elapse < 90) {
            if ($data->debit != 0) {
              $a60days += $data->balance;
            } else {
              $a60days -= $data->balance;
            }
          } else {
            if ($data->debit != 0) {
              $a90days += $data->balance;
            } else {
              $a90days -= $data->balance;
            }
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
      } else {
        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', '', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('AGING OF ENDING BALANCE', null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Current', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('30 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('60 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('90 + DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($acurrent == 0 ? '-' : number_format($acurrent, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($a30days == 0 ? '-' : number_format($a30days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($a60days == 0 ? '-' : number_format($a60days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($a90days == 0 ? '-' : number_format($a90days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $acurrent = 0;
          $a30days = 0;
          $a60days = 0;
          $a90days = 0;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your records, kindly advise us at once. Payments received after statement date are not included.', null, null, false, $border, 'LRTB', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          //$str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '140', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '', '120', false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('YOURREF', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NOTES', '140', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '120', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          if ($data->elapse < 30) {
            if ($data->debit != 0) {
              $acurrent += $data->balance;
            } else {
              $acurrent -= $data->balance;
            }
          } else if ($data->elapse >= 30 && $data->elapse < 60) {
            if ($data->debit != 0) {
              $a30days += $data->balance;
            } else {
              $a30days -= $data->balance;
            }
          } else if ($data->elapse >= 60 && $data->elapse < 90) {
            if ($data->debit != 0) {
              $a60days += $data->balance;
            } else {
              $a60days -= $data->balance;
            }
          } else {
            if ($data->debit != 0) {
              $a90days += $data->balance;
            } else {
              $a90days -= $data->balance;
            }
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '140', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('YOURREF', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NOTES', '140', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col($data->yourref, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->rem, '140', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '120', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          if ($data->elapse < 30) {
            if ($data->debit != 0) {
              $acurrent += $data->balance;
            } else {
              $acurrent -= $data->balance;
            }
          } else if ($data->elapse >= 30 && $data->elapse < 60) {
            if ($data->debit != 0) {
              $a30days += $data->balance;
            } else {
              $a30days -= $data->balance;
            }
          } else if ($data->elapse >= 60 && $data->elapse < 90) {
            if ($data->debit != 0) {
              $a60days += $data->balance;
            } else {
              $a60days -= $data->balance;
            }
          } else {
            if ($data->debit != 0) {
              $a90days += $data->balance;
            } else {
              $a90days -= $data->balance;
            }
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ENDING BALANCE', null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Current', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('30 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('60 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('90 + DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($acurrent == 0 ? '-' : number_format($acurrent, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($a30days == 0 ? '-' : number_format($a30days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($a60days == 0 ? '-' : number_format($a60days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($a90days == 0 ? '-' : number_format($a90days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your records, kindly advise us at once. Payments received after statement date are not included.', null, null, false, $border, 'LRTB', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_cbbsi_project_layout($config)
  {
    $result = $this->cbbsiqry($config);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $attention = $config['params']['dataparams']['attention'];
    $certifby = $config['params']['dataparams']['certifby'];
    $asof = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = 'Century Gothic';
    $fontsize = '10';
    $border = '1px solid';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->displayHeader($config);
    $customer = '';
    $project = '';
    $customersub = '';
    $projectsub = '';
    $this->balance = 0;
    $this->acurrent = 0;
    $this->a30days = 0;
    $this->a60days = 0;
    $this->a90days = 0;
    foreach ($result as $key => $data) {
      if ($project == '' || ($project == $data->project && $data->project != '')) {
        if ($project != $data->project) {
          $project = $data->project;
          if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
            if ($customer != $data->clientname) {
              $customer = $data->clientname;
              $str .= $this->printCBBSI1($data, $border, $font, $fontsize, $attention);
            } elseif ($customer == $data->clientname) {
              $customer = $data->clientname;
              $str .= $this->printCBBSIContent($data, $border, $font, $fontsize);
            } else {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
              $str .= $this->reporter->endrow();
            }
          }
        } elseif ($project == $data->project) {
          if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
            if ($customer != $data->clientname) {
              $customer = $data->clientname;
              $str .= $this->printCBBSI1($data, $border, $font, $fontsize, $attention);
            } elseif ($customer == $data->clientname) {
              $customer = $data->clientname;
              $str .= $this->printCBBSIContent($data, $border, $font, $fontsize);
            } else {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
              $str .= $this->reporter->endrow();
            }
          }
        } else {
          if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
            $customer = $data->clientname;
            if ($customer != $data->clientname) {
              $str .= $this->printCBBSI1($data, $border, $font, $fontsize, $attention);
            } elseif ($customer == $data->clientname) {
              $str .= $this->printCBBSIContent($data, $border, $font, $fontsize);
            } else {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
              $str .= $this->reporter->endrow();
            }
          }
        }
      } else {
        $project = $data->project;
        if (($projectsub != '' && $projectsub != $project) && $this->balance != 0) {
          $projectsub = $data->project;
          $str .= $this->printCBBSIFooter($certifby, $border, $font, $fontsize);
          $this->balance = 0;
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);
          $str .= $this->printCBBSI1($data, $border, $font, $fontsize, $attention);
          $page += $count;
        } else {
          $str .= $this->reporter->page_break();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->displayHeader($config);
          $str .= $this->printCBBSI1($data, $border, $font, $fontsize, $attention);
          $page += $count;
        }
      }
      if ($projectsub == '') {
        $projectsub = $data->project;
      }
    }
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($this->balance, 2), null, null, false, '1.5px solid ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ENDING BALANCE', null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Current', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('30 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('60 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('90 + DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->acurrent == 0 ? '-' : number_format($this->acurrent, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a30days == 0 ? '-' : number_format($this->a30days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a60days == 0 ? '-' : number_format($this->a60days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a90days == 0 ? '-' : number_format($this->a90days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your records, kindly advise us at once. Payments received after statement date are not included.', null, null, false, $border, 'LRTB', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function printCBBSIFooter($certifby, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($this->balance, 2), null, null, false, '1.5px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ENDING BALANCE', null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Current', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('30 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('60 DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('90 + DAYS', 250, 0, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->acurrent == 0 ? '-' : number_format($this->acurrent, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a30days == 0 ? '-' : number_format($this->a30days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a60days == 0 ? '-' : number_format($this->a60days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($this->a90days == 0 ? '-' : number_format($this->a90days, 2), null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $this->acurrent = 0;
    $this->a30days = 0;
    $this->a60days = 0;
    $this->a90days = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, $border, '', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your records, kindly advise us at once. Payments received after statement date are not included.', null, null, false, $border, 'LRTB', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function printCBBSI1($data, $border, $font, $fontsize, $attention)
  {
    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ' . $data->client . ' - ' . $data->clientname, '75px', null, false, $border, 'LT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('PROJECT  : ' . $data->project, '75px', null, false, $border, 'TR', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '75px', null, false, $border, 'R', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TELEPHONE NO.   : ' . $data->tel, null, null, false, $border, 'L', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '75px', null, false, $border, 'R', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LB', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('', '75px', null, false, $border, 'BR', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid', 'B', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('APPLIED', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('<br>', '140', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('<br>', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '120', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TO', '100', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('YOURREF', '120', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('NOTES', '140', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('BALANCE DUE', '120', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->printCBBSIContent($data, $border, $font, $fontsize);
    return $str;
  }

  public function printCBBSIContent($data, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data->docdate, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->refno, '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->applied == 0 ? 'None' : $data->applied, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->yourref, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->rem, '140', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->debit == 0 ? '-' : number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data->credit == 0 ? '-' : number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($data->balance, 2), '120', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
    if ($data->debit != 0) {
      $this->balance += $data->balance;
    } else {
      $this->balance -= $data->balance;
    }

    if ($data->elapse < 30) {
      if ($data->debit != 0) {
        $this->acurrent += $data->balance;
      } else {
        $this->acurrent -= $data->balance;
      }
    } else if ($data->elapse >= 30 && $data->elapse < 60) {
      if ($data->debit != 0) {
        $this->a30days += $data->balance;
      } else {
        $this->a30days -= $data->balance;
      }
    } else if ($data->elapse >= 60 && $data->elapse < 90) {
      if ($data->debit != 0) {
        $this->a60days += $data->balance;
      } else {
        $this->a60days -= $data->balance;
      }
    } else {
      if ($data->debit != 0) {
        $this->a90days += $data->balance;
      } else {
        $this->a90days -= $data->balance;
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 1: // vitaline
        $result     = $this->vitalineqry($config);
        break;
      default:
        $result     = $this->reportDefault($config);
        break;
    }
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->displayHeader($config);
    $customer = '';
    $customersub = '';
    $balance = 0;
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }
          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $customer = $data->clientname;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
      } else {
        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          if ($companyid != 52) { //not technolab
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->col('<br>', null, null, false, $border, 'TLR', 'C', $font, '10', '', '', 'BI');
          }


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '', '100', false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  private function SOA_SUMMARIZED_LAYOUT($config)
  {


    $result = $this->reportDefault_SUMMARIZED_QUERY($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    $count = 50;
    $page = 3;
    $str = '';
    $sign = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $customer = '';
    $customersub = '';
    $balance = 0;
    //$page=1;

    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('DOC DATE', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOC NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '125', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '125', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE', '150', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col($data->docdate, '150', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '250', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }

          $qry = "select ifnull(sum(payment),0) as payment from (
                        select left(coa.alias,2) as alias,detail.db as payment from ladetail as detail
                        left join coa on coa.acno = detail.acno
                        where detail.trno in (select trno from gldetail where refx = " . $data->trno . ")
                        UNION ALL
                        select left(coa.alias,2) as alias,detail.db as payment from gldetail as detail
                        left join coa on coa.acnoid = detail.acnoid
                        where detail.trno in (select trno from gldetail where refx = " . $data->trno . ")) as tbl 
                        where tbl.alias in ('CR','CA','CB')";
          $credit = $this->coreFunctions->datareader($qry);
          if ($credit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($credit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->debit != 0) {
            $sign = '';
          } else {
            $sign = '-';
          }
          $str .= $this->reporter->col($sign . number_format($data->balance, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          // $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $customer = $data->clientname;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '150', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '250', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }

          if ($data->debit != 0) {
            $sign = '';
          } else {
            $sign = '-';
          }

          $str .= $this->reporter->col($sign . number_format($data->balance, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
      } else {

        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, '1px solid ', 'LR', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'LRB', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('PREPARED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }


        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->letterhead($center, $username, $config);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('As of ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '150', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '125', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '125', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '150', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '', '150', false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '125', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '125', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '150', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '250', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('For the Period Ending ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '150', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '200', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '125', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '125', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '150', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '200', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '125', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '125', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '150', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '150', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '250', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '200', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');


          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '125', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your recods,<br>kindly advise us at once. Payments received after statement date are not included.', null, '50px', false, '1px solid ', 'LTRB', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREPARED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  private function GPC_SOA_DETAILED_LAYOUT($config)
  {
    $result = $this->reportDefault_DETAILED_QUERY($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $count = 50;
    $page = 3;
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    $sign = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $customer = '';
    $customersub = '';
    $balance = 0;
    $itemname = '';
    $docno = '';
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOC DATE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOC NO.', '180', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->col('QTY', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('PRICE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '180', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          if ($data->isqty != '') {
            $isqty = number_format($data->isqty, 2);
          } else {
            $isqty = '';
          } //end if

          if ($data->isamt != '') {
            $isamt = number_format($data->isamt, 2);
          } else {
            $isamt = '';
          } //end if

          $str .= $this->reporter->col($isqty, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($docno == $data->refno) {
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            if ($data->debit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            $qry = "select ifnull(sum(payment),0) as payment from (
                                      select left(coa.alias,2) as alias,detail.db as payment from ladetail as detail
                                      left join coa on coa.acno = detail.acno
                                      where detail.trno in (select trno from gldetail where refx = " . $data->trno . ")
                                      UNION ALL
                                      select left(coa.alias,2) as alias,detail.db as payment from gldetail as detail
                                      left join coa on coa.acnoid = detail.acnoid
                                      where detail.trno in (select trno from gldetail where refx = " . $data->trno . ")) as tbl 
                                  where tbl.alias in ('CR','CA')";
            $credit = $this->coreFunctions->datareader($qry);

            if ($credit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            if ($data->debit != 0) {
              $balance = $balance + $data->balance;
            } else {
              $balance = $balance - $data->balance;
            }
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $customer = $data->clientname;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '180', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($docno == $data->refno) {
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            if ($data->debit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            if ($data->credit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            if ($data->debit != 0) {
              $sign = '';
            } else {
              $sign = '-';
            }

            $str .= $this->reporter->col($sign . ' ' . number_format($data->balance, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            if ($data->debit != 0) {
              $balance = $balance + $data->balance;
            } else {
              $balance = $balance - $data->balance;
            }
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
        $itemname = $data->itemname;
        $docno = $data->refno;
      } else {

        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, '1px solid ', 'LR', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px solid ', 'LRB', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('PREPARED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->letterhead($center, $username);
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('As of ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOC DATE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOC NO.', '180', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->col('QTY', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('PRICE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();



          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '180', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($docno == $data->refno) {
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            if ($data->debit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            if ($data->credit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', 'LTRB', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            if ($data->debit != 0) {
              $balance = $balance + $data->balance;
            } else {
              $balance = $balance - $data->balance;
            }
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNT', null, null, false, '1px solid ', '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('For the Period Ending ' . $asof, null, null, false, '1px solid ', '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, '1px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ' . $data->clientname, '75px', null, false, '1px solid ', 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ADDRESS    : ' . $data->addr, null, null, false, '1px solid ', 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ATTENTION : ' . $attention, null, null, false, '1px solid ', 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOC DATE', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOC NO.', '180', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->col('QTY', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('PRICE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '120', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '180', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

          if ($docno == $data->refno) {
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
          } else {
            if ($data->debit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            if ($data->credit == 0) {
              $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            }

            $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');

            if ($data->debit != 0) {
              $balance = $balance + $data->balance;
            } else {
              $balance = $balance - $data->balance;
            }
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $docno = $data->refno;
          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is a statement of your account as it appears in our books. If there are any discrepancies against your recods,<br>kindly advise us at once. Payments received after statement date are not included.', null, '50px', false, '1px solid ', 'LTRB', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREPARED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  // okhead
  private function displayHeader_megacrystal($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $str = '';
    $font = "";
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = "1000";
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);


    if (Storage::disk('sbcpath')->exists('/fonts/OPTIMA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/OPTIMA.TTF');
    }
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style = 'color: rgb(36, 59, 117)'>" . $headerdata[0]->name, null, null, false, $border, '', 'C', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, '10', 'B');
    $str .= $this->reporter->col("<div style = 'color: rgb(36, 59, 117)'>" . $headerdata[0]->address, '400', null, false, $border, '', 'C', $font, '10', 'B');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', $font, '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('for the period ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function mcpcfooter($config, $balance)
  {
    $str = '';
    $font = "";
    $fontsize = 10;
    $border = "1px solid ";
    $layoutsize = "1000";
    $certifby   = $config['params']['dataparams']['certifby'];
    $received =   $config['params']['dataparams']['received'];
    if (Storage::disk('sbcpath')->exists('/fonts/OPTIMA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/OPTIMA.TTF');
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', '800', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;TRANSACTION CODE', '180', null, false, '1px solid ', 'LRT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('&nbsp;', null, null, false, '1px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;SJ - Sales Journal', '180', null, false, '1px solid ', 'LR', 'L', $font, '10', '');
    $str .= $this->reporter->col('&nbsp;', null, null, false, '1px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;SR - Sales Return', '170', null, false, '1px solid ', 'LR', 'L', $font, '10', '');
    $str .= $this->reporter->col('&nbsp;', null, null, false, '1px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;GJ - General Journal', '180', null, false, '1px solid ', 'LRB', 'L', $font, '10', '');
    $str .= $this->reporter->col('&nbsp;', '90px', null, false, '1px solid ', '', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', '700', null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, '300', null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', '400', null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>' . (isset($received) ? $received : ''), null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  //ok
  public function report_megacrystal($config)
  {
    $result     = $this->megacrystal($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $attention  = $config['params']['dataparams']['attention'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "";
    $fontsize = 10;
    $border = "1px solid ";
    $layoutsize = '1000';
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    if (Storage::disk('sbcpath')->exists('/fonts/OPTIMA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/OPTIMA.TTF');
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_megacrystal($config);
    $customer = '';
    $customersub = '';
    $balance = 0;
    $counter = 0;
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;
          //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
          head:
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;CUSTOMER&nbsp;&nbsp;: ', null, '25', false, $border, 'LT', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('' . $data->clientname . '', '900', '25', false, $border, 'T', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('', null, '25', false, $border, 'TR', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;ADDRESS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ', null, '25', false, $border, 'L', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('' . $data->addr, null, '25', false, $border, '', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('', null, '25', false, $border, 'R', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;ATTENTION&nbsp;&nbsp;: ' . $attention, null, '25', false, $border, 'LB', 'L', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('', null, '25', false, $border, 'B', 'C', $font, $fontsize, 'B', '');
          $str .= $this->reporter->col('Terms :&nbsp' . $data->terms, '400', '25', false, $border, 'BR', 'C', $font, $fontsize, 'B', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L',  $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '125', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '155', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '130', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '125', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '10', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE', '125', null, false, $border, '', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '130', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '125', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANS.', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '155', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '130', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '125', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '10', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DUE', '125', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DUE DATE', '130', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          start:
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '155', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          }
          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->balance, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->due, '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } elseif ($customer == $data->clientname) {
          $str .= $this->reporter->addline();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->mcpcfooter($config, $balance);
            $customer = $data->clientname;
            $balance = 0;
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader_megacrystal($config);
            goto head;
          }
          goto start;
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
        }
      } else {
        $customer = $data->clientname;
        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->mcpcfooter($config, $balance);
          $customer = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader_megacrystal($config);
          goto head;
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->mcpcfooter($config, $balance);
          $customer = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader_megacrystal($config);
          goto head;
        } else {
          $str .= $this->mcpcfooter($config, $balance);
          $customer = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader_megacrystal($config);
          goto head;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }
    $str .= $this->mcpcfooter($config, $balance);
    $customer = $data->clientname;
    $balance = 0;
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function report_soa_afti($params)
  {
    $data = $this->afti_query($params);
    $this->othersClass->setDefaultTimeZone();
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $attention  = $params['params']['dataparams']['attention'];
    $asof     = date('m/d/Y', strtotime($params['params']['dataparams']['dateid']));

    $qry = "select name,concat(address,' ',zipcode,'<br>','Tel nos: ',tel,'<br>','E-mail: ',email,'<br>','<b>VAT REG TIN: ',tin,'</b>') as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = 2;
    $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];
    $count = $page = 900;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $font = 'helvetica';

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $fontsize9 = "9";
    $fontsize10 = "10";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)


    //$peso = TCPDF_FONTS::unichr(8369); //php

    $total = 0;
    $peso = "<span>&#8369;</span>";
    $interestrate = $params['params']['dataparams']['interestrate'] != "" ? $params['params']['dataparams']['interestrate'] / 100 : 0;
    $interest = 0;
    $totalamt_due = 0;
    $client = '';

    $total = 0;
    $totala = 0;
    $totalb = 0;
    $totalc = 0;
    $totald = 0;

    $debit = 0;
    $credit = 0;
    $balance = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        //ending
        if ($client != $data[$i]['client']) {
          if ($client != '') {
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(260, 25, ' Current', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(125, 25, ' 30 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(125, 25, ' 60 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(125, 25, ' 90 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(125, 25, ' Amount Due', 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            PDF::SetFont('dejavusans', 'R', $fontsize11);
            PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

            PDF::SetFont($font, 'R', $fontsize11);
            PDF::MultiCell(245, 25, number_format($totala, $decimalprice), 'RB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

            PDF::SetFont('dejavusans', 'R', $fontsize11);
            PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

            PDF::SetFont($font, 'R', $fontsize11);
            PDF::MultiCell(110, 25, number_format($totalb, $decimalprice), 'RB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

            PDF::SetFont('dejavusans', 'R', $fontsize11);
            PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

            PDF::SetFont($font, 'R', $fontsize11);
            PDF::MultiCell(110, 25, number_format($totalc, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

            PDF::SetFont('dejavusans', 'R', $fontsize11);
            PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

            PDF::SetFont($font, 'R', $fontsize11);
            PDF::MultiCell(110, 25, number_format($totald, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

            PDF::SetFont('dejavusans', 'R', $fontsize11);
            PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

            PDF::SetFont($font, 'R', $fontsize11);
            PDF::MultiCell(110, 25, number_format($total, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            $total = 0;
            $totala = 0;
            $totalb = 0;
            $totalc = 0;
            $totald = 0;
            if ($params['params']['dataparams']['interestrate'] != "") {
              PDF::SetFont($font, 'B', $fontsize12);
              PDF::MultiCell(260, 25, 'Interest', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

              PDF::SetFont('dejavusans', 'R', $fontsize11);
              PDF::MultiCell(10, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

              PDF::SetFont($font, 'R', $fontsize11);
              PDF::MultiCell(115, 25, number_format($interest, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

              PDF::MultiCell(260, 25, 'Total Amount Due', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

              PDF::SetFont('dejavusans', 'R', $fontsize11);
              PDF::MultiCell(10, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

              PDF::SetFont($font, 'R', $fontsize11);
              PDF::MultiCell(115, 25, number_format($totalamt_due + $interest, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            }

            do {
              PDF::MultiCell(0, 0, "\n");
            } while (PDF::getY() < 630);


            PDF::MultiCell(0, 30, "\n");
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(500, 15, 'For Inquiries :', 0, 'L', false);

            $userinfo = $this->getuserinfo($params);

            PDF::MultiCell(0, 5, "\n");
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(120, 15, 'Collection Officer : ', 0, 'L', false, 0);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(500, 15, ' ' . (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : ''), 0, 'L', false, 1);

            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(120, 15, 'Contact Number : ', 0, 'L', false, 0);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->tel2) ? $userinfo[0]->tel2 : ''), 0, 'L', false, 1);

            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(120, 15, 'Email Address : ', 0, 'L', false, 0);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->email) ? $userinfo[0]->email : ''), 0, 'L', false, 1);

            PDF::MultiCell(0, 5, "\n");
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(500, 15, 'Collection Department', 0, 'L', false);
            PDF::SetFont($font, '', $fontsize11);

            PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['name']) ? $headerdata[0]['name'] : ''), 0, 'L', false, 1, '', '', true, 0, true);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['address']) ? $headerdata[0]['address'] : ''), 0, 'L', false, 1, '', '', true, 0, true);


            if ($sjlogo == 'wlogo') {
              PDF::MultiCell(0, 40, "\n");
            } else {
              PDF::MultiCell(500, 0, ' ', '', 'L', false, 0);
              PDF::MultiCell(200, 0, 'Received By: ', '', 'L', false, 0);
              PDF::MultiCell(150, 0, ' ', '', 'L');

              PDF::MultiCell(0, 0, "\n");

              PDF::MultiCell(500, 0, '', '', 'L', false, 0);
              PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
              PDF::MultiCell(150, 0, '', '', 'L');
            }

            PDF::MultiCell(0, 0, "");
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(0, 15, "");
            PDF::MultiCell(760, 15, 'This is computer generated, No signature required', 0, 'C', false);

            if ($i < count($data)) {
              PDF::AddPage('P');
            }
          }
        }
        // start
        if ($client != $data[$i]['client']) {

          if ($sjlogo == 'wlogo') {
            PDF::Image('public/images/afti/qslogo.png', '', '', 330, 80);
            PDF::MultiCell(500, 0, '', 0, 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', 17);
            PDF::MultiCell(260, 0, '', 0, 'C', 0, 1, '', '', false, 0, false, false, 0);
          }

          PDF::MultiCell(0, 30, "\n\n\n");

          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['name']) ? $headerdata[0]['name'] : ''), 0, 'L', false, 1, '', '', true, 0, true);
          PDF::SetFont($font, '', $fontsize11);
          PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['address']) ? $headerdata[0]['address'] : ''), 0, 'L', false, 1, '', '', true, 0, true);

          // statement of account email
          PDF::SetFont($font, 'B', $fontsize14);
          PDF::MultiCell(0, 30, "\n");

          PDF::MultiCell(760, 0, ' STATEMENT OF ACCOUNT ', 0, 'C', false);
          // border buttom
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150)));
          PDF::MultiCell(410, 0, "", 'B', 'L', false, 0);
          PDF::MultiCell(50, 0, "", 0, 'L', false, 0);
          PDF::MultiCell(300, 0, "", 'B', 'L', false, 1);

          PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(211, 211, 211)));
          PDF::SetFillColor(211, 211, 211);
          PDF::SetFont($font, 'B', $fontsize12);
          // $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false
          PDF::MultiCell(160, 30, '  ' . 'CUSTOMER NAME: ', 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(250, 30, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 30, ' ', '', 'LR', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::MultiCell(150, 30, ' DATE: ', 'LR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(150, 30, '  ' . $asof, 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

          // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

          // second col
          PDF::SetFillColor(150, 150, 150);
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::MultiCell(160, 100, ' ADDRESS: ', 'TLRB', 'L', false, 0, '', '', true, 1);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(250, 100, '  ' . (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
          PDF::MultiCell(50, 100, ' ', '', 'LR', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::MultiCell(150, 20, ' TERMS: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(150, 20, '  ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

          // 3rd col
          PDF::MultiCell(0, 100, "");
          PDF::SetFillColor(211, 211, 211);
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::MultiCell(160, 20, ' CONTACT NUMBER: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(250, 20, '  ' . (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'TLRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFillColor(211, 211, 211);
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::MultiCell(160, 20, ' ATTENTION: ', 'TLRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, 'B', $fontsize11);
          PDF::MultiCell(250, 20, '  ' . (isset($attention) ? $attention : ''), 'TLRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
          // end header

          // start data
          PDF::SetFont($font, 'B', $fontsize12);
          PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100)));
          PDF::MultiCell(760, 0, "", 'B', 'L', false, 1);
          PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(211, 211, 211)));
          PDF::MultiCell(135, 20, ' Invoice Date ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(125, 20, ' Invoice No ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(125, 20, ' PO No. ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(125, 20, ' Amount ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(125, 20, ' Payment ', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(125, 20, ' Balance ', 'LRB', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        }


        PDF::SetFont($font, 'R', $fontsize11);

        $balance = $data[$i]['balance'];
        $debit =  $data[$i]['debit'];
        $credit = $data[$i]['credit'];

        if ($data[$i]['alias'] == 'AR5') {
          $amount = 0;
          $payment = $credit - $debit;
          $bal = $balance;
        } else {
          $amount = $debit - $credit;
          $payment = $amount - $balance;
          $bal = $balance;
        }

        PDF::MultiCell(135, 25, ' ' . date('m/d/Y', strtotime($data[$i]['ardate'])), 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(125, 25, ' ' . $data[$i]['invoiceno'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(125, 25, ' ' . $data[$i]['yourref'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(110, 25, number_format($amount, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(110, 25, number_format($payment, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont('dejavusans', 'R', $fontsize11);
        PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(110, 25, number_format($bal, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        $payment = ($data[$i]['debit'] - $data[$i]['credit']) - $data[$i]['balance'];
        $total += ($data[$i]['debit'] - $data[$i]['credit']) - $payment;

        if ($data[$i]['elapse'] <= 30) {
          $totala += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $totalb += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $totalc += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 91) {
          $totald += $data[$i]['balance'];
        }

        $date1 = date('Y-m-d');
        $date2 = date('Y-m-d', strtotime($data[$i]['ardate']));
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year1 - $year2) * 12) + ($month1 - $month2);

        $due = date('Y-m-d', strtotime($data[$i]['due']));


        // Interest Computation = Invoice amount x 1% x no. of months overdue
        $interest += $data[$i]['balance'] * $interestrate * $diff;
        $totalamt_due += $data[$i]['balance'];

        $client = $data[$i]['client'];
      }
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(260, 25, ' Current', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 30 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 60 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' 90 +', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 25, ' Amount Due', 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(245, 25, number_format($totala, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totalb, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totalc, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($totald, $decimalprice), 'RB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont('dejavusans', 'R', $fontsize11);
    PDF::MultiCell(15, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

    PDF::SetFont($font, 'R', $fontsize11);
    PDF::MultiCell(110, 25, number_format($total, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    $total = 0;
    $totala = 0;
    $totalb = 0;
    $totalc = 0;

    if ($params['params']['dataparams']['interestrate'] != "") {
      PDF::SetFont($font, 'B', $fontsize12);
      PDF::MultiCell(260, 25, 'Interest', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

      PDF::SetFont('dejavusans', 'R', $fontsize11);
      PDF::MultiCell(10, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

      PDF::SetFont($font, 'R', $fontsize11);
      PDF::MultiCell(115, 25, number_format($interest, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

      PDF::MultiCell(260, 25, 'Total Amount Due', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(125, 25, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);

      PDF::SetFont('dejavusans', 'R', $fontsize11);
      PDF::MultiCell(10, 25, ' ' . $peso, 'LB', 'R', false, 0, '', '', false, 1, true);

      PDF::SetFont($font, 'R', $fontsize11);
      PDF::MultiCell(115, 25, number_format($totalamt_due + $interest, $decimalprice), 'RB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
    }

    do {
      PDF::MultiCell(0, 0, "\n");
    } while (PDF::getY() < 630);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(500, 15, 'For Inquiries :', 0, 'L', false);

    $userinfo = $this->getuserinfo($params);

    PDF::MultiCell(0, 5, "\n");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Collection Officer : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, ' ' . (isset($userinfo[0]->clientname) ? $userinfo[0]->clientname : ''), 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Contact Number : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->tel2) ? $userinfo[0]->tel2 : ''), 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(120, 15, 'Email Address : ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 15, '' . (isset($userinfo[0]->email) ? $userinfo[0]->email : ''), 0, 'L', false, 1);

    PDF::MultiCell(0, 5, "\n");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(500, 15, 'Collection Department', 0, 'L', false);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['name']) ? $headerdata[0]['name'] : ''), 0, 'L', false, 1, '', '', true, 0, true);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(260, 20, '' . (isset($headerdata[0]['address']) ? $headerdata[0]['address'] : ''), 0, 'L', false, 1, '', '', true, 0, true);

    if ($sjlogo == 'wlogo') {
      PDF::MultiCell(0, 40, "\n");
    } else {
      PDF::MultiCell(450, 0, ' ', '', 'L', false, 0);
      PDF::MultiCell(200, 0, 'Received By: ', '', 'L', false, 0);
      PDF::MultiCell(200, 0, ' ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(450, 0, '', '', 'L', false, 0);
      PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
      PDF::MultiCell(200, 0, '', '', 'L');
    }

    PDF::MultiCell(0, 0, "");
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(0, 15, "");
    PDF::MultiCell(760, 15, 'This is computer generated, No signature required', 0, 'C', false);

    if ($i < count($data)) {
      PDF::AddPage('P');
    }

    // &#8369  -- Peso Sign not working
    $pdf = PDF::Output($this->modulename . '.pdf', 'S');
    return $pdf;
  }

  private function getuserinfo($config)
  {
    $adminid = $config['params']['adminid'];
    $qry = "select clientname, tel2, email from client where clientid = ? ";
    return $this->coreFunctions->opentable($qry, [$adminid]);
  }

  private function addrow($border)
  {
    PDF::SetFont('', '', 2);
    PDF::MultiCell(160, 0, '', $border, 'L', false, 0, '', '', true, 1);
    PDF::MultiCell(5, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(245, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', false, 1);

    PDF::MultiCell(150, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(150, 0, '', $border, 'L', false, 1, '', '', false, 1);
  }


  public function rooseveltqry($config)
  {
    $asof      = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center    = $config['params']['center'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];
    $area = $config['params']['dataparams']['area'];

    $filter = "";
    $code = "";

    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and head.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    if ($area != "") {
      $filter .= " and client.area='" . $area . "'";
    }

    $query = "select head.trno,'p' as tr, 1 as trsort, 
     if(head.doc='BE',cl.client,client.client) as client,
     if(head.doc='BE',cl.clientname,client.clientname) as clientname,
     if(head.doc='BE',cl.addr,client.addr) as addr,head.terms,
     if(head.doc='BE',cl.area,client.area) as area,
    date(ar.dateid) as docdate, ar.docno as refno, ar.ref as applied, ar.db as debit, client.tel,
    ar.cr as credit, (ar.bal) as balance, ag.client as agent, ag.clientname as agentname, head.due, head.yourref, head.rem,
    (case when head.doc='sj' then 'sales' else (case when head.doc='cm' then 'return' else 'adjustment' end) end) as trcode,
    client.area, datediff('" . $asof . "', head.dateid) as elapse,count(distinct ar.docno) as cntdocno

    from (((glhead as head 
    left join arledger as ar on ar.trno=head.trno)
    left join client on client.clientid=head.clientid)
    left join coa on coa.acnoid=ar.acnoid)
    left join client as ag on ag.clientid=ar.agentid
    left join gldetail as gd on gd.trno = head.trno and  head.doc='BE'
    left join client as cl on cl.clientid=gd.clientid
    left join cntnum as num on num.trno = head.trno
    where left(coa.alias,2)='ar'
    and num.center = '$center' 
    and date(ar.dateid)<='$asof' and ar.bal<>0 and (client.client IS NOT NULL OR cl.client IS NOT NULL)
    $code $filter 
    group by  head.trno,client.client, client.clientname, client.addr,head.terms,
    ar.dateid, ar.docno, ar.ref, ar.db, client.tel,
    ar.cr, ar.bal, ag.client, ag.clientname, head.due,
    head.yourref, head.rem,head.doc,head.dateid,client.area,cl.client,cl.clientname,cl.addr,cl.area
    order by clientname, docdate, refno";

    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader_roosevelt($config, $cust)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $width = '1000';

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    if ($cust == 0) {
      $str .= $this->reporter->begintable($width);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->letterhead($center, $username, $config);

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br>';

      // $str .= $this->reporter->begintable($width);
      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('<br>');
      // $str .= $this->reporter->endrow();
      // $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($width);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($width);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else { //next=1

      $str .= '<br>';

      $str .= $this->reporter->begintable($width);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($width);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('For the Period Ending ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    return $str;
  }

  public function reportDefaultLayout_roosevelt_orig($config)
  {
    $companyid = $config['params']['companyid'];
    // switch ($companyid) {
    //   case 1: // vitaline
    //     $result     = $this->vitalineqry($config);
    //     break;
    //   default:
    $result     = $this->reportDefault($config);
    //     break;
    // }
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $attention  = $config['params']['dataparams']['attention'];
    $certifby   = $config['params']['dataparams']['certifby'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    $customer = '';
    $customersub = '';
    $balance = 0;
    foreach ($result as $key => $data) {
      if ($customer == '' || ($customer == $data->clientname && $data->clientname != '')) {
        if ($customer != $data->clientname) {
          $customer = $data->clientname;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ', '100', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col($data->clientname, '900', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col($data->addr, '900', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->col($data->area, '900', null, false, $border, '', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT#', '230', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TERMS', '250', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
          // $str .= $this->reporter->col('TO', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '140', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '140', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '140', null, false, $border, 'LBR', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->terms, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->balance, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        // elseif ($customer == $data->clientname) {
        //   $customer = $data->clientname;
        //   $str .= $this->reporter->begintable($layoutsize);
        //   $str .= $this->reporter->startrow();
        //   //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        //   $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
        //   $str .= $this->reporter->col($data->refno, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
        //   $str .= $this->reporter->col($data->terms, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

        //   // if ($data->applied == 0) {
        //   //   $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
        //   // } else {
        //   //   $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
        //   // }

        //   if ($data->debit == 0) {
        //     $str .= $this->reporter->col('-', '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
        //   } else {
        //     $str .= $this->reporter->col(number_format($data->debit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
        //   }


        //   if ($data->credit == 0) {
        //     $str .= $this->reporter->col('-', '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
        //   } else {
        //     $str .= $this->reporter->col(number_format($data->credit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
        //   }

        //   $str .= $this->reporter->col(number_format($data->balance, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

        //   if ($data->debit != 0) {
        //     $balance = $balance + $data->balance;
        //   } else {
        //     $balance = $balance - $data->balance;
        //   }


        //   $str .= $this->reporter->endrow();
        //   $str .= $this->reporter->endtable();
        // } else {
        //   $str .= $this->reporter->startrow();
        //   $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '10', 'B');
        //   $str .= $this->reporter->endrow();
        // }
      } else {
        $customer = $data->clientname;

        if (($customersub != '' && $customersub != $customer) && $balance != 0) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

          $customersub = $data->clientname;
          $balance = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          if ($companyid != 52) { //not technolab
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->col('<br>', null, null, false, $border, 'TLR', 'C', $font, '10', '', '', 'BI');
          }


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ', '50', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->col($data->clientname, '950', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->addr, '1000', null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->area, '1000', null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '', '100', false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        } else {
          $str .= $this->reporter->page_break();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br><br><br><br><br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, $border, '', 'C', 'Courier New', '17', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();

          $enddate = $asof;
          $endd = new DateTime($enddate);
          $end = $endd->format('m/d/Y');

          $str .= $this->reporter->col('As of ' . $end, null, null, false, $border, '', 'C', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br> ', null, null, false, $border, '', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('CUSTOMER : ', '50', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->col($data->clientname, '950', null, false, $border, 'LTR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->addr, '1000', null, false, $border, 'LR', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->area, '1000', null, false, $border, 'LRB', 'L', $font, '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DOCUMENT', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '230', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENT', '250', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('APPLIED', '120', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TRANSACTION', '230', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('NO.', '250', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TO', '120', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DEBIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('CREDIT', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('BALANCE DUE', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          //($txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
          $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->trcode, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->refno, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

          if ($data->applied == 0) {
            $str .= $this->reporter->col('None', '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->applied, '120', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
          }

          if ($data->debit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }


          if ($data->credit == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');

          if ($data->debit != 0) {
            $balance = $balance + $data->balance;
          } else {
            $balance = $balance - $data->balance;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $page = $page + $count;
        }
      }

      if ($customersub == '') {
        $customersub = $data->clientname;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, '1', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1px dotted ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL DUE : ', null, null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1.5px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', 'Courier New', '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLEASE DISREGARD STATEMENT', null, null, false, $border, 'LTR', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    //$txt='',$w=null,$h=null, $bg=false,  $b=false, $b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
    $str .= $this->reporter->col('IF ALREADY PAID', null, null, false, $border, 'LRB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Important: This statement is presumed correct unless otherwise notified within fifteen (15) days of receipt', null, '50px', false, $border, 'LR', 'C', $font, '10', 'BI', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LRB', 'C', $font, '10', '', '', 'BI');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', '', 'L', $font, '10', '', 'B', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CERTIFIED CORRECT:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('RECEIVED BY:', null, null, false, '1px dotted ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>' . $certifby, null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('<br>', null, null, false, '1.5px solid ', 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_roosevelt($config)
  {
    $result = $this->rooseveltqry($config);

    $count = 20; // maximun lines per page
    $this->reporter->linecounter = 0;
    $str = '';
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '1000';

    $customer = '';
    $balance = 0;

    $aging_summ = [
      '30' => ['docs' => 0, 'bal' => 0],
      '60' => ['docs' => 0, 'bal' => 0],
      '120' => ['docs' => 0, 'bal' => 0],
      'over120' => ['docs' => 0, 'bal' => 0],
    ];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // Start of report
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_roosevelt($config, $next = 0);
    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      //  Check kung new customer
      if ($customer != $data->clientname) {
        // If not the first customer, close previous and page break
        // if ($customer != "") {
        //   $str .= $this->reporter->endtable();
        //   $str .= $this->reporter->page_break();
        //   // $str .= $this->reporter->page_break();
        //   $str .= $this->displayHeader_roosevelt($config, $next = 1);
        //   $str .= $this->reporter->begintable($layoutsize);
        // }

        if ($customer != "") {

          $str .= $this->reporter->endtable();
          // aging
          $str .= $this->reporter->begintable($layoutsize);

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, '5', '', '',  '');
          $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
          $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
          $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
          $str .= $this->reporter->col('&nbsp;', '300', null, false,  '', '',  'L', $font, '5', '', '',  '');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100');
          $str .= $this->reporter->col('A/R Aging Summary', '200', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('DOCUMENTS', '150', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('TOTAL BALANCE', '150', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '300');
          $str .= $this->reporter->endrow();

          $sum_docs = 0;
          $sum_bal = 0;

          foreach (['30' => '30 Days', '60' => '60 Days', '120' => '120 Days', 'over120' => 'Over 120 Days'] as $keyA => $label) {
            $docs = $aging_summ[$keyA]['docs'];
            $bal = $aging_summ[$keyA]['bal'];

            $sum_docs += $docs; //para sa summary total
            $sum_bal += $bal;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100');
            $str .= $this->reporter->col($label, '200', null, false, '', 'LB', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col($docs ?: '', '150', null, false, '', 'LB', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, '');
            $str .= $this->reporter->col($bal ? number_format($bal, 2) : '0.00', '150', null, false, '', 'LB', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '300');
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100');
          $str .= $this->reporter->col('Summary Total', '200', null, false, '', 'B', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->col($sum_docs, '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('P', '50', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col(number_format($sum_bal, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '300');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();

          // Page break for next customer
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader_roosevelt($config, $next = 1);
          $str .= $this->reporter->begintable($layoutsize);

          // aging reset
          $aging_summ = [
            '30' => ['docs' => 0, 'bal' => 0],
            '60' => ['docs' => 0, 'bal' => 0],
            '120' => ['docs' => 0, 'bal' => 0],
            'over120' => ['docs' => 0, 'bal' => 0],
          ];
        }

        $customer = $data->clientname;
        $this->reporter->linecounter = 0;

        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($customer, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($data->addr, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($data->area, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>', null, null, false, '2px solid ', 'B', 'L', 'Courier New', '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //Column Headers
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DOCUMENT#', '230', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('TERMS', '250', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DEBIT', '140', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('CREDIT', '140', null, false, $border, 'LB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('BALANCE', '140', null, false, $border, 'LBR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
      }

      // check kung lalampas sa count
      if ($this->reporter->linecounter == $count) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $this->reporter->linecounter = 0;
        $str .= $this->displayHeader_roosevelt($config, $next = 1);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($customer, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($data->addr, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->col($data->area, '900', null, false, $border, '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // column headers
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DOCUMENT#', '230', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('TERMS', '250', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DEBIT', '140', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('CREDIT', '140', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('BALANCE', '140', null, false, $border, 'TLBR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
      }

      //  transaction line
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docdate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data->refno, '230', null, false, $border, 'LTRB', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data->terms, '250', null, false, $border, 'LTRB', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data->debit == 0 ? '-' : number_format($data->debit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data->credit == 0 ? '-' : number_format($data->credit, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize);
      $str .= $this->reporter->col(number_format($data->balance, 2), '140', null, false, $border, 'LTRB', 'R', $font, $fontsize);

      $str .= $this->reporter->endrow();

      if ($data->elapse == 30) {
        $aging_summ['30']['docs'] += $data->cntdocno;
        $aging_summ['30']['bal'] += $data->balance;
      } elseif ($data->elapse >= 31 && $data->elapse <= 60) {
        $aging_summ['60']['docs'] += $data->cntdocno;
        $aging_summ['60']['bal'] += $data->balance;
      } elseif ($data->elapse >= 61 &&  $data->elapse <= 120) {
        $aging_summ['120']['docs'] += $data->cntdocno;
        $aging_summ['120']['bal'] += $data->balance;
      } elseif ($data->elapse > 120) {
        $aging_summ['over120']['docs'] += $data->cntdocno;
        $aging_summ['over120']['bal'] += $data->balance;
      }

      $this->reporter->linecounter++;
    }

    //summary ng last customer
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '100', null, false,  '', '',  'L', $font, '5', '', '',  '');
    $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
    $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
    $str .= $this->reporter->col('&nbsp;', '200', null, false,  '', '',  'L', $font, '5', '', '',  '');
    $str .= $this->reporter->col('&nbsp;', '300', null, false,  '', '',  'L', $font, '5', '', '',  '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100');
    $str .= $this->reporter->col('A/R Aging Summary', '200', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('DOCUMENTS', '150', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TOTAL BALANCE', '150', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '300');
    $str .= $this->reporter->endrow();

    $sum_docs = 0;
    $sum_bal = 0;

    foreach (['30' => '30 Days', '60' => '60 Days', '120' => '120 Days', 'over120' => 'Over 120 Days'] as $keyA => $label) {
      $docs = $aging_summ[$keyA]['docs'];
      $bal = $aging_summ[$keyA]['bal'];
      $sum_docs += $docs; //para sa summary total
      $sum_bal += $bal;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100');
      $str .= $this->reporter->col($label, '200', null, false, '', 'LB', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, '');
      $str .= $this->reporter->col($docs ?: '', '150', null, false, '', 'LB', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->col($bal ? number_format($bal, 2) : '0.00', '150', null, false, '', 'LB', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col('', '300');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100');
    $str .= $this->reporter->col('Summary Total', '200', null, false, '', 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', null, false, '', 'LB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col($sum_docs, '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('P', '50', null, false, '', 'LB', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($sum_bal, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '300');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();
    return $str;
  }


  private function sbc_header($config)
  {
    $border = '1px solid';
    $str = '';
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = 'Tahoma';
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    //  $str .= '<br/><br/>';

    $logopath = URL::to($this->companysetup->getlogopath($config['params']) . 'sbclogo1.jpg');
    // $path = $this->companysetup->getlogopath($config['params']);
    // $path = str_replace('public/', '', $path);
    // $logopath = URL::to($path . 'sbclogo1.jpg');

    $str .= "<div style='margin-bottom:20px; text-align:left;margin-left:-110px;margin-top:-20px;'>"; //margin-top:-30px;
    $str .= "<img src='{$logopath}' width='1200' height='250'>";
    $str .= "</div>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(date('F d, Y', strtotime($asof)), null, null, false, $border, '', 'L', $font, '12', 'B');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    // $str .= '<br/>';
    return $str;
  }

  private function sbc_client_header_block($layoutsize, $data, $attention, $border, $font, $fontsize)
  {
    $str = '';
    $headerLines = 0;

    $slash = '';
    $contactno = isset($data->contactno) ? $data->contactno : '';
    $mobile = isset($data->mobile) ? $data->mobile : '';
    $contact = isset($data->contact) ? $data->contact : '';
    $clientname = isset($data->clientname) && !empty($data->clientname) ? $data->clientname : '';
    $addr = isset($data->addr) ? $data->addr : '';
    $printDate = date('m/d/Y');
    $displayContact = (!empty($data->maincontact)) ? $data->maincontact : $contact;

    if (!empty($data->maincontact)) {
      $parts = array_filter(array(
        isset($data->fax)  ? $data->fax  : '',
        isset($data->tel)  ? $data->tel  : '',
        isset($data->tel2) ? $data->tel2 : '',
      ));
    } else {
      $parts = array_filter(array($contactno, $mobile));
    }
    $displayNumbers = implode(' / ', $parts);

    // Start a single table for all header rows
    $str .= $this->reporter->begintable($layoutsize);

    // Row 1: Company Name and Date
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Company Name:', '150', null, false, '2px solid', 'LT', 'LT', $font, $fontsize, '', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col($clientname, '550', null, false, '2px solid', 'LT', 'LT', $font, $fontsize, 'B', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col('Date: ', '100', null, false, '2px solid', 'TL', 'LT', $font, $fontsize, '', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col($printDate, '225', null, false, '2px solid', 'LTR', 'LT', $font, $fontsize, 'B', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->endrow();
    $this->reporter->linecounter++;
    $headerLines++;

    // Row 2: Company Address
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Company Address:', '150', null, false, '2px solid', 'LT', 'LT', $font, $fontsize, '', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col($addr, '550', null, false, '2px solid', 'LT', 'LT', $font, $fontsize, 'B', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '225', null, false, '2px solid', 'TR', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $this->reporter->linecounter++;
    $headerLines++;

    // Row 3: Attention To and Contact No.
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Attention To: ', '150', null, false, '2px solid', 'LTB', 'LT', $font, $fontsize, '', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col($displayContact, '550', null, false, '2px solid', 'LTB', 'LT', $font, $fontsize, 'B', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col('Contact No.: ', '100', null, false, '2px solid', 'TBL', 'LT', $font, $fontsize, '', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->col($displayNumbers, '225', null, false, '2px solid', 'TRBL', 'LT', $font, $fontsize, 'B', '', '', '', '', 'padding-left: 5px');
    $str .= $this->reporter->endrow();
    $this->reporter->linecounter++;
    $headerLines++;

    // Optional Row 4: Attention (if exists)
    if (!empty($attention)) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($attention, '1000', null, false, '', '', 'LT', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $this->reporter->linecounter++;
      $headerLines++;
    }

    // Empty row for spacing
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '20', false, '2px solid', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $this->reporter->linecounter++;
    $str .= $this->reporter->endtable();
    $headerLines++;

    // TABLE HEADER (separate table)
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PARTICULARS', '780', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('AMOUNT', '220', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $this->reporter->linecounter++;
    $headerLines++;

    return ['str' => $str, 'headerLines' => $headerLines];
  }

  public function sbc_layout($config)
  {
    $result         = $this->sbcqry($config);
    $allCollections = $this->sbccollectionqry($config);

    // Group collections by sales_trno
    $collectionMap = array();
    foreach ($allCollections as $col) {
      if ((float)$col->collection != 0) {
        $collectionMap[$col->sales_trno][] = $col;
      }
    }

    // Map collections to their sales rows
    $processedResult = array();
    foreach ($result as $row) {
      if (!empty($row->itemname) && (float)$row->sales > 0) {
        $currentItem              = clone $row;
        $currentItem->collections = isset($collectionMap[$row->trno]) ? $collectionMap[$row->trno] : array();
        $processedResult[]        = $currentItem;
      }
    }

    // Filter out items with zero balance
    $filteredResult = array_filter($processedResult, function ($item) {
      return (float)$item->bal > 0;
    });

    $result = array_values($filteredResult);

    $str        = '';
    $layoutsize = '1000';
    $font       = 'Tahoma';
    $fontsize   = "10";
    $border     = "1px solid";
    $this->reporter->linecounter = 0;

    $peso      = "<span>&#8369;</span>";
    $attention = $config['params']['dataparams']['attention'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // Group by trno first, preserving client grouping
    $groupedByTrno = [];
    foreach ($result as $row) {
      $groupedByTrno[$row->trno][] = $row;
    }

    // Flatten back but tag each row with isLastItemForTrno
    $taggedResult = [];
    foreach ($groupedByTrno as $trno => $rows) {
      $lastIndex = count($rows) - 1;
      foreach ($rows as $index => $row) {
        $row->isLastItemForTrno = ($index === $lastIndex);
        $taggedResult[] = $row;
      }
    }

    $result = $taggedResult;
    $count  = count($result);

    $maxLinesPerPage     = 30;
    $customer            = '';
    $subtotal            = 0;
    $grandTotal          = 0;
    $isClientVatable     = false;
    $isFirstPageOfClient = true;
    $itemCount           = 0;

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->sbc_header($config);

    for ($i = 0; $i < $count; $i++) {

      $data          = $result[$i];
      $currentClient = isset($data->client) ? $data->client : '';

      $isLastItemForClient = ($i == $count - 1 || (isset($result[$i + 1]->client) && $result[$i + 1]->client != $currentClient));
      $isLastItemForTrno   = $data->isLastItemForTrno;

      if (isset($data->vattype) && $data->vattype == 'VATABLE') {
        $isClientVatable = true;
      }

      // When client changes
      if ($customer != '' && $customer != $data->client) {
        $itemCount = 0;
        $this->fillBlankLines($str, $maxLinesPerPage, $this->reporter->linecounter, $layoutsize, $border, $font, $fontsize);
        $this->addClientTotals($str, $isClientVatable, $subtotal, $peso, $layoutsize, $border, $font, $fontsize);
        $str .= $this->sbc_footer($layoutsize, $border, $font, $fontsize);
        $str .= $this->reporter->page_break();
        $str .= $this->sbc_header($config);

        $this->reporter->linecounter = 0;
        $subtotal            = 0;
        $isFirstPageOfClient = true;
        $isClientVatable     = false;
      }

      // CLIENT HEADER
      if ($customer == '' || $customer != $data->client) {
        if (!isset($data->clientname) || empty($data->clientname)) {
          $data->clientname = '';
        }

        $block = $this->sbc_client_header_block($layoutsize, $data, $attention, $border, $font, $fontsize);
        $str  .= $block['str'];

        $this->reporter->linecounter = 0;
        $isFirstPageOfClient         = false;
        $customer                    = $data->client;
        $isClientVatable             = (isset($data->vattype) && $data->vattype == 'VATABLE') ? true : false;
      }

      $extraLines      = !empty($data->itemname) ? substr_count($data->itemname, "\n") : 0;
      $remainingBalance = (float)$data->bal;
      $hasCollections  = !empty($data->collections);
      $collectionCount = $hasCollections ? count($data->collections) : 0;

      $lineUse  = 1;
      $lineUse += 1;
      $lineUse += 1;
      $lineUse += $hasCollections ? 1 : 0;
      $lineUse += $hasCollections ? $collectionCount : 0;
      $lineUse += !empty($data->yourref) ? 1 : 0;
      $lineUse += !empty($data->yourref) ? 1 : 0;

      $isLastOnPage = false;
      if ($i < $count - 1) {
        $nextData            = $result[$i + 1];
        $nextHasCollections  = !empty($nextData->collections);
        $nextCollectionCount = $nextHasCollections ? count($nextData->collections) : 0;

        // If next item is a different client, it starts fresh on a new page
        $nextIsNewClient = ($nextData->client !== $data->client);

        if ($nextIsNewClient) {
          $isLastOnPage = true;
        } else {
          $nextLineUse  = 1;
          $nextLineUse += 1;
          $nextLineUse += 1;
          $nextLineUse += $nextHasCollections ? 1 : 0;
          $nextLineUse += $nextHasCollections ? $nextCollectionCount : 0;
          $nextLineUse += !empty($nextData->yourref) ? 1 : 0;
          $nextLineUse += !empty($nextData->yourref) ? 1 : 0;

          // After current item is rendered, will the next one fit?
          $lineCounterAfterCurrent = $this->reporter->linecounter + $lineUse;
          $isLastOnPage = ($lineCounterAfterCurrent + $nextLineUse) > $maxLinesPerPage;
        }
      } else {
        $isLastOnPage = true;
      }

      // Page break if current item won't fit
      if (($this->reporter->linecounter + $lineUse) > $maxLinesPerPage) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '780', null, false, $border, 'LB', 'L', $font, $fontsize);
        $str .= $this->reporter->col('&nbsp;', '220', null, false, $border, 'LRB', 'R', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;

        $str .= $this->reporter->page_break();
        $str .= $this->sbc_header($config);

        $block = $this->sbc_client_header_block($layoutsize, $data, $attention, $border, $font, $fontsize);
        $str  .= $block['str'];

        $this->reporter->linecounter = 0;
      }

      // ITEM ROW
      $isFirstItemForTrno = ($i === 0 || $result[$i - 1]->trno !== $data->trno);
      if ($isFirstItemForTrno) {
        $itemCount++;
      }
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($isFirstItemForTrno ? '&nbsp;<b>' . $itemCount . '. &nbsp' . $data->itemname . '</b>' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>' . $data->itemname . '</b>', '780', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '220', null, false, $border, 'LR', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $this->reporter->linecounter++;

      // SALES ROW
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, $border, 'L', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data->rem, '550', null, false, $border, '', 'LT', $font, $fontsize, '');
      $str .= $this->reporter->col('', '20', null, false, $border, '', 'LT', $font, $fontsize, '');
      $str .= $this->reporter->col($peso . ' ' . number_format((float)$data->sales, 2), '160', null, false, $border, '', 'LT', $font, $fontsize, 'B');

      if (!$hasCollections) {
        $str .= $this->reporter->col('', '220', null, false, $border, 'LR', 'RT', $font, $fontsize, 'B');
      } else {
        $str .= $this->reporter->col('', '220', null, false, $border, 'LR', 'R', $font, $fontsize);
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $this->reporter->linecounter++;

      // COLLECTION ROWS
      if ($hasCollections && $data->isLastItemForTrno) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '15', false, $border, 'L', 'R', $font, $fontsize);
        $str .= $this->reporter->col('', '550', '15', false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '180', '15', false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '220', '15', false, $border, 'LR', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;

        $lessLabel = '<b>LESS: </b>';
        foreach ($data->collections as $collection) {
          $collectionAmount = abs((float)$collection->collection);
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '50', null, false, $border, 'L', 'R', $font, $fontsize);
          $str .= $this->reporter->col($lessLabel . $collection->crnote, '550', null, false, $border, '', 'LT', $font, $fontsize);
          $str .= $this->reporter->col('', '20', null, false, $border, '', 'R', $font, $fontsize);
          $str .= $this->reporter->col('<b>(</b>' . $peso . ' ' . number_format($collectionAmount, 2) . '<b>)</b>', '160', null, false, $border, '', 'LT', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '220', null, false, $border, 'LR', 'RT', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $this->reporter->linecounter++;
          $lessLabel = '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
        }
      }

      // YOURREF ROW
      if (!empty($data->yourref)) {
        $youref_display = wordwrap($data->yourref, 80, "\n", true);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '15', false, $border, 'L', 'R', $font, $fontsize);
        $str .= $this->reporter->col('', '550', '15', false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '180', '15', false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '220', '15', false, $border, 'LR', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'L', 'R', $font, $fontsize);
        $str .= $this->reporter->col($youref_display, '730', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '220', null, false, $border, 'LR', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;
      }

      // FOR BILLING ROW
      if ($isLastItemForTrno) {
        $bottomBorder = $isLastItemForClient ? '' : 'B';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '40', null, false, '2px dotted', '', 'R', $font, $fontsize);
        $str .= $this->reporter->col('', '550', null, false, '2px dotted', '', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '20', null, false, '2px dotted', '', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('<i>For Billing ---></i>', '160', null, false, '2px dotted', '', 'LT', $font, $fontsize, '');
        $str .= $this->reporter->col($peso . ' ' . number_format($remainingBalance, 2) . '&nbsp&nbsp', '220', null, false, $border, 'LR', 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;

        $billingBorder = ($isLastOnPage || $isLastItemForClient) ? '1px solid' : '2px dotted';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $billingBorder, 'L' . $bottomBorder, 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '40', null, false, $billingBorder, '' . $bottomBorder, 'R', $font, $fontsize);
        $str .= $this->reporter->col('', '550', null, false, $billingBorder, $bottomBorder, 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '20', null, false, $billingBorder, $bottomBorder, 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '150', null, false, $billingBorder, $bottomBorder, 'LT', $font, $fontsize, '');
        $str .= $this->reporter->col('', '10', null, false, $billingBorder, 'R' . $bottomBorder, 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $billingBorder, '' . $bottomBorder, 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '200', null, false, $billingBorder, '' . $bottomBorder, 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $billingBorder, 'R' . $bottomBorder, 'RT', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;

        $subtotal   += $remainingBalance;
        $grandTotal += $remainingBalance;
      }
    }

    // Final client totals
    $this->fillBlankLines($str, $maxLinesPerPage, $this->reporter->linecounter, $layoutsize, $border, $font, $fontsize);
    $this->addClientTotals($str, $isClientVatable, $subtotal, $peso, $layoutsize, $border, $font, $fontsize);
    $str .= $this->sbc_footer($layoutsize, $border, $font, $fontsize);
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function fillBlankLines(&$str, $maxLines, $usedLines, $layoutsize, $border, $font, $fontsize)
  {
    $available = $maxLines - $usedLines;
    if ($available > 0) {
      for ($j = 0; $j < $available; $j++) {
        $str .= $this->reporter->begintable($layoutsize);
        // REMOVED: $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '780', null, false, $border, 'L', 'L', $font, $fontsize);
        $str .= $this->reporter->col('&nbsp;', '220', null, false, $border, 'LR', 'R', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $this->reporter->linecounter++;
      }
    }
  }

  //sbc soa client totals
  private function addClientTotals(&$str, $isClientVatable, $subtotal, $peso, $layoutsize, $border, $font, $fontsize)
  {
    $vatableSubtotal = 0;
    $vatAmount       = 0;

    if ($isClientVatable) {
      $vatableSubtotal = ((float)$subtotal) / 1.12;
      $vatAmount       = $vatableSubtotal * 0.12;

      $str .= $this->reporter->begintable($layoutsize);
      // REMOVED: $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUBTOTAL&nbsp;', '780', null, false, $border, 'L', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($peso . ' ' . number_format($vatableSubtotal, 2) . '&nbsp;', '220', null, false, $border, 'LR', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $this->reporter->linecounter++; // MANUALLY increment

      $str .= $this->reporter->begintable($layoutsize);
      // REMOVED: $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('12% VAT&nbsp;', '780', null, false, $border, 'L', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($peso . ' ' . number_format($vatAmount, 2) . '&nbsp;', '220', null, false, $border, 'LR', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $this->reporter->linecounter++; // MANUALLY increment
    }

    $str .= $this->reporter->begintable($layoutsize);
    // REMOVED: $this->reporter->addline();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL AMOUNT&nbsp;', '780', null, false, $border, 'LTB', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col($peso . ' ' . number_format($subtotal, 2) . '&nbsp&nbsp;', '220', null, false, $border, 'LTBR', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $this->reporter->linecounter++; // MANUALLY increment
  }

  public function sbcqry($config)
  {
    $asof        = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center      = $config['params']['center'];
    $client      = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code   = "";

    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "and client.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "and ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "and client.grpcode='$client'";
        }
        break;
    }

    $query = "select head.trno, head.doc, ifnull(client.client, '') as client, ifnull(client.clientname, '') as clientname, client.addr, 
    client.contact as maincontact,
    concat(ifnull(cp.salutation,''),' ',ifnull(cp.fname,''),' ',ifnull(cp.mname,''),' ',ifnull(cp.lname,'')) AS contact,
    cp.contactno, cp.mobile, client.fax, client.tel, client.tel2,
    head.yourref, head.vattype, (case when item.itemid in (22, 44) then 'Hardware' else item.itemname end) as itemname, head.rem, ar.bal, sum(stock.ext) as sales
    from glhead head
    left join client ON client.clientid = head.clientid
    left join contactperson cp ON cp.clientid = head.clientid
    left join cntnum num ON num.trno = head.trno
    left join glstock stock ON stock.trno = head.trno
    left join item ON item.itemid = stock.itemid
    left join arledger ar ON ar.trno = head.trno
    where num.center = '$center'
      $filter
      and ifnull(ar.bal, 0) > 0
      and date(ar.dateid) <= '$asof'
    group by head.trno, head.doc, client.client, client.clientname, client.addr,
             client.contact, cp.salutation, cp.fname, cp.mname, cp.lname, ar.bal,
             cp.contactno, cp.mobile, client.fax, client.tel, client.tel2,
             head.yourref, head.vattype, (case when item.itemid in (22, 44) then 'Hardware' else item.itemname end), head.rem

    union all

    select head.trno, head.doc, ifnull(client.client, '') as client, ifnull(client.clientname, '') as clientname, client.addr, 
    client.contact as maincontact,
    concat(ifnull(cp.salutation,''),' ',ifnull(cp.fname,''),' ',ifnull(cp.mname,''),' ',ifnull(cp.lname,'')) AS contact,
    cp.contactno, cp.mobile, client.fax, client.tel, client.tel2,
    head.yourref, head.vattype, (case when item.itemid in (22, 44) then 'Hardware' else item.itemname end) as itemname, head.rem, ar.bal, sum(stock.ext) as sales
    from lahead head
    left join client ON client.client = head.client
    left join contactperson cp ON cp.clientid = client.clientid
    left join cntnum num ON num.trno = head.trno
    left join lastock stock ON stock.trno = head.trno
    left join item ON item.itemid = stock.itemid
    left join arledger ar ON ar.trno = head.trno
    where num.center = '$center'
      $filter
      and ifnull(ar.bal, 0) > 0
      and date(ar.dateid) <= '$asof'
    group by head.trno, head.doc, client.client, client.clientname, client.addr,
             client.contact, cp.salutation, cp.fname, cp.mname, cp.lname, ar.bal,
             cp.contactno, cp.mobile, client.fax, client.tel, client.tel2,
             head.yourref, head.vattype, (case when item.itemid in (22, 44) then 'Hardware' else item.itemname end), head.rem
    order by clientname, trno";
    return $this->coreFunctions->opentable($query);
  }

  public function sbccollectionqry($config)
  {
    $asof        = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $center      = $config['params']['center'];
    $client      = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $customerfilter = $config['params']['dataparams']['customerfilter'];

    $filter = "";
    $code   = "";

    switch ($customerfilter) {
      case '0':
      case '2':
        if ($client != "") {
          $filter = "AND client.clientid='$clientid'";
        }
        break;
      case '1':
        $code = "AND ifnull(client.grpcode,'')<>''";
        if ($client != "") {
          $filter = "AND client.grpcode='$client'";
        }
        break;
    }

    $query = "select ar_detail.refx AS sales_trno,
        IFNULL(client.client, '') AS client,
        IFNULL(client.clientname, '') AS clientname,
        head.trno, head.rem AS crnote, ar_detail.cr AS collection
        FROM glhead head
        LEFT JOIN gldetail ar_detail ON ar_detail.trno = head.trno
        LEFT JOIN coa ar_coa ON ar_coa.acnoid = ar_detail.acnoid
        LEFT JOIN client ON client.clientid = head.clientid
        LEFT JOIN cntnum num ON num.trno = head.trno
        WHERE LEFT(ar_coa.alias, 2) = 'AR'
        AND ar_detail.refx IS NOT NULL
        AND ar_detail.refx <> 0
        AND DATE(head.dateid) <= '$asof'
        AND num.center = '$center'
        $code
        $filter

        union all

        select ar_detail.refx AS sales_trno,
        IFNULL(client.client, '') AS client,
        IFNULL(client.clientname, '') AS clientname,
        head.trno, head.rem AS crnote, ar_detail.cr AS collection
        FROM lahead head
        LEFT JOIN ladetail ar_detail ON ar_detail.trno = head.trno
        LEFT JOIN coa ar_coa ON ar_coa.acnoid = ar_detail.acnoid
        LEFT JOIN client ON client.client = head.client
        LEFT JOIN cntnum num ON num.trno = head.trno
        WHERE LEFT(ar_coa.alias, 2) = 'AR'
        AND ar_detail.refx IS NOT NULL
        AND ar_detail.refx <> 0
        AND DATE(head.dateid) <= '$asof'
        AND num.center = '$center'
        $code
        $filter
        ORDER BY clientname, sales_trno, trno";
    return $this->coreFunctions->opentable($query);
  }

  private function sbc_footer($layoutsize, $border, $font, $fontsize)
  {
    $str = '';

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Looking forward to your immediate settlement.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By: ', '200', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Received By: ', '200', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MR. LEANDRO HABUNAL', '200', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('Received Date:', '200', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Account Representative', '200', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class