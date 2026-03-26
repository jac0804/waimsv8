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
use DateTime;

class consign_and_outright_invoice_report
{
  public $modulename = 'Consign and Outright Invoice Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

  public function __construct()
  {
      $this->companysetup = new companysetup;
      $this->coreFunctions = new coreFunctions;
      $this->othersClass = new othersClass;
      $this->fieldClass = new txtfieldClass;
      $this->reporter = new SBCPDF;
  }
  public function createHeadField($config)// Essentially the input fields from the web 
  {
      $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers',  'dagentname', 'approved' ];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'approved.label', 'Prefix');
      data_set($col1, 'dclientname.lookupclass', 'lookupclient');
      data_set($col1, 'dclientname.label', 'Customer');
      data_set($col1, 'dbranchname.required', true);
      data_set($col1, 'start.required', true);
      data_set($col1, 'end.required', true);

      $fields = [ 'radioreporttype'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'radioreporttype.label','Type of Invoice');  // changed label 
      data_set(
      $col2,
      'radioreporttype.options',
      [
          ['label' => 'Consign Invoice', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Outright Invoice', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Both Invoice', 'value' => '2', 'color' => 'orange']
      ]
      );

      $fields = ['radioreporttypepcv']; 
      $col3 = $this->fieldClass->create($fields);
      data_set(
      $col3,
      'radioreporttypepcv.options',
      [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'red'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'red'],
      ]
      );

      $fields = ['radioposttype', 'radiosorting'];
      $col4 = $this->fieldClass->create($fields);
      data_set(
      $col4,
      'radioposttype.options',
      [
          ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
      );

      $fields = ['print'];
      $col5 = $this->fieldClass->create($fields);

      return array('col1' => $col1, 'col2' => $col2, 
      'col3' => $col3, 
      'col4' => $col4, 'col5'=> $col5);
  }
  public function paramsdata($config)//data parameters; the default values of the input fields
  { // 'names' or 'alias'

     $center = $config['params']['center'];
     $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

      return $this->coreFunctions->opentable( "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '0' as clientid,
        '' as client,
        '' as dclientname,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '2' as posttype,
        '0' as reporttype,
        '0' as reporttypepcv,
        '' as userid,'' as username, '' as reportusers,
        '' as agent, '' as agentname, '' as dagentname, 0 as agentid,
        '' as approved,
        'ASC' as sorting
    
      ");
  }
  public function getloaddata($config)
  {
      return [];
  }
  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status'=>true, 'msg'=>'Msg works', 'report'=>$str,'params'=>$this->reportParams];
  }
  public function reportplotting($config)// Type of Report (radio option) case connection
  {
      $reporttype = $config['params']['dataparams']['reporttype'];
      $reporttypepcv = $config['params']['dataparams']['reporttypepcv'];
      switch ($reporttype) {
        case '0': // Consigned
          // return $this->ConsignLayout_Detailed($config, $this->detailed_query($config));
          switch ($reporttypepcv) {
                case '0':
                    return $this->DefaultLayout_Summarized($config, $this->summarized_query($config));
                case '1':
                    return $this->DefaultLayout_Detailed($config, $this->detailed_query($config));
            }
          break;
        case '1': // Outright
          // return $this->OutrightLayout_Detailed($config, $this->detailed_query($config)); 
          switch ($reporttypepcv) {
                case '0':
                    return $this->DefaultLayout_Summarized($config, $this->summarized_query($config));
                case '1':
                    return $this->DefaultLayout_Detailed($config, $this->detailed_query($config));
            }
          break;
        case '2': // Both
          // return $this->DefaultLayout_Detailed($config, $this->summary_query($config)); 
          switch ($reporttypepcv) {
                case '0':
                    return $this->DefaultLayout_Summarized($config, $this->both_summarized_query($config));
                case '1':
                    return $this->DefaultLayout_Detailed($config, $this->both_detailed_query($config));
            }
          break;
      } 
  }

// Queries
  public function detailed_query($config)  // Query for Detailed Invoice
  {
    $center = $config['params']['center'];
    $filteredcenter = $config['params']['dataparams']['center'];
    $branchname   = $config['params']['dataparams']['dcentername'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client= $config['params']['dataparams']['client'];
    $posttype = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['dagentname'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filter = "";
    $filter1 = "";
    $filterAg1 = "";
    $filterAg2 = "";
    $reporttype = $config['params']['dataparams']["reporttype"];
    $stock = '';
    $stock1 = '';
    $stock2 = '';
    $qty = '';
    $qtyBoth = '';
    $loc = '';
    $locBoth = '';
    $expiry = '';
    $expiryBoth = '';

    // to switch between 3 reports
    if ($reporttype == 0) {
      $filter .= " and head.doc = 'ch'";
    } else if ($reporttype == 1) {
      $filter .= " and head.doc = 'on'";
    } else if ($reporttype == 2){
      $filter .= " and head.doc in ('on','ch')";
    }

    //QTY
    if ($reporttype == 0) {
      $qty .= "stock.isqty,"; // ch
    } else if ($reporttype == 1) {
      $qty .= "stock.qty,"; // on
    } 

    if ($reporttype == 0 && $posttype == 2) {
      $qtyBoth .= "stock.isqty,"; // ch
    } else if ($reporttype == 1 && $posttype == 2) {
      $qtyBoth .= "stock.qty,"; // on
    } 

    //Loc
    if ($reporttype == 0) {
      $loc .= "'' as loc,"; // ch
    } else if ($reporttype == 1) {
      $loc .= "stock.loc,"; // on
    } 

    if ($reporttype == 0 && $posttype == 2) {
      $locBoth .= "'' as loc,"; // ch
    } else if ($reporttype == 1 && $posttype == 2) {
      $locBoth .= "stock.loc,"; // on
    } 


    // Consign
     if ($reporttype == 0 && $posttype == 0) {  
      $stock .= "left join hsistock as stock on stock.trno = head.trno";
    } else if ($reporttype == 0 && $posttype == 1) {
      $stock .= "left join sistock as stock on stock.trno = head.trno";
    } else if ($reporttype == 0 && $posttype == 2) {
      $stock1 .= "left join hsistock as stock on stock.trno = head.trno";
      $stock2 .= "left join sistock as stock on stock.trno = head.trno";
    } 

    // Outright
    else if ($reporttype == 1 && $posttype == 0) { 
      $stock .= "left join cntnum as num2 on num2.svnum = head.trno
                 left join glstock as stock on stock.trno = num2.trno
                 ";
    } else if ($reporttype == 1 && $posttype == 1) {
      $stock .= "left join cntnum as num2 on num2.svnum = head.trno 
                 left join glhead as ghead on ghead.trno = num2.trno 
                 left join glstock as stock on stock.trno = ghead.trno 
                ";
    } else if ($reporttype == 1 && $posttype == 2) {
      $stock1 .= "left join cntnum as num2 on num2.svnum = head.trno
                  left join glstock as stock on stock.trno = num2.trno
                  ";
      $stock2 .= "left join cntnum as num2 on num2.svnum = head.trno
                  left join glhead as ghead on ghead.trno = num2.trno 
                  left join glstock as stock on stock.trno = ghead.trno
                  ";
    } 

    if ($branchname != "") {
      $filter .= " and num.center = '$filteredcenter'";
    }

    if ($client != ''){
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    
    if ($prefix != "") {
      $filter .= " and num.bref = '$prefix' ";
    }

    if ($agentname != '') {
      if ($agentid != '0') {
          $filterAg1   .= " and head.agentid = '" . $agentid . "'";
          $filterAg2   .= " and head.agent = '" . $agent . "'";
      }
    }

    $query = '';
    switch ($posttype) {
      case 0: //Posted
        $query = "select
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, $qty stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          $loc head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          $stock
          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg1 order by head.docno  " . $sorting;
          // var_dump( $query ); 
          
        break;
        
      case 1:  //Unposted
        $query = "select
          'Unposted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, $qty stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
          $loc head.ourref, head.rem
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          $stock
          left join client on head.client = client.client
          left join client as warehouse on warehouse.client = head.wh
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg2 order by head.docno " . $sorting; 
          // var_dump( $query ); 
        break;

      case 2: // All
         $query = "select
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, $qtyBoth stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          $locBoth head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          $stock1
          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg1 

          union all

          select
          'Unposted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, $qtyBoth stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
          $locBoth  head.ourref, head.rem
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          $stock2
          left join client on head.client = client.client
          left join client as warehouse on warehouse.client = head.wh
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg2 order by docno " . $sorting; 
            // var_dump($query);
          break;
         // x icon -> Response Tab
      }

    return $this->coreFunctions->openTable($query);  
  }
    public function both_detailed_query($config)  // Query for Both Invoice (Detailed)
  {
    $center = $config['params']['center'];
    $filteredcenter = $config['params']['dataparams']['center'];
    $branchname   = $config['params']['dataparams']['dcentername'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client= $config['params']['dataparams']['client'];
    $posttype = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['dagentname'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filter = "";
    $filterAg1 = "";
    $filterAg2 = "";


    if ($branchname != "") {
      $filter .= " and num.center = '$filteredcenter'";
    }

    if ($client != ''){
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    
    if ($prefix != "") {
      $filter .= " and num.bref = '$prefix' ";
    }

    if ($agentname != '') {
      if ($agentid != '0') {
          $filterAg1   .= " and head.agentid = '" . $agentid . "'";
          $filterAg2   .= " and head.agent = '" . $agent . "'";
      }
    }



    $query = '';
    switch ($posttype) {
      case 0: //Posted
        $query = "select
          'Consign' as Type,
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.isqty, stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          '' as loc, head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join hsistock as stock on stock.trno = head.trno

          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg1 and head.doc = 'ch'   
          union all
          select
          'Outright' as Type,
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.qty, stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          stock.loc, head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glstock as stock on stock.trno = num2.trno
          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg1 and head.doc = 'on'   order by docno  $sorting";
          // var_dump($query);
        break;
        
      case 1:  //Unposted
        $query = "
          select
            'Unposted' as status,
            num.center, head.trno,head.docno,
            client.client, head.terms,left(head.dateid,10) as dateid,
            head.clientname, head.address,ifnull(agent.client,'') as agent,
            ifnull(agent.clientname,'') as agentname,'' as dagentname,
            warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
            item.barcode, item.itemname, stock.isqty, stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
            '' as loc,  head.ourref, head.rem
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join glhead as ghead on ghead.trno = head.trno
            left join sistock as stock on stock.trno = head.trno
            left join client on head.client = client.client
            left join client as warehouse on warehouse.client = head.wh
            left join client as agent on agent.client = head.agent
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
            where date(head.dateid) between '$start' and '$end'  $filter $filterAg2  and head.doc = 'ch' 
            union all
            select
            'Unposted' as status,
            num.center, head.trno,head.docno,
            client.client, head.terms,left(head.dateid,10) as dateid,
            head.clientname, head.address,ifnull(agent.client,'') as agent,
            ifnull(agent.clientname,'') as agentname,'' as dagentname,
            warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
            item.barcode, item.itemname, stock.qty, stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
            stock.loc, head.ourref, head.rem
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glhead as ghead on ghead.trno = num2.trno
            left join glstock as stock on stock.trno = ghead.trno
            left join client on head.client = client.client
            left join client as warehouse on warehouse.client = head.wh
            left join client as agent on agent.client = head.agent
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom  
            where date(head.dateid) between '$start' and '$end'  $filter $filterAg2  and head.doc = 'on'   order by docno $sorting";
        break;

      case 2: // Both
        
         $query = "select
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.isqty, stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          '' as loc, head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join hsistock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg1 and head.doc = 'ch'   
          union all
          select
          'Posted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.qty, stock.uom, stock.amt,stock.ext, stock.disc, stock.iss, stock.isamt,
          stock.loc, head.ourref, head.rem
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glstock as stock on stock.trno = num2.trno
          left join client on head.clientid = client.clientid
          left join client as warehouse on warehouse.clientid = head.whid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg1 and head.doc = 'on'
          union all
          select
          'Unposted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.isqty, stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
          '' as loc, head.ourref, head.rem
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join glhead as ghead on ghead.trno = head.trno
          left join sistock as stock on stock.trno = head.trno
          left join client on head.client = client.client
          left join client as warehouse on warehouse.client = head.wh
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg2  and head.doc = 'ch' 
          union all
          select
          'Unposted' as status,
          num.center, head.trno,head.docno,
          client.client, head.terms,left(head.dateid,10) as dateid,
          head.clientname, head.address,ifnull(agent.client,'') as agent,
          ifnull(agent.clientname,'') as agentname,'' as dagentname,
          warehouse.client as wh, warehouse.clientname as whname, left(head.due,10) as due,
          item.barcode, item.itemname, stock.qty, stock.uom, stock.amt, stock.ext,stock.disc, stock.iss, stock.isamt,
          stock.loc, head.ourref, head.rem
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno 
          left join glhead as ghead on ghead.trno = num2.trno
          left join glstock as stock on stock.trno = ghead.trno
          left join client on head.client = client.client
          left join client as warehouse on warehouse.client = head.wh
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where date(head.dateid) between '$start' and '$end'  $filter $filterAg2  and head.doc = 'on'   order by docno $sorting";
          break;
      }

    return $this->coreFunctions->openTable($query);  
  }
    public function summarized_query($config)  // Query for Summarized Invoice
  {
    $center = $config['params']['center'];
    $filteredcenter = $config['params']['dataparams']['center'];
    $branchname   = $config['params']['dataparams']['dcentername'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client= $config['params']['dataparams']['client'];
    $posttype = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['dagentname'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filter = "";
    $filter1 = "";
    $filterAg1 = "";
    $filterAg2 = "";
    $reporttype = $config['params']['dataparams']["reporttype"];
    $stock = '';
    $stock1 = '';
    $stock2 = '';


    // to switch between 3 reports
    if ($reporttype == 0) {
      $filter .= " and head.doc = 'ch'";
    } else if ($reporttype == 1) {
      $filter .= " and head.doc = 'on'";
    } else if ($reporttype == 2){
      $filter .= " and head.doc in ('on','ch')";
    }

    // Consign
     if ($reporttype == 0 && $posttype == 0) {  
      $stock .= "left join hsistock as stock on stock.trno = head.trno";
    } else if ($reporttype == 0 && $posttype == 1) {
      $stock .= "left join sistock as stock on stock.trno = head.trno";
    } else if ($reporttype == 0 && $posttype == 2) {
      $stock1 .= "left join hsistock as stock on stock.trno = head.trno";
      $stock2 .= "left join sistock as stock on stock.trno = head.trno";
    } 

    // Outright
    else if ($reporttype == 1 && $posttype == 0) { 
      $stock .= "left join cntnum as num2 on num2.svnum = head.trno
                 left join glstock as stock on stock.trno = num2.trno
                 ";
    } 
    else if ($reporttype == 1 && $posttype == 1) {
      $stock .= "left join cntnum as num2 on num2.svnum = head.trno 
                 left join glhead as ghead on ghead.trno = num2.trno 
                 left join glstock as stock on stock.trno = ghead.trno 
                ";

    } else if ($reporttype == 1 && $posttype == 2) {
      $stock1 .= "left join cntnum as num2 on num2.svnum = head.trno
                  left join glstock as stock on stock.trno = num2.trno
                  ";
      $stock2 .= "left join cntnum as num2 on num2.svnum = head.trno
                  left join glhead as ghead on ghead.trno = num2.trno 
                  left join glstock as stock on stock.trno = ghead.trno
                  ";
    } 

    if ($branchname != "") {
      $filter .= " and num.center = '$filteredcenter'";
    }

    if ($client != ''){
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    
    if ($prefix != "") {
      $filter .= " and num.bref = '$prefix' ";
    }

    if ($agentname != '') {
      if ($agentid != '0') {
          $filterAg1   .= " and head.agentid = '" . $agentid . "'";
          $filterAg2   .= " and head.agent = '" . $agent . "'";
      }
    }

    $query = '';
    switch ($posttype) {
      case 0: //Posted
         $query = "select
         'Posted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          $stock
          left join client on head.clientid = client.clientid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg1 
          group by head.docno, clientname, dateid
          order by head.docno  " . $sorting;
          // var_dump($query);
        break;
        
      case 1:  //Unposted
       $query = "select
          'Unposted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          $stock
          left join client on head.client = client.client
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid     
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg2 
          group by head.docno, clientname, dateid
          order by head.docno " . $sorting; 
          // var_dump( $query);

        break;

      case 2: // All
        $query = "select
         'Posted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          $stock1
          left join client on head.clientid = client.clientid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg1 
          group by head.docno, clientname, dateid
          union all
          select
          'Unposted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          $stock2
          left join client on head.client = client.client
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid     
          where date(head.dateid) between '$start' and '$end' $filter $filter1 $filterAg2 
          group by head.docno, clientname, dateid
          order by docno $sorting " ; 
            // var_dump($query);
          break;
      }

    return $this->coreFunctions->openTable($query);  
  }
      public function both_summarized_query($config)  // Query for Both Invoice (Summarized)
  {
    $center = $config['params']['center'];
    $filteredcenter = $config['params']['dataparams']['center'];
    $branchname   = $config['params']['dataparams']['dcentername'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client= $config['params']['dataparams']['client'];
    $posttype = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['dagentname'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filter = "";
    $filterAg1 = "";
    $filterAg2 = "";
 


    if ($branchname != "") {
      $filter .= " and num.center = '$filteredcenter'";
    }

    if ($client != ''){
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    
    if ($prefix != "") {
      $filter .= " and num.bref = '$prefix' ";
    }

    if ($agentname != '') {
      if ($agentid != '0') {
          $filterAg1   .= " and head.agentid = '" . $agentid . "'";
          $filterAg2   .= " and head.agent = '" . $agent . "'";
      }
    }

    $query = '';
    switch ($posttype) {
      case 0: //Posted
         $query = "select
         'Posted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join hsistock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter $filterAg1 
          group by head.docno, clientname, dateid
          union all
          select
         'Posted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from glhead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glstock as stock on stock.trno = num2.trno
          left join client on head.clientid = client.clientid
          left join client as agent on agent.clientid = head.agentid
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter $filterAg1 
          group by head.docno, clientname, dateid
          order by docno $sorting"; 
          // var_dump( $query ); 
        break;
        
      case 1:  //Unposted
       $query = "select
          'Unposted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join glhead as ghead on ghead.trno = head.trno
          left join sistock as stock on stock.trno = head.trno
          left join client on head.client = client.client
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter $filterAg2 
          group by head.docno, clientname, dateid
          union all
          select
          'Unposted' as status,
          head.docno, left(head.dateid,10) as dateid,
          head.clientname, 
          sum(stock.ext) as ext
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glhead as ghead on ghead.trno = num2.trno
          left join glstock as stock on stock.trno = ghead.trno
          left join client on head.client = client.client
          left join client as agent on agent.client = head.agent
          left join item on item.itemid = stock.itemid
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter $filterAg2
          group by head.docno, clientname, dateid
          order by docno $sorting"; 
          // var_dump( $query ); 
        break;

      case 2: // All
        $query = "
          select
          'Posted' as status,
            head.docno, left(head.dateid,10) as dateid,
            head.clientname, 
            sum(stock.ext) as ext
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join hsistock as stock on stock.trno = head.trno
            left join client on head.clientid = client.clientid
            left join client as agent on agent.clientid = head.agentid
            left join item on item.itemid = stock.itemid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter $filterAg1
            group by head.docno, clientname, dateid
            union all
            select
          'Posted' as status,
            head.docno, left(head.dateid,10) as dateid,
            head.clientname, 
            sum(stock.ext) as ext
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glstock as stock on stock.trno = num2.trno
            left join client on head.clientid = client.clientid
            left join client as agent on agent.clientid = head.agentid
            left join item on item.itemid = stock.itemid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter $filterAg1 
            group by head.docno, clientname, dateid
            union all
            select
            'Unposted' as status,
            head.docno, left(head.dateid,10) as dateid,
            head.clientname, 
            sum(stock.ext) as ext
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join glhead as ghead on ghead.trno = head.trno
            left join sistock as stock on stock.trno = head.trno
            left join client on head.client = client.client
            left join client as agent on agent.client = head.agent
            left join item on item.itemid = stock.itemid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter $filterAg2
            group by head.docno, clientname, dateid
            union all
            select
            'Unposted' as status,
            head.docno, left(head.dateid,10) as dateid,
            head.clientname, 
            sum(stock.ext) as ext
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glhead as ghead on ghead.trno = num2.trno
            left join glstock as stock on stock.trno = ghead.trno
            left join client on head.client = client.client
            left join client as agent on agent.client = head.agent
            left join item on item.itemid = stock.itemid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter $filterAg2
            group by head.docno, clientname, dateid
            order by docno $sorting"; 
            // var_dump($query);
          break;
      }

    return $this->coreFunctions->openTable($query);  
  }


  //  Layout Section - Consign, Outright and Both Invoice
  public function Default_Header($config, $recordCount)//Header (Summary Report)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $datetime = date("m-d-Y H:i:s", strtotime($this->othersClass->getCurrentTimeStamp()));
    $header = $this->coreFunctions->opentable("select name,address from center where code = '$center'");
    $posttype    = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $reporttypepcv = $config['params']['dataparams']['reporttypepcv'];
    $sorting = $config['params']['dataparams']['sorting'];
    $prefix = $config['params']['dataparams']['approved'];
    $filterusername  = $config['params']['dataparams']['username'];
    
    $str = ''; // required
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
   

    
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

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

    if ($reporttypepcv == 0) {
      $reporttypepcv = 'Summarized';
    } else  {
      $reporttypepcv = 'Detailed';
    } 

    
    if ($reporttype == 0) {
      $reporttype = 'Consign Invoice Report';
    } elseif ($reporttype == 1) {
      $reporttype = 'Outright Invoice Report';
    } else{
      $reporttype = 'Consign/Outright Invoice Report';
    }
    

    
    if ($filterusername == "") {
      $filterusername = "ALL USERS";
    } else {
      $filterusername;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(''. $reporttype . ' ('.$reporttypepcv.')', '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $filterusername, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: '. $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;

  }
  public function DefaultLayout_Detailed($config, $result)//Header (Detailed Report)
  {
    $str = '';
    $layoutsize = '1250';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Default_Header($config, count($result));
    $docno = "";
    $total = 0;
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

            $count = 41;
            $page = 40;

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;

          
          $str .= $this->reporter->begintable($layoutsize);

          $str .= '<br/>';

          
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->clientname, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          // $str .= $this->reporter->col('Expiry', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '170', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->whname, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        // $str .= $this->reporter->col($data->expiry, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ourref, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

    

        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->Default_Header($config, count($result));

          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    $str .= $this->reporter->endreport();

    return $str;

  }
  public function DefaultLayout_Summarized($config, $result)//Header (Detailed Report)
  {
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Default_Header($config, count($result));


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', 100, null, false, $border, 'TB','C', $font, $fontsize, 'B','','');
    $str .= $this->reporter->col('CUSTOMER NAME', 300, null, false, $border, 'TB','C', $font, $fontsize, 'B','','');
    $str .= $this->reporter->col('DOCUMENT NO.', 300, null, false, $border, 'TB','C', $font, $fontsize, 'B','','');
    $str .= $this->reporter->col('AMOUNT', 150, null, false, $border, 'TB','R', $font, $fontsize, 'B','','');
    $str .= $this->reporter->col('STATUS', 150, null, false, $border, 'TB','C', $font, $fontsize, 'B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $docno = "";
    $total = 0;
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
            $count = 41;
            $page = 40;

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $str .= $this->reporter->begintable($layoutsize);
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->docno, '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->status, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->addline();

        $total += $data->ext;

        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->Default_Header($config, count($result));

          $page = $page + $count;
        } //end if
      }
    }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' , '700', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('' . number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('' , '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    $str .= $this->reporter->endreport();

    return $str;

  }


  



}

