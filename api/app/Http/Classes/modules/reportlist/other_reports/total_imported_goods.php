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
use App\Http\Classes\modules\masterfile\supplier;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;


class total_imported_goods
{
  public $modulename = 'Total Imported Goods';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
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

    $fields = ['radioprint', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['year'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'year.type', 'input');
    data_set($col2, 'year.readonly', false);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      '' as client,
      '' as clientname,
      left(now(),4) as year
      ";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $result = $this->reportDefault($config);
    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $data = $this->reportDefaultLayout($config, $result);
    return $data;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $year       = $config['params']['dataparams']['year'];

    $filter = "";
    if ($clientname != "") {
      $filter = $filter . "and c.client='$client'";
    }

    $query = "
      select a.barcode,a.itemname,sum(usd) as usd,sum(rate) as rate,
      sum(a.mojan) as mojan,sum(a.mofeb) as mofeb,sum(a.momar) as momar,sum(a.moapr) as moapr,
      sum(a.momay) as momay,sum(a.mojun) as mojun,sum(a.mojul) as mojul,sum(a.moaug) as moaug,
      sum(a.mosep) as mosep,sum(a.mooct) as mooct,sum(a.monov) as monov,sum(a.modec) as modec
      from (
      select head.doc,head.docno,head.dateid,i.barcode,i.itemname,sum(i.amt9) as usd,sum(head.forex) as rate,
      sum(case when month(head.dateid)=1 then stock.qty else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then stock.qty else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then stock.qty else 0 end) as momar,
      sum(case when month(head.dateid)=4 then stock.qty else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then stock.qty else 0 end) as momay,
      sum(case when month(head.dateid)=6 then stock.qty else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then stock.qty else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then stock.qty else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then stock.qty else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then stock.qty else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then stock.qty else 0 end) as monov,
      sum(case when month(head.dateid)=12 then stock.qty else 0 end) as modec
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item as i on i.itemid=stock.itemid
      left join client as c on c.client=head.client
      where head.doc='RR' $filter
      group by head.doc,head.docno,head.dateid,i.barcode,i.itemname
      union all
      select head.doc,head.docno,head.dateid,i.barcode,i.itemname,sum(i.amt9) as usd,sum(head.forex) as rate,
      sum(case when month(head.dateid)=1 then stock.qty else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then stock.qty else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then stock.qty else 0 end) as momar,
      sum(case when month(head.dateid)=4 then stock.qty else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then stock.qty else 0 end) as momay,
      sum(case when month(head.dateid)=6 then stock.qty else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then stock.qty else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then stock.qty else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then stock.qty else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then stock.qty else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then stock.qty else 0 end) as monov,
      sum(case when month(head.dateid)=12 then stock.qty else 0 end) as modec
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item as i on i.itemid=stock.itemid
      left join client as c on c.clientid=head.clientid
      where head.doc='RR' $filter
      group by head.doc,head.docno,head.dateid,i.barcode,i.itemname
      ) as a
      group by a.barcode,a.itemname
      ";
    return $query;
  }


  private function default_displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $year       = $config['params']['dataparams']['year'];

    if ($client == "") {
      $client = "ALL";
    }


    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('Total Imported Goods', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier : ' . strtoupper($client), NULL, null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Year : ' . $year, null, null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM DESCRIPTION', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('UNIT PRICE IN USD', '60', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('RATE', '60', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $year       = $config['params']['dataparams']['year'];

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);


    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $i = 0;
    foreach ($result as $key => $data) {
      $usd = ($data->usd <> 0 ? number_format($data->usd, 2) : '-');
      $rate = ($data->rate <> 0 ? number_format($data->rate, 2) : '-');

      $mojan = ($data->mojan <> 0 ? number_format($data->mojan, 2) : '-');
      $mofeb = ($data->mofeb <> 0 ? number_format($data->mofeb, 2) : '-');
      $momar = ($data->momar <> 0 ? number_format($data->momar, 2) : '-');
      $moapr = ($data->moapr <> 0 ? number_format($data->moapr, 2) : '-');
      $momay = ($data->momay <> 0 ? number_format($data->momay, 2) : '-');
      $mojun = ($data->mojun <> 0 ? number_format($data->mojun, 2) : '-');
      $mojul = ($data->mojul <> 0 ? number_format($data->mojul, 2) : '-');
      $moaug = ($data->moaug <> 0 ? number_format($data->moaug, 2) : '-');
      $mosep = ($data->mosep <> 0 ? number_format($data->mosep, 2) : '-');
      $mooct = ($data->mooct <> 0 ? number_format($data->mooct, 2) : '-');
      $monov = ($data->monov <> 0 ? number_format($data->monov, 2) : '-');
      $modec = ($data->modec <> 0 ? number_format($data->modec, 2) : '-');

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($usd, '60', null, false, $border, '', 'L', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($rate, '60', null, false, $border, '', 'L', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalmojan = $totalmojan + $data->mojan;
      $totalmofeb = $totalmofeb + $data->mofeb;
      $totalmomar = $totalmomar + $data->momar;
      $totalmoapr = $totalmoapr + $data->moapr;
      $totalmomay = $totalmomay + $data->momay;
      $totalmojun = $totalmojun + $data->mojun;
      $totalmojul = $totalmojul + $data->mojul;
      $totalmoaug = $totalmoaug + $data->moaug;
      $totalmosep = $totalmosep + $data->mosep;
      $totalmooct = $totalmooct + $data->mooct;
      $totalmonov = $totalmonov + $data->monov;
      $totalmodec = $totalmodec + $data->modec;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, '9', 'b', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class