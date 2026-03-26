<?php

namespace App\Http\Classes\modules\customform;

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

class viewstationinfo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STATION DETAILS';
  public $gridname = 'customformlisting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = false;
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
    $companyid = $config['params']['companyid'];

    $dateid = 0;
    $station = 1;
    $amt = 2;
    $posamt = 3;
    
    $column =['dateid','station', 'amt', 'journalamt','itemname'];   

    $stockbuttons = [];

    $tab = [$this->gridname => ['gridcolumns' => $column]];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // totalfield
    $obj[0][$this->gridname]['totalfield'] = ['amt', 'journalamt'];

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$posamt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$amt]['style'] =  "text-align:right;width:180px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][$posamt]['style'] =  "text-align:right;width:180px;whiteSpace: normal;min-width:180px;";



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
    $fields = ['client','refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1,'client.type','input');
    data_set($col1,'client.readonly',true);
    data_set($col1,'client.label','Branch');

    $fields = ['dateid'];
    $col2 = $this->fieldClass->create($fields);
    
    $fields = ['amt'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3,'amt.label','Transaction Amount');

    $fields = ['journalamt'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $client = $config['params']['addedparams'][0];
    $dateid = date('Y-m-d',strtotime($config['params']['addedparams'][1]));
    return $this->coreFunctions->opentable("select  0.0 as amt, 0.0 as journalamt,'".$client."' as client,'".$dateid."' as dateid");
  }

  public function data($config)
  {
    return [];
  }

  public function loaddata($config){
    $branch = $config['params']['dataparams']['client'];
    $dateid = date('Y-m-d',strtotime($config['params']['dataparams']['dateid']));

    $branchfilter ="";
    if ($branch !=""){
      $branchfilter = " and head.branch ='". $branch."'";
    }

    $qry = "select left(dateid,10) as dateid,station,format(sum(amt),2) as amt,format(sum(journalamt),2) as journalamt,'' as itemname from
    (select head.dateid,head.station,sum(head.amt) as amt,0 as journalamt from
    lahead as la left join head on head.webtrno = la.trno where head.doc ='BP' and date(head.dateid) ='".date('Y-m-d',strtotime($config['params']['dataparams']['dateid']))."'  ".$branchfilter." group by dateid,station
        union all
        select dateid,station,0 as amt,sum(amt) as journalamt from journal as head where isok2=0 and date(dateid) ='".date('Y-m-d',strtotime($config['params']['dataparams']['dateid']))."'  ".$branchfilter." group by dateid,station) as a group by a.dateid,a.station
    ";

    $data = $this->coreFunctions->opentable($qry);

    $qry = "select '".$dateid."' as dateid,'".$branch."' as client,sum(amt) as amt,sum(journalamt) as journalamt from
    (select head.dateid,head.station,sum(head.amt) as amt,0 as journalamt from
    lahead as la left join head on head.webtrno = la.trno where head.doc ='BP' and date(head.dateid) ='".date('Y-m-d',strtotime($config['params']['dataparams']['dateid']))."'  ".$branchfilter."  group by head.dateid,head.station
        union all
        select dateid,station,0 as amt,sum(amt) as journalamt from journal as head where isok2=0 and date(dateid) ='".date('Y-m-d',strtotime($config['params']['dataparams']['dateid']))."'  ".$branchfilter." group by head.dateid,head.station) as a
    ";

    $data2 = $this->coreFunctions->opentable($qry);
   
    return ['status'=>true,'msg'=> 'Successfully loaded.','data'=>$data,'txtdata'=>$data2];
  }
} //end class
