<?php

namespace App\Http\Classes\modules\dashboard;

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

class ts
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TS TRANSACTION';
  public $gridname = 'customformlisting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'status', 'dateid', 'docno', 'listsource', 'clientname', 'bal',  'rem']
      ]
    ];

    $stockbuttons = [];
    // 'action'
    $stockbuttons = ['jumpmodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][6]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][6]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][4]['label'] = 'Source WH';
    $obj[0][$this->gridname]['columns'][5]['label'] = 'Destination WH';


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'rr');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $doc = $config['params']['lookupclass'];
    $classid = $config['params']['classid'];
    switch ($classid) {
      case 'posted':
        $this->modulename = 'POSTED - TS TRANSACTION';
        break;
      case 'unposted':
        $this->modulename = 'UNPOSTED - TS TRANSACTION';
        break;
    }
    return $this->coreFunctions->opentable("select left(now(),10) as dateid, 0.0 as ext,? as classid, '" . $doc . "' as doc ", [$classid]);
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['dataparams']['doc'];
    $url = $this->checkdoc($doc, $companyid);
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $classid = $config['params']['dataparams']['classid'];
    switch ($classid) {
      case 'posted':
        $qry = "select trno,  doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype,
            format(sum(ext),2) as bal,clientname,source,
            rem, status from (select `cntnum`.`doc` as `doc`,glhead.`docno`,`glhead`.`trno` as `trno`,
            `glhead`.`dateid` as `dateid`,stock.ext,
            (`glhead`.`rem`) as `rem`,client.clientname,wh.clientname as source,
            `glhead`.`ourref` as `reference`,'POSTED' as `status` from `glhead`
            left join `cntnum` on `cntnum`.`trno` = `glhead`.`trno` left join glstock as stock on stock.trno = glhead.trno 
            left join client as wh on wh.clientid = glhead.whid left join client on client.clientid = glhead.clientid
            where cntnum.doc='TS' and  glhead.dateid='$date'
            and cntnum.center = '$center'
            ) as t  group by trno,  doc, docno,dateid,rem,status,clientname,source order by dateid desc,docno";
        break;
      case 'unposted':
        $qry = "select trno,  doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 'DBTODO' as tabtype, '$url' as url,
        'module' as moduletype,
        format(sum(ext),2) as bal,clientname,source,
         rem, status from (select `cntnum`.`doc` as `doc`,lahead.`docno`,`lahead`.`trno` as `trno`,
        `lahead`.`dateid` as `dateid`,stock.ext,
        (`lahead`.`rem`) as `rem`,client.clientname,wh.clientname as source,
        `lahead`.`ourref` as `reference`,'UNPOSTED' as `status` from `lahead`
        left join `cntnum` on `cntnum`.`trno` = `lahead`.`trno` left join lastock as stock on stock.trno = lahead.trno 
        left join client as wh on wh.client = lahead.wh left join client on client.client = lahead.client
        where cntnum.doc='TS' and  lahead.dateid ='$date' and cntnum.center = '$center'
        ) as t  group by trno,  doc, docno,dateid,rem,status,clientname,source order by dateid desc,docno";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
  public function checkdoc($doc, $companyid)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'ts':
        $folderloc = 'inventory';
        $url = "/module/" . $folderloc . "/";
        break;
    }
    return $url;
  }
} //end class
