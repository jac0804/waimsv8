<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewwhinv
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INVENTORY';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
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
        'gridcolumns' => ['itemname', 'barcode', 'bal', 'uom']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];
    
    $obj[0][$this->gridname]['columns'][0]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][0]['label'] = 'Itemname';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';

    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][2]['style'] = 'text-align:right;width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'text-align:center;width:100px;whiteSpace: normal;min-width:100px;';
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
    data_set($col1, 'dateid.label', 'Start Date');

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'pdc');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date =  date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));

    $qry = "select item.barcode,item.itemname,round(sum(bal),2) as bal,rrstatus.uom
            from rrstatus
            left join client on client.clientid = rrstatus.whid
            left join item on item.itemid=rrstatus.itemid
            left join cntnum on cntnum.trno=rrstatus.trno
            where cntnum.center='$center' and client.clientid = '$clientid' and rrstatus.dateid>='$date'
            group by item.barcode,item.itemname,rrstatus.uom,rrstatus.itemid having sum(bal)<>0";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
