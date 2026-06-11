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

class sales_vs_collection
{
  public $modulename = 'Sales vs Collection';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1832px;max-width:1832px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1832'];



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
    $fields = ['radioprint', 'dcentername', 'dclientname', 'year'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
    ]);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'ALL', 'value' => '2', 'color' => 'teal']
    ]);

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


    $paramstr = "select 
            'default' as print,
            year(now()) as year,
            '' as dclientname,
            '' as client,
            '' as clientname,
            '0' as clientid,
            '0' as posttype,
            '' as center,
            '' as centername,
            '' as dcentername ";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $result = $this->reportDefaultLayout_LAYOUT($config, $result); // POSTED
    return $result;
  }

  public function reportDefault($config)
  {
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1': // UNPOSTED
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      case  '2': // ALL
        $query = $this->reportDefault_qry($config);
        break;
    }
    $result = $this->coreFunctions->opentable($query);
    return $this->reportplotting($config, $result);
  }

  public function reportDefault_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $year = $config['params']['dataparams']['year'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];

    $filter = "";
    if ($filtercenter != "") {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    $query = "select client,clientname,sum(smojan) as smojan, sum(smofeb) as smofeb, sum(smomar) as smomar,
                        sum(smoapr) as smoapr, sum(smomay) as smomay, sum(smojun) as smojun, sum(smojul) as smojul, sum(smoaug) as smoaug,
                        sum(smosep) as smosep, sum(smooct) as smooct, sum(smonov) as smonov, sum(smodec) as smodec,
                        sum(cmojan) as cmojan, sum(cmofeb) as cmofeb, sum(cmomar) as cmomar,
                        sum(cmoapr) as cmoapr, sum(cmomay) as cmomay, sum(cmojun) as cmojun, sum(cmojul) as cmojul, sum(cmoaug) as cmoaug,
                        sum(cmosep) as cmosep, sum(cmooct) as cmooct, sum(cmonov) as cmonov, sum(cmodec) as cmodec
                from (
                    select 'Sales' as module ,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                            sum(case when month(head.dateid)=1 then d.cr else 0 end) as smojan,
                            sum(case when month(head.dateid)=2 then d.cr else 0 end) as smofeb,
                            sum(case when month(head.dateid)=3 then d.cr else 0 end) as smomar,
                            sum(case when month(head.dateid)=4 then d.cr else 0 end) as smoapr,
                            sum(case when month(head.dateid)=5 then d.cr else 0 end) as smomay,
                            sum(case when month(head.dateid)=6 then d.cr else 0 end) as smojun,
                            sum(case when month(head.dateid)=7 then d.cr else 0 end) as smojul,
                            sum(case when month(head.dateid)=8 then d.cr else 0 end) as smoaug,
                            sum(case when month(head.dateid)=9 then d.cr else 0 end) as smosep,
                            sum(case when month(head.dateid)=10 then d.cr else 0 end) as smooct,
                            sum(case when month(head.dateid)=11 then d.cr else 0 end) as smonov,
                            sum(case when month(head.dateid)=12 then d.cr else 0 end) as smodec,
                            0 as cmojan,0 as cmofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                            0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec
                    from glhead as head
                    left join gldetail as d on d.trno=head.trno
                    left join client as client on client.clientid=head.clientid
                    left join cntnum on cntnum.trno=head.trno
                    left join coa as c on c.acnoid=d.acnoid
                    where head.doc ='SJ' and left(c.alias,2)='SA' and  year(head.dateid) ='$year' $filter 
                group by client.client,client.clientname, client.alias
                union all

                select 'Collection' as module,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                0 as smojan,0 as smofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec,

                    sum(case when month(glhead.dateid)=1 then gldetail.db else 0 end) as cmojan,
                    sum(case when month(glhead.dateid)=2 then gldetail.db else 0 end) as cmofeb,
                    sum(case when month(glhead.dateid)=3 then gldetail.db else 0 end) as cmomar,
                    sum(case when month(glhead.dateid)=4 then gldetail.db else 0 end) as cmoapr,
                    sum(case when month(glhead.dateid)=5 then gldetail.db else 0 end) as cmomay,
                    sum(case when month(glhead.dateid)=6 then gldetail.db else 0 end) as cmojun,
                    sum(case when month(glhead.dateid)=7 then gldetail.db else 0 end) as cmojul,
                    sum(case when month(glhead.dateid)=8 then gldetail.db else 0 end) as cmoaug,
                    sum(case when month(glhead.dateid)=9 then gldetail.db else 0 end) as cmosep,
                    sum(case when month(glhead.dateid)=10 then gldetail.db else 0 end) as cmooct,
                    sum(case when month(glhead.dateid)=11 then gldetail.db else 0 end) as cmonov,
                    sum(case when month(glhead.dateid)=12 then gldetail.db else 0 end) as cmodec

                    from glhead
                    left join gldetail on glhead.trno=gldetail.trno
                    left join client on glhead.clientid=client.clientid
                    left join cntnum on glhead.trno=cntnum.trno
                    left join coa on gldetail.acnoid=coa.acnoid
                    where left(coa.alias,2) in ('CA','CR','PC','CB') and glhead.doc='CR' and  year(glhead.dateid) = '$year' $filter
                group by client.client,client.clientname, client.alias) as a
                group by clientname,client
                order by clientname";

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $year = $config['params']['dataparams']['year'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];

    $filter = "";
    if ($filtercenter != "") {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    $query = "select client,clientname,sum(smojan) as smojan, sum(smofeb) as smofeb, sum(smomar) as smomar,
                        sum(smoapr) as smoapr, sum(smomay) as smomay, sum(smojun) as smojun, sum(smojul) as smojul, sum(smoaug) as smoaug,
                        sum(smosep) as smosep, sum(smooct) as smooct, sum(smonov) as smonov, sum(smodec) as smodec,
                        sum(cmojan) as cmojan, sum(cmofeb) as cmofeb, sum(cmomar) as cmomar,
                        sum(cmoapr) as cmoapr, sum(cmomay) as cmomay, sum(cmojun) as cmojun, sum(cmojul) as cmojul, sum(cmoaug) as cmoaug,
                        sum(cmosep) as cmosep, sum(cmooct) as cmooct, sum(cmonov) as cmonov, sum(cmodec) as cmodec
                from (
                     select 'Sales' as module ,client.client,if(client.alias = '', client.clientname, client.alias) as clientname, 
                            sum(case when month(head.dateid)=1 then d.cr else 0 end) as smojan,
                            sum(case when month(head.dateid)=2 then d.cr else 0 end) as smofeb,
                            sum(case when month(head.dateid)=3 then d.cr else 0 end) as smomar,
                            sum(case when month(head.dateid)=4 then d.cr else 0 end) as smoapr,
                            sum(case when month(head.dateid)=5 then d.cr else 0 end) as smomay,
                            sum(case when month(head.dateid)=6 then d.cr else 0 end) as smojun,
                            sum(case when month(head.dateid)=7 then d.cr else 0 end) as smojul,
                            sum(case when month(head.dateid)=8 then d.cr else 0 end) as smoaug,
                            sum(case when month(head.dateid)=9 then d.cr else 0 end) as smosep,
                            sum(case when month(head.dateid)=10 then d.cr else 0 end) as smooct,
                            sum(case when month(head.dateid)=11 then d.cr else 0 end) as smonov,
                            sum(case when month(head.dateid)=12 then d.cr else 0 end) as smodec,
                            0 as cmojan,0 as cmofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                            0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec
                    from lahead as head
                    left join ladetail as d on d.trno=head.trno
                    left join client as client on client.client=head.client
                    left join cntnum on cntnum.trno=head.trno
                    left join coa as c on c.acnoid=d.acnoid
                    where head.doc ='SJ' and left(c.alias,2)='SA' and  year(head.dateid) ='$year' $filter 
                group by client.client,client.clientname, client.alias
                      union all 
                    
                    select 'Collection' as module,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                0 as smojan,0 as smofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec,

                    sum(case when month(lahead.dateid)=1 then ladetail.db else 0 end) as cmojan,
                    sum(case when month(lahead.dateid)=2 then ladetail.db else 0 end) as cmofeb,
                    sum(case when month(lahead.dateid)=3 then ladetail.db else 0 end) as cmomar,
                    sum(case when month(lahead.dateid)=4 then ladetail.db else 0 end) as cmoapr,
                    sum(case when month(lahead.dateid)=5 then ladetail.db else 0 end) as cmomay,
                    sum(case when month(lahead.dateid)=6 then ladetail.db else 0 end) as cmojun,
                    sum(case when month(lahead.dateid)=7 then ladetail.db else 0 end) as cmojul,
                    sum(case when month(lahead.dateid)=8 then ladetail.db else 0 end) as cmoaug,
                    sum(case when month(lahead.dateid)=9 then ladetail.db else 0 end) as cmosep,
                    sum(case when month(lahead.dateid)=10 then ladetail.db else 0 end) as cmooct,
                    sum(case when month(lahead.dateid)=11 then ladetail.db else 0 end) as cmonov,
                    sum(case when month(lahead.dateid)=12 then ladetail.db else 0 end) as cmodec

                    from lahead
                    left join ladetail on lahead.trno=ladetail.trno
                    left join client on lahead.client=client.client
                    left join cntnum on lahead.trno=cntnum.trno
                    left join coa on ladetail.acnoid=coa.acnoid
                    where left(coa.alias,2) in ('CA','CR','PC', 'CB') and lahead.doc='CR' and  year(lahead.dateid) = '$year' $filter
                group by client.client,client.clientname, client.alias) as a
                group by clientname,client
                order by clientname";

    return $query;
  }

  public function reportDefault_qry($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $year = $config['params']['dataparams']['year'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];

    $filter = "";
    if ($filtercenter != "") {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    $query = "select client,clientname,sum(smojan) as smojan, sum(smofeb) as smofeb, sum(smomar) as smomar,
                        sum(smoapr) as smoapr, sum(smomay) as smomay, sum(smojun) as smojun, sum(smojul) as smojul, sum(smoaug) as smoaug,
                        sum(smosep) as smosep, sum(smooct) as smooct, sum(smonov) as smonov, sum(smodec) as smodec,
                        sum(cmojan) as cmojan, sum(cmofeb) as cmofeb, sum(cmomar) as cmomar,
                        sum(cmoapr) as cmoapr, sum(cmomay) as cmomay, sum(cmojun) as cmojun, sum(cmojul) as cmojul, sum(cmoaug) as cmoaug,
                        sum(cmosep) as cmosep, sum(cmooct) as cmooct, sum(cmonov) as cmonov, sum(cmodec) as cmodec
                from (
                    select 'Sales' as module ,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                            sum(case when month(head.dateid)=1 then d.cr else 0 end) as smojan,
                            sum(case when month(head.dateid)=2 then d.cr else 0 end) as smofeb,
                            sum(case when month(head.dateid)=3 then d.cr else 0 end) as smomar,
                            sum(case when month(head.dateid)=4 then d.cr else 0 end) as smoapr,
                            sum(case when month(head.dateid)=5 then d.cr else 0 end) as smomay,
                            sum(case when month(head.dateid)=6 then d.cr else 0 end) as smojun,
                            sum(case when month(head.dateid)=7 then d.cr else 0 end) as smojul,
                            sum(case when month(head.dateid)=8 then d.cr else 0 end) as smoaug,
                            sum(case when month(head.dateid)=9 then d.cr else 0 end) as smosep,
                            sum(case when month(head.dateid)=10 then d.cr else 0 end) as smooct,
                            sum(case when month(head.dateid)=11 then d.cr else 0 end) as smonov,
                            sum(case when month(head.dateid)=12 then d.cr else 0 end) as smodec,
                            0 as cmojan,0 as cmofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                            0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec
                    from glhead as head
                    left join gldetail as d on d.trno=head.trno
                    left join client as client on client.clientid=head.clientid
                    left join cntnum on cntnum.trno=head.trno
                    left join coa as c on c.acnoid=d.acnoid
                    where head.doc ='SJ' and left(c.alias,2)='SA' and  year(head.dateid) ='$year' $filter 
                group by client.client,client.clientname, client.alias
                union all

                select 'Collection' as module,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                0 as smojan,0 as smofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec,

                    sum(case when month(glhead.dateid)=1 then gldetail.db else 0 end) as cmojan,
                    sum(case when month(glhead.dateid)=2 then gldetail.db else 0 end) as cmofeb,
                    sum(case when month(glhead.dateid)=3 then gldetail.db else 0 end) as cmomar,
                    sum(case when month(glhead.dateid)=4 then gldetail.db else 0 end) as cmoapr,
                    sum(case when month(glhead.dateid)=5 then gldetail.db else 0 end) as cmomay,
                    sum(case when month(glhead.dateid)=6 then gldetail.db else 0 end) as cmojun,
                    sum(case when month(glhead.dateid)=7 then gldetail.db else 0 end) as cmojul,
                    sum(case when month(glhead.dateid)=8 then gldetail.db else 0 end) as cmoaug,
                    sum(case when month(glhead.dateid)=9 then gldetail.db else 0 end) as cmosep,
                    sum(case when month(glhead.dateid)=10 then gldetail.db else 0 end) as cmooct,
                    sum(case when month(glhead.dateid)=11 then gldetail.db else 0 end) as cmonov,
                    sum(case when month(glhead.dateid)=12 then gldetail.db else 0 end) as cmodec

                    from glhead
                    left join gldetail on glhead.trno=gldetail.trno
                    left join client on glhead.clientid=client.clientid
                    left join cntnum on glhead.trno=cntnum.trno
                    left join coa on gldetail.acnoid=coa.acnoid
                    where left(coa.alias,2) in ('CA','CR','PC', 'CB') and glhead.doc='CR' and  year(glhead.dateid) = '$year' $filter
                group by client.client,client.clientname, client.alias
                
                    union all 
                     select 'Sales' as module ,client.client,if(client.alias = '', client.clientname, client.alias) as clientname, 
                            sum(case when month(head.dateid)=1 then d.cr else 0 end) as smojan,
                            sum(case when month(head.dateid)=2 then d.cr else 0 end) as smofeb,
                            sum(case when month(head.dateid)=3 then d.cr else 0 end) as smomar,
                            sum(case when month(head.dateid)=4 then d.cr else 0 end) as smoapr,
                            sum(case when month(head.dateid)=5 then d.cr else 0 end) as smomay,
                            sum(case when month(head.dateid)=6 then d.cr else 0 end) as smojun,
                            sum(case when month(head.dateid)=7 then d.cr else 0 end) as smojul,
                            sum(case when month(head.dateid)=8 then d.cr else 0 end) as smoaug,
                            sum(case when month(head.dateid)=9 then d.cr else 0 end) as smosep,
                            sum(case when month(head.dateid)=10 then d.cr else 0 end) as smooct,
                            sum(case when month(head.dateid)=11 then d.cr else 0 end) as smonov,
                            sum(case when month(head.dateid)=12 then d.cr else 0 end) as smodec,
                            0 as cmojan,0 as cmofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                            0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec
                    from lahead as head
                    left join ladetail as d on d.trno=head.trno
                    left join client as client on client.client=head.client
                    left join cntnum on cntnum.trno=head.trno
                    left join coa as c on c.acnoid=d.acnoid
                    where head.doc ='SJ' and left(c.alias,2)='SA' and  year(head.dateid) ='$year' $filter 
                group by client.client,client.clientname, client.alias
                      union all 
                    
                    select 'Collection' as module,client.client,if(client.alias = '', client.clientname, client.alias) as clientname,
                0 as smojan,0 as smofeb, 0 as cmomar,0 as cmoapr, 0 as cmomay,
                0 as cmojun, 0 as cmojul, 0 as cmoaug, 0 as cmosep,0 as cmooct, 0 as cmonov, 0 as cmodec,

                    sum(case when month(lahead.dateid)=1 then ladetail.db else 0 end) as cmojan,
                    sum(case when month(lahead.dateid)=2 then ladetail.db else 0 end) as cmofeb,
                    sum(case when month(lahead.dateid)=3 then ladetail.db else 0 end) as cmomar,
                    sum(case when month(lahead.dateid)=4 then ladetail.db else 0 end) as cmoapr,
                    sum(case when month(lahead.dateid)=5 then ladetail.db else 0 end) as cmomay,
                    sum(case when month(lahead.dateid)=6 then ladetail.db else 0 end) as cmojun,
                    sum(case when month(lahead.dateid)=7 then ladetail.db else 0 end) as cmojul,
                    sum(case when month(lahead.dateid)=8 then ladetail.db else 0 end) as cmoaug,
                    sum(case when month(lahead.dateid)=9 then ladetail.db else 0 end) as cmosep,
                    sum(case when month(lahead.dateid)=10 then ladetail.db else 0 end) as cmooct,
                    sum(case when month(lahead.dateid)=11 then ladetail.db else 0 end) as cmonov,
                    sum(case when month(lahead.dateid)=12 then ladetail.db else 0 end) as cmodec

                    from lahead
                    left join ladetail on lahead.trno=ladetail.trno
                    left join client on lahead.client=client.client
                    left join cntnum on lahead.trno=cntnum.trno
                    left join coa on ladetail.acnoid=coa.acnoid
                    where left(coa.alias,2) in ('CA','CR','PC', 'CB') and lahead.doc='CR' and  year(lahead.dateid) = '$year' $filter
                group by client.client,client.clientname, client.alias) as a
                group by clientname,client
                order by clientname";
    return $query;
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];
    $filtercenter = $config['params']['dataparams']['centername'];

    if ($filtercenter != "") {
      $branchDisplay = "BRANCH: " . $filtercenter;
    } else {
      $branchDisplay = "ALL BRANCH";
    }



    $str = '';
    $layoutsize = '1832';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "7.5";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales vs Collection' . '-' . 'Annual '  . strtoupper($year), null, null, false, $border, '', '', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;
      case 1:
        $posttype = 'Unposted';
        break;
      case 2:
        $posttype = 'All';
        break;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Year : ' . strtoupper($year), '200', null, false, $border, '', 'L', $font, '10', '', 'b', '');
    // $str .= $this->reporter->col('All Transaction', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col(($posttype) . ' Transaction', '585', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    // Example: Add it to your report
    $str .= $this->reporter->col($branchDisplay, '632', null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->pagenumber('Page', '585', null, false, false, '', 'R', $font, '8', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER', '96', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JANUARY', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEBRUARY', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MARCH', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APRIL', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUNE', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JULY', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUGUST', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEPTEMBER', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCTOBER', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOVEMBER', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DECEMBER', '122', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '142', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NAME', '96', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '61', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '61', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '71', '', '', $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COL', '71', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_LAYOUT($config, $result)
  {

    $linecounter = 0;
    $count = 28;
    $page = 28;
    $layoutsize = '1832';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "7.5";
    $border = "1px solid ";
    $dotted = "1px dotted";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '165px;margin-top:5px;');
    $str .= $this->displayHeader($config);

    //sales
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

    //collection
    $totalcmojan = 0;
    $totalcmofeb = 0;
    $totalcmomar = 0;
    $totalcmoapr = 0;
    $totalcmomay = 0;
    $totalcmojun = 0;
    $totalcmojul = 0;
    $totalcmoaug = 0;
    $totalcmosep = 0;
    $totalcmooct = 0;
    $totalcmonov = 0;
    $totalcmodec = 0;

    //totalsales and collection jan-dec
    $totalsaless = 0;
    $totalcoll = 0;



    foreach ($result as $key => $data) {
      //sales
      $smojan = number_format($data->smojan, 2);
      if ($smojan < 1) {
        $smojan = '-';
      }

      $smofeb = number_format($data->smofeb, 2);
      if ($smofeb < 1) {
        $smofeb = '-';
      }

      $smomar = number_format($data->smomar, 2);
      if ($smomar < 1) {
        $smomar = '-';
      }

      $smoapr = number_format($data->smoapr, 2);
      if ($smoapr < 1) {
        $smoapr = '-';
      }
      $smomay = number_format($data->smomay, 2);
      if ($smomay < 1) {
        $smomay = '-';
      }
      $smojun = number_format($data->smojun, 2);
      if ($smojun < 1) {
        $smojun = '-';
      }
      $smojul = number_format($data->smojul, 2);
      if ($smojul < 1) {
        $smojul = '-';
      }
      $smoaug = number_format($data->smoaug, 2);
      if ($smoaug < 1) {
        $smoaug = '-';
      }
      $smosep = number_format($data->smosep, 2);
      if ($smosep < 1) {
        $smosep = '-';
      }
      $smooct = number_format($data->smooct, 2);
      if ($smooct < 1) {
        $smooct = '-';
      }
      $smonov = number_format($data->smonov, 2);
      if ($smonov < 1) {
        $smonov = '-';
      }
      $smodec = number_format($data->smodec, 2);
      if ($smodec < 1) {
        $smodec = '-';
      }

      //collection

      $cmojan = number_format($data->cmojan, 2);
      if ($cmojan < 1) {
        $cmojan = '-';
      }

      $cmofeb = number_format($data->cmofeb, 2);
      if ($cmofeb < 1) {
        $cmofeb = '-';
      }

      $cmomar = number_format($data->cmomar, 2);
      if ($cmomar < 1) {
        $cmomar = '-';
      }

      $cmoapr = number_format($data->cmoapr, 2);
      if ($cmoapr < 1) {
        $cmoapr = '-';
      }
      $cmomay = number_format($data->cmomay, 2);
      if ($cmomay < 1) {
        $cmomay = '-';
      }
      $cmojun = number_format($data->cmojun, 2);
      if ($cmojun < 1) {
        $cmojun = '-';
      }
      $cmojul = number_format($data->cmojul, 2);
      if ($cmojul < 1) {
        $cmojul = '-';
      }
      $cmoaug = number_format($data->cmoaug, 2);
      if ($cmoaug < 1) {
        $cmoaug = '-';
      }
      $cmosep = number_format($data->cmosep, 2);
      if ($cmosep < 1) {
        $cmosep = '-';
      }
      $cmooct = number_format($data->cmooct, 2);
      if ($cmooct < 1) {
        $cmooct = '-';
      }
      $cmonov = number_format($data->cmonov, 2);
      if ($cmonov < 1) {
        $cmonov = '-';
      }
      $cmodec = number_format($data->cmodec, 2);
      if ($cmodec < 1) {
        $cmodec = '-';
      }

      $tsales = $data->smojan + $data->smofeb + $data->smomar + $data->smoapr + $data->smomay + $data->smojun + $data->smojul + $data->smoaug + $data->smosep + $data->smooct + $data->smonov + $data->smodec;
      $tcollection = $data->cmojan + $data->cmofeb + $data->cmomar + $data->cmoapr + $data->cmomay + $data->cmojun + $data->cmojul + $data->cmoaug + $data->cmosep + $data->cmooct + $data->cmonov + $data->cmodec;

      if ($linecounter === $count) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page += $count;
        $linecounter = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<b>' . $data->clientname . '</b>', '96', null, false, $dotted, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smojan, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmojan, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smofeb, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmofeb, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smomar, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmomar, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smoapr, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmoapr, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smomay, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmomay, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smojun, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmojun, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smojul, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmojul, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smoaug, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmoaug, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smosep, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmosep, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smooct, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmooct, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smonov, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmonov, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col($smodec, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($cmodec, '61', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', '', '', $dotted, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col(number_format($tsales, 2), '71', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($tcollection, 2), '71', null, false, $dotted, 'B', 'RT', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      //sales
      $totalmojan = $totalmojan + $data->smojan;
      $totalmofeb = $totalmofeb + $data->smofeb;
      $totalmomar = $totalmomar + $data->smomar;
      $totalmoapr = $totalmoapr + $data->smoapr;
      $totalmomay = $totalmomay + $data->smomay;
      $totalmojun = $totalmojun + $data->smojun;
      $totalmojul = $totalmojul + $data->smojul;
      $totalmoaug = $totalmoaug + $data->smoaug;
      $totalmosep = $totalmosep + $data->smosep;
      $totalmooct = $totalmooct + $data->smooct;
      $totalmonov = $totalmonov + $data->smonov;
      $totalmodec = $totalmodec + $data->smodec;

      //collection

      $totalcmojan = $totalcmojan + $data->cmojan;
      $totalcmofeb = $totalcmofeb + $data->cmofeb;
      $totalcmomar = $totalcmomar + $data->cmomar;
      $totalcmoapr = $totalcmoapr + $data->cmoapr;
      $totalcmomay = $totalcmomay + $data->cmomay;
      $totalcmojun = $totalcmojun + $data->cmojun;
      $totalcmojul = $totalcmojul + $data->cmojul;
      $totalcmoaug = $totalcmoaug + $data->cmoaug;
      $totalcmosep = $totalcmosep + $data->cmosep;
      $totalcmooct = $totalcmooct + $data->cmooct;
      $totalcmonov = $totalcmonov + $data->cmonov;
      $totalcmodec = $totalcmodec + $data->cmodec;

      //Gtotalsales and Gtotalcollection jan-dec
      $totalsaless = $totalsaless + $tsales;
      $totalcoll = $totalcoll + $tcollection;

      $linecounter++;
    //   if ($this->reporter->linecounter == $page) {
    //     $str .= $this->reporter->page_break();
    //     $str .= $this->displayHeader($config);
    //     $page = $page + $count;
    //   }
    }



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(' ', '1960', '30', false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //total sales per month
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL ', '96', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($totalmojan, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmofeb, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmomar, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmoapr, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmomay, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');


    $str .= $this->reporter->col(number_format($totalmojun, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmojul, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmoaug, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmosep, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmooct, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmonov, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalmodec, 2), '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '61', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'T', 'C', $font, $fontsize, '', '', '');

    // total sales jan-dec
    $str .= $this->reporter->col(number_format($totalsaless, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //total collection  per month   //rowen-03-15-2024
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '96', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmojan, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmofeb, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmomar, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmoapr, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmomay, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmojun, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmojul, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmoaug, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmosep, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmooct, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmonov, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '61', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcmodec, 2), '61', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    // total  collection jan-dec
    $str .= $this->reporter->col('', '71', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcoll, 2), '71', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
} //endclass