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

class analyze_customer_sales_monthly
{
  public $modulename = 'Analyze Customer Sales Monthly';
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $fields = ['radioprint', 'dclientname', 'dcentername'];
    } else {
      $fields = ['radioprint', 'dclientname', 'dcentername', 'categoryname', 'subcatname'];
    }
    switch ($companyid) {
      case 1: //vitaline
        array_push($fields, 'brand', 'dagentname');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 10: // afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.readonly', true);
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        break;
      case 15: //nathina
        array_push($fields, 'groupid');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
        data_set($col1, 'groupid.action', 'lookupclientgroupledger');
        data_set($col1, 'groupid.class', 'csgroup');
        data_set($col1, 'groupid.readonly', false);
        break;
      case 24: //goodfound
        array_push($fields, 'radioreportanalyzedby');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreportanalyzedby.options', [
          ['label' => 'Sold Value', 'value' => 'ext', 'color' => 'orange'],
          ['label' => 'Unit Qty', 'value' => 'iss', 'color' => 'orange'],
        ]);
        break;
      case 36: //rozlab
      case 21: //kg
        array_push($fields, 'dagentname');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 59: //roosevelt
        array_push($fields, 'radiotypeofreportdrsi');
        $col1 = $this->fieldClass->create($fields);
        break;

      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    if ($companyid == 34) { //evergreen
      data_set($col1, 'dclientname.label', 'Payor');
    }
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    if ($companyid == 59) { //roosevelt
      data_set($col1, 'radiotypeofreportdrsi.options', [
        ['label' => 'Per Customer Only', 'value' => 'percust', 'color' => 'teal'],
        ['label' => 'All Customer', 'value' => 'allcust', 'color' => 'teal']
      ]);
    }

    switch ($companyid) {
      case 1: //vitaline
        $fields = ['year', 'radioreporttype', 'radioposttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.label', 'Report Format');
        break;
      case 36: //rozlab
        $fields = ['year', 'radiotypeofreportsales', 'radioposttype'];
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        $fields = ['year', 'radioposttype'];
        $col2 = $this->fieldClass->create($fields);
        break;
    }

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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 'default' as print,
                       '' as client,'0' as clientid,'' as clientname,'' as dclientname,
                       left(now(),4) as year,
                       '0' as posttype,'0' as reporttype,
                       '' as categoryname,'' as category,
                       '' as subcatname, '' as subcat, 
                       'ext' as analyzedby,
                       '" . $defaultcenter[0]['center'] . "' as center,
                       '" . $defaultcenter[0]['centername'] . "' as centername,
                       '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                       
                       '' as project, '' as projectid, '' as projectname, 
                       '' as ddeptname, '' as dept, '' as deptname,'' as industry,
                       
                       '' as brand, '' as brandid,
                       '' as dagentname,'' as agent,'0' as agentid,'' as agentname,
                       
                       'report' as typeofreport,'percust' as typeofdrsi";

    // switch ($companyid) {
    // case 10: // afti
    // case 12: //afti usd
    //   $paramstr .= ", '' as project, '' as projectid, '' as projectname, 
    //                '' as ddeptname, '' as dept, '' as deptname,'' as industry";
    //   break;
    // case 1: //vitaline
    // case 36: //rozlab
    //   $paramstr .= ", '' as brand, '' as brandid,'' as dagentname,'' as agent,'' as agentid,'' as agentname";
    //   break;
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
    $reporttype = $config['params']['dataparams']['reporttype'];
    $rtype = $config['params']['dataparams']['typeofdrsi'];
    $companyid = $config['params']['companyid'];

    // $result = $this->reportDefaultLayout($config);

    // if ($companyid == 1) { //vitaline
    //   switch ($reporttype) {
    //     case 0:
    //       $result = $this->summarizedLayout($config);
    //       break;

    //     case 1:
    //       $result = $this->detailedLayout($config);
    //       break;
    //   }
    // }

    switch ($companyid) {
      case 1: //vitaline
        switch ($reporttype) {
          case 0:
            $result = $this->summarizedLayout($config);
            break;

          case 1:
            $result = $this->detailedLayout($config);
            break;
        }
        break;
      case 59: //roosevelt
        switch ($rtype) {
          case 'percust':
            $result = $this->roosevelt_percust_layout($config);
            break;
          case 'allcust':
            $result = $this->reportDefaultLayout($config); //existing
            break;
        }
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $rtype = $config['params']['dataparams']['typeofdrsi'];

    switch ($companyid) {
      case 36: //rozlab
        $query = $this->reportDefault_QRY($config);
        break;

      case 59: //roosevelt
        switch ($rtype) {
          case 'percust': //per customer
            switch ($posttype) {
              case '0': // POSTED
                $query = $this->roosevelt_reportDefault_POSTED($config);
                break;
              case '1': // UNPOSTED
                $query = $this->roosevelt_reportDefault_UNPOSTED($config);
                break;

              case '2': // all
                $query = $this->roosevelt_reportDefault_ALL($config);
                break;
            }
            break;
          case 'allcust': //all customer
            switch ($posttype) {
              case '0': // POSTED
                $query = $this->reportDefault_POSTED($config);
                break;
              case '1': // UNPOSTED
                $query = $this->reportDefault_UNPOSTED($config);
                break;

              case '2': // all
                $query = $this->reportDefault_ALL($config);
                break;
            }
            break;
        }

        break;

      default:
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->reportDefault_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->reportDefault_UNPOSTED($config);
            break;

          case '2': // all
            $query = $this->reportDefault_ALL($config);
            break;
        }
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_QRY($config)
  {
    // QUERY

    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $analby =  $config['params']['dataparams']['analyzedby']; // default ext
    $reporttype = $config['params']['dataparams']['reporttype'];
    $agent = $config['params']['dataparams']['agent'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $posttype   = $config['params']['dataparams']['posttype'];


    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }


    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }


    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    switch ($posttype) {
      case '0': // POSTED
        switch ($typeofreport) {
          case 'report':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                             sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                             sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                             sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                    from (select 'p' as tr, ifnull(client.clientname,'') as clientname,
                                  year(head.dateid) as yr,
                                  sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
                                  sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
                                  sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
                                  sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
                                  sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
                                  sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
                                  sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
                                  sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
                                  sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
                                  sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                                  sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                                  sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec  
                          from ((glhead as head 
                          left join glstock as stock on stock.trno=head.trno)
                          left join client on client.clientid=head.clientid) 
                          left join cntnum on cntnum.trno=head.trno
                          left join item on item.itemid=stock.itemid
                          left join itemcategory as cat on cat.line = item.category
                          left join itemsubcategory as subcat on subcat.line = item.subcat 
                          left join client as agent on agent.clientid=head.agentid
                          where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year
                          and stock.ext<>0 $filter and item.isofficesupplies=0
                          group by ifnull(client.clientname,''), year(head.dateid)) as x 
                    group by clientname, yr
                    order by clientname, yr";

            break;
          case 'lessreturn':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                             sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                             sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                             sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                    from (select 'p' as tr, ifnull(client.clientname,'') as clientname,
                                  year(head.dateid) as yr,
                                  sum(case when month(head.dateid)=1 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojan,
                                  sum(case when month(head.dateid)=2 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mofeb,
                                  sum(case when month(head.dateid)=3 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momar,
                                  sum(case when month(head.dateid)=4 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moapr,
                                  sum(case when month(head.dateid)=5 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momay,
                                  sum(case when month(head.dateid)=6 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojun,
                                  sum(case when month(head.dateid)=7 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojul,
                                  sum(case when month(head.dateid)=8 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moaug,
                                  sum(case when month(head.dateid)=9 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mosep,
                                  sum(case when month(head.dateid)=10 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mooct,
                                  sum(case when month(head.dateid)=11 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as monov,
                                  sum(case when month(head.dateid)=12 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as modec  
                          from ((glhead as head left join glstock as stock on stock.trno=head.trno)
                          left join client on client.clientid=head.clientid) 
                          left join cntnum on cntnum.trno=head.trno
                          left join item on item.itemid=stock.itemid
                          left join itemcategory as cat on cat.line = item.category
                          left join itemsubcategory as subcat on subcat.line = item.subcat 
                          left join client as agent on agent.clientid=head.agentid
                          where head.doc in ('sj','mj','sd','se','sf','cm') and year(head.dateid)=$year
                          and stock.ext<>0 $filter and item.isofficesupplies=0
                          group by ifnull(client.clientname,''),year(head.dateid)) as x 
                    group by clientname, yr
                    order by clientname, yr";
            break;
          case 'return':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                             sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                             sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                             sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                      from (select 'p' as tr, ifnull(client.clientname,'') as clientname, 
                                    year(head.dateid) as yr,
                                    sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
                                    sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
                                    sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
                                    sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
                                    sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
                                    sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
                                    sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
                                    sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
                                    sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
                                    sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                                    sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                                    sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec  
                            from ((glhead as head left join glstock as stock on stock.trno=head.trno)
                            left join client on client.clientid=head.clientid) 
                            left join cntnum on cntnum.trno=head.trno
                            left join item on item.itemid=stock.itemid
                            left join itemcategory as cat on cat.line = item.category
                            left join itemsubcategory as subcat on subcat.line = item.subcat 
                            left join client as agent on agent.clientid=head.agentid
                            where head.doc = 'CM' and year(head.dateid)=$year
                            and stock.ext<>0 $filter and item.isofficesupplies=0
                            group by ifnull(client.clientname,''),year(head.dateid)) as x 
                    group by clientname, yr
                    order by clientname, yr";
            break;
        }
        break;
      case  '1': // UNPOSTED
        switch ($typeofreport) {
          case 'report':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                             sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                             sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                             sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec 
                      from (select 'u' as tr, ifnull(client.clientname,'') as clientname, 
                                  year(head.dateid) as yr,
                                  sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
                                  sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
                                  sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
                                  sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
                                  sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
                                  sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
                                  sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
                                  sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
                                  sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
                                  sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                                  sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                                  sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
                            from ((lahead as head 
                            left join lastock as stock on stock.trno=head.trno)  
                            left join client on client.client=head.client) 
                            left join cntnum on cntnum.trno=head.trno
                            left join item on item.itemid=stock.itemid
                            left join itemcategory as cat on cat.line = item.category
                            left join itemsubcategory as subcat on subcat.line = item.subcat 
                            left join client as agent on agent.client=head.agent
                            where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year 
                                  and stock.ext <> 0 $filter and item.isofficesupplies=0
                            group by ifnull(client.clientname,''), year(head.dateid)) as x 
                            group by clientname, yr
                            order by clientname, yr";
            break;

          case 'lessreturn':
            $query = "select clientname,yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                            sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                            sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec 
                      from (select 'u' as tr, ifnull(client.clientname,'') as clientname, 
                                    year(head.dateid) as yr,
                                    sum(case when month(head.dateid)=1 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mojan,
                                    sum(case when month(head.dateid)=2 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mofeb,
                                    sum(case when month(head.dateid)=3 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as momar,
                                    sum(case when month(head.dateid)=4 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as moapr,
                                    sum(case when month(head.dateid)=5 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as momay,
                                    sum(case when month(head.dateid)=6 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mojun,
                                    sum(case when month(head.dateid)=7 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mojul,
                                    sum(case when month(head.dateid)=8 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as moaug,
                                    sum(case when month(head.dateid)=9 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mosep,
                                    sum(case when month(head.dateid)=10 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as mooct,
                                    sum(case when month(head.dateid)=11 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as monov,
                                    sum(case when month(head.dateid)=12 then (case when head.doc='sj' then stock.ext else (stock.ext*-1) end) else 0 end) as modec
                            from ((lahead as head 
                            left join lastock as stock on stock.trno=head.trno)  
                            left join client on client.client=head.client)
                            left join cntnum on cntnum.trno=head.trno
                            left join item on item.itemid=stock.itemid
                            left join itemcategory as cat on cat.line = item.category
                            left join itemsubcategory as subcat on subcat.line = item.subcat 
                            left join client as agent on agent.client=head.agent
                            where head.doc in ('sj','mj','sd','se','sf','cm') and year(head.dateid)=$year 
                                  and stock.ext <> 0 $filter and item.isofficesupplies=0
                            group by ifnull(client.clientname,''), year(head.dateid)) as x 
                group by clientname, yr
                order by clientname, yr";

          case 'return':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                            sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                            sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec 
                      from (select 'u' as tr, ifnull(client.clientname,'') as clientname, 
                                    year(head.dateid) as yr,
                                    sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
                                    sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
                                    sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
                                    sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
                                    sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
                                    sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
                                    sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
                                    sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
                                    sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
                                    sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                                    sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                                    sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
                            from ((lahead as head 
                            left join lastock as stock on stock.trno=head.trno)  
                            left join client on client.client=head.client) 
                            left join cntnum on cntnum.trno=head.trno
                            left join item on item.itemid=stock.itemid
                            left join itemcategory as cat on cat.line = item.category
                            left join itemsubcategory as subcat on subcat.line = item.subcat 
                            left join client as agent on agent.client=head.agent
                            where head.doc = 'CM' and year(head.dateid)=$year 
                                  and stock.ext <> 0 $filter and item.isofficesupplies=0
                            group by ifnull(client.clientname,''), year(head.dateid)) as x 
                      group by clientname, yr
                      order by clientname, yr";
            break;
        }
        //
        break;
      default:
        switch ($typeofreport) {
          case 'report':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                            sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                            sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                        from (select 'p' as tr, ifnull(client.clientname, '') as clientname,
                                      year(head.dateid) as yr,
                                      sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                                      sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                                      sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                                      sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                                      sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                                      sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                                      sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                                      sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                                      sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                                      sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                                      sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                                      sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                              from ((glhead as head 
                              left join glstock as stock on stock.trno = head.trno)
                              left join client on client.clientid = head.clientid) 
                              left join cntnum on cntnum.trno = head.trno
                              left join item on item.itemid = stock.itemid
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat 
                              left join client as agent on agent.clientid=head.agentid
                              where head.doc in ('sj','mj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                              and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)
                        union all
                        select 'u' as tr, ifnull(client.clientname, '') as clientname,year(head.dateid) as yr,
                        sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                        sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                        sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                        sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                        sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                        sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                        sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                        sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                        sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                        sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                        sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                        sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                        from ((lahead as head left join lastock as stock on stock.trno = head.trno)
                        left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
                        left join item on item.itemid = stock.itemid
                        left join itemcategory as cat on cat.line = item.category
                        left join itemsubcategory as subcat on subcat.line = item.subcat 
                        left join client as agent on agent.client=head.agent
                        where head.doc in ('sj','mj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                        and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)) as x
                        group by clientname, yr";

            break;

          case 'lessreturn':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                            sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                            sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                        from (select 'p' as tr, ifnull(client.clientname, '') as clientname,
                                      year(head.dateid) as yr,
                                      sum(case when month(head.dateid) = 1 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojan,
                                      sum(case when month(head.dateid) = 2 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mofeb,
                                      sum(case when month(head.dateid) = 3 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momar,
                                      sum(case when month(head.dateid) = 4 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moapr,
                                      sum(case when month(head.dateid) = 5 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momay,
                                      sum(case when month(head.dateid) = 6 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojun,
                                      sum(case when month(head.dateid) = 7 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojul,
                                      sum(case when month(head.dateid) = 8 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moaug,
                                      sum(case when month(head.dateid) = 9 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mosep,
                                      sum(case when month(head.dateid) = 10 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mooct,
                                      sum(case when month(head.dateid) = 11 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as monov,
                                      sum(case when month(head.dateid) = 12 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as modec
                              from ((glhead as head 
                              left join glstock as stock on stock.trno = head.trno)
                              left join client on client.clientid = head.clientid) 
                              left join cntnum on cntnum.trno = head.trno
                              left join item on item.itemid = stock.itemid
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat 
                              left join client as agent on agent.clientid=head.agentid
                              where head.doc in ('sj','mj', 'sd', 'se', 'sf','cm') and year(head.dateid) = $year
                              and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)
                        union all
                        select 'u' as tr, ifnull(client.clientname, '') as clientname,year(head.dateid) as yr,
                        sum(case when month(head.dateid) = 1 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojan,
                        sum(case when month(head.dateid) = 2 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mofeb,
                        sum(case when month(head.dateid) = 3 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momar,
                        sum(case when month(head.dateid) = 4 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moapr,
                        sum(case when month(head.dateid) = 5 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as momay,
                        sum(case when month(head.dateid) = 6 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojun,
                        sum(case when month(head.dateid) = 7 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mojul,
                        sum(case when month(head.dateid) = 8 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as moaug,
                        sum(case when month(head.dateid) = 9 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mosep,
                        sum(case when month(head.dateid) = 10 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as mooct,
                        sum(case when month(head.dateid) = 11 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as monov,
                        sum(case when month(head.dateid) = 12 then (case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) else 0 end) as modec
                        from ((lahead as head left join lastock as stock on stock.trno = head.trno)
                        left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
                        left join item on item.itemid = stock.itemid
                        left join itemcategory as cat on cat.line = item.category
                        left join itemsubcategory as subcat on subcat.line = item.subcat 
                        left join client as agent on agent.client=head.agent
                        where head.doc in ('sj','mj', 'sd', 'se', 'sf','cm') and year(head.dateid) = $year
                        and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)) as x
                        group by clientname, yr";

            break;

          case 'return':
            $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, 
                            sum(momar) as momar,sum(moapr) as moapr, sum(momay) as momay, 
                            sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                        from (select 'p' as tr, ifnull(client.clientname, '') as clientname,
                                      year(head.dateid) as yr,
                                      sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                                      sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                                      sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                                      sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                                      sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                                      sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                                      sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                                      sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                                      sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                                      sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                                      sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                                      sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                              from ((glhead as head 
                              left join glstock as stock on stock.trno = head.trno)
                              left join client on client.clientid = head.clientid) 
                              left join cntnum on cntnum.trno = head.trno
                              left join item on item.itemid = stock.itemid
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat 
                              left join client as agent on agent.clientid=head.agentid
                              where head.doc = 'CM' and year(head.dateid) = $year
                              and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)
                        union all
                        select 'u' as tr, ifnull(client.clientname, '') as clientname,year(head.dateid) as yr,
                        sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                        sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                        sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                        sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                        sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                        sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                        sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                        sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                        sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                        sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                        sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                        sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                        from ((lahead as head left join lastock as stock on stock.trno = head.trno)
                        left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
                        left join item on item.itemid = stock.itemid
                        left join itemcategory as cat on cat.line = item.category
                        left join itemsubcategory as subcat on subcat.line = item.subcat 
                        left join client as agent on agent.client=head.agent
                        where head.doc = 'CM' and year(head.dateid) = $year
                        and stock.ext <> 0 $filter and item.isofficesupplies = 0
                        group by ifnull(client.clientname, ''), year(head.dateid)) as x
                        group by clientname, yr";
            break;
        }
        break;
    }

    return $query;
  }

  public function reportDefault_POSTED($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $analby =  $config['params']['dataparams']['analyzedby']; // default ext
    $reporttype = $config['params']['dataparams']['reporttype'];


    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }


    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 1: //vitaline
        $brandid    = $config['params']['dataparams']['brandid'];
        $agent       = $config['params']['dataparams']['agent'];

        if ($brandid != "") {
          $filter .= " and item.brand='$brandid'";
        }

        if ($agent != "") {
          $filter .= " and agent.client='$agent'";
        }
        break;
      case 10: //afti
      case 12: //afti usd                                                     
        $prjid = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['ddeptname'];
        $project = $config['params']['dataparams']['projectid'];
        $indus = $config['params']['dataparams']['industry'];
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        if ($indus != "") {
          $filter1 .= " and client.industry = '$indus'";
        }
        break;
      case 36: //rozlab
      case 21: //kg
        $agent      = $config['params']['dataparams']['agent'];
        $agentid       = $config['params']['dataparams']['agentid'];
        if ($agent != "") {
          $filter .= " and agent.clientid='$agentid'";
        }
      default:
        $filter1 .= "";
        break;
    }

    $barcode = "";
    $item = "";

    if ($reporttype == 1) {
      $barcode = "item.itemname,";
      $item = "itemname,";
      $analby = "iss";
    }

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (detail.cr-detail.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (detail.cr-detail.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (detail.cr-detail.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (detail.cr-detail.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (detail.cr-detail.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (detail.cr-detail.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (detail.cr-detail.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (detail.cr-detail.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (detail.cr-detail.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (detail.cr-detail.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (detail.cr-detail.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (detail.cr-detail.db) else 0 end) as modec    
      from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
      left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc in ('sj','sd','se','sf','cp') and year(head.dateid)=" . $year . " and left(coa.alias,2) in ('SA', 'SD', 'SR')
      " . $filter . " " . $filter1 . "
      group by ifnull(client.clientname,''), year(head.dateid)) as x 
      group by clientname, yr
      order by clientname, yr";
    } else {
      $leftjoin = "";
      if ($companyid == 1 || $companyid == 36 || $companyid == 21) { //vitaline & rozlab 
        $leftjoin = " left join client as agent on agent.clientid=head.agentid ";
      }

      $query = "select clientname, $item yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
                            sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                            sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                    from (select 'p' as tr, ifnull(client.clientname,'') as clientname, $barcode year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1 then stock.$analby else 0 end) as mojan,
                    sum(case when month(head.dateid)=2 then stock.$analby else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3 then stock.$analby else 0 end) as momar,
                    sum(case when month(head.dateid)=4 then stock.$analby else 0 end) as moapr,
                    sum(case when month(head.dateid)=5 then stock.$analby else 0 end) as momay,
                    sum(case when month(head.dateid)=6 then stock.$analby else 0 end) as mojun,
                    sum(case when month(head.dateid)=7 then stock.$analby else 0 end) as mojul,
                    sum(case when month(head.dateid)=8 then stock.$analby else 0 end) as moaug,
                    sum(case when month(head.dateid)=9 then stock.$analby else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 then stock.$analby else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 then stock.$analby else 0 end) as monov,
                    sum(case when month(head.dateid)=12 then stock.$analby else 0 end) as modec  
                    from ((glhead as head left join glstock as stock on stock.trno=head.trno)
                    left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat $leftjoin
                    where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year
                    and stock.$analby<>0 $filter $filter1 and item.isofficesupplies=0
                    group by ifnull(client.clientname,''), $barcode year(head.dateid)) as x 
                    group by clientname, $item yr
                    order by clientname, yr";
    }

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $analby =  $config['params']['dataparams']['analyzedby']; // default ext
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 1: //vitaline
        $brandid    = $config['params']['dataparams']['brandid'];
        $agent       = $config['params']['dataparams']['agent'];

        if ($brandid != "") {
          $filter .= " and item.brand='$brandid'";
        }

        if ($agent != "") {
          $filter .= " and agent.client='$agent'";
        }
        break;
      case 10: //afti
      case 12: //afti usd
        $prjid = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['ddeptname'];
        $project = $config['params']['dataparams']['projectid'];
        $indus = $config['params']['dataparams']['industry'];
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        if ($indus != "") {
          $filter1 .= " and client.industry = '$indus'";
        }
        break;
      case 36: //rozlab
      case 21: //kg
        $agent      = $config['params']['dataparams']['agent'];
        $agentid       = $config['params']['dataparams']['agentid'];
        if ($agent != "") {
          $filter .= " and agent.clientid='$agentid'";
        }
        break;
      default:
        $filter1 .= "";
        break;
    }


    $barcode = "";
    $item = "";

    if ($reporttype == 1) {
      $barcode = "item.itemname,";
      $item = "itemname,";
      $analby = "iss";
    }

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec 
      from (select 'u' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (detail.cr-detail.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (detail.cr-detail.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (detail.cr-detail.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (detail.cr-detail.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (detail.cr-detail.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (detail.cr-detail.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (detail.cr-detail.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (detail.cr-detail.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (detail.cr-detail.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (detail.cr-detail.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (detail.cr-detail.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (detail.cr-detail.db) else 0 end) as modec
      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc in ('sj','sd','se','sf','cp') and year(head.dateid)=" . $year . " and left(coa.alias,2) in ('SA', 'SD', 'SR')
      " . $filter . " " . $filter1 . "
      group by ifnull(client.clientname,''), year(head.dateid)) as x 
      group by clientname, yr
      order by clientname, yr";
    } else {
      $leftjoin = "";
      if ($companyid == 1 || $companyid == 36 || $companyid == 21) { //vitaline, rozlab ,kgeorge
        $leftjoin = " left join client as agent on agent.client=head.agent  ";
      }
      $query = "select clientname, $item yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec 
      from (select 'u' as tr, ifnull(client.clientname,'') as clientname, $barcode year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then stock.$analby else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then stock.$analby else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then stock.$analby else 0 end) as momar,
      sum(case when month(head.dateid)=4 then stock.$analby else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then stock.$analby else 0 end) as momay,
      sum(case when month(head.dateid)=6 then stock.$analby else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then stock.$analby else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then stock.$analby else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then stock.$analby else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then stock.$analby else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then stock.$analby else 0 end) as monov,
      sum(case when month(head.dateid)=12 then stock.$analby else 0 end) as modec
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)  
      left join client on client.client=head.client)left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat $leftjoin
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year 
      and stock.$analby <> 0 $filter $filter1 and item.isofficesupplies=0
      group by ifnull(client.clientname,''), $barcode year(head.dateid)) as x 
      group by clientname, $item yr
      order by clientname, yr";
    }
    return $query;
  }

  public function reportDefault_ALL($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $analby =  $config['params']['dataparams']['analyzedby'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 1: //vitaline
        $brandid    = $config['params']['dataparams']['brandid'];
        $agent       = $config['params']['dataparams']['agent'];

        if ($brandid != "") {
          $filter .= " and item.brand='$brandid'";
        }

        if ($agent != "") {
          $filter .= " and agent.client='$agent'";
        }
        break;
      case 10: // afti
      case 12: //afti usd
        $prjid = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['ddeptname'];
        $project = $config['params']['dataparams']['projectid'];
        $indus = $config['params']['dataparams']['industry'];
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        if ($indus != "") {
          $filter1 .= " and client.industry = '$indus'";
        }
        break;
      case 36: //rozlab
      case 21: //kg
        $agent      = $config['params']['dataparams']['agent'];
        $agentid       = $config['params']['dataparams']['agentid'];
        if ($agent != "") {
          $filter .= " and agent.clientid='$agentid'";
        }
      default:
        $filter1 .= "";
        break;
    }

    $barcode = "";
    $item = "";

    if ($reporttype == 1) {
      $barcode = "item.itemname,";
      $item = "itemname,";
      $analby = "iss";
    }

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (detail.cr-detail.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (detail.cr-detail.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (detail.cr-detail.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (detail.cr-detail.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (detail.cr-detail.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (detail.cr-detail.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (detail.cr-detail.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (detail.cr-detail.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (detail.cr-detail.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (detail.cr-detail.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (detail.cr-detail.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (detail.cr-detail.db) else 0 end) as modec
      from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
      left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc in ('sj','sd','se','sf','cp') and year(head.dateid)= " . $year . " and left(coa.alias,2) in ('SA', 'SD', 'SR')
      " . $filter . " " . $filter1 . "
      group by ifnull(client.clientname,''), year(head.dateid)
      union all
      select 'u' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (detail.cr-detail.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (detail.cr-detail.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (detail.cr-detail.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (detail.cr-detail.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (detail.cr-detail.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (detail.cr-detail.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (detail.cr-detail.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (detail.cr-detail.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (detail.cr-detail.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (detail.cr-detail.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (detail.cr-detail.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (detail.cr-detail.db) else 0 end) as modec
      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc in ('sj','sd','se','sf','cp') and year(head.dateid)=" . $year . " and left(coa.alias,2) in ('SA', 'SD', 'SR')
      " . $filter . " " . $filter1 . "
      group by ifnull(client.clientname,''), year(head.dateid)) as x
      group by clientname, yr
      order by clientname, yr";
    } else {
      $ljoin = "";
      $gjoin = "";
      if ($companyid == 1 || $companyid == 36 || $companyid == 21) { //vitaline & rozlab ,kgeorge
        $ljoin = " left join client as agent on agent.client=head.agent ";
        $gjoin = " left join client as agent on agent.clientid=head.agentid ";
      }
      $query = "select clientname, $item yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (select 'p' as tr, ifnull(client.clientname, '') as clientname, $barcode year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then stock.$analby else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then stock.$analby else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then stock.$analby else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then stock.$analby else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then stock.$analby else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then stock.$analby else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then stock.$analby else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then stock.$analby else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then stock.$analby else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then stock.$analby else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then stock.$analby else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then stock.$analby else 0 end) as modec
      from ((glhead as head left join glstock as stock on stock.trno = head.trno)
      left join client on client.clientid = head.clientid) left join cntnum on cntnum.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat $gjoin
      where head.doc in ('sj','mj', 'sd', 'se', 'sf') and year(head.dateid) = $year
      and stock.$analby <> 0 $filter $filter1 and item.isofficesupplies = 0
      group by ifnull(client.clientname, ''), $barcode year(head.dateid)
      union all
      select 'u' as tr, ifnull(client.clientname, '') as clientname, $barcode year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then stock.$analby else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then stock.$analby else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then stock.$analby else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then stock.$analby else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then stock.$analby else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then stock.$analby else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then stock.$analby else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then stock.$analby else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then stock.$analby else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then stock.$analby else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then stock.$analby else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then stock.$analby else 0 end) as modec
      from ((lahead as head left join lastock as stock on stock.trno = head.trno)
      left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat $ljoin
      where head.doc in ('sj','mj', 'sd', 'se', 'sf') and year(head.dateid) = $year
      and stock.$analby <> 0 $filter $filter1 and item.isofficesupplies = 0
      group by ifnull(client.clientname, ''), $barcode year(head.dateid)) as x
      group by clientname, $item yr";
    }
    return $query;
  }

  public function reportDetailed($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 1) { //vitaline
      $brandid    = $config['params']['dataparams']['brandid'];
      $agent       = $config['params']['dataparams']['agent'];

      if ($brandid != "") {
        $filter .= " and item.brand='$brandid'";
      }

      if ($agent != "") {
        $filter .= " and agent.client='$agent'";
      }
    }

    switch ($posttype) {
      case 0:
        $gjoin = "";
        if ($companyid == 1) { //vitaline
          $gjoin = " left join client as agent on agent.clientid=head.agentid ";
        }
        $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
                        sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                        sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                  from (select 'p' as tr, ifnull(client.clientname, '') as clientname, year(head.dateid) as yr,
                  sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                  sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                  sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                  sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                  sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                  sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                  sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                  sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                  sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                  sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                  sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                  sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                  from ((glhead as head left join glstock as stock on stock.trno = head.trno)
                  left join client on client.clientid = head.clientid) left join cntnum on cntnum.trno = head.trno
                  left join item on item.itemid = stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat $gjoin
                  where head.doc in ('sj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                  and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
                  group by ifnull(client.clientname, ''), year(head.dateid)) as x
                  group by clientname, yr";
        break;
      case 1:
        $ljoin = "";
        if ($companyid == 1) { //vitaline
          $ljoin = " left join client as agent on agent.client=head.agent ";
        }
        $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
                        sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                        sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                  from (select 'u' as tr, ifnull(client.clientname, '') as clientname, year(head.dateid) as yr,
                  sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                  sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                  sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                  sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                  sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                  sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                  sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                  sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                  sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                  sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                  sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                  sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                  from ((lahead as head left join lastock as stock on stock.trno = head.trno)
                  left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
                  left join item on item.itemid = stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat $ljoin
                  where head.doc in ('sj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                  and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
                  group by ifnull(client.clientname, ''), year(head.dateid)) as x
                  group by clientname, yr";
        break;

      default:
        $ljoin = "";
        $gjoin = "";
        if ($companyid == 1) { //vitaline
          $ljoin = " left join client as agent on agent.client=head.agent ";
          $gjoin = " left join client as agent on agent.clientid=head.agentid ";
        }
        $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
                        sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
                        sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
                  from (select 'p' as tr, ifnull(client.clientname, '') as clientname, year(head.dateid) as yr,
                  sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                  sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                  sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                  sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                  sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                  sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                  sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                  sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                  sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                  sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                  sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                  sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                  from ((glhead as head left join glstock as stock on stock.trno = head.trno)
                  left join client on client.clientid = head.clientid) left join cntnum on cntnum.trno = head.trno
                  left join item on item.itemid = stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat $gjoin
                  where head.doc in ('sj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                  and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
                  group by ifnull(client.clientname, ''), year(head.dateid)
                  union all
                  select 'u' as tr, ifnull(client.clientname, '') as clientname, year(head.dateid) as yr,
                  sum(case when month(head.dateid) = 1 then stock.ext else 0 end) as mojan,
                  sum(case when month(head.dateid) = 2 then stock.ext else 0 end) as mofeb,
                  sum(case when month(head.dateid) = 3 then stock.ext else 0 end) as momar,
                  sum(case when month(head.dateid) = 4 then stock.ext else 0 end) as moapr,
                  sum(case when month(head.dateid) = 5 then stock.ext else 0 end) as momay,
                  sum(case when month(head.dateid) = 6 then stock.ext else 0 end) as mojun,
                  sum(case when month(head.dateid) = 7 then stock.ext else 0 end) as mojul,
                  sum(case when month(head.dateid) = 8 then stock.ext else 0 end) as moaug,
                  sum(case when month(head.dateid) = 9 then stock.ext else 0 end) as mosep,
                  sum(case when month(head.dateid) = 10 then stock.ext else 0 end) as mooct,
                  sum(case when month(head.dateid) = 11 then stock.ext else 0 end) as monov,
                  sum(case when month(head.dateid) = 12 then stock.ext else 0 end) as modec
                  from ((lahead as head left join lastock as stock on stock.trno = head.trno)
                  left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
                  left join item on item.itemid = stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat $ljoin
                  where head.doc in ('sj', 'sd', 'se', 'sf') and year(head.dateid) = $year
                  and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
                  group by ifnull(client.clientname, ''), year(head.dateid)) as x
                  group by clientname, yr";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == '') {
      $filtercenter = $center;
    }

    if ($companyid == 10 || $companyid == 12) { //afti
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    if ($companyid == 36 || $companyid == 21) { //rozlab,kinggeorge
      $agent       = $config['params']['dataparams']['agent'];
      if ($agent != "") {
        $agentname = $config['params']['dataparams']['agentname'];
      } else {
        $agentname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $reptype = '';

    if ($companyid == 1 && $reporttype == 0) { //vitaline
      $reptype = 'SUMMARIZED';
    } else if ($companyid == 1 && $reporttype == 1) { //vitaline
      $reptype = 'DETAILED';
    }

    if ($companyid == 36) { //rozlab
      $typeofreport = $config['params']['dataparams']['typeofreport'];
      switch ($typeofreport) {
        case 'report':
          $reptype = ' - SALES REPORT';
          break;
        case 'lessreturn':
          $reptype = ' - SALES LESS RETURN';
          break;
        case 'return':
          $reptype = ' - SALES RETURN';
          break;
      }
    }

    if ($companyid == 59) { //kinggeorge
      $reptype = 'All Customer';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col("ANALYZE CUSTOMER SALES (MONTHLY) $reptype", null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow('200', null, false, $border, '', 'C', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Year : ' . strtoupper($year), '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');

    switch ($posttype) {
      case 0: //posted
        $posttype = 'Posted';
        break;
      case 1: //unposted
        $posttype = 'Unposted';
        break;
      case 2: //all
        $posttype = 'ALL';
        break;
    }


    if ($companyid == 21) { //kg
      $size = '200';
    } else {
      $size = '100';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    if ($companyid != 21) { // not kinggeorge
      $str .= $this->reporter->col('Center : ' . $filtercenter, '100', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', $size, null, false, $border, '', 'L', $font, '10', '', 'b', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, $size, null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', $size, null, false, $border, '', 'L', $font, '10', '', 'b', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, $size, null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, false, $border, '', 'L', $font, '10', '', 'b', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }

    if ($companyid == 36 || $companyid == 21) { //rozlab
      $str .= $this->reporter->col('Agent : ' . $agentname, '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }

    $str .= $this->reporter->pagenumber('Page', null, null, false, false, '', 'R');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $name = 'CLIENT NAME';
    if ($companyid == 34) { //evergreen
      $name = 'PLAN HOLDERS NAME';
    }
    $str .= $this->reporter->col($name, '120', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 86;
    $page = 88;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {

      $mojan = number_format($data->mojan, 2) < 1 ? '-' : number_format($data->mojan, 2);
      $mofeb = number_format($data->mofeb, 2) < 1 ? '-' : number_format($data->mofeb, 2);
      $momar = number_format($data->momar, 2) < 1 ? '-' : number_format($data->momar, 2);
      $moapr = number_format($data->moapr, 2) < 1 ? '-' : number_format($data->moapr, 2);
      $momay = number_format($data->momay, 2) < 1 ? '-' : number_format($data->momay, 2);
      $mojun = number_format($data->mojun, 2) < 1 ? '-' : number_format($data->mojun, 2);
      $mojul = number_format($data->mojul, 2) < 1 ? '-' : number_format($data->mojul, 2);
      $moaug = number_format($data->moaug, 2) < 1 ? '-' : number_format($data->moaug, 2);
      $mosep = number_format($data->mosep, 2) < 1 ? '-' : number_format($data->mosep, 2);
      $mooct = number_format($data->mooct, 2) < 1 ? '-' : number_format($data->mooct, 2);
      $monov = number_format($data->monov, 2) < 1 ? '-' : number_format($data->monov, 2);
      $modec = number_format($data->modec, 2) < 1 ? '-' : number_format($data->modec, 2);

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

      $totalmojan += $data->mojan;
      $totalmofeb += $data->mofeb;
      $totalmomar += $data->momar;
      $totalmoapr += $data->moapr;
      $totalmomay += $data->momay;
      $totalmojun += $data->mojun;
      $totalmojul += $data->mojul;
      $totalmoaug += $data->moaug;
      $totalmosep += $data->mosep;
      $totalmooct += $data->mooct;
      $totalmonov += $data->monov;
      $totalmodec += $data->modec;
      $totalamt += $amt;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'R', 'Century Gothic', '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarizedLayout($config)
  {
    $result = $this->reportDefault($config);
    $count = 86;
    $page = 88;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {

      $mojan = number_format($data->mojan, 2) < 1 ? '-' : number_format($data->mojan, 2);
      $mofeb = number_format($data->mofeb, 2) < 1 ? '-' : number_format($data->mofeb, 2);
      $momar = number_format($data->momar, 2) < 1 ? '-' : number_format($data->momar, 2);
      $moapr = number_format($data->moapr, 2) < 1 ? '-' : number_format($data->moapr, 2);
      $momay = number_format($data->momay, 2) < 1 ? '-' : number_format($data->momay, 2);
      $mojun = number_format($data->mojun, 2) < 1 ? '-' : number_format($data->mojun, 2);
      $mojul = number_format($data->mojul, 2) < 1 ? '-' : number_format($data->mojul, 2);
      $moaug = number_format($data->moaug, 2) < 1 ? '-' : number_format($data->moaug, 2);
      $mosep = number_format($data->mosep, 2) < 1 ? '-' : number_format($data->mosep, 2);
      $mooct = number_format($data->mooct, 2) < 1 ? '-' : number_format($data->mooct, 2);
      $monov = number_format($data->monov, 2) < 1 ? '-' : number_format($data->monov, 2);
      $modec = number_format($data->modec, 2) < 1 ? '-' : number_format($data->modec, 2);



      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

      $totalmojan += $data->mojan;
      $totalmofeb += $data->mofeb;
      $totalmomar += $data->momar;
      $totalmoapr += $data->moapr;
      $totalmomay += $data->momay;
      $totalmojun += $data->mojun;
      $totalmojul += $data->mojul;
      $totalmoaug += $data->moaug;
      $totalmosep += $data->mosep;
      $totalmooct += $data->mooct;
      $totalmonov += $data->monov;
      $totalmodec += $data->modec;
      $totalamt += $amt;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'R', 'Century Gothic', '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function detailedLayout($config)
  {
    $result = $this->reportDefault($config);
    $dataResult = $this->reportDetailed($config);
    $count = 86;
    $page = 88;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;
    $clientname = '';

    foreach ($result as $key => $data) {

      $mojan = number_format($data->mojan, 2) < 1 ? '-' : number_format($data->mojan, 2);
      $mofeb = number_format($data->mofeb, 2) < 1 ? '-' : number_format($data->mofeb, 2);
      $momar = number_format($data->momar, 2) < 1 ? '-' : number_format($data->momar, 2);
      $moapr = number_format($data->moapr, 2) < 1 ? '-' : number_format($data->moapr, 2);
      $momay = number_format($data->momay, 2) < 1 ? '-' : number_format($data->momay, 2);
      $mojun = number_format($data->mojun, 2) < 1 ? '-' : number_format($data->mojun, 2);
      $mojul = number_format($data->mojul, 2) < 1 ? '-' : number_format($data->mojul, 2);
      $moaug = number_format($data->moaug, 2) < 1 ? '-' : number_format($data->moaug, 2);
      $mosep = number_format($data->mosep, 2) < 1 ? '-' : number_format($data->mosep, 2);
      $mooct = number_format($data->mooct, 2) < 1 ? '-' : number_format($data->mooct, 2);
      $monov = number_format($data->monov, 2) < 1 ? '-' : number_format($data->monov, 2);
      $modec = number_format($data->modec, 2) < 1 ? '-' : number_format($data->modec, 2);

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $dmojan = 0;
      $dmofeb = 0;
      $dmomar = 0;
      $dmoapr = 0;
      $dmomay = 0;
      $dmojun = 0;
      $dmojul = 0;
      $dmoaug = 0;
      $dmosep = 0;
      $dmooct = 0;
      $dmonov = 0;
      $dmodec = 0;
      $damt = 0;

      for ($i = 0; $i < count($dataResult); $i++) {
        if ($dataResult[$i]->clientname == $data->clientname) {
          $dmojan = number_format($dataResult[$i]->mojan, 2) < 1 ? '-' : number_format($dataResult[$i]->mojan, 2);
          $dmofeb = number_format($dataResult[$i]->mofeb, 2) < 1 ? '-' : number_format($dataResult[$i]->mofeb, 2);
          $dmomar = number_format($dataResult[$i]->momar, 2) < 1 ? '-' : number_format($dataResult[$i]->momar, 2);
          $dmoapr = number_format($dataResult[$i]->moapr, 2) < 1 ? '-' : number_format($dataResult[$i]->moapr, 2);
          $dmomay = number_format($dataResult[$i]->momay, 2) < 1 ? '-' : number_format($dataResult[$i]->momay, 2);
          $dmojun = number_format($dataResult[$i]->mojun, 2) < 1 ? '-' : number_format($dataResult[$i]->mojun, 2);
          $dmojul = number_format($dataResult[$i]->mojul, 2) < 1 ? '-' : number_format($dataResult[$i]->mojul, 2);
          $dmoaug = number_format($dataResult[$i]->moaug, 2) < 1 ? '-' : number_format($dataResult[$i]->moaug, 2);
          $dmosep = number_format($dataResult[$i]->mosep, 2) < 1 ? '-' : number_format($dataResult[$i]->mosep, 2);
          $dmooct = number_format($dataResult[$i]->mooct, 2) < 1 ? '-' : number_format($dataResult[$i]->mooct, 2);
          $dmonov = number_format($dataResult[$i]->monov, 2) < 1 ? '-' : number_format($dataResult[$i]->monov, 2);
          $dmodec = number_format($dataResult[$i]->modec, 2) < 1 ? '-' : number_format($dataResult[$i]->modec, 2);

          $damt = $dataResult[$i]->mojan + $dataResult[$i]->mofeb + $dataResult[$i]->momar + $dataResult[$i]->moapr + $dataResult[$i]->momay + $dataResult[$i]->mojun + $dataResult[$i]->mojul + $dataResult[$i]->moaug + $dataResult[$i]->mosep + $dataResult[$i]->mooct + $dataResult[$i]->monov + $dataResult[$i]->modec;
          break;
        }
      }

      if ($clientname == '' || $clientname != $data->clientname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($dmojan, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmofeb, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmomar, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmoapr, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmomay, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmojun, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmojul, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmoaug, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmosep, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmooct, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmonov, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($dmodec, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($damt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page += $count;
        }
      }

      $str .= $this->reporter->addline();


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->itemname, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalmojan += $dmojan;
      $totalmofeb += $dmofeb;
      $totalmomar += $dmomar;
      $totalmoapr += $dmoapr;
      $totalmomay += $dmomay;
      $totalmojun += $dmojun;
      $totalmojul += $dmojul;
      $totalmoaug += $dmoaug;
      $totalmosep += $dmosep;
      $totalmooct += $dmooct;
      $totalmonov += $dmonov;
      $totalmodec += $dmodec;
      $totalamt += $damt;

      $clientname = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'R', 'Century Gothic', '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }


  public function roosevelt_reportDefault_POSTED($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $rtype = $config['params']['dataparams']['typeofdrsi'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }


    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $docin = "";
    $amount = " stock.ext ";
    if ($rtype == 'percust') { //per customer
      $docin = "head.doc in ('sj','cm')";
    } else { //all customer
      $docin = "head.doc ='sj' ";
    }

    $query = "select clientname, yr, sum(mojan) as mojan, sum(mojan1) as mojan1,  sum(mofeb) as mofeb, sum(mofeb1) as mofeb1, 
                                     sum(momar) as momar, sum(momar1) as momar1,  sum(moapr) as moapr, sum(moapr1) as moapr1,
                                     sum(momay) as momay, sum(momay1) as momay1,  sum(mojun) as mojun, sum(mojun1) as mojun1, 
                                     sum(mojul) as mojul, sum(mojul1) as mojul1,  sum(moaug) as moaug, sum(moaug1) as moaug1,
                                     sum(mosep) as mosep, sum(mosep1) as mosep1,  sum(mooct) as mooct, sum(mooct1) as mooct1,
                                     sum(monov) as monov, sum(monov1) as monov1,  sum(modec) as modec, sum(modec1) as modec1
                    from (select 'p' as tr, ifnull(client.clientname,'') as clientname,  year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1 and head.doc ='sj' then  $amount else 0 end) as mojan,
                    sum(case when month(head.dateid)=1 and head.doc ='cm' then  $amount else 0 end) as mojan1,

                    sum(case when month(head.dateid)=2 and head.doc ='sj' then  $amount else 0 end) as mofeb,
                    sum(case when month(head.dateid)=2 and head.doc ='cm' then  $amount else 0 end) as mofeb1,

                    sum(case when month(head.dateid)=3 and head.doc ='sj' then  $amount else 0 end) as momar,
                    sum(case when month(head.dateid)=3 and head.doc ='cm' then  $amount else 0 end) as momar1,

                    sum(case when month(head.dateid)=4 and head.doc ='sj' then  $amount else 0 end) as moapr,
                    sum(case when month(head.dateid)=4 and head.doc ='cm' then  $amount else 0 end) as moapr1,


                    sum(case when month(head.dateid)=5 and head.doc ='sj' then  $amount else 0 end) as momay,
                    sum(case when month(head.dateid)=5 and head.doc ='cm' then  $amount else 0 end) as momay1,

                    sum(case when month(head.dateid)=6 and head.doc ='sj' then  $amount else 0 end) as mojun,
                    sum(case when month(head.dateid)=6 and head.doc ='cm' then  $amount else 0 end) as mojun1,


                    sum(case when month(head.dateid)=7 and head.doc ='sj' then  $amount else 0 end) as mojul,
                    sum(case when month(head.dateid)=7 and head.doc ='cm' then  $amount else 0 end) as mojul1,


                    sum(case when month(head.dateid)=8 and head.doc ='sj' then  $amount else 0 end) as moaug,
                    sum(case when month(head.dateid)=8 and head.doc ='cm' then  $amount else 0 end) as moaug1,

                    sum(case when month(head.dateid)=9 and head.doc ='sj'  then  $amount else 0 end) as mosep,
                    sum(case when month(head.dateid)=9 and head.doc ='cm' then  $amount else 0 end) as mosep1,

                    sum(case when month(head.dateid)=10 and head.doc ='sj' then  $amount else 0 end) as mooct,
                    sum(case when month(head.dateid)=10 and head.doc ='cm' then  $amount else 0 end) as mooct1,


                    sum(case when month(head.dateid)=11 and head.doc ='sj' then  $amount else 0 end) as monov,
                    sum(case when month(head.dateid)=11 and head.doc ='cm' then  $amount else 0 end) as monov1,

                    sum(case when month(head.dateid)=12 and head.doc ='sj' then  $amount else 0 end) as modec,
                    sum(case when month(head.dateid)=12 and head.doc ='cm' then  $amount else 0 end) as modec1  

                    from ((glhead as head left join glstock as stock on stock.trno=head.trno)
                    left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat 
                    where $docin and year(head.dateid)=$year
                    and stock.ext<>0 $filter $filter1 and item.isofficesupplies=0
                    group by ifnull(client.clientname,''), year(head.dateid)) as x 
                    group by clientname, yr
                    order by clientname, yr";
    return $query;
  }


  public function roosevelt_reportDefault_UNPOSTED($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $rtype = $config['params']['dataparams']['typeofdrsi'];


    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }


    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $docin = "";
    $amount = " stock.ext ";
    if ($rtype == 'percust') { //per customer
      $docin = "head.doc in ('sj','cm')";
    } else { //all customer
      $docin = "head.doc ='sj' ";
    }


    $query = "select clientname, yr, sum(mojan) as mojan, sum(mojan1) as mojan1,  sum(mofeb) as mofeb, sum(mofeb1) as mofeb1, 
                                     sum(momar) as momar, sum(momar1) as momar1,  sum(moapr) as moapr, sum(moapr1) as moapr1,
                                     sum(momay) as momay, sum(momay1) as momay1,  sum(mojun) as mojun, sum(mojun1) as mojun1, 
                                     sum(mojul) as mojul, sum(mojul1) as mojul1,  sum(moaug) as moaug, sum(moaug1) as moaug1,
                                     sum(mosep) as mosep, sum(mosep1) as mosep1,  sum(mooct) as mooct, sum(mooct1) as mooct1,
                                     sum(monov) as monov, sum(monov1) as monov1,  sum(modec) as modec, sum(modec1) as modec1
      from (select 'u' as tr, ifnull(client.clientname,'') as clientname,  year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 and head.doc ='sj' then  $amount else 0 end) as mojan,
                    sum(case when month(head.dateid)=1 and head.doc ='cm' then  $amount else 0 end) as mojan1,

                    sum(case when month(head.dateid)=2 and head.doc ='sj' then  $amount else 0 end) as mofeb,
                    sum(case when month(head.dateid)=2 and head.doc ='cm' then  $amount else 0 end) as mofeb1,

                    sum(case when month(head.dateid)=3 and head.doc ='sj' then  $amount else 0 end) as momar,
                    sum(case when month(head.dateid)=3 and head.doc ='cm' then  $amount else 0 end) as momar1,

                    sum(case when month(head.dateid)=4 and head.doc ='sj' then  $amount else 0 end) as moapr,
                    sum(case when month(head.dateid)=4 and head.doc ='cm' then  $amount else 0 end) as moapr1,


                    sum(case when month(head.dateid)=5 and head.doc ='sj' then  $amount else 0 end) as momay,
                    sum(case when month(head.dateid)=5 and head.doc ='cm' then  $amount else 0 end) as momay1,

                    sum(case when month(head.dateid)=6 and head.doc ='sj' then  $amount else 0 end) as mojun,
                    sum(case when month(head.dateid)=6 and head.doc ='cm' then  $amount else 0 end) as mojun1,


                    sum(case when month(head.dateid)=7 and head.doc ='sj' then  $amount else 0 end) as mojul,
                    sum(case when month(head.dateid)=7 and head.doc ='cm' then  $amount else 0 end) as mojul1,


                    sum(case when month(head.dateid)=8 and head.doc ='sj' then  $amount else 0 end) as moaug,
                    sum(case when month(head.dateid)=8 and head.doc ='cm' then  $amount else 0 end) as moaug1,

                    sum(case when month(head.dateid)=9 and head.doc ='sj'  then  $amount else 0 end) as mosep,
                    sum(case when month(head.dateid)=9 and head.doc ='cm' then  $amount else 0 end) as mosep1,

                    sum(case when month(head.dateid)=10 and head.doc ='sj' then  $amount else 0 end) as mooct,
                    sum(case when month(head.dateid)=10 and head.doc ='cm' then  $amount else 0 end) as mooct1,


                    sum(case when month(head.dateid)=11 and head.doc ='sj' then  $amount else 0 end) as monov,
                    sum(case when month(head.dateid)=11 and head.doc ='cm' then  $amount else 0 end) as monov1,

                    sum(case when month(head.dateid)=12 and head.doc ='sj' then  $amount else 0 end) as modec,
                    sum(case when month(head.dateid)=12 and head.doc ='cm' then  $amount else 0 end) as modec1  
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)  
      left join client on client.client=head.client)left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat 
      where $docin and year(head.dateid)=$year 
      and stock.ext <> 0 $filter $filter1 and item.isofficesupplies=0
      group by ifnull(client.clientname,''), year(head.dateid)) as x 
      group by clientname, yr
      order by clientname, yr";
    return $query;
  }


  public function roosevelt_reportDefault_ALL($config)
  {

    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $rtype = $config['params']['dataparams']['typeofdrsi'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }



    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $docin = "";
    $amount = " stock.ext ";
    if ($rtype == 'percust') { //per customer
      $docin = "head.doc in ('sj','cm')";
    } else { //all customer
      $docin = "head.doc ='sj' ";
    }

    $query = "select clientname, yr, sum(mojan) as mojan, sum(mojan1) as mojan1,  sum(mofeb) as mofeb, sum(mofeb1) as mofeb1, 
                                     sum(momar) as momar, sum(momar1) as momar1,  sum(moapr) as moapr, sum(moapr1) as moapr1,
                                     sum(momay) as momay, sum(momay1) as momay1,  sum(mojun) as mojun, sum(mojun1) as mojun1, 
                                     sum(mojul) as mojul, sum(mojul1) as mojul1,  sum(moaug) as moaug, sum(moaug1) as moaug1,
                                     sum(mosep) as mosep, sum(mosep1) as mosep1,  sum(mooct) as mooct, sum(mooct1) as mooct1,
                                     sum(monov) as monov, sum(monov1) as monov1,  sum(modec) as modec, sum(modec1) as modec1
      from (select 'p' as tr, ifnull(client.clientname, '') as clientname, year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1 and head.doc ='sj' then  $amount else 0 end) as mojan,
                    sum(case when month(head.dateid)=1 and head.doc ='cm' then  $amount else 0 end) as mojan1,

                    sum(case when month(head.dateid)=2 and head.doc ='sj' then  $amount else 0 end) as mofeb,
                    sum(case when month(head.dateid)=2 and head.doc ='cm' then  $amount else 0 end) as mofeb1,

                    sum(case when month(head.dateid)=3 and head.doc ='sj' then  $amount else 0 end) as momar,
                    sum(case when month(head.dateid)=3 and head.doc ='cm' then  $amount else 0 end) as momar1,

                    sum(case when month(head.dateid)=4 and head.doc ='sj' then  $amount else 0 end) as moapr,
                    sum(case when month(head.dateid)=4 and head.doc ='cm' then  $amount else 0 end) as moapr1,


                    sum(case when month(head.dateid)=5 and head.doc ='sj' then  $amount else 0 end) as momay,
                    sum(case when month(head.dateid)=5 and head.doc ='cm' then  $amount else 0 end) as momay1,

                    sum(case when month(head.dateid)=6 and head.doc ='sj' then  $amount else 0 end) as mojun,
                    sum(case when month(head.dateid)=6 and head.doc ='cm' then  $amount else 0 end) as mojun1,


                    sum(case when month(head.dateid)=7 and head.doc ='sj' then  $amount else 0 end) as mojul,
                    sum(case when month(head.dateid)=7 and head.doc ='cm' then  $amount else 0 end) as mojul1,


                    sum(case when month(head.dateid)=8 and head.doc ='sj' then  $amount else 0 end) as moaug,
                    sum(case when month(head.dateid)=8 and head.doc ='cm' then  $amount else 0 end) as moaug1,

                    sum(case when month(head.dateid)=9 and head.doc ='sj'  then  $amount else 0 end) as mosep,
                    sum(case when month(head.dateid)=9 and head.doc ='cm' then  $amount else 0 end) as mosep1,

                    sum(case when month(head.dateid)=10 and head.doc ='sj' then  $amount else 0 end) as mooct,
                    sum(case when month(head.dateid)=10 and head.doc ='cm' then  $amount else 0 end) as mooct1,


                    sum(case when month(head.dateid)=11 and head.doc ='sj' then  $amount else 0 end) as monov,
                    sum(case when month(head.dateid)=11 and head.doc ='cm' then  $amount else 0 end) as monov1,

                    sum(case when month(head.dateid)=12 and head.doc ='sj' then  $amount else 0 end) as modec,
                    sum(case when month(head.dateid)=12 and head.doc ='cm' then  $amount else 0 end) as modec1
      from ((glhead as head left join glstock as stock on stock.trno = head.trno)
      left join client on client.clientid = head.clientid) left join cntnum on cntnum.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat 
      where $docin and year(head.dateid) = $year
      and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
      group by ifnull(client.clientname, ''),  year(head.dateid)
      union all
      select 'u' as tr, ifnull(client.clientname, '') as clientname,  year(head.dateid) as yr,
     sum(case when month(head.dateid)=1 and head.doc ='sj' then  $amount else 0 end) as mojan,
                    sum(case when month(head.dateid)=1 and head.doc ='cm' then  $amount else 0 end) as mojan1,

                    sum(case when month(head.dateid)=2 and head.doc ='sj' then  $amount else 0 end) as mofeb,
                    sum(case when month(head.dateid)=2 and head.doc ='cm' then  $amount else 0 end) as mofeb1,

                    sum(case when month(head.dateid)=3 and head.doc ='sj' then  $amount else 0 end) as momar,
                    sum(case when month(head.dateid)=3 and head.doc ='cm' then  $amount else 0 end) as momar1,

                    sum(case when month(head.dateid)=4 and head.doc ='sj' then  $amount else 0 end) as moapr,
                    sum(case when month(head.dateid)=4 and head.doc ='cm' then  $amount else 0 end) as moapr1,


                    sum(case when month(head.dateid)=5 and head.doc ='sj' then  $amount else 0 end) as momay,
                    sum(case when month(head.dateid)=5 and head.doc ='cm' then  $amount else 0 end) as momay1,

                    sum(case when month(head.dateid)=6 and head.doc ='sj' then  $amount else 0 end) as mojun,
                    sum(case when month(head.dateid)=6 and head.doc ='cm' then  $amount else 0 end) as mojun1,


                    sum(case when month(head.dateid)=7 and head.doc ='sj' then  $amount else 0 end) as mojul,
                    sum(case when month(head.dateid)=7 and head.doc ='cm' then  $amount else 0 end) as mojul1,


                    sum(case when month(head.dateid)=8 and head.doc ='sj' then  $amount else 0 end) as moaug,
                    sum(case when month(head.dateid)=8 and head.doc ='cm' then  $amount else 0 end) as moaug1,

                    sum(case when month(head.dateid)=9 and head.doc ='sj'  then  $amount else 0 end) as mosep,
                    sum(case when month(head.dateid)=9 and head.doc ='cm' then  $amount else 0 end) as mosep1,

                    sum(case when month(head.dateid)=10 and head.doc ='sj' then  $amount else 0 end) as mooct,
                    sum(case when month(head.dateid)=10 and head.doc ='cm' then  $amount else 0 end) as mooct1,


                    sum(case when month(head.dateid)=11 and head.doc ='sj' then  $amount else 0 end) as monov,
                    sum(case when month(head.dateid)=11 and head.doc ='cm' then  $amount else 0 end) as monov1,

                    sum(case when month(head.dateid)=12 and head.doc ='sj' then  $amount else 0 end) as modec,
                    sum(case when month(head.dateid)=12 and head.doc ='cm' then  $amount else 0 end) as modec1
      from ((lahead as head left join lastock as stock on stock.trno = head.trno)
      left join client on client.client = head.client) left join cntnum on cntnum.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat 
      where $docin and year(head.dateid) = $year
      and stock.ext <> 0 $filter $filter1 and item.isofficesupplies = 0
      group by ifnull(client.clientname, ''),  year(head.dateid)) as x
      group by clientname, yr";
    return $query;
  }

  private function roosevelt_percust_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $filtercenter = $config['params']['dataparams']['center'];


    if ($filtercenter == '') {
      $filtercenter = $center;
    }
    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $reptype = 'Per Customer Only';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col("ANALYZE CUSTOMER SALES (MONTHLY) $reptype", null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow('200', null, false, $border, '', 'C', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year : ' . strtoupper($year), '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');

    switch ($posttype) {
      case 0: //posted
        $posttype = 'Posted';
        break;
      case 1: //unposted
        $posttype = 'Unposted';
        break;
      case 2: //all
        $posttype = 'ALL';
        break;
    }
    // $size = '100';
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '100', null, false, $border, '', 'L', $font, '10', '', 'b', '');


    // $str .= $this->reporter->pagenumber('Page', null, null, false, false, '', 'R');
    $str .= $this->reporter->pagenumber('Page', '100',  null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname,  '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $name = 'MONTH NAME';
    $str .= $this->reporter->col($name, '400', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '200', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RETURN', '200', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    return $str;
  }


  public function roosevelt_percust_layout($config)
  {
    $result = $this->reportDefault($config);
    $count = 86;
    $page = 88;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:200px');
    $months = [
      'JANUARY' => 'mojan',
      'FEBRUARY' => 'mofeb',
      'MARCH' => 'momar',
      'APRIL' => 'moapr',
      'MAY' => 'momay',
      'JUNE' => 'mojun',
      'JULY' => 'mojul',
      'AUGUST' => 'moaug',
      'SEPTEMBER' => 'mosep',
      'OCTOBER' => 'mooct',
      'NOVEMBER' => 'monov',
      'DECEMBER' => 'modec'
    ];

    $clientname = "";
    foreach ($result as $data) {
      // kapag bagong customer, magpage break
      if ($clientname == "" || $clientname != $data->clientname) {
        if ($clientname != "") {
          // close previous table + page break
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
        }
        $clientname = $data->clientname;
        // print header for the new customer
        $str .= $this->roosevelt_percust_header($config, $clientname);
        $total_sales = 0;
        $total_return = 0;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('Customer Name: ' . $data->clientname, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        foreach ($months as $monthname => $field) {
          $sales = isset($data->$field) ? (float)$data->$field : 0;
          $returnfield = $field . '1';
          $returns = isset($data->$returnfield) ? (float)$data->$returnfield : 0;

          $saless = $sales != 0 ?  number_format($sales, 2)  : '-';
          $returnss = $returns != 0 ?  number_format($returns, 2)  : '-';
          // compute totals
          $total_sales += $sales;
          $total_return += $returns;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($monthname, '400', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($saless, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($returnss, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
        }

        $total_saless = $total_sales != 0 ?  number_format($total_sales, 2)  : '-';
        $total_returns = $total_return != 0 ?  number_format($total_return, 2)  : '-';

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('TOTAL', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total_saless, '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total_returns, '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class