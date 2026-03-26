<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class document_series_sales_journal_report
{
  public $modulename = 'Document Series Sales Journal Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'prefix', 'seqstart', 'seqend'];

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'prefix.type', 'lookup');
    data_set($col1, 'prefix.readonly', true);
    data_set($col1, 'prefix.class', 'csprefix sbccsreadonly');
    data_set($col1, 'prefix.lookupclass', 'lookupprefix');
    data_set($col1, 'prefix.action', 'lookupprefix');


    // $fields = ['radioposttype', 'radioreporttype'];
    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    //    adddate(left(now(),10),-360) as start, left(now(),10) as end,
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '2' as posttype,
    '' as prefix,
    '' as seqstart,
    '' as seqend";
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    // $center      = $config['params']['dataparams']['center'];
    $center = '';
    $isposted    = $config['params']['dataparams']['posttype'];
    $companyid = $config['params']['companyid'];

    if ($center == '') {
      $center = $config['params']['center'];
    }
    switch ($isposted) {
      case '1':
        $query = $this->defaultQuery_unposted($config);
        break;
      case '0':
        $query = $this->defaultQuery_posted($config);
        break;
      default:
        $query = $this->default_QUERY_ALL($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery_unposted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['prefix'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $seqend    = $config['params']['dataparams']['seqend'];
    $filter = "";
    $doc = "head.doc = 'SJ'";
    if ($prefix != '') {
      $doc = " head.doc = 'SJ' and cntnum.bref = '$prefix' ";
    }
    if ($seqstart != '') {
      $pos = strcspn($seqstart, '0123456789');
      $seqs = (int) substr($seqstart, $pos);
      $filter .= " and CAST(SUBSTRING(head.docno, LOCATE('0', head.docno)) AS UNSIGNED) >= $seqs ";
    }
    if ($seqend != '') {
      $posend = strcspn($seqend, '0123456789');
      $seqe = (int) substr($seqend, $posend);
      $filter .= " and CAST(SUBSTRING(head.docno, LOCATE('0', head.docno)) AS UNSIGNED) <= $seqe ";
    }

    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }

    // $filter = "";
    // $filter1 = "";
    // if ($center != '') {
    //   $filter = " and cntnum.center='$center'";
    // }

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   if ($dept != "") {
    //     $filter1 .= " and head.deptid = $deptid";
    //   }
    // }

    // switch ($isdetailed) {
    //   case '1': //detailed
    //     $query = "select docno,date(dateid) as dateid,clientname,acno,acnoname,snotes as description, sum(db-cr) as amount  from (
    //     select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname,  coa.acno, coa.acnoname,
    //     detail.db, detail.cr, info.rem as snotes
    //     from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
    //     left join cntnum on cntnum.trno=head.trno
    //     left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
    //     where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ) as exp
    //     group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
    //     order by dateid, docno";
    //     break;
    //   case '0': //summarized
    //     $query = "select acno, acnoname, sum(db-cr) as amount
    //     from (select   coa.acno, coa.acnoname,
    //     detail.db, detail.cr
    //     from lahead as head left join ladetail as detail on detail.trno=head.trno 
    //     left join coa on coa.acnoid=detail.acnoid
    //     left join cntnum on cntnum.trno=head.trno
    //     where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ) as exp
    //     group by acno, acnoname
    //     order by acnoname";
    //     break;
    // }

    //and head.dateid between '" . $start . "' and '" . $end . "'
    $query = "select head.docno, head.clientname, head.trno, sum(stock.ext) as total, head.rem,
        date(head.dateid) as dateid, 0 as paid, 0 as bal,cntnum.seq as docseq,'UNPOSTED' as status
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        where $doc $filter 
        group by head.docno, head.trno, head.rem, head.clientname, head.dateid,stock.trno,cntnum.seq
        order by docseq,docno";
    return $query;
  }


  public function defaultQuery_posted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['prefix'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $seqend    = $config['params']['dataparams']['seqend'];

    $filter = "";
    $doc = " head.doc = 'SJ'";
    if ($prefix != '') {
      $doc = " head.doc = 'SJ' and cntnum.bref = '$prefix' ";
    }
    if ($seqstart != '') {
      $pos = strcspn($seqstart, '0123456789');
      $seqs = (int) substr($seqstart, $pos);
      $filter .= " and cntnum.seq >= $seqs ";
    }
    if ($seqend != '') {
      $posend = strcspn($seqend, '0123456789');
      $seqe = (int) substr($seqend, $posend);
      $filter .= " and cntnum.seq <= $seqe ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }
    //and  head.dateid between '" . $start . "' and '" . $end . "'
    $query = "select head.docno, head.clientname, head.trno, sum(arledger.db-arledger.cr) as total, head.rem,
        date(head.dateid) as dateid, ((arledger.db+arledger.cr)-arledger.bal) as paid, arledger.bal,
        cntnum.seq as docseq,'POSTED' as status
        from glhead as head
        left join arledger on arledger.trno=head.trno 
        left join coa on coa.acnoid=arledger.acnoid
        left join cntnum on cntnum.trno=head.trno
        where $doc $filter 
        group by head.docno, head.trno, head.rem, dateid, arledger.bal, arledger.db, arledger.cr, head.clientname,cntnum.seq
        order by docseq,docno";
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['center'];
    $posttype    = $config['params']['dataparams']['posttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['prefix'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $seqend    = $config['params']['dataparams']['seqend'];

    $filter = "";
    $doc = " head.doc = 'SJ'";
    if ($prefix != '') {
      $doc = " head.doc = 'SJ' and cntnum.bref = '$prefix' ";
    }

    if ($seqstart != '') {
      $pos = strcspn($seqstart, '0123456789');
      $seqs = (int) substr($seqstart, $pos);
      $filter .= " and cntnum.seq >= $seqs ";
    }
    if ($seqend != '') {
      $posend = strcspn($seqend, '0123456789');
      $seqe = (int) substr($seqend, $posend);
      $filter .= " and cntnum.seq <= $seqe ";
    }
    if ($center != '') {
      $filter .= " and cntnum.center = '$center' ";
    }

    //and head.dateid between '" . $start . "' and '" . $end . "'
    $query = "select head.docno, head.clientname, head.trno, ifnull(sum(stock.ext),0) as total, head.rem,
        date(head.dateid) as dateid, 0 as paid, 0 as bal,
        cntnum.seq as docseq,'UNPOSTED' as status
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        where $doc $filter
        group by head.docno, head.trno, head.rem, head.clientname, head.dateid, stock.trno,cntnum.seq
         union all
        select head.docno, head.clientname, head.trno, ifnull(sum(arledger.db-arledger.cr),0) as total, head.rem,
        date(head.dateid) as dateid, ifnull(((arledger.db+arledger.cr)-arledger.bal),0) as paid, ifnull(arledger.bal,0) as bal,
        cntnum.seq as docseq,'POSTED' as status
        from glhead as head
        left join arledger on arledger.trno=head.trno 
        left join coa on coa.acnoid=arledger.acnoid
        left join cntnum on cntnum.trno=head.trno
        where $doc $filter 
        group by head.docno, head.trno, head.rem, head.dateid, arledger.bal, arledger.db, arledger.cr, head.clientname,cntnum.seq
        order by docseq,docno";


    // switch ($reporttype) {
    //   case 0: // summarized
    //     switch ($posttype) {
    //       case 2: // all
    //         $query = "select acno, acnoname, sum(db-cr) as amount
    //         from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
    //         detail.db, detail.cr, detail.rem as snotes, detail.postdate
    //         from glhead as head
    //         left join gldetail as detail on detail.trno=head.trno
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join cntnum on cntnum.trno=head.trno
    //         where head.doc in ('cv','pv') and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 $selecthjcsum) as exp
    //         group by acno, acnoname
    //         union all
    //         (select acno, acnoname, sum(db-cr) as amount
    //         from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
    //         detail.db, detail.cr, detail.rem as snotes, detail.postdate
    //         from lahead as head
    //         left join ladetail as detail on detail.trno=head.trno
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join cntnum on cntnum.trno=head.trno
    //         where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcsum) as exp
    //         group by acno, acnoname)
    //         order by acnoname;";
    //         break;
    //     }
    //     break;
    //   case 1: // detailed
    //     switch ($posttype) {
    //       case 2:
    //         $query = "select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
    //         select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
    //         detail.db, detail.cr, info.rem as snotes, detail.postdate
    //         from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
    //         left join cntnum on cntnum.trno=head.trno
    //         left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
    //         where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selecthjcdet) as exp
    //         group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
    //         union all
    //         select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
    //         select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
    //         detail.db, detail.cr, info.rem as snotes, detail.postdate
    //         from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
    //         left join cntnum on cntnum.trno=head.trno
    //         left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
    //         where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcdet) as exp
    //         group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
    //         order by dateid, docno;";
    //         break;
    //     }
    //     break;
    // }

    return $query;
  }

  public function defaultHeader_layout($config, $title)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $seqstart = $config['params']['dataparams']['seqstart'];
    $seqend = $config['params']['dataparams']['seqend'];


    $sstart = strcspn($seqstart, '0123456789');
    $seqs = (int) substr($seqstart, $sstart);


    $send = strcspn($seqend, '0123456789');
    $seqe = (int) substr($seqend, $send);

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '1100';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document Series Sales Journal Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Range: ' . $seqs . ' - ' . $seqe, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $username, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', 110, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('INV. NO.', 100, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('', 10, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('CUSTOMER', 200, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('INV. REMARKS / BTB/ CHEQUE DETAILS', 300, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('TOTAL SALES', 100, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('AMOUNT PAID', 100, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('A/R BALANCE', 100, null, '', $border, 'B', 'C', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('STATUS', 80, null, '', $border, 'B', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function reportDefaultLayout($config)
  {
    // PRINT LAYOUT
    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $seqstart = $config['params']['dataparams']['seqstart'];
    $seqend = $config['params']['dataparams']['seqend'];


    $send = strcspn($seqend, '0123456789');
    $seqe = (int) substr($seqend, $send);



    $sstart = strcspn($seqstart, '0123456789');
    $seqs = (int) substr($seqstart, $sstart);

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px dotted";
    $layoutsize = '1100';
    $bg = 'red';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $reporttype = $config['params']['dataparams']['reporttype'];
    $count = 56;
    $page = 55;
    $this->reporter->linecounter = 0;
    $str = '';
    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:50px');
    $str .= $this->defaultHeader_layout($config, "DOCUMENT SERIES SALES JOURNAL REPORT");
    $i = 0;
    for ($seqs; $seqs <= $seqe; $seqs++) {
      $foundseq = false;

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      foreach ($result as $key => $data) {
        if ($data->docseq == $seqs) {
          $i++;
          $foundseq = true;

          // $checkd = $this->coreFunctions->opentable("        
          //         select detail.trno, (
          // 		    select GROUP_CONCAT(checkno)
          //  as value from gldetail as d
          // 		    left join coa on coa.acnoid = d.acnoid where d.trno = detail.trno and left(coa.alias,2) in ('CB','CA','CR')
          //         ) value from gldetail as detail
          //         where detail.refx = " . $data->trno . "
          //         union all
          //         select detail.trno, (
          // 		    select GROUP_CONCAT(checkno)
          //  as value from ladetail as d
          // 		    left join coa on coa.acnoid = d.acnoid where d.trno = detail.trno and left(coa.alias,2) in ('CB','CA','CR')
          //         ) value from ladetail as detail
          //         where detail.refx = " . $data->trno . "
          //         ");

          $checkd = $this->coreFunctions->opentable("        
            select group_concat(checkno) as value from (select (
            select group_concat((case left(coa.alias,2) when 'CA' then concat('Cash ',format(d.db,2)) else checkno end) SEPARATOR ' ') as v from gldetail as d
            left join coa on coa.acnoid = d.acnoid where d.trno = detail.trno and left(coa.alias,2) in ('CB','CA','CR')
            ) as checkno from gldetail as detail
            where detail.refx = " . $data->trno . "
            union all
            select  (
            select group_concat((case left(coa.alias,2) when 'CA' then concat('Cash ',format(d.db,2)) else checkno end) SEPARATOR ' ') as v
          from ladetail as d  left join coa on coa.acnoid = d.acnoid where d.trno = detail.trno and left(coa.alias,2) in ('CB','CA','CR')
            ) checkno from ladetail as detail
            where detail.refx = " . $data->trno . "
            union all
            select (
            select group_concat(concat(c.bref,'-',c.seq),' - ',format(d.db,2)) as v 
            from gldetail as d left join coa on coa.acnoid = d.acnoid left join cntnum as c on c.trno = d.refx
            where left(coa.alias,2) = 'ar' and c.doc = 'cm' and d.trno = detail.trno) as a
            from gldetail as detail where detail.refx = " . $data->trno . "
            union all 
            select (select group_concat(concat(c.bref,'-',c.seq),' - ',format(d.db,2)) as v 
            from ladetail as d left join coa on coa.acnoid = d.acnoid left join cntnum as c on c.trno = d.refx
            where left(coa.alias,2) = 'ar' and c.doc = 'cm' and d.trno = detail.trno) as a
            from ladetail as detail where detail.refx = " . $data->trno . "
            ) as a
                  ");


          $checno = '';
          // foreach ($checkd as $checkey => $checvalue) {
          //   if ($checno != '') {
          //     $checno .= ', ' . $checvalue->value;
          //   } else {
          //     $checno .= $checvalue->value;
          //   }
          // }
          if (!empty($checkd)) {
            $checno =  $checkd[0]->value;
          }


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->dateid, 110, null, '', $border, 'B', 'CT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->docno, 100, null, '', $border, 'B', 'CT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', 10, null, '', $border, 'B', 'CT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->clientname, 200, null, '', $border, 'B', 'LT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($checno, 300, null, '', $border, 'B', 'LT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->total != 0 ? number_format($data->total, 2) : '-', 100, null, '', $border, 'B', 'RT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->paid != 0 ? number_format($data->paid, 2) : '-', 100, null, '', $border, 'B', 'RT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->bal != 0 ? number_format($data->bal, 2) : '-', 100, null, '', $border, 'B', 'RT', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->status, 80, null, '', $border, 'B', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          if (strlen($data->clientname) > 26) { //fix for long names
            $i = $i + 1;
          }
          break;
        }
      }

      if (!$foundseq) {
        $i++;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 110, null, '', $border, 'B', 'C', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col($seqs, 100, null, '', $border, 'B', 'C', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 10, null, '', $border, 'B', 'CT', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', 200, null, '', $border, 'B', 'L', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 300, null, '', $border, 'B', 'L', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 100, null, '', $border, 'B', 'R', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 100, null, '', $border, 'B', 'R', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 100, null, '', $border, 'B', 'R', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->col('', 80, null, '', $border, 'B', 'R', $font, $fontsize, '', $bg, '5px');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();
      if ($i >= $count) {
        $str .= $this->reporter->page_break();
        $str .= $this->defaultHeader_layout($config, "DOCUMENT SERIES SALES JOURNAL REPORT");
        $page = $page + $count;
        $i = 0;
      }
    }

    $str .= $this->reporter->endreport();
    // switch ($reporttype) {
    //   case '0': //summarized
    //     break;
    //   case '1': //detailed
    //     $count = 56;
    //     $page = 55;
    //     $this->reporter->linecounter = 0;
    //     $str = '';

    //     $str .= $this->reporter->beginreport();
    //     $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT DETAILED");
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('DATE', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
    //     $str .= $this->reporter->col('PCV#', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
    //     $str .= $this->reporter->col('NAME', '150', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
    //     $str .= $this->reporter->col('ACCT NAME', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
    //     $str .= $this->reporter->col('DESCRIPTION', '250', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');

    //     $totaldet = 0;

    //     foreach ($result as $key => $data) {
    //       $str .= $this->reporter->startrow();
    //       $detamt = number_format($data->amount, 2);
    //       if ($detamt == 0) {
    //         $detamt = '-';
    //       }

    //       $str .= $this->reporter->col($data->dateid, '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col($data->docno, '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col($data->clientname, '150', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col($data->acnoname, '100', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col($data->description, '250', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col($detamt, '100', null, '', $border, 'LRTB', 'R', $font, $fontsize, '', '', '');
    //       $totaldet = $totaldet + $data->amount;
    //       $str .= $this->reporter->endrow();
    //       if ($this->reporter->linecounter == $page) {
    //         $str .= $this->reporter->page_break();
    //         $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT DETAILED");
    //         $page = $page + $count;
    //       }
    //     }
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('TOTAL : ', '100', null, '', $border, 'LTB', 'C', $font, '10', 'B', '', '');
    //     $str .= $this->reporter->col('', '100', null, '', $border, 'TB', 'C', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '150', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '100', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '250', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col(number_format($totaldet, 2), '100', null, '', $border, 'RTB', 'R', $font, '10', 'B', '', '');

    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->endreport();
    //     break;
    // } // end switch
    return $str;
  }
}//end class