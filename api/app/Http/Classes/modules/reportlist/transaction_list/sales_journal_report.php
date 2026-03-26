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

class sales_journal_report
{
  public $modulename = 'Sales Journal Report';
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

    switch ($companyid) {
      case 21: //kinggeorge
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dwhname', 'reportusers', 'approved', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        break;
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dbranchname', 'reportusers', 'approved'];
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      case 17: //unihome
      case 39: //CBBSI
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.name', 'projectname');
        data_set($col1, 'project.required', false);
        break;
      case 32: //3M
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'brgy', 'area', 'region', 'dcentername', 'dagentname', 'reportusers', 'approved', 'salestype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'salestype.lookupclass', 'sjtype');
        data_set($col1, 'salestype.required', false);
        break;
      case 19: //housegem
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'reportusers', 'approved', 'project'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.name', 'projectname');
        data_set($col1, 'project.required', false);
        break;
      case 48: //seastar
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'reportusers', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

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

    switch ($companyid) {
      case 11: //SUMMIT
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summary Per Document', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'teal'],
            ['label' => 'Summarized Per Item', 'value' => '2', 'color' => 'teal']
          ]
        );
        break;
      case 32: //3m
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
        if ($viewcost == '1') {
          data_set($col2, 'radioreporttype.options', [
            ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detail All', 'value' => '1', 'color' => 'orange'],
            ['label' => 'Detailed with Balance', 'value' => '4', 'color' => 'orange'],
            ['label' => 'With Cost', 'value' => '3', 'color' => 'orange']
          ]);
        }
        break;
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $companyid = $config['params']['companyid'];
    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname,'' as userid,'' as username, '' as agent, '' as agentname, '' as dagentname, 0 as agentid, '' as approved,'' as project,'' as projectname,'' as projectid, '0' as posttype,'0' as reporttype,'ASC' as sorting,'' as dclientname,'' as reportusers,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as branchid,'' as branchname,'' as branchcode,'' as branch,
    '' as wh,'' as whname,'' as dwhname,'0' as whid,
    '' as brgy, '' as region, '' as area, '' as salestype,'0' as deptid,
    '0' as clientid";
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
    $companyid = $config['params']['companyid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $result = $this->reportAFTILayout_SUMMARIZED($config);
            break;
          case 37: //MEGACRYSTAL
            $result = $this->reportMCPCLayout_SUMMARIZED($config);
            break;
          case 47: //kitchenstar
            $result = $this->report_Kstar_Layout_SUMMARIZED($config);
            break;
          case 49: //hotmix
            $result = $this->report_HOTMIX_Layout_SUMMARIZED($config);
            break;
          case 60: //Transpower
            $result = $this->report_TRANSPOWER_Layout_SUMMARIZED($config);
            break;
          default:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
        } // end switch comp config  
        break;

      case '1': // DETAILED
      case '3': // DETAILED WITH COST, MARKUP
      case '4':
        switch ($companyid) {
          case 32: //3m
            $result = $this->report3mDefaultLayout_DETAILED($config);
            break;
          case 35: //aquamax
            $result = $this->reportAquaDefaultLayout_DETAILED($config);
            break;
          case 14: //majesty
            $result = $this->MAJESTY_Layout_DETAILED($config);
            break;
          case 48: //seastar
            $result = $this->Seastar_Layout_DETAILED($config);
            break;
          case 49: //hotmix
            $result = $this->reportHOTMIXDefaultLayout_DETAILED($config);
            break;
          default:
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
      case '2': // SUMMARIZED PER ITEM
        $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($posttype) {
      case '0': // POSTED
        if ($reporttype != 2) {
          $query = $this->default_QUERY_POSTED($config); //detailed/summary
        } else {
          $query = $this->SUMMIT_QUERY_POSTED($config); //per item
        }

        break;
      case '1': // UNPOSTED
        if ($reporttype != 2) {
          $query = $this->default_QUERY_UNPOSTED($config); //detailed/summary
        } else {
          $query = $this->SUMMIT_QUERY_UNPOSTED($config); //per item
        }

        break;
      case '2': // ALL
        if ($reporttype != 2) {
          $query = $this->default_QUERY_ALL($config); //detailed/summary
        } else {
          $query = $this->SUMMIT_QUERY_ALL($config); //per item
        }

        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function reportTranspower_Summarized($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];// 0 for post, 1 for unpost, 2 for all
    $reporttype = $config['params']['dataparams']['reporttype'];// 0 for summary, 1 for detail
    
    ////////
      $filter = "";
      $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      
      $client     = $config['params']['dataparams']['client'];
      $clientid = $config['params']['dataparams']['clientid'];

      if ($client != "") {
        $filter .= " and client.clientid = '$clientid' ";
      }

      $branchname   = isset($config['params']['dataparams']['centername']) ? $config['params']['dataparams']['centername'] : '';
      $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';

      if ($branchname != "") {
        $filter .= " and cntnum.center = '$fcenter'";
      }

      $agent = isset($config['params']['dataparams']['agent']) ? $config['params']['dataparams']['agent'] : '';
      $agentid = isset($config['params']['dataparams']['agentid']) ? $config['params']['dataparams']['agentid'] : '';
      $agentname = isset($config['params']['dataparams']['dagentname']) ? $config['params']['dataparams']['dagentname'] : '';
      
      if ($agentname != '') {
        $filter .= " and ag.agentid='" . $agentid . "'";
      }
      
      $username   = isset($config['params']['user']) ? $config['params']['user'] : '';
      $filterusername  = isset($config['params']['dataparams']['username']) ? $config['params']['dataparams']['username'] : '';

      if ($filterusername != "") {
        $filter .= " and head.createby = '$filterusername' ";
      }

      $prefix     = isset($config['params']['dataparams']['approved']) ? $config['params']['dataparams']['approved'] : '';

      if ($prefix != "") {
        $filter .= " and cntnum.bref = '$prefix' ";
      }
      
      $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';

    //////////

    // var_dump($config['params']);

    switch ($posttype) {
      case '0': // POSTED
        $query = "
        select 'POSTED' as 'status',head.dateid,client.clientname,head.docno,head.trno,ifnull(sum(stock.ext),0) as ext 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as ag on ag.clientid=head.agentid
        where head.doc in ('SJ','MJ') and date(head.dateid) between '$start' and '$end' $filter
        group by head.dateid,client.clientname,head.docno,head.trno
        order by docno $sorting";
        
        break;
      case '1': // UNPOSTED
        $query = "select 'UNPOSTED' as 'status',head.dateid,client.clientname,head.docno,head.trno,ifnull(sum(stock.ext),0) as ext 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client=head.client
        left join client as ag on ag.client=head.agent
        where head.doc in ('SJ','MJ') and date(head.dateid) between '$start' and '$end' $filter
        group by head.dateid,client.clientname,head.docno,head.trno
        order by docno $sorting";

        break;
      case '2': // ALL
        $query = "select 'UNPOSTED' as 'status',head.dateid,client.clientname,head.docno,head.trno,ifnull(sum(stock.ext),0) as ext 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client=head.client
        left join client as ag on ag.client=head.agent
        where head.doc in ('SJ','MJ') and date(head.dateid) between '$start' and '$end' $filter
        group by head.dateid,client.clientname,head.docno,head.trno
        union all
        select 'POSTED' as 'status',head.dateid,client.clientname,head.docno,head.trno,ifnull(sum(stock.ext),0) as ext 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as ag on ag.clientid=head.agentid
        where head.doc in ('SJ','MJ') and date(head.dateid) between '$start' and '$end' $filter
        group by head.dateid,client.clientname,head.docno,head.trno
        order by docno $sorting";
        
        break;
    }
        
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_POSTED($config)
  {
    $companyid  = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $whid = $config['params']['dataparams']['whid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh         = !isset($config['params']['dataparams']['wh']) ? '' : $config['params']['dataparams']['wh'];

    $branchname = '';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $branchname   = $config['params']['dataparams']['branchname'];
      $fcenter  = isset($config['params']['dataparams']['branchid']) ? $config['params']['dataparams']['branchid'] : '';
    } else {
      $branchname   = $config['params']['dataparams']['centername'];
      $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    }

    $filter2 = '';
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['dagentname'];

    if ($agentname != '') {
      $filter2 = " and head.agentid='" . $agentid . "'";
    }

    $proj    = $config['params']['dataparams']['projectid'];


    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    if ($branchname != "") {
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $filter .= " and branch.clientid = '$fcenter'";
      } else {
        $filter .= " and cntnum.center = '$fcenter'";
      }
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid'";
    }

    if ($companyid == 19) { //housegem
      $project    = $config['params']['dataparams']['projectname'];
      if ($project != '') {
        $filter .= " and proj.line = '$proj'";
      }
    } else {
      if ($proj != "") {
        $filter .= " and proj.line = '$proj'";
      }
    }

    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $collectorjoin = '';
    $agentfield = '';
    $agentfield2 = '';
    $agjoin = '';
    $aggroupby = '';

    $addedfields = '';
    $addedfields2 = '';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectcode = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['deptid'];
      $projectid = $config['params']['dataparams']['projectid'];
      $dept = $config['params']['dataparams']['dept'];

      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "
        left join model_masterfile as model on model.model_id=item.model 
        left join frontend_ebrands as brand on brand.brandid = item.brand 
        left join iteminfo as i on i.itemid  = item.itemid";
      $collectorjoin = 'left join client as branch on branch.clientid=head.branch';
      $leftjoinproject = ' left join projectmasterfile as proj on proj.line = stock.projectid ';
    } else {

      $agentfield2 = ',agentname';
      $agentfield = ',ag.clientname as agentname';
      $agjoin = 'left join client as ag on ag.clientid=head.agentid';
      $aggroupby = ',ag.clientname';
      $addedfields = " ,head.terms,head.createby";
      $addedfields2 = " ,terms,createby";
      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }
    switch ($companyid) {
      case 14: //majesty
      case 32: //3m
      case 49: //hotmix
        $isqty = 'stock.isqty';
        break;

      default:
        $isqty = 'stock.iss';
        break;
    }

    $filter3 = "";
    $addfilter3m = "";
    if ($companyid == 32) { //3m
      $brgy = $config['params']['dataparams']['brgy'];
      $area = $config['params']['dataparams']['area'];
      $region = $config['params']['dataparams']['region'];
      $salestype = $config['params']['dataparams']['salestype'];
      if ($brgy != '') $filter3 .= " and client.brgy = '" . $brgy . "' ";
      if ($area != '') $filter3 .= " and client.area = '" . $area . "' ";
      if ($region != '') $filter3 .= " and client.region = '" . $region . "' ";
      if ($salestype != '') $filter3 .= " and head.salestype = '" . $salestype . "' ";
      if ($reporttype == '4') $addfilter3m = " and arledger.bal > 0";
      $addjoin = " left join arledger on arledger.trno =  head.trno ";
    }

    switch ($reporttype) {
      case '1': //detailed
      case '3': // detailed with cost, markup (3m)
      case '4': // detailed with balance (3m)
        switch ($companyid) {
          case 10: //afti
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $agjoin . "
              " . $leftjoinproject . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','ai') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
              order by docno " . $sorting;
            break;
          case 32: //3m
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, client.brgy, client.area,
              ifnull((select arledger.bal from arledger where arledger.trno=head.trno), 0) as bal, head.terms, (stock.cost * uom.factor) as cost,
              stock.ext - ((stock.cost * uom.factor) * stock.isqty) as markup " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              " . $agjoin . "
              " . $leftjoinproject . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='sj' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . " " . $filter3 . " " . $addfilter3m . "
              order by docno " . $sorting;
            break;
          case 35: //aquamax
            $query = "select head.yourref,head.docno,client.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty3,
              FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=stock.suppid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $agjoin . "
              " . $leftjoinproject . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='wm' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
              order by docno " . $sorting;
            break;
          case 49: //hotmix
            $query = "select head.trno,head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
              stock.uom," . $isqty . " as iss,stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,
              stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, 
              ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,proj.name as projname,head.ourref " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $agjoin . "
              " . $leftjoinproject . "
              " . $addjoin . "
              left join hcntnuminfo as hinfo on hinfo.trno = head.trno
              where head.doc in ('sj','mj') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
              order by docno " . $sorting;
            break;
          case 48: //seastar
            $query = "select head.docno,head.clientname as supplier,head.dateid,head.rem,
                            wh.clientname as whname,whto.clientname as whtoname,
                            sh.clientname as shipper,head.tax,head.vattype,head.terms,
                            proj.name as project,info.itemdesc as itemname,info.unit as uom,
                            FORMAT(info.weight,2) as weight,FORMAT(stock.isamt,2) as isamt,cinfo.trnxtype,
                            ROUND(stock.isqty)  as isqty, sum(cinfo.weight+cinfo.valamt+cinfo.cumsmt+cinfo.delivery) as totalcharges
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                      left join cntnum on cntnum.trno=head.trno
                      left join client as wh on wh.clientid = head.whid
                      left join client as whto on whto.client=head.whto
                      left join client as sh on sh.clientid=head.shipperid
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                      left join hcntnuminfo as cinfo on cinfo.trno=head.trno
                      where head.doc in ('sj') and date(head.dateid)
                            between  '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
                      group by head.docno,head.clientname,head.dateid,head.rem,
                            wh.clientname,whto.clientname,sh.clientname,head.tax,head.vattype,head.terms,
                            proj.name,info.itemdesc,info.unit,info.weight,stock.isamt,stock.isqty,cinfo.trnxtype
                      order by docno " . $sorting;

            break;
          default:
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,head.terms " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $agjoin . "
              " . $leftjoinproject . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
              order by docno " . $sorting;
            break;
        }

        break;
      case '0': //summary
        switch ($companyid) {
          case 10: //afti
            $query = "select status, docno,dateid, clientname, yourref, ext,taxdef,tax, remarks,modeofdelivery,
            endorsedby,receiveby,receivedate,trackingno,delcharge,insurance " . $agentfield2 . "
            from (select 'POSTED' as status,head.docno,head.dateid,head.clientname, head.yourref,head.taxdef,head.tax,
            sum(stock.ext) as ext,ds.remarks,ds.modeofdelivery,ds.driver as endorsedby,
            ds.receiveby,left(ds.receivedate,10) as receivedate,ds.trackingno,ds.delcharge,stock.insurance " . $agentfield . "
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join delstatus as ds on ds.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            left join cntnum on cntnum.trno=head.trno
            " . $agjoin . "
            " . $leftjoinproject . "
            " . $collectorjoin . "
            where head.doc in ('sj','ai') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
            group by head.docno,head.dateid, head.clientname,
            head.yourref,head.taxdef,head.tax, ds.remarks,ds.modeofdelivery,ds.driver,ds.receiveby,ds.receivedate,ds.trackingno,ds.delcharge,stock.insurance " . $aggroupby . ") as a
            order by docno " . $sorting;
            break;
          case 26: //bee healthy
            $query = "select status, docno, supplier, ext, clientname, dateid,  wh, deptcode,deptname" . $agentfield2 . " " . $addedfields2 . "
            from (select 'POSTED' as status,head.docno,
            head.clientname as supplier,sum(stock.cr) as ext, '' as clientname, '' as wh,
            date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
            " . $addedfields . "
            from glhead as head
            left join gldetail as stock on stock.trno=head.trno
            left join coa on coa.acnoid=stock.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as dept on dept.clientid = head.deptid
            " . $agjoin . "
            " . $leftjoinproject . "
            where head.doc='sj' and coa.alias='SA1'
            and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
            group by head.docno, head.clientname,
            head.dateid, dept.client, dept.clientname " . $aggroupby . "  " . $addedfields . ") as a
            order by docno " . $sorting;
            break;
          case 32: //3m
            $query = "select status, docno, supplier, ext, clientname, dateid, wh, deptcode,deptname, brgy, area, bal " . $agentfield2 . " " . $addedfields2 . "
            from (select 'POSTED' as status,head.docno,
            head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
            date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname,
            client.brgy, client.area, ifnull((select arledger.bal from arledger where arledger.trno=head.trno), 0) as bal " . $agentfield . "
            " . $addedfields . "
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            " . $agjoin . "
            " . $leftjoinproject . "
            where head.doc='sj'
            and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . " " . $filter3 . "
            group by head.docno, head.clientname,
            wh.clientname, wh.client, head.dateid, dept.client, dept.clientname,client.brgy,client.area,head.trno,head.terms " . $aggroupby . ") as a
            order by docno " . $sorting;
            break;
          case 49: //hotmix
            $agfilter2 = '';
            $agentid = $config['params']['dataparams']['agentid'];
            $agentname = $config['params']['dataparams']['dagentname'];

            if ($agentname != '') {
              $agfilter2 = " and head.agentid='" . $agentid . "'";
            }
            $query = "select k.*,(ext-netcomm) as total from (select 'POSTED' as status, head.docno,
              head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, wh.client as wh, head.yourref,
              left(head.dateid, 10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.client as code " . $agentfield . "  " . $addedfields . ", ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno), 'OPEN') as paidstat, head.rem,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid = head.clientid 
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              left join hcntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              left join client as ag on ag.clientid=head.agentid
              where head.doc in ('sj', 'mj')
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              group by head.docno, head.yourref, head.clientname, wh.clientname, head.dateid, wh.client, dept.client, dept.clientname, head.trno, head.rem, client.client, hinfo.commamt, hinfo.commvat" . $aggroupby . "  " . $addedfields . " 
              order by head.docno $sorting) as k";
            break;
          case 48: //seastar
            $query = "select status, docno, supplier, ext, clientname, dateid, wh, deptcode, deptname, paidstat, rem, code " . $agentfield2 . " " . $addedfields2 . " 
            from (select 'POSTED' as status, head.docno, head.clientname as supplier, 
            sum(info.weight+info.valamt+info.cumsmt+info.delivery) as ext, wh.clientname, wh.client as wh,
            ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno), 'OPEN') as paidstat, head.rem, date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname, client.client as code " . $agentfield . " " . $addedfields . "
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid = head.clientid
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            left join hcntnuminfo as info on info.trno=head.trno
            " . $agjoin . "
            " . $leftjoinproject . " 
            where head.doc in ('sj','mj') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "  
            group by head.docno, head.clientname, wh.clientname, wh.client, head.dateid, dept.client, dept.clientname, head.trno, head.rem, client.client" . $aggroupby . "  " . $addedfields . "
            ) as a order by docno " . $sorting;
            break;
          default:
            $query = "select status, docno, supplier, ext, clientname,trno, dateid, wh, deptcode, deptname, paidstat, rem, code " . $agentfield2 . " " . $addedfields2 . " 
            from (select 'POSTED' as status, head.docno, head.clientname as supplier,head.trno, sum(stock.ext) as ext, wh.clientname, wh.client as wh,
            ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno), 'OPEN') as paidstat, head.rem, date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname, client.client as code " . $agentfield . " " . $addedfields . "
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid = head.clientid
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            " . $agjoin . "
            " . $leftjoinproject . " 
            where head.doc in ('sj','mj') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "  
            group by head.docno, head.clientname, wh.clientname, wh.client, head.dateid, dept.client, dept.clientname, head.trno, head.rem, client.client" . $aggroupby . "  " . $addedfields . "
            ) as a order by docno " . $sorting;

            break;
        }

        break;
    }

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $companyid = $config['params']['companyid'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $whid = $config['params']['dataparams']['whid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh        = !isset($config['params']['dataparams']['wh']) ? '' : $config['params']['dataparams']['wh'];

    $branchname = '';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $branchname   = $config['params']['dataparams']['branchname'];
      $fcenter  = isset($config['params']['dataparams']['branchid']) ? $config['params']['dataparams']['branchid'] : '';
    } else {
      $branchname   = $config['params']['dataparams']['centername'];
      $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    }
    $proj    = $config['params']['dataparams']['projectid'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($branchname != "") {
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $filter .= " and branch.clientid = '$fcenter'";
      } else {
        $filter .= " and cntnum.center = '$fcenter'";
      }
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid'";
    }

    if ($companyid == 19) { //housegem
      $project    = $config['params']['dataparams']['projectname'];
      if ($project != '') {
        $filter .= " and proj.line = '$proj'";
      }
    } else {
      if ($proj != "") {
        $filter .= " and proj.line = '$proj'";
      }
    }

    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $collectorjoin = '';
    $agentfield = '';
    $agjoin = '';
    $aggroupby = '';
    $filter2 = '';
    $addedfields = '';
    $addedfields2 = '';

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectcode = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['dept'];
      $projectid = $config['params']['dataparams']['projectid'];
      $deptid = $config['params']['dataparams']['deptid'];
      // if ($deptid == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['deptid'];
      // }
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "
        left join model_masterfile as model on model.model_id=item.model 
        left join frontend_ebrands as brand on brand.brandid = item.brand 
        left join iteminfo as i on i.itemid  = item.itemid";
      $collectorjoin = 'left join client as branch on branch.clientid=head.branch';
      $leftjoinproject = ' left join projectmasterfile as proj on proj.line = stock.projectid ';
    } else {

      $agentfield = ',ag.clientname as agentname';
      $agjoin = 'left join client as ag on ag.client=head.agent';
      $aggroupby = ',ag.clientname';
      $addedfields = " ,head.terms,head.createby";
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['dagentname'];

      if ($agentname != "") {
        $filter2 = " and head.agent='" . $agent . "'";
      }

      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    $filter3 = "";
    $addfilter3m = "";
    if ($companyid == 32) { //3m
      $brgy = $config['params']['dataparams']['brgy'];
      $area = $config['params']['dataparams']['area'];
      $region = $config['params']['dataparams']['region'];
      $salestype = $config['params']['dataparams']['salestype'];
      if ($brgy != '') $filter3 .= " and client.brgy = '" . $brgy . "' ";
      if ($area != '') $filter3 .= " and client.area = '" . $area . "' ";
      if ($region != '') $filter3 .= " and client.region = '" . $region . "' ";
      if ($salestype != '') $filter3 .= " and head.salestype = '" . $salestype . "' ";
    }

    switch ($companyid) {
      case 14: //majesty
      case 32: //3m
      case 49: //hotmix
        $isqty = 'stock.isqty';
        break;

      default:
        $isqty = 'stock.iss';
        break;
    }

    switch ($reporttype) {
      case '1': //detailed
      case '3': // detailed with cost, markup (3m)
      case '4': // detailed with balance (3m)
        switch ($companyid) {
          case 10: //afti
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','ai') 
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              order by docno $sorting";
            break;
          case 32: //3m
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, client.brgy, client.area, 0 as bal, head.terms,
              (stock.cost * uom.factor) as cost,
              stock.ext - ((stock.cost * uom.factor) * stock.isqty) as markup " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              " . $leftjoinproject . "
              " . $agjoin . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='sj' 
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . " " . $filter3 . " " . $addfilter3m . "
              order by docno $sorting";
            break;
          case 35: //aquamax
            $query = "select head.yourref,head.docno,client.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty3,
              FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=stock.suppid
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='wm' 
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              order by docno $sorting";
            break;
          case 49: //hotmix
            $query = "select head.trno,head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, 
              ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,proj.name as projname,head.ourref " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              " . $addjoin . "
              left join cntnuminfo as hinfo on hinfo.trno = head.trno
              where head.doc in ('sj', 'mj') 
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              order by docno $sorting";
            break;
          case 48: //seastar
            $query = "select head.docno,head.clientname as supplier,head.dateid,head.rem,
                            wh.clientname as whname,whto.clientname as whtoname,
                            sh.clientname as shipper,head.tax,head.vattype,head.terms,
                            proj.name as project,info.itemdesc as itemname,info.unit as uom,
                            FORMAT(info.weight,2) as weight,FORMAT(stock.isamt,2) as isamt,cinfo.trnxtype,
                            ROUND(stock.isqty) as isqty, sum(cinfo.weight+cinfo.valamt+cinfo.cumsmt+cinfo.delivery) as totalcharges
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join cntnum on cntnum.trno=head.trno
                      left join client as wh on wh.client= head.wh
                      left join client as whto on whto.client=head.whto
                      left join client as sh on sh.clientid=head.shipperid
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                      left join cntnuminfo as cinfo on cinfo.trno=head.trno
                      where head.doc in ('sj') and date(head.dateid)
                            between  '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
                      group by head.docno,head.clientname,head.dateid,head.rem,
                            wh.clientname,whto.clientname,sh.clientname,head.tax,head.vattype,head.terms,
                            proj.name,info.itemdesc,info.unit,info.weight,stock.isamt,stock.isqty,cinfo.trnxtype
                      order by docno " . $sorting;
            break;
          default:
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,head.terms " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj', 'mj') 
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              order by docno $sorting";
            break;
        }

        break;
      case '0': //summary
        switch ($companyid) {
          case 10: //afti
            $query = "select 'UNPOSTED' as status,head.docno,head.dateid,head.clientname, head.yourref,head.taxdef,head.tax,
                              sum(stock.ext) as ext,ds.remarks,ds.modeofdelivery,ds.driver as endorsedby,
                              ds.receiveby,left(ds.receivedate,10) as receivedate,ds.trackingno,ds.delcharge,stock.insurance " . $agentfield . "
                              " . $addedfields . "
                from lastock as stock
                left join lahead as head on head.trno=stock.trno
                left join delstatus as ds on ds.trno=head.trno
                left join client on client.client=head.client
                left join client as wh on wh.client = head.wh
                left join client as dept on dept.clientid = head.deptid
                left join cntnum on cntnum.trno=head.trno
                " . $leftjoinproject . "
                " . $agjoin . "                
                " . $collectorjoin . "
                where head.doc in ('sj','ai') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
                group by head.docno,head.dateid, head.clientname,
                          head.yourref,head.taxdef,head.tax, ds.remarks,ds.modeofdelivery,ds.driver,ds.receiveby,ds.receivedate,ds.trackingno,ds.delcharge,stock.insurance " . $aggroupby . " " . $addedfields . "
                order by head.docno $sorting";
            break;
          case 32: //3m
            $query = "select 'UNPOSTED' as status ,head.yourref,
              head.docno,head.clientname as supplier,
              sum(stock.ext) as ext, wh.clientname, wh.client as wh,
              left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.brgy, client.area, 0 as bal " . $agentfield . " " . $addedfields . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              where head.doc='sj' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . " " . $filter3 . "
              group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client, dept.client, dept.clientname, client.brgy, client.area " . $aggroupby . " " . $addedfields . "
              order by head.docno $sorting";
            break;
          case 49: //hotmix
            $agfilter1 = '';
            $agent = $config['params']['dataparams']['agent'];
            $agentname = $config['params']['dataparams']['dagentname'];

            if ($agentname != '') {
              $agfilter1 = " and head.agent='" . $agent . "'";
            }
            $query = "select k.*,(ext-netcomm) as total from (select 'UNPOSTED' as status , head.docno, head.clientname as supplier,
              sum(stock.ext) as ext, wh.clientname, wh.client as wh, head.yourref,
              left(head.dateid, 10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.client as code " . $agentfield . " " . $addedfields . ", ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno), 'OPEN') as paidstat, head.rem,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno = head.trno
              left join client on client.client = head.client
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join cntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              left join client as ag on ag.client=head.agent
              where head.doc in ('sj', 'mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              group by head.docno, head.yourref, head.clientname, wh.clientname, head.dateid, wh.client, dept.client, dept.clientname, head.trno, head.rem, client.client, hinfo.commamt, hinfo.commvat" . $aggroupby . "  " . $addedfields . "
              order by head.docno $sorting) as k";
            break;
          case 48: //seastar
            $query = "select 'UNPOSTED' as status ,head.yourref,
              head.docno,head.clientname as supplier,
              sum(info.weight+info.valamt+info.cumsmt+info.delivery) as ext, wh.clientname, wh.client as wh,
              ifnull((select (case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem,
              left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code" . $agentfield . " " . $addedfields . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join cntnuminfo as info on info.trno=head.trno
              " . $leftjoinproject . "
              " . $agjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client, dept.client, dept.clientname,head.trno, head.rem,client.client" . $aggroupby . " " . $addedfields . "
              order by head.docno $sorting";
            break;
          default:
            $query = "select 'UNPOSTED' as status ,head.yourref,
              head.docno,head.clientname as supplier,head.trno,
              sum(stock.ext) as ext, wh.clientname, wh.client as wh,
              ifnull((select (case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem,
              left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code" . $agentfield . " " . $addedfields . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
              group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client, dept.client, dept.clientname,head.trno, head.rem,client.client" . $aggroupby . " " . $addedfields . "
              order by head.docno $sorting";

            break;
        }
        break;
    }
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $companyid = $config['params']['companyid'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $whid = $config['params']['dataparams']['whid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh         = !isset($config['params']['dataparams']['wh']) ? '' : $config['params']['dataparams']['wh'];

    $branchname = '';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $branchname   = $config['params']['dataparams']['branchname'];
      $fcenter  = isset($config['params']['dataparams']['branchid']) ? $config['params']['dataparams']['branchid'] : '';
    } else {
      $branchname   = $config['params']['dataparams']['centername'];
      $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    }
    $proj    = $config['params']['dataparams']['projectid'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($branchname != "") {
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $filter .= " and branch.clientid = '$fcenter'";
      } else {
        $filter .= " and cntnum.center = '$fcenter'";
      }
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid'";
    }

    if ($companyid == 19) { //housegem
      $project    = $config['params']['dataparams']['projectname'];
      if ($project != '') {
        $filter .= " and proj.line = '$proj'";
      }
    } else {
      if ($proj != "") {
        $filter .= " and proj.line = '$proj'";
      }
    }
    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $collectorjoin = '';
    $agentfield = '';
    $agjoin1 = '';
    $agjoin2 = '';
    $aggroupby = '';
    $agfilter1 = '';
    $agfilter2 = '';
    $addedfields = '';
    $addedfields2 = '';

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectcode = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['dept'];
      $projectid = $config['params']['dataparams']['projectid'];
      $deptid = $config['params']['dataparams']['deptid'];
      // if ($deptid == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['deptid'];
      // }
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "
      left join model_masterfile as model on model.model_id=item.model 
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid";
      $collectorjoin = 'left join client as branch on branch.clientid=head.branch';
      $leftjoinproject = ' left join projectmasterfile as proj on proj.line = stock.projectid ';
    } else {
      $agentfield = ',ag.clientname as agentname';
      $agjoin1 = 'left join client as ag on ag.client=head.agent';
      $agjoin2 = 'left join client as ag on ag.clientid=head.agentid';
      $aggroupby = ',ag.clientname';

      $addedfields = " ,head.terms,head.createby";
      $addedfield2 = " ,terms";

      $agent = $config['params']['dataparams']['agent'];
      $agentid = $config['params']['dataparams']['agentid'];
      $agentname = $config['params']['dataparams']['dagentname'];

      if ($agentname != '') {
        $agfilter1 = " and head.agent='" . $agent . "'";
        $agfilter2 = " and head.agentid='" . $agentid . "'";
      }

      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    $filter3 = "";
    $addfields3m = "";
    $addfields3m2 = "";
    $addfilter3m = "";
    if ($companyid == 32) { //3m
      $brgy = $config['params']['dataparams']['brgy'];
      $area = $config['params']['dataparams']['area'];
      $region = $config['params']['dataparams']['region'];
      $salestype = $config['params']['dataparams']['salestype'];
      if ($brgy != '') $filter3 .= " and client.brgy = '" . $brgy . "' ";
      if ($area != '') $filter3 .= " and client.area = '" . $area . "' ";
      if ($region != '') $filter3 .= " and client.region = '" . $region . "' ";
      if ($salestype != '') $filter3 .= " and head.salestype = '" . $salestype . "' ";
      $addfields3m = ",client.brgy, client.area, 0 as bal";
      $addfields3m2 = ",client.brgy, client.area, ifnull((select arledger.bal from arledger where arledger.trno=head.trno), 0) as bal";
      if ($reporttype == '4') $addfilter3m = " and arledger.bal > 0";
      $addjoin = " left join arledger on arledger.trno =  head.trno ";
    }

    switch ($companyid) {
      case 14: //majesty
      case 32: //3m
      case 49: //hotmix
        $isqty = 'stock.isqty';
        break;

      default:
        $isqty = 'stock.iss';
        break;
    }

    switch ($reporttype) {
      case '0': //summary
        switch ($companyid) {
          case 10: //afti
            $query = "select * from (select 'UNPOSTED' as status,head.docno,head.dateid,head.clientname, head.yourref,head.taxdef,head.tax,
            sum(stock.ext) as ext,ds.remarks,ds.modeofdelivery,ds.driver as endorsedby,
            ds.receiveby,left(ds.receivedate,10) as receivedate,ds.trackingno,ds.delcharge,stock.insurance 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join delstatus as ds on ds.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            left join cntnum on cntnum.trno=head.trno
            " . $leftjoinproject . "
            " . $collectorjoin . "
            where head.doc in ('sj','ai') and date(head.dateid) between '$start' and '$end' $filter $filter1
            group by head.docno,head.dateid, head.clientname,head.yourref,head.taxdef,head.tax, ds.remarks,ds.modeofdelivery,
            ds.driver,ds.receiveby,ds.receivedate,ds.trackingno,ds.delcharge,stock.insurance
            UNION ALL
            select 'POSTED' as status,head.docno,head.dateid,head.clientname, head.yourref,head.taxdef,head.tax,
            sum(stock.ext) as ext,ds.remarks,ds.modeofdelivery,ds.driver as endorsedby,
            ds.receiveby,left(ds.receivedate,10) as receivedate,ds.trackingno,ds.delcharge,stock.insurance
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join delstatus as ds on ds.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            left join cntnum on cntnum.trno=head.trno
            " . $leftjoinproject . "
            " . $collectorjoin . "
            where head.doc in ('sj','ai') and date(head.dateid) between '$start' and '$end' $filter $filter1
            group by head.docno,head.dateid, head.clientname,head.yourref,head.taxdef,head.tax, ds.remarks,ds.modeofdelivery,
            ds.driver,ds.receiveby,ds.receivedate,ds.trackingno,ds.delcharge,stock.insurance) as g order by g.docno $sorting";
            break;
          case 26: //bee healthy
            $query = "select * from (select 'UNPOSTED' as status ,
            head.docno,head.clientname as supplier,
            sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname " . $agentfield . " " . $addedfields . "
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin1 . "
            where head.doc='sj' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
            group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client,dept.client,dept.clientname " . $aggroupby . "  " . $addedfields . "
            UNION ALL
            select 'POSTED' as status,head.docno,
            head.clientname as supplier,sum(stock.cr) as ext, '' as clientname, '' as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "  " . $addedfields . "
            from glhead as head
            left join gldetail as stock on stock.trno=head.trno
            left join coa on coa.acnoid=stock.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin2 . "
            where head.doc='sj' and coa.alias='SA1'
            and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
            group by head.docno,head.yourref,head.clientname,
            head.dateid, dept.client, dept.clientname " . $aggroupby . "  " . $addedfields . "
            ) as g order by g.docno $sorting";
            break;
          case 32: //3m
            $query = "select * from (
            select 'UNPOSTED' as status ,
            head.docno,head.clientname as supplier,
            sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.brgy, client.area, sum(stock.ext) as bal " . $agentfield . " " . $addedfields . "
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin1 . "
            where head.doc='sj' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . " " . $filter3 . "
            group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client,dept.client,dept.clientname, client.brgy, client.area, head.trno " . $aggroupby . "  " . $addedfields . "
            UNION ALL
            select 'POSTED' as status,head.docno,
            head.clientname as supplier,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.brgy, client.area, ifnull((select arledger.bal from arledger where arledger.trno=head.trno), 0) as bal " . $agentfield . "  " . $addedfields . "
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $addjoin . "
            " . $agjoin2 . "
            where head.doc='sj'
            and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . " " . $filter3 . "
            group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client,dept.client, dept.clientname, client.brgy, client.area, head.trno " . $aggroupby . "  " . $addedfields . "
            ) as g order by g.docno $sorting";
            break;
          case 35: //aquamax
            $query = "select * from (
            select 'UNPOSTED' as status,head.docno,client.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname " . $agentfield . " " . $addedfields . "
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=stock.suppid
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin1 . "
            where head.doc='wm' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
            group by head.docno,head.yourref,client.clientname,
              wh.clientname,head.dateid, wh.client,dept.client,dept.clientname " . $aggroupby . "  " . $addedfields . "
            UNION ALL
            select 'POSTED' as status,head.docno,client.clientname as supplier,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "  " . $addedfields . "
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=stock.suppid
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin2 . "
            where head.doc='wm'
            and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
            group by head.docno,head.yourref,client.clientname,
              wh.clientname,head.dateid, wh.client,dept.client, dept.clientname " . $aggroupby . "  " . $addedfields . "
            ) as g order by g.docno $sorting";

            break;
          case 49: //hotmix
            $query = "select g.*,(ext-netcomm) as total from (select 'UNPOSTED' as status , head.docno, 
                head.clientname as supplier,
              sum(stock.ext) as ext, wh.clientname, wh.client as wh, head.yourref,
              left(head.dateid, 10) as dateid, dept.client as deptcode, dept.clientname as deptname, 
              client.client as code " . $agentfield . " " . $addedfields . ", 
              ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar 
              where ar.trno = head.trno), 'OPEN') as paidstat, head.rem,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, 
              ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,proj.name as projname,head.ourref
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno = head.trno
              left join client on client.client = head.client
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join cntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              " . $agjoin1 . "
              where head.doc in ('sj', 'mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              group by head.docno, head.yourref, head.clientname, wh.clientname, head.dateid, wh.client, dept.client, dept.clientname, head.trno, head.rem, client.client, hinfo.commamt, hinfo.commvat" . $aggroupby . "  " . $addedfields . "
              UNION ALL
              select 'POSTED' as status, head.docno,
              head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, wh.client as wh, head.yourref,
              left(head.dateid, 10) as dateid, dept.client as deptcode, dept.clientname as deptname, client.client as code " . $agentfield . "  " . $addedfields . ", ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno), 'OPEN') as paidstat, head.rem,
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, 
              ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,proj.name as projname,head.ourref
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid = head.clientid 
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              left join hcntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              " . $agjoin2 . "
              where head.doc in ('sj', 'mj')
              and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              group by head.docno, head.yourref, head.clientname, wh.clientname, head.dateid, wh.client, dept.client, dept.clientname, head.trno, head.rem, client.client, hinfo.commamt, hinfo.commvat" . $aggroupby . "  " . $addedfields . ") as g 
              order by g.docno $sorting";
            break;

          case 48: //seastar
            $query = "select * from (select 'UNPOSTED' as status ,head.docno,head.clientname as supplier,
            sum(info.weight+info.valamt+info.cumsmt+info.delivery) as ext, wh.clientname, wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,
            client.client as code " . $agentfield . " " . $addedfields . ", 
            ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) 
            from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            left join cntnuminfo as info on info.trno=head.trno
            " . $leftjoinproject . "
            " . $agjoin1 . "
            where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
            group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client,dept.client,dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . "
            UNION ALL
            select 'POSTED' as status,head.docno,
            head.clientname as supplier,sum(info.weight+info.valamt+info.cumsmt+info.delivery) as ext, wh.clientname,  wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code " . $agentfield . "  " . $addedfields . ", ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            left join hcntnuminfo as info on info.trno=head.trno
            " . $leftjoinproject . "
            " . $agjoin2 . "
            where head.doc in ('sj','mj')
            and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
            group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client,dept.client, dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . ") as g order by g.docno $sorting";

            break;

          default:
            $query = "select * from (select 'UNPOSTED' as status ,head.docno,head.clientname as supplier,head.trno,
            sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,
            client.client as code " . $agentfield . " " . $addedfields . ", 
            ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) 
            from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin1 . "
            where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
            group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client,dept.client,dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . "
            UNION ALL
            select 'POSTED' as status,head.docno,
            head.clientname as supplier,head.trno,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code " . $agentfield . "  " . $addedfields . ", ifnull((select max(case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client on client.clientid=head.clientid 
            left join client as wh on wh.clientid = head.whid
            left join client as dept on dept.clientid = head.deptid
            " . $leftjoinproject . "
            " . $agjoin2 . "
            where head.doc in ('sj','mj')
            and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
            group by head.docno,head.yourref,head.clientname,
              wh.clientname,head.dateid, wh.client,dept.client, dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . ") as g order by g.docno $sorting";
            break;
        }
        break;

      case '1': //detailed
      case '3': // detailed with cost,markup (3m)
      case '4': // detailed with balance (3m)
        switch ($companyid) {
          case 10: //afti
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin1 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','ai') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              union all
              select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin2 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','ai') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              order by docno $sorting";
            break;
          case 32: //3m
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,
              client.brgy, client.area, 0 as bal, (stock.cost * uom.factor) as cost,
              stock.ext - ((stock.cost * uom.factor) * stock.isqty) as markup " . $addedfields . "
              " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              " . $leftjoinproject . "
              " . $agjoin1 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='sj' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . " " . $filter3 . " " . $addfilter3m . "
              union all
              select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,
              client.brgy, client.area, ifnull((select arledger.bal from arledger where arledger.trno=head.trno), 0) as bal,
              (stock.cost * uom.factor) as cost,
              stock.ext - ((stock.cost * uom.factor) * stock.isqty) as markup " . $addedfields . "
              " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              " . $leftjoinproject . "
              " . $agjoin2 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='sj' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . " " . $filter3 . " " . $addfilter3m . "
              order by docno $sorting";
            break;
          case 35: //aquamax
            $query = "select head.yourref,head.docno,client.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty3,
              FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
              " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=stock.suppid
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin1 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='wm' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              union all
              select head.yourref,head.docno,client.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty3,
              FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
              " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=stock.suppid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin2 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc='wm' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              order by docno $sorting";
            break;
          case 49: //hotmix
            $query = "select head.trno,head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
              stock.uom," . $isqty . " as iss,stock.isamt,stock.disc,stock.ext,wh.clientname,
              head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref, 
              dept.client as deptcode, dept.clientname as deptname,ifnull(hinfo.commamt, 0) as commamt, 
              ifnull(hinfo.commvat, 0) as commvat, ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,
              proj.name as projname,head.ourref
              " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              left join cntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              " . $agjoin1 . "
              " . $addjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              union all
              select head.trno,head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, 
              ifnull(hinfo.commamt, 0) as commamt, ifnull(hinfo.commvat, 0) as commvat, 
              ifnull(hinfo.commamt, 0) - ifnull(hinfo.commvat, 0) as netcomm,proj.name as projname,head.ourref
              " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              left join hcntnuminfo as hinfo on hinfo.trno = head.trno
              " . $leftjoinproject . "
              " . $agjoin2 . "
              " . $addjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              order by docno $sorting";
            break;
          case 48: //seastar
            $query = "select head.docno,head.clientname as supplier,head.dateid,head.rem,
                            wh.clientname as whname,whto.clientname as whtoname,
                            sh.clientname as shipper,head.tax,head.vattype,head.terms,
                            proj.name as project,info.itemdesc as itemname,info.unit as uom,
                            FORMAT(info.weight,2) as weight,FORMAT(stock.isamt,2) as isamt,cinfo.trnxtype,
                            ROUND(stock.isqty) as isqty, sum(cinfo.weight+cinfo.valamt+cinfo.cumsmt+cinfo.delivery) as totalcharges
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join cntnum on cntnum.trno=head.trno
                      left join client as wh on wh.client= head.wh
                      left join client as whto on whto.client=head.whto
                      left join client as sh on sh.clientid=head.shipperid
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                      left join cntnuminfo as cinfo on cinfo.trno=head.trno
                      where head.doc in ('sj') and date(head.dateid)
                            between  '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . "
                      group by head.docno,head.clientname,head.dateid,head.rem,
                            wh.clientname,whto.clientname,sh.clientname,head.tax,head.vattype,head.terms,
                            proj.name,info.itemdesc,info.unit,info.weight,stock.isamt,stock.isqty,cinfo.trnxtype
                      union all
                      select head.docno,head.clientname as supplier,head.dateid,head.rem,
                            wh.clientname as whname,whto.clientname as whtoname,
                            sh.clientname as shipper,head.tax,head.vattype,head.terms,
                            proj.name as project,info.itemdesc as itemname,info.unit as uom,
                            FORMAT(info.weight,2) as weight,FORMAT(stock.isamt,2) as isamt,cinfo.trnxtype,
                            ROUND(stock.isqty)  as isqty, sum(cinfo.weight+cinfo.valamt+cinfo.cumsmt+cinfo.delivery) as totalcharges
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                      left join cntnum on cntnum.trno=head.trno
                      left join client as wh on wh.clientid = head.whid
                      left join client as whto on whto.client=head.whto
                      left join client as sh on sh.clientid=head.shipperid
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                      left join hcntnuminfo as cinfo on cinfo.trno=head.trno
                      where head.doc in ('sj') and date(head.dateid)
                            between  '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
                      group by head.docno,head.clientname,head.dateid,head.rem,
                            wh.clientname,whto.clientname,sh.clientname,head.tax,head.vattype,head.terms,
                            proj.name,info.itemdesc,info.unit,info.weight,stock.isamt,stock.isqty,cinfo.trnxtype
                      order by docno " . $sorting;

            break;

          default:
            $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,head.terms
              " . $agentfield . "
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin1 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
              union all
              select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
              stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
              left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname,head.terms
              " . $agentfield . "
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid = head.whid
              left join client as dept on dept.clientid = head.deptid
              " . $leftjoinproject . "
              " . $agjoin2 . "
              " . $addjoin . "
              " . $collectorjoin . "
              where head.doc in ('sj','mj') and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
              order by docno $sorting";
            break;
        }

        break;
    }

    return $query;
  }

  public function SUMMIT_QUERY_POSTED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    $isqty = 'stock.iss';

    $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                sum(stock.ext) as ext,sum(stock.isqty) as isqty
                from glstock as stock
                left join glhead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as wh on wh.clientid = head.whid
                where head.doc='sj'
                and date(head.dateid) between '$start' and '$end' $filter 
                group by wh.clientname, wh.client, item.itemname,item.uom
                order by clientname,itemname $sorting";

    return $query;
  }

  public function SUMMIT_QUERY_UNPOSTED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    $isqty = 'stock.iss';


    $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
      sum(stock.ext) as ext,sum(stock.isqty) as isqty
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.client=head.client
      left join client as wh on wh.client = head.wh
      where head.doc='sj'
      and date(head.dateid) between '$start' and '$end' $filter 
      group by wh.clientname, wh.client, item.itemname,item.uom
      order by clientname,itemname $sorting";


    return $query;
  }

  public function SUMMIT_QUERY_ALL($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    $isqty = 'stock.iss';

    $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                sum(stock.ext) as ext,sum(stock.isqty) as isqty
                from lastock as stock
                left join lahead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as wh on wh.client = head.wh
                where head.doc='sj'
                and date(head.dateid) between '$start' and '$end' $filter 
                group by wh.clientname, wh.client, item.itemname,item.uom
                UNION ALL
                select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                sum(stock.ext) as ext,sum(stock.isqty) as isqty
                from glstock as stock
                left join glhead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as wh on wh.clientid = head.whid
                where head.doc='sj'
                and date(head.dateid) between '$start' and '$end' $filter 
                group by wh.clientname, wh.client, item.itemname,item.uom
                order by clientname,itemname $sorting";


    return $query;
  }

  public function MAJESTY_displayheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];



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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else if ($reporttype == 1) {
      $reporttype = 'Detailed';
    } else {
      $reporttype = 'Summarized Per Item';
    }

    $str = '';

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

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Journal Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function MAJESTY_Layout_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    if ($companyid == 21) { //kinggeorge
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      if ($agent == '') $agentname = 'ALL';
    }

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayheader($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;


          $str .= $this->reporter->begintable($layoutsize);

          $str .= '<br/>';
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Amount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '190', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(date("m/d/Y", strtotime($data->expiry)), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();


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

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($companyid) {
      case 21: //kinggeorge
        $agent = $config['params']['dataparams']['agent'];
        $agentname = $config['params']['dataparams']['agentname'];
        if ($agent == '') $agentname = 'ALL';
        break;
      case 10: //afti
      case 12: //afti usd
        $dept   = $config['params']['dataparams']['ddeptname'];
        $proj   = $config['params']['dataparams']['project'];
        if ($dept != "") {
          $deptname = $config['params']['dataparams']['deptname'];
        } else {
          $deptname = "ALL";
        }
        if ($proj != "") {
          $projname = $config['params']['dataparams']['projectname'];
        } else {
          $projname = "ALL";
        }
        break;
      case 17: //unihome
      case 39: //CBBSI
        $proj  = $config['params']['dataparams']['projectname'];
        if ($proj == "") {
          $projname = "ALL";
        }
        break;
    }

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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else if ($reporttype == 1) {
      $reporttype = 'Detailed';
    } else {
      $reporttype = 'Summarized Per Item';
    }

    $str = '';
    $layoutsize = '1000';

    if ($companyid == 49 && $reporttype == 1) { //hotmix
      $layoutsize = '1100';
    }
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

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Journal Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      if ($companyid == 32) { //3m
        $salestype = $config['params']['dataparams']['salestype'];
        if ($salestype == '') $salestype = 'ALL';
        $str .= $this->reporter->col('Sales Type: ' . $salestype, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->endrow();
      if ($companyid == 17) { //unihome
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Project : ' . $proj, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        if ($companyid == 21 && $reporttype != 'Detailed') { //kinggeorge
          $str .= $this->reporter->col('Agent: ' . $agentname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
          if ($companyid == 32) { //3m
            $brgy = $config['params']['dataparams']['brgy'];
            $area = $config['params']['dataparams']['area'];
            $region = $config['params']['dataparams']['region'];
            if ($brgy == '') $brgy = 'ALL';
            if ($area == '') $area =  'ALL';
            if ($region == '') $region = 'ALL';
            $str .= $this->reporter->col('Barangay: ' . $brgy, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Area: ' . $area, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Region: ' . $region, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          }
        }
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }
    }
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    if ($companyid == 21) { //kinggeoorge
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      if ($agent == '') $agentname = 'ALL';
    }

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;

          if ($companyid == 32) { //3m
            $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LTR');
          } else {
            $str .= $this->reporter->begintable($layoutsize);
          }
          $str .= '<br/>';

          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            // $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            switch ($companyid) {
              case 21: //kinggeorge
                $str .= $this->reporter->col('Agent: ' . $agentname, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                break;
              case 36: //rozlab
                $str .= $this->reporter->col('Terms: ' . $data->terms, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

                break;
            }
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '190', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);

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

    return $str;
  }

  public function Seastar_Layout_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        // if ($docno != "" && $docno != $data->docno) {
        //   $str .= $this->reporter->begintable($layoutsize);
        //   $str .= $this->reporter->startrow();
        //   $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
        //   $str .= $this->reporter->endrow();
        //   $str .= $this->reporter->endtable();
        // }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= '<br/>';


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('From: ' . $data->whname, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('To: ' . $data->whtoname, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Project: ' . $data->project, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Shipper: ' . $data->shipper, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Charges: ' . $data->totalcharges, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Notes: ' . $data->rem, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Vat Type: ' . $data->tax . ' - ' . $data->vattype, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Trnx Type: ' . $data->trnxtype, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Terms: ' . $data->terms, 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Weight', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Declared Value', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->weight, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->isamt, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        // if ($docno == $data->docno) {
        //   $total += $data->ext;
        // }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);

          $page = $page + $count;
        } //end if

        // if ($i == (count((array)$result) - 1)) {
        //   $str .= $this->reporter->begintable($layoutsize);
        //   $str .= $this->reporter->startrow();
        //   $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
        //   $str .= $this->reporter->endrow();
        //   $str .= $this->reporter->endtable();
        // }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function report3mDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totmarkup = 0;
    $totbal = 0;
    $gtotal = 0;
    $gtotpartial = 0;
    $gtotbal = 0;
    $gtotmarkup = 0;
    $totdisc = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LRB');
          $str .= $this->reporter->startrow();
          if ($reporttype == '1') {
            $str .= $this->reporter->col('Total Discount: ', '340', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totdisc, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total: ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(($totbal == 0 ? 'Paid: ' : 'Partial: ') . number_format($totpartial, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Balance: ' . number_format($totbal, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('Total Discount: ', '370', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totdisc, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Total: ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totmarkup, 2), '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($totbal == 0 ? 'Paid: ' : 'Partial: ') . number_format($totpartial, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Balance: ' . number_format($totbal, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          }
          $gtotpartial += $totpartial;
          $gtotmarkup += $totmarkup;
          $gtotbal += $totbal;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;
          $totmarkup = 0;
          $totdisc = 0;

          $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LTR');
          $str .= '<br/>';
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Terms: ' . $data->terms, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Barangay: ' . $data->brgy, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Area: ' . $data->area, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LR');
          $str .= $this->reporter->startrow();
          if ($reporttype == '1') {
            $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Price', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Total Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Notes', '320', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('Barcode', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Price', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Discount', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Total Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Cost', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Markup', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }


        $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LR');
        $str .= $this->reporter->startrow();
        if ($reporttype == '1') {
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '190', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->rem, '320', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->barcode, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '190', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->disc, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->cost, 2), '60', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->markup, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();


        $netamt = 0;
        $disc = 0;
        if ($docno == $data->docno) {
          $total += $data->ext;
          if ($companyid == 32) { //3m
            $gtotal += $data->ext;
            $totbal = $data->bal;
            $totpartial = $total - $totbal;
            $totmarkup += $data->markup;
            if ($data->disc != "") {
              $netamt = $this->othersClass->Discount($data->isamt, $data->disc);
              $disc = $data->isamt - $netamt;
            }

            $totdisc += $disc;
          }
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($reporttype == '1') {
            $str .= $this->reporter->col('Total Discount: ', '340', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totdisc, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total: ', '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(($totbal == 0 ? 'Paid: ' : 'Partial: ') . number_format($totpartial, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Balance: ' . number_format($totbal, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('Total Discount: ', '370', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totdisc, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Total: ', '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totmarkup, 2), '140', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($totbal == 0 ? 'Paid: ' : 'Partial: ') . number_format($totpartial, 2), '120', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Balance: ' . number_format($totbal, 2), '120', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($reporttype == '1') {
            $str .= $this->reporter->col('Grand Total: ', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($gtotal, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format(($gtotpartial + $totpartial), 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format(($gtotbal + $totbal), 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('Grand Total: ', '530', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($gtotal, 2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($gtotmarkup, 2), '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format(($gtotpartial + $totpartial), 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format(($gtotbal + $totbal), 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          }
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
    //oks
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
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

        switch ($companyid) {
          case 32: //3m
            $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->terms, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            break;
          case 19: //housegem
            $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '280', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->terms, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->agentname, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            break;
          case 21: //kinggeorge
            $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->agentname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->terms, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            break;
          case 36: //rozlab
          case 27: //nte
            $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->code, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            break;
          default:
            $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            break;
        }

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 32) { //3m
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    } else {
      if ($companyid == 36 || $companyid == 27) { //rozlab, nte
        $colsize = 225;
      } else if ($companyid == 21) { //kinggeorge
        $colsize = 395;
      } else {
        $colsize = 125;
      }
      $str .= $this->reporter->col('', $colsize, null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_MCPC($config)
  {
    //oks
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $datetime = date("m-d-Y H:i:s", strtotime($this->othersClass->getCurrentTimeStamp()));
    $header = $this->coreFunctions->opentable("select name,address from center where code = '$center'");
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($companyid) {
      case 21: //kinggeorge
        $agent = $config['params']['dataparams']['agent'];
        $agentname = $config['params']['dataparams']['agentname'];
        if ($agent == '') $agentname = 'ALL';
        break;
      case 10: //afti
      case 12: //afti usd
        $dept   = $config['params']['dataparams']['ddeptname'];
        $proj   = $config['params']['dataparams']['project'];
        if ($dept != "") {
          $deptname = $config['params']['dataparams']['deptname'];
        } else {
          $deptname = "ALL";
        }
        if ($proj != "") {
          $projname = $config['params']['dataparams']['projectname'];
        } else {
          $projname = "ALL";
        }
        break;
      case 17: //unihome
      case 39: //CBBSI
        $proj  = $config['params']['dataparams']['projectname'];
        if ($proj == "") {
          $projname = "ALL";
        }
        break;
    }

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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else if ($reporttype == 1) {
      $reporttype = 'Detailed';
    } else {
      $reporttype = 'Summarized Per Item';
    }

    $str = '';

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "12";
    $border = "1px solid ";
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($username . ' - ' . $datetime . ' ' . strtoupper($header[0]->name), '1000', null, false, $border, '', 'C', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col(strtoupper($header[0]->name), '1000', null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($header[0]->address), '1000', null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Journal Report (' . $reporttype . ')', '1000', null, false, $border, '', 'C', $font, '16', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '1000', null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Users: ' . $user, '115', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '135', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '170', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '115', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportMCPCLayout_SUMMARIZED($config)
  {
    //oks
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 14;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_MCPC($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '115', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '225', null, false, $border, 'BL', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '135', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '110', null, false, $border, 'BL', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->paidstat, '115', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '200', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        if (count($result) == ($i + 1)) {
          $str .= $this->reporter->col('', '115', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '225', null, false, $border, 'BL', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL:', '135', null, false, $border, 'BL', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '110', null, false, $border, 'BL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '115', null, false, $border, 'BL', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_MCPC($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

      }
    }


    $str .= $this->reporter->endreport();

    return $str;
  }
  public function reportAquaDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    if ($companyid == 21) { //kinggeorge
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      if ($agent == '') $agentname = 'ALL';
    }

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;

          if ($companyid == 32) { //3m
            $str .= $this->reporter->begintable($layoutsize, null, false, '1px solid', 'LTR');
          } else {
            $str .= $this->reporter->begintable($layoutsize);
          }
          $str .= '<br/>';

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Meter No.', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Present Reading', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Previous Reading', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Consumption', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Rate', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Amount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty3, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty2, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);

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

    return $str;
  }

  public function reportAFTILayout_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->afti_tableheader($layoutsize, $config);


    $totalext = 0;
    $totalcharge = 0;
    $totalgrandtotalcol = 0;
    $totalbal = 0;



    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $charges = 0;
        $taxdef = $data->taxdef;
        if ($data->tax != 0) {
          $charges = $data->ext * .12;
        }
        if ($taxdef != 0) {
          $charges = $taxdef;
        }
        $grandtotalcol = $data->ext + $charges;
        $str .= $this->reporter->col(number_format($charges, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($grandtotalcol, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->remarks, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->delcharge, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->insurance, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->trackingno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->modeofdelivery, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->endorsedby, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->receiveby, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->receivedate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $totalcharge = $totalcharge + $charges;
        $totalgrandtotalcol = $totalgrandtotalcol + $grandtotalcol;


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->afti_tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }



    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcharge, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrandtotalcol, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARYPERITEM($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_SUMMARYPERITEM($config);
    $client = "";
    $total = 0;
    $i = 0;
    $totalext = 0;
    $totalqty = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($client != "" && $client != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($client == "" || $client != $data->clientname) {
          $client = $data->clientname;
          $totalqty = 0;
          $totalext = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize + 5, 'B', '', '', '8px');

          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEM', '425', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '425', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($client == $data->clientname) {
          $totalext += $data->ext;
          $totalqty += $data->iss;
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_SUMMARYPERITEM($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_SUMMARYPERITEM($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 21) { //kinggeorge
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      if ($agent == '') {
        $agentname = 'ALL';
      }
    }

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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else if ($reporttype == 1) {
      $reporttype = 'Detailed';
    } else {
      $reporttype = 'Summarized Per Item';
    }

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '800';
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

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Journal Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    if ($companyid == 21) { //kinggeorge
      $str .= $this->reporter->col('Agent: ' . $agentname, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 21: //kinggeorge
        $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AGENT', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TERMS', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('DATE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TERMS', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 19: //housegem
        $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '280', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TERMS', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SALES AGENT', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 37: //mega crystal
        $fontsize = 12;
        $str .= $this->reporter->col('DATE', '115', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('CODE', '115', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '225', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '135', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '110', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAID STATUS', '115', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '200', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 36: //rozlab
      case 27: //ntess
        $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        break;
      case 47: // kstar
        $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREATEBY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 49: // hotmix
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COMM', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('VAT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NET COMM', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NET SALES', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function afti_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DR NO', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TAX & CHARGES', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DELIVERY AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INSURANCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TRACKING #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MODE OF DELIVERY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ENDORSED BY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RECEIVED BY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RECEIVED DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');


    return $str;
  }

  public function report_Kstar_Layout_SUMMARIZED($config)
  {
    //oks
    $result = $this->reportDefault($config);
    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
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

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, 'min-width:125px;max-width:125px;word-wrap:break-word;');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_HOTMIX_Layout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
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
    $totalcommamt = 0;
    $totalcommvat = 0;
    $totalnetcomm = 0;
    $totalnetsales = 0;
    $netsales = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $commamt = $data->commamt == 0 ? '-' : number_format($data->commamt, 2);
        $commvat = $data->commvat == 0 ? '-' : number_format($data->commvat, 2);
        $netcomm = $data->netcomm == 0 ? '-' : number_format($data->netcomm, 2);

        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // DATE, CUSTOMER NAME, DOCUMENT NO., COMM, VAT, NET COMM, NET SALES, AMOUNT, STATUS
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($commamt, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($commvat, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($netcomm, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->total == 0 ? '-' : number_format($data->total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ext == 0 ? '-' : number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $totalcommamt += $data->commamt;
        $totalcommvat += $data->commvat;
        $totalnetcomm += $data->netcomm;
        $totalnetsales += $data->total;
        $totalext += $data->ext;

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) {
            $str .= $this->header_DEFAULT($config);
          }
          $str .= $this->tableheader($layoutsize, $config);
          $page += $count;
        } //end if
      }
      // $totalcommamt += $commamt;
      // $totalcommvat += $commvat;
      // $totalnetcomm += $netcomm;
      // $totalnetsales += $netsales;
      // $totalext += $data->ext;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalcommamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcommvat, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetcomm, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetsales, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportHOTMIXDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;
    $i = 0;
    $totpartial = 0;
    $totbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totpartial = 0;
          $totbal = 0;
          $amt = 0;
          $amt = $data->ext;



          $tot  = $this->coreFunctions->datareader("select sum(ext) as value from (select sum(stock.ext) as ext 
          from lastock as stock 
          where stock.trno =  $data->trno 
          union all
          select sum(stock.ext) as ext 
          from glstock as stock 
          where stock.trno =  $data->trno ) as k");
          //net sales
          $amt = $tot - $data->netcomm;


          $str .= $this->reporter->begintable($layoutsize);
          $str .= '<br/>';
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Comm: ' . number_format($data->commamt, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Net Comm: ' . number_format($data->netcomm, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Agent: ' . $data->agentname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Vat: ' . number_format($data->commvat, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Net Sales: ' . number_format($amt, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Project: ' . $data->projname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PO#: ' . $data->yourref, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Ourref: ' . $data->ourref, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }

        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);

          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $i++;
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }


  // Transpower Summarized
  public function header_TRANSPOWER($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];


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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else if ($reporttype == 1) {
      $reporttype = 'Detailed';
    } else {
      $reporttype = 'Summarized Per Item';
    }

    $str = '';
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

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Journal Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    
    $str .= $this->reporter->endrow();
    
    $str .= $this->reporter->startrow();
    
      
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
  

    $str .= $this->reporter->endtable();

    
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CE NO', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    

    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    return $str;
  }

  
  public function report_TRANSPOWER_Layout_SUMMARIZED($config)
  {
    //oks
    $companyid = $config['params']['companyid'];

    $result = $this->reportTranspower_Summarized($config);
    // var_dump($result);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_TRANSPOWER($config);




    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
            $ceno = '';
            $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            
            $ceno  = $this->coreFunctions->datareader(
              "select group_concat(docno) as value from cntnum where trno in (select trno from gldetail where refx=".$data->trno.") and doc='CR'");
            
            $str .= $this->reporter->col($ceno, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_TRANSPOWER($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
  

    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class                                                            