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

class sales_return_report
{
  public $modulename = 'Sales Return Report';
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

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved', 'dagentname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      case 17: //unihome
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        break;
      case 21: //kinggeorge
        array_push($fields, 'dwhname');
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
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
            ['label' => 'Summarized Per Item', 'value' => '3', 'color' => 'teal']
          ]
        );
        break;
      case 14: //majesty
        data_set($col2, 'radioreporttype.options', [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Transaction Listing', 'value' => '2', 'color' => 'orange']
        ]);
        break;
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,
                    '' as clientname,'' as userid,'' as username,'' as approved,'0' as posttype,'0' as reporttype, 'ASC' as sorting,
                    '' as dclientname,'' as reportusers,'' as agent, '' as agentname,'' as dagentname,
                    '" . $defaultcenter[0]['center'] . "' as center,
                    '" . $defaultcenter[0]['centername'] . "' as centername,
                    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                    '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,
                    '' as itemid, '' as barcode, '' as itemname, '' as wh, '' as dwhname, '' as whid, '' as whname,
                    '0' as clientid ";

    // switch ($companyid) {
    //   case 10: //afti
    //   case 12: //afti usd
    //     $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname ";
    //     break;
    //   case 21: //kinggeorge
    //     $paramstr .= ", '' as itemid, '' as barcode, '' as itemname, '' as wh, '' as dwhname, '' as whid, '' as whname";
    //     break;
    //   case 17: //unihome
    //     $paramstr .= " ,'' as project, '' as projectid, '' as projectname";
    //     break;
    // }
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
    $companyid = $config['params']['companyid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        if ($companyid == 47) { //kitchenstar
          $result = $this->report_Kstar_Layout_SUMMARIZED($config);
        } else {
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
        }

        break;
      case '1': // DETAILED
        if ($companyid == 40) { //cdo
          $result = $this->cdo_reportDefaultLayout_DETAILED($config);
        } else {
          $result = $this->reportDefaultLayout_DETAILED($config);
        }

        break;
      case '2': // TRASACTION
        $result = $this->reportDefaultLayout_TRANSACTION($config);
        break;
      case '3': //summit - Summarized Per Item
        $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $query = $this->default_QUERY_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
      case '2': // Transaction list
        $query = $this->default_QUERY_TRANSACTION_LIST($config);
        break;
      case '3': //summit - Summarized Per Item
        $query = $this->SUMMIT_QUERY($config);
        break;
    }

    // return $query
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $agent     = $config['params']['dataparams']['agent'];
    $wh = "";
    if ($companyid == 21) { //kinggeorge
      $wh = $config['params']['dataparams']['wh'];
    }

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";;
    }

    $leftjoinproject = '';
    if ($companyid == 17) { //unihome
      $proj    = $config['params']['dataparams']['projectid'];
      if ($proj != "") {
        $filter .= " and proj.line = '$proj'";
      }
      $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter3 .= " and stock.projectid = $project";
      }
      if ($deptid != "") {
        $filter3 .= " and head.deptid = $dept";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter3 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }
    $ext = "(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)))";
    if ($companyid == 21) { //kinggeorge
      $ext = "stock.ext";
    }

    $addfield = '';
    if ($companyid == 32) { //3m
      $addfield = ",client.brgy, client.area";
    }
    if ($companyid == 40) { //cdo
      $addfield = ",item.partno";
    }

    switch ($posttype) {
      case '0': // POSTED
        $filter2 = '';
        if ($wh != "") {
          $whid = $config['params']['dataparams']['whid'];
          $filter2 .= " and head.whid = '$whid' ";
        }
        if ($agent != "") {
          $agentid     = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
        }

        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        head.dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as dept on dept.clientid = head.deptid
        left join client as a on a.clientid=head.agentid
        left join client as wh on wh.clientid=head.whid
        left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
        " . $addjoin . "
        " . $leftjoinproject . "
        where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter2
        order by docno $sorting";
        break;
      case '1': // UNPOSTED
        if ($agent != "") {
          $filter .= " and head.agent = '$agent' ";
        }
        if ($wh != "") {
          $filter .= " and wh.client = '$wh' ";
        }
        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        head.dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client on client.client=head.client
        left join client as dept on dept.clientid = head.deptid
        left join client as a on a.client=head.agent
        left join client as wh on wh.client=head.wh
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        " . $addjoin . "
        " . $leftjoinproject . "
        where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 order by docno $sorting";
        break;
      case '2':
        $filter1 = '';
        $filter2 = '';
        if ($wh != "") {
          $whid = $config['params']['dataparams']['whid'];
          $filter2 .= " and head.whid = '$whid' ";
          $filter1 .= " and wh.client = '$wh' ";
        }
        if ($agent != "") {
          $agentid     = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
          $filter1 .= " and head.agent = '$agent' ";
        }
        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
              client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
              from glstock as stock
              left join glhead as head on head.trno=stock.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as a on a.clientid=head.agentid
              left join client as wh on wh.clientid=head.whid
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              " . $addjoin . "
              " . $leftjoinproject . "
              where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter2
              union all
              select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
              client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
              from lastock as stock
              left join lahead as head on head.trno=stock.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as a on a.client=head.agent
              left join client as wh on wh.client=head.wh
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              " . $addjoin . "
              " . $leftjoinproject . "
              where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter1
              order by docno $sorting";
        break;
    }

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $agent     = $config['params']['dataparams']['agent'];

    $wh = "";
    if ($companyid == 21) { //kinggeorge
      $wh = $config['params']['dataparams']['wh'];
    }

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    if ($companyid != 21) { //not kinggeorge
      $fcenter    = $config['params']['dataparams']['center'];
      if ($fcenter != "") {
        $filter .= " and cntnum.center = '$fcenter'";
      }
    }
    $leftjoinproject = '';
    if ($companyid == 17) { //unihome
      $proj    = $config['params']['dataparams']['projectid'];
      if ($proj != "") {
        $filter .= " and proj.line = '$proj'";
      }
      $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectcode = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['deptid'];
      $projectid = $config['params']['dataparams']['projectid'];
      $deptid = $config['params']['dataparams']['deptid'];
      // if ($deptid == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['deptid'];
      // }
      if ($projectcode != "") {
        $filter3 .= " and stock.projectid = $projectid";
      }
      if ($deptid != "") {
        $filter3 .= " and head.deptid = $deptid";
      }
    } else {
      $filter3 .= "";
    }

    $ext = "sum(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)))";

    if ($companyid == 21) { //kinggeorge
      $ext = "sum(stock.ext)";
    }

    $addfield = '';
    if ($companyid == 32) { //3m
      $addfield = ",client.brgy, client.area";
    }

    switch ($posttype) {
      case '0': // POSTED
        $filter2 = '';
        if ($wh != "") {
          $whid = $config['params']['dataparams']['whid'];
          $filter2 .= " and head.whid = '$whid' ";
        }
        if ($agent != "") {
          $agentid = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
        }

        $query = "select 'POSTED' as status,head.docno, head.ourref, head.yourref,
        head.clientname as supplier," . $ext . " as ext, wh.clientname, head.createby, client.client, ag.clientname as agentname,
        left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname " . $addfield . "
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid 
        left join client as wh on wh.clientid = head.whid
        left join client as ag on ag.clientid=head.agentid
        left join client as dept on dept.clientid = head.deptid
        left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
        $leftjoinproject
        where head.doc='cm'
        and head.dateid between '$start' and '$end' $filter $filter2 $filter3
        group by head.docno,head.clientname, wh.clientname, head.createby, head.dateid, head.ourref, head.yourref, ag.clientname, client.client,
                 dept.client, dept.clientname " . $addfield . "
        order by docno $sorting";
        break;
      case '1': // UNPOSTED
        if ($agent != "") {
          $filter .= " and head.agent = '$agent' ";
        }
        if ($wh != "") {
          $filter .= " and wh.client = '$wh' ";
        }

        $query = "select 'UNPOSTED' as status ,head.yourref, head.ourref,
        head.docno,head.clientname as supplier, client.client,
        " . $ext . " as ext, wh.clientname,head.createby, ag.clientname as agentname,
        left(head.dateid,10) as dateid,dept.client as deptcode, dept.clientname as deptname " . $addfield . "
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        left join client as ag on ag.client=head.agent
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        $leftjoinproject
        where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter3
        group by head.docno, head.yourref,head.clientname, wh.clientname,  head.ourref, ag.clientname, client.client,
                 head.createby, head.dateid, dept.client, dept.clientname " . $addfield . "
        order by docno $sorting";
        break;
      case '2':
        $filter1 = '';
        $filter2 = '';
        if ($wh != "") {
          $whid = $config['params']['dataparams']['whid'];
          $filter2 .= " and head.whid = '$whid' ";
          $filter1 .= " and wh.client = '$wh' ";
        }
        if ($agent != "") {
          $agentid     = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
          $filter1 .= " and head.agent = '$agent' ";
        }

        $query = "select * from (
        select 'UNPOSTED' as status ,
        head.docno, head.ourref, head.yourref, ag.clientname as agentname, head.clientname as supplier,
        " . $ext . " as ext, wh.clientname,head.createby, client.client,
        left(head.dateid,10) as dateid " . $addfield . "
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as ag on ag.client=head.agent
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        $leftjoinproject
        where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1 $filter3
        group by head.docno,head.clientname, wh.clientname, head.createby, head.dateid, head.ourref, head.yourref, ag.clientname, client.client " . $addfield . "
        union all
        select 'POSTED' as status,head.docno, head.ourref, head.yourref, ag.clientname as agentname,
        head.clientname as supplier," . $ext . " as ext, wh.clientname, head.createby, client.client,
        left(head.dateid,10) as dateid " . $addfield . "
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid 
        left join client as wh on wh.clientid = head.whid
        left join client as ag on ag.clientid=head.agentid
        left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
        $leftjoinproject
        where head.doc='cm'
        and head.dateid between '$start' and '$end' $filter $filter2 $filter3
        group by head.docno,head.clientname, wh.clientname, head.createby, head.dateid, head.ourref, head.yourref, ag.clientname, client.client " . $addfield . "
        ) as g order by g.docno $sorting";

        break;
    }
    return $query;
  }

  public function default_QUERY_TRANSACTION_LIST($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $agent     = $config['params']['dataparams']['agent'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectcode = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['dept'];
      $projectid = $config['params']['dataparams']['projectid'];
      $deptid = $config['params']['dataparams']['deptid'];
      if ($projectcode != "") {
        $filter3 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter3 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter3 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }
    $ext = "(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)))";
    if ($companyid == 21) { //kinggeorge
      $ext = "stock.ext";
    }

    $addfield = '';
    if ($companyid == 32) { //3m
      $addfield = ",client.brgy, client.area";
    }

    $sorting = ' docno, itemname';
    if ($companyid == 14) { //majesty
      if ($reporttype == '2') {
        $sorting = ' itemname, dateid';
      }
    }
    switch ($posttype) {
      case '0': // POSTED
        if ($agent != "") {
          $agentid     = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
        } else {
          $filter2 = '';
        }

        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        head.dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as dept on dept.clientid = head.deptid
        left join client as a on a.clientid=head.agentid
        left join client as wh on wh.clientid=head.whid
        left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
        " . $addjoin . "
        where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter2
        order by $sorting";
        break;
      case '1': // UNPOSTED
        if ($agent != "") {
          $filter .= " and head.agent = '$agent' ";
        }
        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        head.dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client on client.client=head.client
        left join client as dept on dept.clientid = head.deptid
        left join client as a on a.client=head.agent
        left join client as wh on wh.client=head.wh
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        " . $addjoin . "
        where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 order by docno $sorting";
        break;
      case '2':
        if ($agent != "") {
          $agentid     = $config['params']['dataparams']['agentid'];
          $filter2 .= " and head.agentid = '$agentid' ";
          $filter1 .= " and head.agent = '$agent' ";
        } else {
          $filter1 = '';
          $filter2 = '';
        }
        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
              client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid as dateid ,stock.ref, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
              from glstock as stock
              left join glhead as head on head.trno=stock.trno
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              left join client as a on a.clientid=head.agentid
              left join client as wh on wh.clientid=head.whid
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              " . $addjoin . "
              where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter2
              union all
              select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty as iss,stock.isamt,stock.disc," . $ext . " as ext,
              client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid as dateid,stock.ref, a.clientname as agentname,head.yourref,head.ourref,wh.client as whclient,wh.clientname as whclientname, head.rem as hrem " . $addfield . "
              from lastock as stock
              left join lahead as head on head.trno=stock.trno
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as a on a.client=head.agent
              left join client as wh on wh.client=head.wh
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              " . $addjoin . "
              where head.doc='CM' and head.dateid between '$start' and '$end' $filter $filter3 $filter1
              order by docno $sorting";
        break;
    }

    return $query;
  }

  public function SUMMIT_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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


    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
          sum(stock.ext) as ext
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='CM'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting
          ";
        break;

      case 1: // unposted
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                    sum(stock.ext) as ext
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='CM'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    order by clientname,itemname $sorting";
        break;

      default: // all
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                    sum(stock.ext) as ext
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='CM'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    UNION ALL
                    select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.iss) as iss,
                    sum(stock.ext) as ext
                    from glstock as stock
                    left join glhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    left join client as wh on wh.clientid = head.whid
                    where head.doc='CM'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    order by clientname,itemname $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 17) { //unihome
      $proj  = $config['params']['dataparams']['projectname'];
      if ($proj == "") {
        $proj = "ALL";
      }
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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

    switch ($reporttype) {
      case 0:
        $reporttype = 'Summarized';
        break;
      case 1:
        $reporttype = 'Detailed';
        break;
      default:
        $reporttype = 'Transaction Listing';
        break;
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
    $str .= $this->reporter->col('Sales Return Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
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
        break;

      case 17: //unihome
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project: ' . $proj, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      default:
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
        break;
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_SUMMARYPERITEM($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
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
    } else {
      $reporttype = 'Detailed';
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
    $str .= $this->reporter->col('Sales Return Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_transaction_list($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
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

    switch ($reporttype) {
      case 0:
        $reporttype = 'Summarized';
        break;
      case 1:
        $reporttype = 'Detailed';
        break;
      default:
        $reporttype = 'Transaction Listing';
        break;
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
    $str .= $this->reporter->col('Sales Return Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

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
    $str .= $this->reporter->col('', null, '15', false, $border, '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Doc#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Description', '230', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', ''); #
    $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Price', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Warehouse', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function dateformat($date)
  {
    return (new DateTime($date))->format('n/j/y');
  }
  public function reportDefaultLayout_TRANSACTION($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    if ($companyid == 21) { //kinggeorge
      $itemid = $config['params']['dataparams']['itemid'];
      $itemname = $itemid == '' ? 'ALL' : $config['params']['dataparams']['itemname'];
    }

    $agent = '';
    $agentname    = $config['params']['dataparams']['agentname'];

    if ($agentname == '') {
      $agent = 'ALL';
    } else {
      $agent = $agentname;
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
    $str .= $this->header_transaction_list($config);
    $docno = "";
    $total = 0;
    $i = 0;


    foreach ($result as $key => $data) {
      $dateformat = $this->dateformat($data->dateid);
      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($dateformat, '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->itemname, '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->iss, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->isamt, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->whclient, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->rem, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      $total += $data->ext;
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total:' . number_format($total, 2), '800', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function reportDefaultLayout_DETAILED($config)
  {

    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    if ($companyid == 21) { //kinggeorge
      $itemid = $config['params']['dataparams']['itemid'];
      $itemname = $itemid == '' ? 'ALL' : $config['params']['dataparams']['itemname'];
    }

    $agent = '';
    $agentname    = $config['params']['dataparams']['agentname'];

    if ($agentname == '') {
      $agent = 'ALL';
    } else {
      $agent = $agentname;
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

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          if ($companyid == 21) { //kinggeorge
            $str .= $this->reporter->begintable('1000');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '850', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total: ' . number_format($total, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          } else {
            $str .= $this->reporter->begintable('600');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable('1000');
          $str .= '<br/>';
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();

              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('Customer: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Agent: ' . $agent, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();
              break;
            case 21: //kinggeorge
              break;
            default:
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Notes :' . $data->hrem, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();

              $str .= $this->reporter->startrow();
              if ($companyid == 32) { //3m
                $str .= $this->reporter->col('Customer: ' . $data->supplier . ' - ' . $data->brgy . ', ' . $data->area, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              } else {
                $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              }
              $str .= $this->reporter->col('Agent: ' . $agent, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();
              break;
          }

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          if ($companyid == 21) { //kinggeorge
            $str .= $this->reporter->col('Item Description', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Quantity', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('UOM', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Price', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Discount', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total Amount', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->begintable('1000');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno . ' - ' . $data->dateid . ' - ' . $data->supplier . ' - Agent: ' . $data->agentname, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          } else {
            switch ($companyid) {
              case 10: //afti
              case 12: //afti usd
                $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                break;
              case 19: //housegem
                $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Your Ref', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                break;
              default:
                $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                if ($companyid == 40) { //cdo
                  $str .= $this->reporter->col('Part Number', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                }
                $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                break;
            }

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        }
        $dateformat = $this->dateformat($data->dateid);
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        if ($companyid == 21) { //kinggeorge
          $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->iss, 2), '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        } else {
          switch ($companyid) {
            case 19: //housegem
              $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->whclientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              break;
            default:
              $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              if ($companyid == 40) { //cdo
                $str .= $this->reporter->col($data->partno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              }
              $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
              break;
          }
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();


        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 21: //kinggeorge
              $str .= $this->reporter->col('', '880', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('Total: ' . number_format($total, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              break;
            case 19: //housegem
              $str .= $this->reporter->col('Total: ', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Total: ', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
              break;
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
    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
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
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 39) { //cbbsi
          $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->supplier, '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->yourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(strtoupper($data->supplier), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          if ($companyid == 32) { //3m
            $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        }
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

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
    if ($companyid == 39) { //cbbsi
      $str .= $this->reporter->col('', '720', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL: ', '80', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      if ($companyid == 32) { //3m
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARYPERITEM($config)
  {
    $result = $this->reportDefault($config);
    $client     = $config['params']['dataparams']['client'];

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
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_SUMMARYPERITEM($config);

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

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 39: // cbbsi
        $str .= $this->reporter->col('DATE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WH', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '165', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AGENT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OURREF', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('YOURREF', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 32: // 3M
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 47: // kitchenstar
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREATEBY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function report_Kstar_Layout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
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
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(strtoupper($data->supplier), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

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

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function cdo_reportDefaultLayout_DETAILED($config)
  {

    $result = $this->reportDefault($config);
    $agent = '';
    $agentname    = $config['params']['dataparams']['agentname'];

    if ($agentname == '') {
      $agent = 'ALL';
    } else {
      $agent = $agentname;
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

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable('600');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable('1000');
          $str .= '<br/>';
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes :' . $data->hrem, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Agent: ' . $agent, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->col('Part Number', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $dateformat = $this->dateformat($data->dateid);
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->partno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();


        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class