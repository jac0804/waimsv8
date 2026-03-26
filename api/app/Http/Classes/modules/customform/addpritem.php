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

class addpritem
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Available Stocks';
  public $gridname = 'editgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = false;
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';



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
        'gridcolumns' => ['rrqty','rqty','cost', 'bal','docno', 'itemname']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = ['itemname', 'barcode'];
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][4]['type'] = "label";
    $obj[0][$this->gridname]['columns'][5]['type'] = "label";
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
    $fields = ['refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action', 'approvepr');
    data_set($col1, 'refresh.label', 'SAVE');

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $qry = " ";
    $data = [];
    return [];
  }

  public function data($config)
  {
    $center = $config['params']['center'];
    $row = isset($config['params']['row']) ? $config['params']['row'] : $config['params']['rows'];
    $wh = $row['source'];
    $project = $row['projectid'];
    $subproject = $row['subproject'];
    $rrqty = $row['rrqty'];
    $itemid = $row['itemid'];
    $prtrno = $row['trno'];
    $prline = $row['line'];
    $stageid = $row['stageid'];
    $prref = $row['docno'];
    $uom = $row['uom'];
    $trno = $row['mtrno'];

    $qry = "select stock.trno,stock.line,item.itemid,item.itemname,head.docno,
                  left(head.dateid,10) as dateid,item.barcode,".$rrqty." as rqty,
                  0 as rrqty,
                  FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
                  FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
                  FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                  FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
                  stock.loc,head.yourref,stock.rem,stat.bal,".$prtrno." as prtrno,".$prline." as prline,'".$uom."' as uom,'".$stageid."' as stageid,
                  '".$prref."' as prref,".$project." as projectid,".$subproject." as subproject,".$trno." as mttrno,'".$wh."' as source
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client as wh on wh.clientid=stock.whid
            left join item on item.itemid = stock.itemid
            left join rrstatus as stat on stat.trno= head.trno and stat.itemid = stock.itemid and stat.line=stock.line            
            where head.doc = 'RR' and item.itemid = ".$itemid." and wh.client = '" . $wh . "' and cntnum.center = '" . $center . "'   and stock.void = 0 and stat.bal > 0 
            group by head.docno,head.clientname,stock.trno,head.dateid,stock.rrqty,stock.qty,stock.rrcost,
           stock.ext,wh.client,stock.qa,stock.loc,stock.line,stock.itemid,item.itemname,item.barcode,
            stock.stageid,stock.cost,stat.bal,head.yourref,stock.rem,stock.ref,stock.refx,stock.linex,item.itemid
            ";
    
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $rows = $config['params']['rows'];
    $user = $config['params']['user'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $path = 'App\Http\Classes\modules\construction\\mt';

    foreach ($rows as $key) {
      $trno = $key['mttrno'];
      
      if ($key['rrqty'] != 0) {
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['uom'] = $key['uom'];
        $config['params']['data']['itemid'] = $key['itemid'];
        $config['params']['data']['disc'] = '';
        $config['params']['data']['qty'] = $key['rrqty'];
        $config['params']['data']['wh'] = $key['wh'];
        $config['params']['data']['loc'] =  $key['loc'];
        $config['params']['data']['expiry'] = '';
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $key['prtrno'];
        $config['params']['data']['linex'] = $key['prline'];
        $config['params']['data']['rrrefx'] = $key['trno'];
        $config['params']['data']['rrlinex'] = $key['line'];
        $config['params']['data']['ref'] =$key['prref'];
        $config['params']['data']['amt'] = $key['rrcost'];
        $config['params']['data']['stageid'] =  $key['stageid'];
        $config['params']['trno'] = $trno;
        $return = app($path)->additem('insert', $config);

        if ($return['status']) {
          if (app($path)->setservedpritems($key['prtrno'], $key['prline']) == 0) {
            $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
            app($path)->setservedpritems($key['prtrno'], $key['prline']);
          }
        }
      }
    }

    //$data = $this->data($config);
    return ['status' => true, 'msg' => 'Successfully Added.', 'data' => []];
  } //end function

} //end class
