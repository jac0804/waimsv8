<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class sales_item_by_location
{
  public $modulename = 'Sales Item By Location';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dagentname', 'ditemname', 'stock_groupname', 'brandname', 'categoryname', 'subcatname', 'region', 'province', 'area', 'brgy', 'radioreporttype'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioreporttype.options',  [
      ['label' => 'By Region', 'value' => '1', 'color' => 'blue'],
      ['label' => 'By Province', 'value' => '2', 'color' => 'green'],
      ['label' => 'By Area', 'value' => '3', 'color' => 'pink'],
      ['label' => 'By Barangay', 'value' => '4', 'color' => 'orange'],
    ]);

    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];


    $paramstr = "
    select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as dagentname,
    '' as agentname,
    '' as agent,
    0 as agentid,
    '' as ditemname,
    '' as itemname,
    '' as barcode,
    0 as itemid,
    '' as stock_groupname,
    0 as groupid,
    '' as brandname,
    0 as brandid,
    '' as categoryname,
    0 as category,
    '' as subcatname,
    0 as subcat,
    '' as region,
    '' as province,
    '' as area,
    '' as brgy,
    '1' as reporttype";

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
    $company = $config['params']['companyid'];
    $data  = $this->reportDefault($config);

    $result = $this->reportDefaultLayout($config, $data);

    return $result;
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $groupid = $config['params']['dataparams']['groupid'];

    $brandname = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];

    $categoryname = $config['params']['dataparams']['categoryname'];
    $category = $config['params']['dataparams']['category'];

    $subcatname = $config['params']['dataparams']['subcatname'];
    $subcat = $config['params']['dataparams']['subcat'];

    $region = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $area = $config['params']['dataparams']['area'];
    $brgy = $config['params']['dataparams']['brgy'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";

    if ($agentname != "") {
      $filter .= " and agent.clientid = " . $agentid . " ";
    } //end if

    if ($itemname != "") {
      $filter .= " and item.itemid = " . $itemid . " ";
    } //end if

    if ($stock_groupname != "") {
      $filter .= " and grp.stockgrp_id = " . $groupid . " ";
    } //end if

    if ($brandname != "") {
      $filter .= " and brand.brandid = " . $brandid . " ";
    } //end if

    if ($categoryname != "") {
      $filter .= " and cat.line = " . $category . " ";
    } //end if

    if ($subcatname != "") {
      $filter .= " and subcat.line = " . $subcat . " ";
    } //end if

    if ($region != "") {
      $filter .= " and client.region = '" . $region . "'' ";
    } //end if

    if ($province != "") {
      $filter .= " and client.province = '" . $province . "'' ";
    } //end if

    if ($area != "") {
      $filter .= " and client.area = '" . $area . "'' ";
    } //end if

    if ($brgy != "") {
      $filter .= " and client.brgy = '" . $brgy . "'' ";
    } //end if

    $fields = "";
    $fieldsx = "";
    switch ($reporttype) {
      case 1:
        $fields = " if(region = '', ' ', region) as region";
        $fieldsx = " region";
        break;

      case 2:
        $fields = " if(province = '', ' ', province) as province , if(region = '', ' ', region) as region ";
        $fieldsx = " province ,region ";
        break;

      case 3:
        $fields = " if(area = '', ' ', area) as area, if(province = '', ' ', province) as province , if(region = '', ' ', region) as region   ";
        $fieldsx = " area ,province ,region  ";
        break;

      case 4:
        $fields = " if(brgy = '' ,'Blank', brgy) as brgy, if(area = '', ' ', area) as area, if(province = '', ' ', province) as province , if(region = '', ' ', region) as region  ";
        $fieldsx = " brgy ,area ,province ,region ,brgy";
        break;
    }
    $query = "select  barcode, itemname, sum(qty) as qty, sum(ext) as amt, $fields
              from (select 'u' as tr, head.trno,item.barcode, item.itemname, stock.iss as qty, stock.ext,
                client.region, client.province, client.area, client.brgy
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno 
                left join client on client.client=head.client
                left join client as agent on agent.client = head.agent
                left join item on item.itemid=stock.itemid 
                left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
                left join frontend_ebrands as brand on brand.brandid = item.brand
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('SJ') $filter 
                and date(head.dateid) between '$start' and '$end'
                union all
                select 'p' as tr, head.trno, item.barcode,item.itemname, stock.iss as qty, stock.ext,
                client.region, client.province, client.area, client.brgy
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno 
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid = head.agentid
                left join item on item.itemid=stock.itemid 
                left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
                left join frontend_ebrands as brand on brand.brandid = item.brand
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('SJ') $filter 
                and date(head.dateid) between '$start' and '$end') as sa
                group by  barcode, itemname, $fieldsx
              order by $fieldsx, itemname";

    return $this->coreFunctions->opentable($query);
  }


  private function default_displayHeader($config, $data)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $groupid = $config['params']['dataparams']['groupid'];

    $brandname = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];

    $categoryname = $config['params']['dataparams']['categoryname'];
    $category = $config['params']['dataparams']['category'];

    $subcatname = $config['params']['dataparams']['subcatname'];
    $subcat = $config['params']['dataparams']['subcat'];

    $region = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $area = $config['params']['dataparams']['area'];
    $brgy = $config['params']['dataparams']['brgy'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES ITEM BY LOCATION ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>DATE FROM : </b>' . $start . ' TO ' . $end, '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>AGENT : </b>' . ($agentname != '' ? $agentname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>ITEM : </b>' . ($itemname != '' ? $itemname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>GROUP : </b>' . ($stock_groupname != '' ? $stock_groupname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>CATEGORY : </b>' . ($categoryname != '' ? $categoryname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>SUB-CATEGORY : </b>' . ($subcatname != '' ? $subcatname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>BRAND : </b>' . ($brandname != '' ? $brandname : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>REGION : </b>' . ($region != '' ? $region : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>PROVINCE :' . ($province != '' ? $province : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>AREA : </b>' . ($area != '' ? $area : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>BRGY : </b>' . ($brgy != '' ? $brgy : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->pagenumber('Page', '1000', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('LOCATION & ITEM DESCRIPTION', '800', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('QTY ', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config, $data)
  {
    $company   = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $reporttype = $config['params']['dataparams']['reporttype'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config, $data);

    $region = "";
    $province = "";
    $area = "";
    $brgy = "";

    $stregion = $stprovince  = $starea = $stbrgy = 0;
    $gtregion = $gtprovince  = $gtarea = $gtbrgy = 0;
    $gt = 0;

    foreach ($data as $key => $value) {

      if ($reporttype == 4) {

        if ($area != $value->area) {
          if ($area != "") {
            $brgy .= " ";
          }
        }

        if ($brgy != $value->brgy) {
          if ($brgy != "") {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('BARANGAY : ' . $brgy . ' SUB-TOTAL : ', '1000', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($stbrgy, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $stbrgy = 0;
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        }
      }

      if ($reporttype == 3 || $reporttype == 4) {
        if ($area != $value->area) {

          if ($area != "") {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Area : ' . $area . ' SUB-TOTAL : ', '800', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($starea, 2), '400', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $starea = 0;
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        }
      }

      if ($reporttype == 2 || $reporttype == 3 || $reporttype == 4) {
        if ($province != $value->province) {
          if ($stprovince != "") {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Province : ' . $province . ' SUB-TOTAL : ', '600', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($stprovince, 2), '600', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '', '');
            $stprovince = 0;
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        }
      }


      if ($region != $value->region) {

        if ($region != "") {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Region : ' . $region . ' SUB-TOTAL : ', '800', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($stregion, 2), '400', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '', '');
          $stregion = 0;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Region : ' . $value->region, '1200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }


      if ($reporttype == 2 || $reporttype == 3 || $reporttype == 4) {
        if ($province != $value->province) {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('Province : ' . $value->province, '1100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
      }

      if ($reporttype == 3 || $reporttype == 4) {
        if ($area != $value->area) {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('Area : ' . $value->area, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $brgy = "";
        }
      }

      if ($reporttype == 4) {

        if ($brgy != $value->brgy) {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('Barangay : ' . $value->brgy, '900', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($value->itemname, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($value->qty, 2), '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $region = $value->region;
      $province = isset($value->province) ? $value->province : "";
      $area = isset($value->area) ? $value->area : "";
      $brgy = isset($value->brgy) ? $value->brgy : "";

      $stregion += $value->amt;
      $stprovince  += $value->amt;
      $starea += $value->amt;
      $stbrgy += $value->amt;
      $gtregion += $value->amt;
      $gtprovince  += $value->amt;
      $gtarea += $value->amt;
      $gtbrgy += $value->amt;
      $gt += $value->amt;
    }
    if ($reporttype == 4) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('BARANGAY : ' . $brgy . ' SUB-TOTAL : ', '1000', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($stbrgy, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $stbrgy = 0;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    if ($reporttype == 3 || $reporttype == 4) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Area : ' . $area . ' SUB-TOTAL : ', '800', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($starea, 2), '400', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $starea = 0;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    if ($reporttype == 3 || $reporttype == 2 || $reporttype == 4) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Province : ' . $province . ' SUB-TOTAL : ', '600', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($stprovince, 2), '600', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      $stprovince = 0;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Region : ' . $region . ' SUB-TOTAL : ', '400', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($stregion, 2), '800', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $stregion = 0;
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '1000', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $stregion = 0;
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class