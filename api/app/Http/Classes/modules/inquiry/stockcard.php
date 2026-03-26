<?php

namespace App\Http\Classes\modules\inquiry;

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
  public $modulename = 'ITEMQUERY';
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

  private $fields = [
    'barcode', 'picture', 'itemname', 'uom', 'cost', 'itemrem', 'shortname',
    'part', 'model', 'class', 'brand', 'groupid', 'critical', 'reorder',
    'category', 'subcat', 'body', 'sizeid', 'color', 'asset', 'liability', 'revenue', 'expense',
    'isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnoninv', 'isserial',
    'markup', 'foramt', 'supplier', 'partno', 'subcode', 'packaging', 'islabor', 'dqty',
    'ispositem', 'isprintable', 'projectid', 'moq', 'mmoq', 'linkdept', 'amt', 'amt2', 'famt', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9',
    'disc', 'disc2', 'disc3', 'disc4', 'disc5', 'disc6', 'disc7', 'disc8', 'disc9', 'foramt'
  ];
  private $except = ['itemid', 'itemrem'];
  private $blnfields = ['isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnoninv', 'isserial', 'islabor', 'ispositem', 'isprintable'];
  private $acctg = [];
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
      'view' => 3626,
      'new' => 3626
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    $companyid = $config['params']['companyid'];
    $getcols = ['action', 'barcode', 'itemname', 'amt', 'uom', 'cat_name', 'subcat_name', 'supplier', 'foramt'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view', 'listingshowbalance'];
    if($companyid==60 ){//transpower
     array_push($stockbuttons, 'intransaction','poitemhistory','soitemhistory');
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$itemname]['label'] = 'Itemname';
    $cols[$supplier]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$cat_name]['label'] = 'Category';
    $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$amt]['label'] = 'SRP';
    $cols[$amt]['align'] = 'text-left';
    if ($companyid == 47) { //kstar
      $cols[$supplier]['type'] = 'coldel';
    }

    if ($companyid != 47) { //not kitchenstar
      $cols[$foramt]['type'] = 'coldel';
    }

    if($companyid==60 ){//transpower
       $cols[$action]['btns']['poitemhistory']['action'] = 'customformdialog';
       $cols[$action]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    }
    $cols = $this->tabClass->delcollisting($cols);

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $addedfields = "";
    $filtersearch = "";
    $condition  = "";
    $searchfield = [];
    $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
    $joins = "";
    $condition .= "where 1=1 and item.barcode not in ('#','$','*','**','***','$$','$$$','##') and item.isgeneric=0 and item.isfa=0";


    if (isset($config['params']['search'])) {
      $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    // add others link masterfile
    $qry = "select item.itemid, ifnull(model.model_name,'') as model_name, item.itemname, item.barcode, item.uom,
    FORMAT(item.amt, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,
    cat.name as cat_name,
    subcat.name as subcat_name, ifnull(supp.clientname, '') as supplier,item.foramt
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
    order by barcode " . $limit;

    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $btns = array(
      'load',
      'logs',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    if ($this->companysetup->getbarcodelength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $history = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardtransactionledger']];
    $warehouse = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardwh']];
    $intransaction = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardrr']];

    $return['HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $history];
    $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $intransaction];
    $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-warehouse', 'customform' => $warehouse];

    return $return;
  }



  public function createTab($config)
  {
    $tab = [
      'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystockcardwh', 'label' => 'BALANCE PER WAREHOUSE']
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isserial = $this->companysetup->getserial($config['params']);
    $ispos =  $this->companysetup->getispos($config['params']);

    $fields = ['barcode', 'uom', 'cost', 'dclientname', 'partno', 'partname', 'modelname', 'classname', 'brandname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
    data_set($col1, 'barcode.required', true);
    data_set($col1, 'uom.type', 'input');
    data_set($col1, 'dclientname.type', 'input');
    data_set($col1, 'cost.label', 'Last Cost');
    data_set($col1, 'partname.type', 'input');
    data_set($col1, 'modelname.type', 'input');
    data_set($col1, 'classname.type', 'input');
    data_set($col1, 'brandname.type', 'input');

    $fields = ['itemname', 'stockgrp', 'categoryname', 'subcatname', 'body', 'sizeid', 'color'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'itemname.type', 'textarea');
    data_set($col2, 'stockgrp.type', 'input');
    data_set($col2, 'categoryname.type', 'input');
    data_set($col2, 'subcatname.type', 'input');
    data_set($col2, 'body.type', 'input');
    data_set($col2, 'sizeid.type', 'input');
    data_set($col2, 'color.type', 'input');

   
    if($companyid==60){//transpower
       $allowview = $this->othersClass->checkAccess($config['params']['user'], 5488);
       $fields = [['amt5', 'disc5'],['amt7', 'disc7'],['amt2', 'disc2'],['amt', 'disc'],  ['famt', 'disc3'], ['amt6', 'disc6'],['amt4', 'disc4']]; 
        
       if(!$allowview){
           $fields = [['amt5', 'disc5'],['amt7', 'disc7'],['amt2', 'disc2'],['amt', 'disc']]; 
        }
     
    }else{
     $fields = [['amt', 'disc'], ['famt', 'disc3'], ['amt2', 'disc2'], ['amt4', 'disc4'], ['amt5', 'disc5'], ['amt6', 'disc6'], ['amt7', 'disc7'], ['amt8', 'disc8'], ['amt9', 'disc9']];
    }

    $col3 = $this->fieldClass->create($fields);
    if ($companyid == 19) { //housegem
      data_set($col3, 'famt.label', 'RJC (A)');
      data_set($col3, 'amt4.label', 'Freelance (B)');
      data_set($col3, 'amt5.label', 'Chinese Agent (C)');
      data_set($col3, 'amt6.label', 'TSC (D)');

      data_set($col3, 'disc3.label', 'RJC Disc (A)');
      data_set($col3, 'disc4.label', 'Freelance Disc (B)');
      data_set($col3, 'disc5.label', 'Chinese Agent Disc (C)');
      data_set($col3, 'disc6.label', 'TSC Disc (D)');
    }
    if ($companyid == 28) { //xcomp
      data_set($col3, 'amt.label', 'SI Regular Price');
      data_set($col3, 'amt2.label', 'DR Cash Price');
      data_set($col3, 'famt.label', 'SI Cash Price');
    }

     if($companyid==60){//transpower
      data_set($col3, 'amt.label', 'Base Price');
      data_set($col3, 'disc.label', 'Base Discount');
      data_set($col3, 'amt2.label', 'Wholesale Price');
      data_set($col3, 'disc2.label', 'Wholesale Discount');
      data_set($col3, 'famt.label', 'Distributor');
      data_set($col3, 'disc3.label', 'Distributor Discount');
      data_set($col3, 'amt4.label', 'Cost');
      data_set($col3, 'disc4.label', 'Cost Discount');
      data_set($col3, 'amt5.label', 'Invoice Price');
      data_set($col3, 'disc5.label', 'Invoice Discount');
      data_set($col3, 'amt6.label', 'Lowest Price');
      data_set($col3, 'disc6.label', 'Lowest Discount');
      data_set($col3, 'amt7.label', 'DR Price');
      data_set($col3, 'disc7.label', 'DR Discount');
     }

    $fields = ['picture', 'rem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'picture.folder', 'product');
    data_set($col4, 'picture.table', 'item');
    data_set($col4, 'picture.fieldid', 'itemid');
    data_set($col4, 'rem.name', 'itemrem');
    data_set($col4, 'rem.label', 'Item Remark');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    if ($companyid == 10 || $companyid == 12) { //afti
      $filter = ' and item.isoutsource =0 ';
    }

    if ($itemid == 0) {
      $itemid = $this->othersClass->readprofile($doc, $config);
      if ($itemid == 0) {
        $itemid = $this->coreFunctions->datareader("select itemid as value from item where isinactive=0 " . $filter . " order by itemid desc limit 1");
      }
      $config['params']['itemid'] = $itemid;
    } else {
      $this->othersClass->checkprofile($doc, $itemid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'item.itemid, item.barcode as docno';
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',item.' . $value;
    }
    $qryselect = "select " . $fields . ", ifnull(pmaster.part_name,'') as partname, item.part as partid,
        ifnull(mmaster.model_name,'') as modelname, item.model as model,
        ifnull(itemclass.cl_name,'') as classname,item.class as class,
        ifnull(brand.brand_desc,'') as brandname, ifnull(item.brand,'') as brand,
        ifnull(stockgrp.stockgrp_name,'') as stockgrp, item.groupid as groupid, item.groupid as grid,
        cat.line as category,
        cat.name as categoryname,
        subcat.line as subcat,
        subcat.name as subcatname,
        ifnull(coa1.acnoname,'')  as assetname,
        ifnull(coa2.acnoname,'')  as liabilityname,
        ifnull(coa3.acnoname,'')  as revenuename,
        ifnull(coa4.acnoname,'')  as expensename,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname,
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging,
        ifnull(prj.code, '') as projectcode,
        ifnull(prj.name, '') as projectname,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue,
        ifnull(dept.clientname,'') as deptname,
        item.linkdept, '' as cost";

    $qry = $qryselect . " from item
        left join part_masterfile as pmaster on pmaster.part_id = item.part
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join item_class as itemclass on itemclass.cl_id = item.class
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join coa as coa1 on coa1.acno = item.asset
        left join coa as coa2 on coa2.acno = item.liability
        left join coa as coa3 on coa3.acno = item.revenue
        left join coa as coa4 on coa4.acno = item.expense
        left join client as cl on cl.clientid = item.supplier
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join projectmasterfile as prj on prj.line = item.projectid
        left join client as dept on dept.clientid=item.linkdept
        where item.itemid = ? ";

    $head = $this->coreFunctions->opentable($qry, [$itemid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
      if ($viewcost) {
        $head[0]->cost = $this->getlatestprice($config, $head[0]->barcode);
      }

      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['itemid' => $itemid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['itemid']];
    } else {
      $head[0]['itemid'] = 0;
      $head[0]['barcode'] = '';
      $head[0]['itemname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }


  public function getlatestprice($config, $barcode)
  {
    $center = $config['params']['center'];
    $qry = "select left(dateid,10) as dateid, round(amt,2) as amt from(select head.dateid, stock.rrcost*head.forex as amt
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid = stock.itemid
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and stock.cost <> 0
      UNION ALL
      select head.dateid, stock.rrcost*head.forex as amt from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join client on client.clientid = head.clientid
      left join cntnum on cntnum.trno=head.trno
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and stock.cost <> 0
      order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);

    if (!empty($data)) {
      return $data[0]->amt;
    } else {
      return '';
    }
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
