<?php

namespace App\Http\Classes\modules\productinquiry;

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
use PDO;

class stockcard
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MC UNIT INQUIRY';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'item';
  public $prefix = 'IT';
  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_item_log';
  private $stockselect;


  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4452
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    $action = 0;
    $barcode = 1;
    $brandname = 2;
    $itemname = 3;

    $getcols = ['action', 'barcode', 'brandname', 'itemname'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    // $stockbuttons = ['view', 'listingshowbalance'];
    $stockbuttons = ['listingshowproductinquirybalance'];
    // }


    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$itemname]['label'] = 'Itemname';
    $cols[$brandname]['lookupclass'] = '';
    $cols[$brandname]['action'] = '';
    $cols[$brandname]['type'] = 'input';
    $cols[$brandname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$barcode]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$itemname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$brandname]['align'] = 'text-left';
    return $cols;
  }


  public function paramsdatalisting($config)
  {
    return [];
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $addedfields = "";
    $filtersearch = "";
    $condition  = "";
    $searchfield = [];
    $limit = '50';
    $joins = "";
    $condition .= "where cat.name ='MC UNIT' ";

    if (isset($config['params']['search'])) {
      $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'brand.brand_desc'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select item.itemid, item.itemname, item.barcode,
    
    brand.brand_desc as brandname
    " . $addedfields . "
    from item
    left join item_class as cls on cls.cl_id=item.class
    left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
    left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    left join client as supp on supp.clientid = item.supplier
    " . $joins . "
    " . $condition . " " . $filtersearch . "
    order by barcode limit " . $limit;

    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {

    return [];
  } // createHeadbutton


  public function createtab2($access, $config)
  {

    return [];
  }



  public function createTab($config)
  {

    return [];
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {

    return [];
  }

  public function loadheaddata($config)
  {

    return [];
  }


  public function getlatestprice($config, $barcode)
  {

    return '';
  } // end function



  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
    }
  }
} //end class
