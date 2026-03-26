<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class sales_journal_series_report
{
  public $modulename = 'Sales Journal Series Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];



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

    $fields = ['radioprint', 'start', 'end', 'pref'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'pref.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as pref";
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

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY


    $query = $this->default_query($config);

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function default_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $pref = $config['params']['dataparams']['pref'];
    $filter = "";

    if ($pref != "") {
      $filter .= " and num.bref = '$pref' ";
    }

    $query = "select doc,bref,refno,docno,client,clientname,dateid,amt from (
        select head.doc,num.bref, (right(head.docno,12) * 1) as refno,head.docno, 
                head.client, head.clientname, head.dateid, sum(stock.ext) as amt
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno=head.trno
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        group by head.docno,head.client,head.clientname,head.dateid,head.doc,num.bref
        union all
        select head.doc,num.bref, (right(head.docno,12) * 1) as refno,head.docno,
                client.client, head.clientname, head.dateid, sum(stock.ext) as amt
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client on client.clientid = head.clientid
        left join cntnum as num on num.trno=head.trno
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        group by head.docno,client.client,head.clientname,head.dateid,head.doc,num.bref) as a
        order by bref,refno,dateid";
    return $query;
  }



  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $pref     = $config['params']['dataparams']['pref'];

    $str = '';


    $layoutsize = '700';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($pref == "") {
      $pref = 'ALL';
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INVOICE SERIES REPORT', '400', null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col(date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '400', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page : ', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREFIX', '100', null, false, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REF.', '50', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO.#', '50', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '250', null, false, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INV. DATE', '100', null, false, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    return $str;
  }


  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $this->reporter->linecounter = 0;
    $count = 76;
    $page = 75;

    $str = '';
    $layoutsize = '700';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    if (empty($result)) {
    } else {
      $totalext = 0;
      $totalbal = 0;
      $ctr = $result[0]['refno'];
      $data = [];
      $max = 0;
      for ($i = 0; $i < count($result); $i++) {
        $data[$result[$i]['refno']]['bref'] = $result[$i]['bref'];
        $data[$result[$i]['refno']]['refno'] = $result[$i]['refno'];
        $data[$result[$i]['refno']]['clientname'] = $result[$i]['clientname'];
        $data[$result[$i]['refno']]['dateid'] = $result[$i]['dateid'];
        $data[$result[$i]['refno']]['amt'] = $result[$i]['amt'];
      }

      $end = end($data);

      for ($i = $ctr; $i <= $end['refno']; $i++) {

        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();

        if (array_key_exists($i, $data)) {

          $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['bref'], '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['refno'], '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['clientname'], '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['dateid'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data[$i]['amt'], 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("<div style='color:red;'>" . $ctr . "</div>", '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }
        $ctr += 1;

        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $page = $page + $count;
        } //end if
      }
    }


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class