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

class sales_summary_per_vat_type
{
  public $modulename = 'Sales Summary Per Vat Type';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'part', 'divsion', 'category', 'dvattype', 'salestype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'salestype.required', false);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'part.label', 'Principal');
    data_set($col1, 'divsion.label', 'Division');
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');

    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radioposttype.label', 'Transaction Status');
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => 'posted', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => 'unposted', 'color' => 'teal'],
        ['label' => 'All', 'value' => 'all', 'color' => 'teal']
      ]
    );



    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as client,
      '' as clientname,
      '' as dagentname,'' as agent,'' as agentname,
      '' as partid,
      '' as partname,
      '' as groupid,
      '' as stockgrp,
      '' as category,
      '' as categoryname,
      '12' as tax,
      '12~VATABLE' as dvattype,
      'VATABLE' as vattype,
      'REGULAR' as salestype,
      'all' as posttype
      
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

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $agent     = $config['params']['dataparams']['agent'];
    $principal     = $config['params']['dataparams']['partid'];
    $division     = $config['params']['dataparams']['groupid'];
    $category     = $config['params']['dataparams']['category'];
    $vattype     = $config['params']['dataparams']['vattype'];
    $salestype     = $config['params']['dataparams']['salestype'];
    $posttype     = $config['params']['dataparams']['posttype'];

    $filter = "";

    if ($client != "") {
      $filter .= " and c.client = '$client' ";
    }
    if ($agent != "") {
      $filter .= " and a.client = '$agent' ";
    }
    if ($principal != "") {
      $filter .= " and p.part_id = '$principal' ";
    }
    if ($division != "") {
      $filter .= " and g.stockgrp_id = '$division' ";
    }
    if ($category != "") {
      $filter .= " and ic.line = '$category' ";
    }
    if ($vattype != "") {
      $filter .= " and head.vattype = '$vattype' ";
    }
    if ($salestype != "") {
      $filter .= " and head.salestype = '$salestype' ";
    }

    switch ($posttype) {
      case 'posted':
        $query = "
        select 'POSTED' as trans,head.vattype,ifnull(a.client,'') as agcode,ifnull(a.clientname,'') as agname,
        ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,ifnull(ic.name,'') as category,
        head.docno,date(head.dateid) as dateid,ifnull(c.client,'') as code,c.clientname as custname,ifnull(stock.ext,0.00) as amt,
        ifnull((stock.ext/1.12)*0.12,0.00) as vat,ifnull(stock.ext/1.12,0.00) as vatex
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as c on c.clientid=head.clientid
        left join client as a on a.clientid=head.agentid
        left join item as i on i.itemid=stock.itemid
        left join part_masterfile as p on p.part_id=i.part
        left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
        left join itemcategory as ic on ic.line=i.category
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        order by docno";
        break;

      case 'unposted':
        $query = "
        select 'UNPOSTED' as trans,head.vattype,ifnull(a.client,'') as agcode,ifnull(a.clientname,'') as agname,
        ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,ifnull(ic.name,'') as category,
        head.docno,date(head.dateid) as dateid,ifnull(c.client,'') as code,c.clientname as custname,ifnull(stock.ext,0.00) as amt,
        ifnull((stock.ext/1.12)*0.12,0.00) as vat,ifnull(stock.ext/1.12,0.00) as vatex
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as c on c.client=head.client
        left join client as a on a.client=head.agent
        left join item as i on i.itemid=stock.itemid
        left join part_masterfile as p on p.part_id=i.part
        left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
        left join itemcategory as ic on ic.line=i.category
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        order by docno";
        break;

      default:
        $query = "
        select 'UNPOSTED' as trans,head.vattype,ifnull(a.client,'') as agcode,ifnull(a.clientname,'') as agname,
        ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,ifnull(ic.name,'') as category,
        head.docno,date(head.dateid) as dateid,ifnull(c.client,'') as code,c.clientname as custname,ifnull(stock.ext,0.00) as amt,
        ifnull((stock.ext/1.12)*0.12,0.00) as vat,ifnull(stock.ext/1.12,0.00) as vatex
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as c on c.client=head.client
        left join client as a on a.client=head.agent
        left join item as i on i.itemid=stock.itemid
        left join part_masterfile as p on p.part_id=i.part
        left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
        left join itemcategory as ic on ic.line=i.category
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        union all
        select 'POSTED' as trans,head.vattype,ifnull(a.client,'') as agcode,ifnull(a.clientname,'') as agname,
        ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,ifnull(ic.name,'') as category,
        head.docno,date(head.dateid) as dateid,ifnull(c.client,'') as code,c.clientname as custname,ifnull(stock.ext,0.00) as amt,
        ifnull((stock.ext/1.12)*0.12,0.00) as vat,ifnull(stock.ext/1.12,0.00) as vatex
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as c on c.clientid=head.clientid
        left join client as a on a.clientid=head.agentid
        left join item as i on i.itemid=stock.itemid
        left join part_masterfile as p on p.part_id=i.part
        left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
        left join itemcategory as ic on ic.line=i.category
        where head.doc='SJ' and head.dateid between '$start' and '$end' $filter
        order by docno";
        break;
    }
    
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $client   = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $vattype     = $config['params']['dataparams']['vattype'];
    $salestype     = $config['params']['dataparams']['salestype'];
    $category     = $config['params']['dataparams']['categoryname'];

    if ($clientname != "") {
      $clientname = $config['params']['dataparams']['clientname'];
    } else {
      $clientname = "ALL";
    }
    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . $clientname, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Principal: ' . $principal, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Trnx Type: ' . $salestype, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Division: ' . $division, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Status: ' . strtoupper($posttype), '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Type: ' . strtoupper($vattype), '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category: ' . $category, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOC#', '125', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CODE', '125', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUST. NAME', '250', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT', '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT EX', '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

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

    $agentname = "";
    $collector = "";
    $amount = 0;
    $subtotal = 0;
    $gtotal = 0;
    $gvat = 0;
    $gvatex = 0;


    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->code, '125', null, false, $border, '', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->custname, '250', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      if ($data->vattype == 'VATABLE') {
        $str .= $this->reporter->col(number_format($data->vat, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
        $str .= $this->reporter->col(number_format($data->vatex, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
        $gvat += $data->vat;
        $gvatex += $data->vatex;
      } else {
        $str .= $this->reporter->col('-', '75', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
        $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
        $gvat += 0;
        $gvatex += $data->amt;
      }
      $gtotal += $data->amt;


      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gtotal, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gvat, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gvatex, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class