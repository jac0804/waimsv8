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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class packing_list_report
{
  public $modulename = 'Packing List Report';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dclientname.lookupclass', 'wasupplier');

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
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
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '0' as clientid,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as center,
    '' as dclientname,'' as reportusers
    ");
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
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0: // summarized
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1: // detailed
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

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
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status,left(hpo.dateid,10) as dateid,hpo.docno,hpo.clientname,hpo.createby,sum(hpos.ext) as ext
          from hplhead as head
          left join hplstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          group by hpo.dateid,hpo.docno,hpo.clientname,hpo.createby,hpos.ext
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status,left(hpo.dateid,10) as dateid,hpo.docno,hpo.clientname,hpo.createby,sum(hpos.ext) as ext
          from plhead as head
          left join plstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          group by hpo.dateid,hpo.docno,hpo.clientname,hpo.createby,hpos.ext
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status,left(hpo.dateid,10) as dateid,hpo.docno,hpo.clientname,hpo.createby,sum(hpos.ext) as ext
                    from plhead as head
                    left join plstock as stock on head.trno=stock.trno
                    left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
                    left join hpohead as hpo on hpo.trno=hpos.trno
                    where head.doc='PL' and head.dateid between '$start' and '$end' $filter
                    group by hpo.dateid,hpo.docno,hpo.clientname,hpo.createby,hpos.ext
                    union all
                    select 'POSTED' as status,left(hpo.dateid,10) as dateid,hpo.docno,hpo.clientname,hpo.createby,sum(hpos.ext) as ext
                    from hplhead as head
                    left join hplstock as stock on head.trno=stock.trno
                    left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
                    left join hpohead as hpo on hpo.trno=hpos.trno
                    where head.doc='PL' and head.dateid between '$start' and '$end' $filter
                    group by hpo.dateid,hpo.docno,hpo.clientname,hpo.createby,hpos.ext
                    order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status,left(hpo.dateid,10) as dateid,head.docno,hpo.clientname,hpo.createby,hpos.ext,item.barcode,item.itemname,stock.rrqty,stock.uom,stock.rrcost,stock.disc,hpo.docno as ref
          from hplhead as head
          left join hplstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          left join item on item.itemid = stock.itemid
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status,left(hpo.dateid,10) as dateid,head.docno,hpo.clientname,hpo.createby,hpos.ext,item.barcode,item.itemname,stock.rrqty,stock.uom,stock.rrcost,stock.disc,hpo.docno as ref
          from plhead as head
          left join plstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          left join item on item.itemid = stock.itemid
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status,left(hpo.dateid,10) as dateid,head.docno,hpo.clientname,hpo.createby,hpos.ext,item.barcode,item.itemname,stock.rrqty,stock.uom,stock.rrcost,stock.disc,hpo.docno as ref
          from plhead as head
          left join plstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          left join item on item.itemid = stock.itemid
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          union all
          select 'POSTED' as status,left(hpo.dateid,10) as dateid,head.docno,hpo.clientname,hpo.createby,hpos.ext,item.barcode,item.itemname,stock.rrqty,stock.uom,stock.rrcost,stock.disc,hpo.docno as ref
          from hplhead as head
          left join hplstock as stock on head.trno=stock.trno
          left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
          left join hpohead as hpo on hpo.trno=hpos.trno
          left join item on item.itemid = stock.itemid
          where head.doc='PL' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Packing List Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class