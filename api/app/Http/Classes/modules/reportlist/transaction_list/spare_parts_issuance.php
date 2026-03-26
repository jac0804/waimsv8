<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\SBCPDF;

class spare_parts_issuance
{
  public $modulename = 'Spare Parts Issuance';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'reportusers', 'approved'];
    $col1 = $this->fieldClass->create($fields);

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

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    /**
     * This file contains the logic for handling the spare parts issuance transaction list module.
     * The input name is set to an alias.
     */

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname,'' as userid,'' as username, '' as agent, '' as agentname, '' as dagentname, 0 as agentid, '' as approved,'' as project,'' as projectname,'' as projectid, '0' as posttype,'0' as reporttype,'ASC' as sorting,'' as dclientname,'' as reportusers,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '0' as clientid";

    return $this->coreFunctions->opentable($paramstr);
  }

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
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      default: // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    /**
     * Set the memory limit to unlimited and the maximum execution time to unlimited.
     * This is done to ensure that the script can use as much memory and time as needed to complete its task.
     * Note: Setting the memory limit and execution time to unlimited can have potential security risks and should be used with caution.
     */

    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->default_QUERY_POSTED($config);
        break;
      case '1': // UNPOSTED
        $query = $this->default_QUERY_UNPOSTED($config);
        break;
      default: // ALL
        $query = $this->default_QUERY_ALL($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $clientid     = $config['params']['dataparams']['clientid'];

    $filter2 = '';
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
    if ($proj != "") {
      $filter .= " and proj.line = '$proj'";
    }

    $collectorjoin = '';
    $agentfield = '';
    $agentfield2 = '';
    $agjoin = '';
    $aggroupby = '';
    $addedfields = '';
    $addedfields2 = '';
    $addjoin = "";
    $filter1 .= "";

    $agentfield2 = ',agentname';
    $agentfield = ',ag.clientname as agentname';
    $agjoin = 'left join client as ag on ag.clientid=head.agentid';
    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $aggroupby = ',ag.clientname';
    $addedfields = " ,head.terms";
    $addedfields2 = " ,terms";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $isqty = 'stock.iss';

    switch ($reporttype) {
      case '0': //summary
        $query = "select status, docno, supplier, ext, clientname, dateid, wh, deptcode,deptname, paidstat, rem,code" . $agentfield2 . " " . $addedfields2 . " 
        from (select 'POSTED' as status,head.docno,
        head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        ifnull((select (case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem,
        date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code " . $agentfield . "
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
        where head.doc='CI'
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
        group by head.docno, head.clientname,
        wh.clientname, wh.client, head.dateid, dept.client, dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . ") as a
        order by docno " . $sorting;
        break;
      case '1': //detailed
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
        where head.doc='CI' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " " . $filter2 . "
        order by docno " . $sorting;
        break;
    }

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $branchname = '';
    $branchname   = $config['params']['dataparams']['centername'];
    $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    $proj    = $config['params']['dataparams']['projectid'];
    $agent = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['dagentname'];
    $clientid     = $config['params']['dataparams']['clientid'];

    $filter = '';
    $filter1 = '';
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
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($proj != "") {
      $filter .= " and proj.line = '$proj'";
    }
    if ($agentname != "") {
      $filter2 = " and head.agent='" . $agent . "'";
    }

    $collectorjoin = '';
    $agentfield = '';
    $agjoin = '';
    $aggroupby = '';
    $filter2 = '';
    $addedfields = '';
    $filter1 .= '';
    $addjoin = '';

    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $agentfield = ',ag.clientname as agentname';
    $agjoin = 'left join client as ag on ag.client=head.agent';
    $aggroupby = ',ag.clientname';
    $addedfields = " ,head.terms";
    $isqty = 'stock.iss';
    $barcodeitemnamefield = ",item.barcode,item.itemname";

    switch ($reporttype) {
      case '0': //summary
        $query = "select 'UNPOSTED' as status ,head.yourref,
        head.docno,head.clientname as supplier,
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
        where head.doc='CI' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
        group by head.docno,head.yourref,head.clientname,
        wh.clientname,head.dateid, wh.client, dept.client, dept.clientname,head.trno, head.rem,client.client" . $aggroupby . " " . $addedfields . "
        order by head.docno $sorting";
        break;
      case '1': //detailed
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
        where head.doc='CI' 
        and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $filter2 . "
        order by docno $sorting";
        break;
    }

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $branchname = '';
    $branchname   = $config['params']['dataparams']['centername'];
    $fcenter  = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    $proj    = $config['params']['dataparams']['projectid'];
    $clientid     = $config['params']['dataparams']['clientid'];

    $filter = '';
    $filter1 = '';

    if ($filterusername != '') {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($branchname != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($proj != "") {
      $filter .= " and proj.line = '$proj'";
    }

    $collectorjoin = '';
    $agentfield = '';
    $agjoin1 = '';
    $agjoin2 = '';
    $aggroupby = '';
    $agfilter1 = '';
    $agfilter2 = '';
    $addedfields = '';

    $leftjoinproject = ' left join projectmasterfile as proj on proj.line = head.projectid ';
    $agentfield = ',ag.clientname as agentname';
    $agjoin1 = 'left join client as ag on ag.client=head.agent';
    $agjoin2 = 'left join client as ag on ag.clientid=head.agentid';
    $aggroupby = ',ag.clientname';
    $addedfields = " ,head.terms";
    $isqty = 'stock.iss';

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

    switch ($reporttype) {
      case '0': //summary
        $query = "select x.* from (
        select 'UNPOSTED' as status ,
        head.docno,head.clientname as supplier,
        sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
        left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code " . $agentfield . " " . $addedfields . "
        ,ifnull((select (case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $leftjoinproject . "
        " . $agjoin1 . "
        where head.doc='CI' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
        group by head.docno,head.yourref,head.clientname,
        wh.clientname,head.dateid, wh.client,dept.client,dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . "
        union all
        select 'POSTED' as status,head.docno,
        head.clientname as supplier,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
        left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,client.client as code " . $agentfield . "  " . $addedfields . "
        ,ifnull((select (case when ar.docno = '' or ar.bal > 0 then 'OPEN' else 'CLOSE' end) from arledger as ar where ar.trno = head.trno),'OPEN') as paidstat,head.rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=head.clientid 
        left join client as wh on wh.clientid = head.whid
        left join client as dept on dept.clientid = head.deptid
        " . $leftjoinproject . "
        " . $agjoin2 . "
        where head.doc='CI' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
        group by head.docno,head.yourref,head.clientname,
        wh.clientname,head.dateid, wh.client,dept.client, dept.clientname,head.trno,head.rem,client.client" . $aggroupby . "  " . $addedfields . "
        ) as x order by x.docno $sorting";
        break;
      default: //detailed
        $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
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
        where head.doc='CI' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter1 . "
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
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
        where head.doc='CI' and date(head.dateid) between '$start' and '$end' $filter $filter1 " . $agfilter2 . "
        order by docno $sorting";
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
    $modulename = 'Spare Parts Issuance';
    $str .= $this->reporter->col($modulename . ' (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

  public function tableHeader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $posttype = $config['params']['dataparams']['posttype'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT NO.', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    if ($posttype == 2) {
      $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $posttype = $config['params']['dataparams']['posttype'];

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
    $str .= $this->tableHeader($layoutsize, $config);

    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        if ($posttype == 2) {
          $str .= $this->reporter->col(number_format($data->ext, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }

        $totalext += $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) {
            $str .= $this->header_DEFAULT($config);
          }
          $str .= $this->tableHeader($layoutsize, $config);
          $page += $count;
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    if ($posttype == 2) {
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
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

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

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
}//end class