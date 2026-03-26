<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class provisional_receipt_report
{
  public $modulename = 'Provisional Receipt Report';
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

    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as `end`";
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
    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));




    $qry = "
        select a.distributor,a.sodocno,a.cwodr,a.dateid,
        ifnull((
          select kr.docno from krhead as kr
          where kr.trno=a.kr
          union all
          select kr.docno from hkrhead as kr
          where kr.trno=a.kr
          ),'') as krdocno,
        sum(a.portqty) as portqty,sum(a.pozzqty) as pozzqty,
        sum(a.portamt) as portamt,sum(a.pozzamt) as pozzamt,
        sum(a.portext) as portext,sum(a.pozzext) as pozzext
        from(
          select sjhead.clientname as distributor,ifnull(trans.docno,'') as sodocno,
          icat.name as category,
          sjhead.docno as cwodr,
          sjhead.dateid,ledger.kr,
          (case when icat.line=1 then sum(sjstock.isqty) else 0 end) as portqty,
          (case when icat.line=3114 then sum(sjstock.isqty) else 0 end) as pozzqty,
          (case when icat.line=1 then sum(sjstock.isamt) else 0 end) as portamt,
          (case when icat.line=3114 then sum(sjstock.isamt) else 0 end) as pozzamt,
          (case when icat.line=1 then sum(sjstock.ext) else 0 end) as portext,
          (case when icat.line=3114 then sum(sjstock.ext) else 0 end) as pozzext
          from arledger as ledger
          left join glstock as sjstock on sjstock.trno=ledger.trno
          left join glhead as sjhead on sjhead.trno=sjstock.trno
          left join item as i on i.itemid=sjstock.itemid
          left join itemcategory as icat on icat.line=i.category
          left join transnum as trans on trans.trno=sjstock.refx
          where date(ledger.dateid) between '$start' and '$end' 

          group by sjhead.clientname,trans.docno,
          icat.name,icat.line,
          sjhead.docno,sjhead.dateid,ledger.kr
        ) as a
        group by distributor,sodocno,cwodr,dateid,kr
        order by sodocno";


    return $qry;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1100';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Provisional Receipt Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));



    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($config, $layoutsize, $font, $fontsize, $border);

    $docno = "";
    $total = 0;
    $count = 0;
    $fontsize = $fontsize - 2;

    $ext = 0;
    $totalqty = 0;
    $totalext = 0;
    $totalportqty = 0;
    $totalpozzqty = 0;

    $str .= $this->reporter->begintable($layoutsize);
    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $count += 1;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($count, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->distributor, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->sodocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->portqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pozzqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalportqty += $data->portqty;
        $totalpozzqty += $data->pozzqty;
        $str .= $this->reporter->col(number_format($data->portamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pozzamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $ext = $data->portext + $data->pozzext;
        $str .= $this->reporter->col(number_format($ext, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->krdocno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext += $ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalportqty, 2), '100', null, false, $border, 'TBL', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalpozzqty, 2), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalext, 2), '150', null, false, $border, 'TBR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '100', null, false, $border, 'TLBR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalportqty + $totalpozzqty, 2), '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($config, $layoutsize, $font, $fontsize, $border)
  {
    $companyid = $config['params']['companyid'];
    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('#', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DISTRIBUTOR', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES ORDER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PORT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POZZ QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PORT PRICE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POZZ PRICE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PROVISIONAL RECEIPT#', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class