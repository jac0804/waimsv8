<?php

namespace App\Http\Classes\modules\modulereport\democons;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pb
{

  private $modulename = "Progress Billing";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter($config)
  {
    $fields = ['radiobilling'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['prepared', 'approved', 'received', 'lblrem', 'notes', 'remarks', 'customername', 'print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col1, 'radiobilling.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      ['label' => 'Billing Invoice', 'value' => '1', 'color' => 'red'],
      ['label' => 'Billing Invoice for Advances', 'value' => '2', 'color' => 'red'],
      ['label' => 'Billing Invoice for Retention', 'value' => '3', 'color' => 'red'],
      ['label' => 'Billing Summary', 'value' => '4', 'color' => 'red']
    ]);
    data_set($col2, 'received.label', 'Corrected by');
    data_set($col2, 'lblrem.label', 'For Billing Summary : ');
    data_set($col2, 'notes.label', 'PROGRESS BILLING # ');
    data_set($col2, 'notes.readonly', false);
    data_set($col2, 'remarks.label', 'WAC NO.');
    data_set($col2, 'remarks.readonly', false);
    data_set($col2, 'customername.label', 'BILLING PERIOD');
    data_set($col2, 'customername.readonly', false);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }

    $paramsstr = "select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '$user' as prepared,
      'PDFM' as radiobilling,
      '' as notes,
      '' as remarks,
      '' as customername";


    return $this->coreFunctions->opentable($paramsstr);
  }

  public function query_result($config)
  {
    $bill = $config['params']['dataparams']['radiobilling'];
    switch ($bill) {
      case '1':
      case '2':
      case '3':
      case '4':
        $query = $this->report_default_query($config);
        break;

      default:
        $query = $this->default_query($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
        head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, 
        detail.checkno, detail.db, detail.cr, detail.line,head.rem
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.client=detail.client
        where head.doc='PB' and head.trno= " . $trno . " 
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
        head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, 
        detail.checkno, detail.db, detail.cr, detail.line,head.rem
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid
        where head.doc='PB' and head.trno= " . $trno . " order by line";
    return $this->coreFunctions->opentable($query);
  }

  public function report_default_query($config)
  {
    $bill = $config['params']['dataparams']['radiobilling'];
    $trno = $config['params']['dataid'];
    switch ($bill) {
      case '1':
      case '3':
        $query = "select trno, dateid, dco, docno, clientname, address,
          yourref, client, rem, postdate, terms, projcode, projname, tcp, ocp,
          sum(retention) as retention,
          sum(recoup) as recoup,
          sum(ewt) as ewt,
          sum(ar) as ar,
          sum(totalamt) as totalamt
          from (
          select head.trno, date(head.dateid) as dateid,left(head.docno,2) as dco, head.docno, head.clientname,
          head.address, head.yourref,client.client, head.rem,
          date(detail.postdate) as postdate,head.terms,proj.code as projcode,
          proj.name as projname, 
          if(coa.alias = 'ar2',detail.db,0) as retention,
          if(coa.alias = 'ar3',detail.db,0) as recoup,
          if(coa.alias = 'ar4',detail.db,0) as ewt,
          if(coa.alias = 'ar1',detail.db,0) as ar,
          detail.db as totalamt,
          tcp.tcp,tcp.ocp
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join client on client.client=detail.client
          left join projectmasterfile as proj on proj.line= head.projectid
          left join (select projectid,tcp,ocp from pmhead
          union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
          where head.doc='PB' and head.trno= " . $trno . "
          group by head.trno, date(head.dateid),left(head.docno,2), head.docno, head.clientname,
          head.address, head.yourref,client.client,  head.rem,
          date(detail.postdate),
          head.terms,proj.code,proj.name, 
          tcp.tcp,tcp.ocp,coa.alias,detail.db 
          union all
          select head.trno, date(head.dateid) as dateid,left(head.docno,2) as dco, head.docno, head.clientname,
          head.address, head.yourref,client.client, head.rem,
          date(detail.postdate) as postdate,head.terms,proj.code as projcode,
          proj.name as projname, 
          if(coa.alias = 'ar2',detail.db,0) as retention,
          if(coa.alias = 'ar3',detail.db,0) as recoup,
          if(coa.alias = 'ar4',detail.db,0) as ewt,
          if(coa.alias = 'ar1',detail.db,0) as ar,
          detail.db as totalamt,
          tcp.tcp,tcp.ocp
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join client on client.clientid=detail.clientid
          left join projectmasterfile as proj on proj.line= head.projectid
          left join (select projectid,tcp,ocp from pmhead
          union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
          where head.doc='PB' and head.trno= " . $trno . "
          group by head.trno, date(head.dateid),left(head.docno,2), head.docno, head.clientname,
          head.address, head.yourref,client.client,  head.rem,
          date(detail.postdate),
          head.terms,proj.code,proj.name, 
          tcp.tcp,tcp.ocp,coa.alias,detail.db) as x
          group by trno, dateid, dco, docno, clientname, address,
          yourref, client, rem, postdate, terms, projcode, projname, tcp, ocp
        ";
        break;
      case 2:
        $query = "select trno, dateid, dco, docno, clientname, address,
          yourref, client, rem, postdate, terms, projcode, projname, tcp, ocp,
          sum(retention) as retention,
          sum(recoup) as recoup,
          sum(ewt) as ewt,
          sum(ar) as ar,
          sum(totalamt) as totalamt
          from (
          select head.trno, date(head.dateid) as dateid,left(head.docno,2) as dco, head.docno, head.clientname,
          head.address, head.yourref,client.client, head.rem,
          date(detail.postdate) as postdate,head.terms,proj.code as projcode,
          proj.name as projname, 
          if(coa.alias = 'ar2',detail.db,0) as retention,
          if(coa.alias = 'ar3',detail.cr,0) as recoup,
          if(coa.alias = 'ar4',detail.db,0) as ewt,
          if(coa.alias = 'ar1',detail.db,0) as ar,
          detail.db as totalamt,
          tcp.tcp,tcp.ocp
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join client on client.client=detail.client
          left join projectmasterfile as proj on proj.line= head.projectid
          left join (select projectid,tcp,ocp from pmhead
          union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
          where head.doc='PB' and head.trno= " . $trno . "
          group by head.trno, date(head.dateid),left(head.docno,2), head.docno, head.clientname,
          head.address, head.yourref,client.client,  head.rem,
          date(detail.postdate),
          head.terms,proj.code,proj.name, 
          tcp.tcp,tcp.ocp,coa.alias,detail.db,detail.cr
          union all
          select head.trno, date(head.dateid) as dateid,left(head.docno,2) as dco, head.docno, head.clientname,
          head.address, head.yourref,client.client, head.rem,
          date(detail.postdate) as postdate,head.terms,proj.code as projcode,
          proj.name as projname, 
          if(coa.alias = 'ar2',detail.db,0) as retention,
          if(coa.alias = 'ar3',detail.cr,0) as recoup,
          if(coa.alias = 'ar4',detail.db,0) as ewt,
          if(coa.alias = 'ar1',detail.db,0) as ar,
          detail.db as totalamt,
          tcp.tcp,tcp.ocp
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join client on client.clientid=detail.clientid
          left join projectmasterfile as proj on proj.line= head.projectid
          left join (select projectid,tcp,ocp from pmhead
          union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
          where head.doc='PB' and head.trno= " . $trno . "
          group by head.trno, date(head.dateid),left(head.docno,2), head.docno, head.clientname,
          head.address, head.yourref,client.client,  head.rem,
          date(detail.postdate),
          head.terms,proj.code,proj.name, 
          tcp.tcp,tcp.ocp,coa.alias,detail.db,detail.cr) as x
          group by trno, dateid, dco, docno, clientname, address,
          yourref, client, rem, postdate, terms, projcode, projname, tcp, ocp
        ";

        break;
      case '4':
        $query = "select trno,docnum,docno,dateid,projectid,projcode,projname,tcp,ocp,ceffect,cdue,subproject,refx,conduration,yourref,ourref 
                  from (select head.trno,right(head.docno,13) as docnum,head.docno,head.dateid,head.projectid,
                               proj.code as projcode, proj.name as projname,
                               pm.tcp, date(pm.dateid) as ceffect,date(pm.due) as cdue,pm.ocp, 
                               head.subproject,detail.refx,pm.conduration,head.yourref,head.ourref
                        from lahead as head
                        left join ladetail as detail on detail.trno=head.trno
                        left join projectmasterfile as proj on proj.line= head.projectid
                        left join (select projectid,tcp,dateid,due,ocp,conduration from pmhead
                                  union all
                                  select projectid,tcp,dateid,due,ocp,conduration from hpmhead as pmhead) as pm on pm.projectid = head.projectid
                        where head.trno = $trno
                        union all
                        select head.trno,right(head.docno,13) as docnum,head.docno,head.dateid,head.projectid,proj.code as projcode, proj.name as projname,
                               pm.tcp, date(pm.dateid) as ceffect,date(pm.due) as cdue,pm.ocp, 
                               head.subproject,detail.refx,pm.conduration,head.yourref,head.ourref
                        from glhead as head
                        left join gldetail as detail on detail.trno=head.trno
                        left join projectmasterfile as proj on proj.line= head.projectid
                        left join (select projectid,tcp,dateid,due,ocp,conduration from pmhead
                                  union all
                                  select projectid,tcp,dateid,due,ocp,conduration from hpmhead as pmhead) as pm on pm.projectid = head.projectid
                                  where head.trno = $trno) as a
                    group by refx,trno,docnum,docno,dateid,projectid,projcode,projname,tcp,ceffect,cdue,subproject,ocp,conduration,yourref,ourref";
        break;

      default:
        $query = "select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
            head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
            coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, 
            detail.checkno, detail.db, detail.cr, detail.line,head.rem
            from ((lahead as head 
            left join ladetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.client=detail.client
            where head.doc='PB' and head.trno= " . $trno . " 
            union all
            select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
            head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
            coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, 
            detail.checkno, detail.db, detail.cr, detail.line,head.rem
            from ((glhead as head 
            left join gldetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.clientid=detail.clientid
            where head.doc='PB' and head.trno= " . $trno . " order by line";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportplotting($config, $data)
  {
    $bill = $config['params']['dataparams']['radiobilling'];
    switch ($bill) {
      case '1':
        return $this->billing_invoice_PDF($config, $data);

        break;
      case '2':
        return $this->billing_invoice_advances_PDF($config, $data);
        break;
      case '3':
        return $this->billing_invoice_retention_PDF($config, $data);
        break;
      case '4':
        return $this->billing_invoice_summary_PDF($config, $data);
        break;
      default:
        return $this->default_PB_PDF($config, $data);
        break;
    }
  }

  private function rpt_gj_header_default($config, $result)
  {

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];
    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PROGRESS BILLING', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER: ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->address) ? $result[0]->address : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->yourref) ? $result[0]->yourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_layout($config)
  {
    $result = $this->default_query($config);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_gj_header_default($config, $result);


    $totaldb = 0;
    $totalcr = 0;
    foreach ($result as $key => $data) {
      $debit = number_format($data->db, 2);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data->cr, 2);
      $credit = $credit < 0 ? '-' : $credit;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acno, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->acnoname, '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->ref, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->postdate, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->client, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data->db;
      $totalcr = $totalcr + $data->cr;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_gj_header_default($config, $result);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


  public function billing_invoice($config)
  {
    $result = $this->report_default_query($config);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .=  '<div style="margin-top:10px; position: absolute;">&nbsp</div>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='margin-top: 10px;'>" . '' . "</div>", '620', null, false, '1px solid ', '', 'R', 'Century Gothic', '14', '', '', '');
    $str .= $this->reporter->col('', '280', null, false, '1px solid ', '', 'R', 'Century Gothic', '14', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $date = date('M d, Y', strtotime($result[0]->dateid));

    $str .=  '<div style="top:145px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:90px;">' . (isset($result[0]->clientname) ? $result[0]->clientname : '') . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('<div style="margin-left:10px;">' . (isset($date) ? $date : '') . '</div>', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:165px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:90px;">' . (isset($result[0]->address) ? $result[0]->address : '') . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('<div style="margin-left:10px;">' . (isset($result[0]->terms) ? $result[0]->terms : '') . '</div>', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:185px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:130px;">' . '' . '</div>', '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('<div style="margin-left:-10px;">' . '' . '</div>', '250', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('<div style="margin-left:-30px;">' . '' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .=  '<div style="top:300px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:150px;">' . (isset($result[0]->projname) ? $result[0]->projname : '') . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .=  '<div style="top:410px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:180px;">' . (isset($result[0]->rem) ? $result[0]->rem : '') . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .=  '<div style="top:500px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:250px;">' . 'Gross Amount' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->totalamt, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:530px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:250px;">' . '15% Recoupment' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->recoup, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:550px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:250px;">' . '10% Retention' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->retention, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:570px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:250px;">' . '2% EWT' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->ewt, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:590px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:250px;">' . 'Due for Payment' . '</div>', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->ar, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .=  '<div style="top:650px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:180px;">' . 'APPROVED FOR PAYMENT: ' . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:690px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:180px;">' . $config['params']['dataparams']['approved'] . '</div>', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '30px', '2px');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .=  '<div style="top:750px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style="left:100px;">' . number_format($result[0]->ar, 2) . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .=  '<div style="top:850px; position: absolute;">';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left:100px;">' . $config['params']['dataparams']['received'] . '</div>', '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('<div style=margin-right:120px;">' . $config['params']['dataparams']['prepared'] . '</div>', '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', 'Century Gothic', '15', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";



    $str .= "</div>";
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_PB_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'sbc.png', '45', '35', 100, 40);


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]->docno) ? $data[0]->docno : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(490, 0, (isset($data[0]->clientname) ? $data[0]->clientname : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->dateid) ? $data[0]->dateid : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(490, 0, (isset($data[0]->address) ? $data[0]->address : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->yourref) ? $data[0]->yourref : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Notes: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(490, 0, (isset($data[0]->rem) ? $data[0]->rem : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'C', false, 0);
    PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_PB_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PB_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $acno = $data[$i]->acno;
        $acnoname = $data[$i]->acnoname;
        $ref = $data[$i]->ref;
        $postdate = $data[$i]->postdate;
        $debit = number_format($data[$i]->db, $decimalcurr);
        $credit = number_format($data[$i]->cr, $decimalcurr);
        $client = $data[$i]->client;
        $debit = $debit < 0 ? '-' : $debit;
        $credit = $credit < 0 ? '-' : $credit;

        $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
        $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '40', 0);
        $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
        $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(160, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
        }

        $totaldb += $data[$i]->db;
        $totalcr += $data[$i]->cr;

        if (intVal($i) + 1 == $page) {
          $this->default_PB_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(425, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function billing_invoice_PDF_HEADER($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");
  }

  public function billing_invoice_PDF_SUMM_HEADER($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [800, 1200]);
    PDF::SetMargins(40, 20);


    $left = '';
    $top = '';
    $right = '';
    $bottom = '';

    PDF::setCellPadding($left, $top, $right, $bottom);
    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(0, 0, "");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(1150, 0, "DETAILS OF BILLING ACCOMPLISHMENT", '', 'C', false);

    $arrprojname = $this->reporter->fixcolumn([$data[0]->projname . ' ' . $data[0]->projcode], 100, 0);
    $cprojname = count($arrprojname);

    for ($r = 0; $r < $cprojname; $r++) {
      PDF::MultiCell(300, 0, '', '', 'C', false, 0);
      PDF::MultiCell(550, 0, (isset($arrprojname[$r]) ? $arrprojname[$r] : ''), '', 'C', false, 1);
      PDF::MultiCell(300, 0, '', '', 'C', false);
    }

    PDF::SetFont($fontbold, '', 12);
    $dcno = $data[0]->yourref;
    $pbnotes = $params['params']['dataparams']['notes'];
    $wacno = $params['params']['dataparams']['remarks'];
    $billperiod = $params['params']['dataparams']['customername'];
    PDF::MultiCell(1150, 15, "PROGRESS BILLING NO. " . $dcno, '', 'C', false);

    if ($wacno != '') {
      PDF::MultiCell(1150, 0, "WAC NO: " . $wacno, '', 'C', false);
    }
    PDF::MultiCell(1150, 0, "BILLING PERIOD: " . $billperiod, '', 'C', false);

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'sbc.png', '70', '20', 120, 65);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "i.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "CONTRACT AMOUNT: ", '', 'L', false, 0);

    if ($data[0]->ocp == '0.00' || $data[0]->ocp == '') {
      PDF::MultiCell(680, 0, number_format($data[0]->tcp, 2), '', 'L', false, 0);
    } else {
      PDF::MultiCell(680, 0, number_format($data[0]->ocp, 2), '', 'L', false, 0);
    }
    PDF::MultiCell(50, 0, "DATE: ", '', 'L', false, 0);
    PDF::MultiCell(100, 0, $data[0]->dateid, '', 'L', false);

    PDF::MultiCell(20, 0, "ii.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "CONTRACTOR: ", '', 'L', false, 0);
    PDF::MultiCell(580, 0, $this->companysetup->getcompanyname($params['params']), '', 'L', false);

    PDF::MultiCell(20, 0, "iii.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "CONTRACT DURATION: ", '', 'L', false, 0);
    PDF::MultiCell(580, 0, $data[0]->conduration, '', 'L', false);

    PDF::MultiCell(20, 0, "iv.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "CONTRACT EFFECTIVITY: ", '', 'L', false, 0);
    PDF::MultiCell(580, 0, $data[0]->ceffect, '', 'L', false);

    PDF::MultiCell(20, 0, "v.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "CONTRACT EXPIRATION: ", '', 'L', false, 0);
    PDF::MultiCell(580, 0, $data[0]->cdue, '', 'L', false);


    PDF::MultiCell(20, 0, "vi.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "REVISED CONTRACT EXPIRATION: ", '', 'L', false, 0);
    if ($data[0]->ocp == '0.00' || $data[0]->ocp == '') {
      PDF::MultiCell(580, 0, number_format($data[0]->ocp, 2), '', 'L', false);
    } else {
      PDF::MultiCell(580, 0, number_format($data[0]->tcp, 2), '', 'L', false);
    }

    PDF::MultiCell(0, 0, "\n");

    $left = '10';
    $top = '';
    $right = '';
    $bottom = '';

    PDF::setCellPadding($left, $top, $right, $bottom);

    PDF::SetFont($fontbold, '', 10);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 15, 'Item no.', 'TLR', 'C', 1, 0);
    PDF::MultiCell(300, 15, 'Description of Works', 'TLR', 'C', 1, 0);
    PDF::MultiCell(80, 15, 'Qty', 'TLR', 'C', 1, 0);
    PDF::MultiCell(60, 15, 'Unit', 'TLR', 'C', 1, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(90, 15, 'ORIG. Unit Price', 'TLRB', 'C', 1, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(110, 15, 'ORIG. Total Price', 'TLRB', 'C', 1, 0);
    PDF::SetFillColor(176, 196, 222);
    PDF::MultiCell(430, 15, $pbnotes, 'TLRB', 'C', 1);

    $left = '2';
    PDF::setCellPadding($left, $top, $right, $bottom);


    PDF::SetFont($fontbold, '', 10);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 15, '', 'LRB', 'C', 1, 0);
    PDF::MultiCell(300, 15, '', 'LRB', 'C', 1, 0);
    PDF::MultiCell(80, 15, '', 'LRB', 'C', 1, 0);
    PDF::MultiCell(60, 15, '', 'LRB', 'C', 1, 0);
    PDF::MultiCell(90, 15, '(Peso)', 'TLRB', 'C', 1, 0);
    PDF::MultiCell(110, 15, '(Peso)', 'TLRB', 'C', 1, 0);

    PDF::MultiCell(143, 15, 'PREVIOUS', 'TLRB', 'C', false, 0);
    PDF::MultiCell(143, 15, 'THIS PERIOD', 'TLRB', 'C', false, 0);
    PDF::MultiCell(144, 15, 'TO DATE', 'TLRB', 'C', false);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 15, '', 'TLRB', 'C', false, 0);
    PDF::MultiCell(300, 15, '', 'TLRB', 'C', false, 0);

    PDF::MultiCell(140, 15, '(a)', 'TLRB', 'C', false, 0);

    PDF::MultiCell(90, 15, '(b)', 'TLRB', 'C', false, 0);
    PDF::MultiCell(110, 15, '(axb)', 'TLRB', 'C', false, 0);

    PDF::MultiCell(60, 15, 'QTY', 'TLRB', 'C', false, 0);
    PDF::MultiCell(83, 15, 'AMOUNT', 'TLRB', 'C', false, 0);

    PDF::MultiCell(60, 15, 'QTY', 'TLRB', 'C', false, 0);
    PDF::MultiCell(83, 15, 'AMOUNT', 'TLRB', 'C', false, 0);

    PDF::MultiCell(60, 15, 'QTY', 'TLRB', 'C', false, 0);
    PDF::MultiCell(84, 15, 'AMOUNT', 'TLRB', 'C', false);
  }

  public function billing_invoice_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontitalic = "";
    $fontbolditalic = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALI.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
      $fontbolditalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALBI.ttf');
    }
    $this->billing_invoice_PDF_HEADER($params, $data);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    if (!empty($data)) {
      PDF::SetFont($fontbold, '', $fontsize);
      $maxh = PDF::GetStringHeight(300, $data[0]->clientname);
      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(360, 0, $data[0]->clientname, '', 'L', false, 0, 118, 152);
      $date = date('M d, Y', strtotime($data[0]->dateid));
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(100, 0, $date, '', 'R', false, 0, 477, 152);

      PDF::MultiCell(0, $maxh, "\n\n");

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]->address, '', 'L', false, 0, 118, 170);

      PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");

      PDF::MultiCell(475, 0, $data[0]->projname, '', 'L', false, 0, 118, 260);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(475, 0, $data[0]->projcode, '', 'L', false, 0, 118, 280);

      PDF::MultiCell(0, 0, "\n\n\n\n");

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(430, 0, $data[0]->rem, '', 'L', false, 0, 175, 345);

      PDF::MultiCell(0, 0, "\n\n");
      PDF::SetFont($fontitalic, '', $fontsize);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Gross Amount:', '', 'L', false, 0, 290, 414);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(150, 0, number_format($data[0]->totalamt, $decimalcurr), '', 'R', false, 1, 434, 414);

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($fontitalic, '', $fontsize);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(200, 0, 'Less 10% Retention', '', 'L', false, 0, 290, 445);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(150, 0, number_format($data[0]->retention, $decimalcurr), '', 'R', false, 1, 434, 445);

      PDF::SetFont($fontitalic, '', $fontsize);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '15% Recoupment', '', 'L', false, 0, 290, 463);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(150, 0, number_format($data[0]->recoup, $decimalcurr), '', 'R', false, 1, 434, 463);

      $due = 0;
      $due = $data[0]->totalamt - ($data[0]->retention + $data[0]->recoup);

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, '2% WT', '', 'R', false, 0, 290, 481);
      PDF::MultiCell(180, 0, number_format($data[0]->ewt, $decimalcurr), '', 'R', false, 1, 404, 481);

      $netamt = $due - $data[0]->ewt;
      PDF::SetFont($fontbolditalic, '', $fontsize);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Due for Payment:', '', 'L', false, 0, 290, 499);
      PDF::MultiCell(150, 0, number_format($netamt, $decimalcurr), '', 'R', false, 1, 434, 499);

      PDF::MultiCell(0, 0, "\n\n\n\n");
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, 'APPROVED FOR PAYMENT:', '', 'L', false, 0, 170, 530);

      PDF::MultiCell(0, 0, "\n\n");

      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(430, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0, 170, 565);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(150, 0, number_format($data[0]->ar, $decimalcurr), '', 'R', false, 1, 430, 585);

      PDF::MultiCell(0, 200, "\n\n\n\n\n");

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], '', 'L', false, 0, 140, 770);
      PDF::MultiCell(150, 0, '', '', 'L', false, 0);

      PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0, 470, 770);
    }


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function billing_invoice_advances_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->billing_invoice_PDF_HEADER($params, $data);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    if (!empty($data)) {
      PDF::SetFont($font, '', $fontsize);
      $maxh = PDF::GetStringHeight(300, $data[0]->clientname);
      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]->clientname, '', 'L', false, 0);
      $date = date('M d, Y', strtotime($data[0]->dateid));
      PDF::MultiCell(100, 0, $date, '', 'R', false, 0);

      PDF::MultiCell(0, $maxh, "\n");

      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]->address, '', 'L', false, 0);
      PDF::MultiCell(100, 0, $data[0]->terms, '', 'R', false, 0);

      PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(450, 0, $data[0]->projname, '', 'L', false);

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(450, 0, $data[0]->projcode, '', 'L', false);

      PDF::MultiCell(0, 0, "\n\n\n\n");

      if ($data[0]->dco == 'AD') {
        PDF::MultiCell(120, 0, '', '', 'L', false, 0);
        PDF::MultiCell(430, 0, '15% ADVANCE PAYMENT', '', 'L', false, 0);
      }

      PDF::MultiCell(0, 0, "\n\n\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Gross Amount', '', 'L', false, 0);
      if ($data[0]->ocp != 0) {
        PDF::MultiCell(150, 0, number_format($data[0]->ocp, $decimalcurr), '', 'R', false, 1);
      } else {
        PDF::MultiCell(150, 0, number_format($data[0]->tcp, $decimalcurr), '', 'R', false, 1);
      }


      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '15%', '', 'L', false, 0);
      PDF::MultiCell(150, 0, number_format($data[0]->recoup, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '2% EWT', '', 'L', false, 0);
      PDF::MultiCell(150, 0, number_format($data[0]->ewt, $decimalcurr), '', 'R', false, 1);

      $due = $data[0]->recoup - $data[0]->ewt;

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Due for Payment', '', 'L', false, 0);
      PDF::MultiCell(150, 0, number_format($due, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(0, 0, "\n\n\n\n");

      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, 'APPROVED FOR PAYMENT:', '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n\n");

      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(150, 0, number_format($data[0]->ar, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(0, 200, "\n\n\n\n\n");

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], '', 'L', false, 0);
      PDF::MultiCell(150, 0, '', '', 'L', false, 0);

      PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    }


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function billing_invoice_retention_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->billing_invoice_PDF_HEADER($params, $data);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    if (!empty($data)) {
      PDF::SetFont($font, '', $fontsize);
      $maxh = PDF::GetStringHeight(300, $data[0]->clientname);
      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]->clientname, '', 'L', false, 0);
      $date = date('M d, Y', strtotime($data[0]->dateid));
      PDF::MultiCell(100, 0, $date, '', 'R', false, 0);

      PDF::MultiCell(0, $maxh, "\n");

      PDF::MultiCell(75, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]->address, '', 'L', false, 0);
      PDF::MultiCell(100, 0, $data[0]->terms, '', 'R', false, 0);

      PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(450, 0, $data[0]->projname, '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n\n\n\n");

      PDF::MultiCell(120, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, 'TEN PERCENT (10%) RETENTION', '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n\n\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Gross Amount', '', 'L', false, 0);
      PDF::MultiCell(150, 0, number_format($data[0]->retention, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, 'Due for Payment', '', 'L', false, 0);
      PDF::MultiCell(50, 0, 'Php', '', 'R', false, 0);
      PDF::MultiCell(100, 0, number_format($data[0]->retention, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(0, 0, "\n\n\n\n");

      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, 'APPROVED FOR PAYMENT:', '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n\n");

      PDF::MultiCell(170, 0, '', '', 'L', false, 0);
      PDF::MultiCell(430, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(250, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(50, 0, 'Php', '', 'R', false, 0);
      PDF::MultiCell(100, 0, number_format($data[0]->retention, $decimalcurr), '', 'R', false, 1);

      PDF::MultiCell(0, 0, "\n\n\n\n\n");

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::MultiCell(100, 0, $params['params']['dataparams']['received'], '', 'L', false, 0);
      PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], '', 'R', false, 0);
    }

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function billing_invoice_summary_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->billing_invoice_PDF_SUMM_HEADER($params, $data);

    $trno = $data[0]->trno;

    $subproject = "select distinct sp.subproject,head.subproject as subprojid from hbahead as head
                   left join subproject as sp on sp.line = head.subproject where pbtrno = $trno";

    $spresult = json_decode(json_encode($this->coreFunctions->opentable($subproject)), true);

    $left = '5';
    $top = '';
    $right = '';
    $bottom = '';
    $gtotalorigprice = 0;
    $gtotalprevamt = 0;
    $gtotalnowamt = 0;
    $gtotaltodateamt = 0;

    PDF::setCellPadding($left, $top, $right, $bottom);
    $a = 'A';

    for ($k = 0; $k < count($spresult); $k++) {
      PDF::SetFont($fontbold, '', 10);
      PDF::SetFillColor(220, 255, 255);
      PDF::MultiCell(360, 15, $a . '. ' . $spresult[$k]['subproject'], 'TLRB', 'L', 1, 0);
      PDF::MultiCell(140, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(90, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(110, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(60, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(83, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(60, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(83, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(60, 15, '', 'TLRB', 'C', 1, 0);
      PDF::MultiCell(84, 15, '', 'TLRB', 'C', 1);

      $subprojid = $spresult[$k]['subprojid'];

      $substage = "select distinct a.substage,s.description,s.line as stage,s.stage as stagename from hbahead as head
                left join hbastock as stock on stock.trno=head.trno
                left join substages as a on a.line=stock.activity
                left join stagesmasterfile as s on s.line=stock.stage
                where pbtrno = $trno and head.subproject =$subprojid ";

      $ssresult = json_decode(json_encode($this->coreFunctions->opentable($substage)), true);

      $countarr = 0;
      for ($i = 0; $i < count($ssresult); $i++) {
        $maxrow = 1;
        $wdescname = $this->reporter->fixcolumn([$ssresult[$i]['description']], '58', 0);
        $countarr = count($wdescname);
        $maxrow = $countarr;


        PDF::SetFont($fontbold, '', 10);

        PDF::MultiCell(60, 15, $i + 1, 'TLRB', 'C', false, 0);
        PDF::MultiCell(300, 15, $ssresult[$i]['substage'], 'TLRB', 'L', false, 0);
        PDF::MultiCell(140, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(90, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(110, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(60, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(83, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(60, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(83, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(60, 15, '', 'TLRB', 'C', false, 0);
        PDF::MultiCell(84, 15, '', 'TLRB', 'C', false);


        for ($m = 0; $m < $maxrow; $m++) {
          $left = '';

          PDF::SetFont($font, '', 9);
          PDF::setCellPadding($left, $top, $right, $bottom);
          PDF::MultiCell(60, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(300, 0, isset($wdescname[$m]) ? ' ' . $wdescname[$m] : '', 'LR', 'L', false, 0);
          PDF::MultiCell(140, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(90, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(110, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(83, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(83, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LR', 'C', false, 0);
          PDF::MultiCell(84, 0, '', 'LR', 'C', false);
        }
        PDF::SetFont($font, '', 1);
        PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(300, 0, '', 'LRB', 'L', false, 0);
        PDF::MultiCell(140, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(110, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(83, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(83, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
        PDF::MultiCell(84, 0, '', 'LRB', 'C', false);

        $subactivity = '';
        $todateqty = 0;
        $todateext = 0;
        $r = 1;
        $l = 1;
        $y = 0;
        $s = 1;
        $o = 0;

        $totalorigprice = 0;
        $totalprevamt = 0;
        $totalnowamt = 0;
        $totaltodateamt = 0;



        $stageid =  $ssresult[$i]['stage'];
        $subactivity = "select distinct sa.subactivity,head.subproject,stock.stage,stock.activity,stock.subactivity from hbahead as head
                    left join hbastock as stock on stock.trno=head.trno
                    left join subactivity as sa on sa.line=stock.subactivity
                    where pbtrno = $trno and head.subproject=$subprojid and stock.stage=$stageid";
        $saresult = json_decode(json_encode($this->coreFunctions->opentable($subactivity)), true);

        $works = "select sum(prrqty) as prrqty, sum(prrcost) as prrcost, sum(pext) as pext, 
                  sum(prevrrqty) as prevrrqty, sum(prevext) as prevext, sum(nowrrqty) as nowrrqty,
                  sum(nowext) as nowext, task,
                  puom from (select head.projectid,
                  psub.rrqty as prrqty, psub.rrcost as prrcost, psub.ext as pext, psub.uom as puom,
                  stock.activity,stock.subactivity as headsubact,
                  ifnull((select sum(hs.rrqty) from hbahead as hb left join hbastock as hs on hs.trno = hb.trno
                  where hb.pbtrno < $trno and  hs.subactivity=stock.subactivity),0) as prevrrqty,
                  ifnull((select sum(hs.ext) from hbahead as hb left join hbastock as hs on hs.trno = hb.trno
                  where hb.pbtrno < $trno and  hs.subactivity=stock.subactivity),0) as prevext,
                  ifnull((select sum(hs.rrqty) from hbahead as hb left join hbastock as hs on hs.trno = hb.trno
                  where hb.pbtrno = $trno and  hs.subactivity=stock.subactivity),0) as nowrrqty,
                  ifnull((select sum(hs.ext) from hbahead as hb left join hbastock as hs on hs.trno = hb.trno
                  where hb.pbtrno = $trno and  hs.subactivity=stock.subactivity),0) as nowext,
                  sp.subproject,s.stage,s.description,a.substage,sa.subactivity,sa.description as task
              from hbahead as head
              left join hbastock as stock on stock.trno=head.trno
              left join subproject as sp on sp.line = head.subproject
              left join stagesmasterfile as s on s.line=stock.stage
              left join substages as a on a.line=stock.activity
              left join subactivity as sa on sa.line=stock.subactivity
              left join psubactivity as psub on psub.line=sa.line
              where pbtrno = $trno and head.subproject=$subprojid and stock.stage=$stageid and psub.rrqty <> 0 and psub.rrcost <> 0 and psub.ext <> 0
              group by head.projectid,stock.activity,
                    sp.subproject,s.stage,s.description,a.substage,sa.subactivity,sa.description,psub.uom,
                    psub.rrqty,psub.rrcost,psub.ext,stock.rrcost,stock.subactivity
                    order by a.substage,sa.subactivity) as x
              group by puom, task";


        $worksresult = json_decode(json_encode($this->coreFunctions->opentable($works)), true);

        for ($e = 0; $e < count($worksresult); $e++) {
          $todateqty = $worksresult[$e]['prevrrqty'] + $worksresult[$e]['nowrrqty'];
          $todateext = $worksresult[$e]['prevext'] + $worksresult[$e]['nowext'];
          $left = '5';
          PDF::setCellPadding($left, $top, $right, $bottom);


          $qty = number_format($worksresult[$e]['prrqty'], 2);
          $prrcost = number_format($worksresult[$e]['prrcost'], 2);
          $pext = number_format($worksresult[$e]['pext'], 2);
          $prevrrqty = number_format($worksresult[$e]['prevrrqty'], 2) == 0.00 ? '-' : number_format($worksresult[$e]['prevrrqty'], 2);
          $prevext = number_format($worksresult[$e]['prevext'], 2) == 0.00 ? '-' : number_format($worksresult[$e]['prevext'], 2);
          $nowrrqty = number_format($worksresult[$e]['nowrrqty'], 2) == 0.00 ? '-' : number_format($worksresult[$e]['nowrrqty'], 2);
          $nowext = number_format($worksresult[$e]['nowext'], 2) == 0.00 ? '-' : number_format($worksresult[$e]['nowext'], 2);
          $todateqty = number_format($worksresult[$e]['prevrrqty'] + $worksresult[$e]['nowrrqty'], 2);
          $todateext = number_format($worksresult[$e]['prevext'] + $worksresult[$e]['nowext'], 2);

          $maxrow = 1;
          $wtask = $this->reporter->fixcolumn([$worksresult[$e]['task']], '58', 0);
          $wprrqty = $this->reporter->fixcolumn([$qty], '10', 0);
          $wpuom = $this->reporter->fixcolumn([$worksresult[$e]['puom']], '20', 0);
          $wprrcost = $this->reporter->fixcolumn([$prrcost], '15', 0);
          $wpext = $this->reporter->fixcolumn([$pext], '15', 0);
          $wprevrrqty = $this->reporter->fixcolumn([$prevrrqty], '15', 0);
          $wprevext = $this->reporter->fixcolumn([$prevext], '15', 0);
          $wnowrrqty = $this->reporter->fixcolumn([$nowrrqty], '15', 0);
          $wnowext = $this->reporter->fixcolumn([$nowext], '15', 0);
          $wtodateqty = $this->reporter->fixcolumn([$todateqty], '15', 0);
          $wtodateext = $this->reporter->fixcolumn([$todateext], '15', 0);



          $countarrtask = count($wtask);
          $countarrqty = count($wprrqty);
          $countarruom = count($wpuom);
          $countarrcost = count($wprrcost);
          $countarrext = count($wpext);
          $countarrprevqty = count($wprevrrqty);
          $countarrprevext = count($wprevext);
          $countarrnowqty = count($wnowrrqty);
          $countarrnowext = count($wnowext);
          $countarrtoqty = count($wtodateqty);
          $countarrtoext = count($wtodateext);

          $maxrow = $this->othersClass->getmaxcolumn([
            $countarrtask,
            $countarrqty,
            $countarruom,
            $countarrcost,
            $countarrext,
            $countarrprevqty,
            $countarrprevext,
            $countarrnowqty,
            $countarrnowext,
            $countarrtoqty,
            $countarrtoext
          ]);



          for ($b = 0; $b < $maxrow; $b++) {
            $left = '';

            PDF::SetFont($font, '', 9);
            PDF::setCellPadding($left, $top, $right, $bottom);
            PDF::MultiCell(60, 0, '1.' . $r . '.' . $s, 'LR', 'C', false, 0);
            PDF::MultiCell(300, 0, isset($wtask[$b]) ? ' ' . $wtask[$b] : '', 'LR', 'L', false, 0);

            PDF::MultiCell(80, 0, isset($wprrqty[$b]) ? ' ' . $wprrqty[$b] : '', 'L', 'R', false, 0);
            PDF::MultiCell(60, 0, isset($wpuom[$b]) ? ' ' . $wpuom[$b] : '', 'R', 'L', false, 0);

            PDF::MultiCell(90, 0, isset($wprrcost[$b]) ? ' ' . $wprrcost[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(110, 0, isset($wpext[$b]) ? ' ' . $wpext[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(60, 0, isset($wprevrrqty[$b]) ? ' ' . $wprevrrqty[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(83, 0, isset($wprevext[$b]) ? ' ' . $wprevext[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(60, 0, isset($wnowrrqty[$b]) ? ' ' . $wnowrrqty[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(83, 0, isset($wnowext[$b]) ? ' ' . $wnowext[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(60, 0, isset($wtodateqty[$b]) ? ' ' . $wtodateqty[$b] : '', 'LR', 'R', false, 0);
            PDF::MultiCell(84, 0, isset($wtodateext[$b]) ? ' ' . $wtodateext[$b] : '', 'LR', 'R', false);
          }


          PDF::SetFont($font, '', 1);
          PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(300, 0, '', 'LRB', 'L', false, 0);

          PDF::MultiCell(80, 0, '', 'LB', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'RB', 'C', false, 0);

          PDF::MultiCell(90, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(110, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(83, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(83, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
          PDF::MultiCell(84, 0, '', 'LRB', 'C', false);

          $y = $r;
          $r = $r + 1;
          $o = $s + 1;

          $totalorigprice += $worksresult[$e]['pext'];
          $totalprevamt += $worksresult[$e]['prevext'];
          $totalnowamt += $worksresult[$e]['nowext'];
          $totaltodateamt += $todateext;

          $gtotalorigprice += $totalorigprice;
          $gtotalprevamt += $totalprevamt;
          $gtotalnowamt += $totalnowamt;
          $gtotaltodateamt += $totaltodateamt;

          if (PDF::getY() >= 720) {
            $this->billing_invoice_PDF_SUMM_HEADER($params, $data);
          }
        }
        $l = 0;
      }

      PDF::SetFont($fontbold, '', 10);
      PDF::SetFillColor(243, 229, 171);
      PDF::MultiCell(590, 0, 'TOTAL (FOR ' . $spresult[$k]['subproject'] . ')', 'LRB', 'R', 1, 0);
      PDF::MultiCell(110, 0, number_format($totalorigprice, 2), 'LRB', 'R', 1, 0);
      PDF::MultiCell(60, 0, '', 'LRB', 'C', 1, 0);
      PDF::MultiCell(83, 0, number_format($totalprevamt, 2), 'LRB', 'R', 1, 0);
      PDF::MultiCell(60, 0, '', 'LRB', 'C', 1, 0);
      PDF::MultiCell(83, 0, number_format($totalnowamt, 2), 'LRB', 'R', 1, 0);
      PDF::MultiCell(60, 0, '', 'LRB', 'C', 1, 0);
      PDF::MultiCell(84, 0, number_format($totaltodateamt, 2), 'LRB', 'R', 1);

      $totalorigprice =
        $totalprevamt =
        $totalnowamt =
        $totaltodateamt = 0;
      $charconvert = ord($a);
      $charconvert++;
      $a = chr($charconvert);
    }

    PDF::SetFont($fontbold, '', 10);
    PDF::SetFillColor(255, 192, 0);
    PDF::MultiCell(590, 0, 'GRAND TOTAL', 'TLRB', 'R', 1, 0);
    PDF::MultiCell(110, 0, number_format($gtotalorigprice, 2), 'TLRB', 'R', 1, 0);
    PDF::MultiCell(60, 0, '', 'TLRB', 'C', 1, 0);
    PDF::MultiCell(83, 0, number_format($gtotalprevamt, 2), 'TLRB', 'R', 1, 0);
    PDF::MultiCell(60, 0, '', 'TLRB', 'C', 1, 0);
    PDF::MultiCell(83, 0, number_format($gtotalnowamt, 2), 'TLRB', 'R', 1, 0);
    PDF::MultiCell(60, 0, '', 'TLRB', 'C', 1, 0);
    PDF::MultiCell(84, 0, number_format($gtotaltodateamt, 2), 'TLRB', 'R', 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
