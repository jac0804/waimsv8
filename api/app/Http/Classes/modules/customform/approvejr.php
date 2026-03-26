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

class approvejr
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Approval Form';
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
    $allowapprove = $this->othersClass->checkAccess($config['params']['user'], 2444);
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['radiostatus', 'rqty', 'rrqty', 'uom', 'subactid', 'subactivity', 'itemname']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = ['itemname', 'barcode'];
    $obj[0][$this->gridname]['columns'][2]['label'] = "Approve Qty";

    if ($allowapprove == '0') {
      $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    }


    $obj[0][$this->gridname]['columns'][4]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['style'] = "text-align:left;width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
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
    data_set($col1, 'refresh.action', 'approvejr');
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
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
    }

    $qry = "select head.trno,stock.line,head.dateid,head.docno,p.name as projectname,
                  head.doc,item.barcode,item.itemname,
                  FORMAT(stock.rqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rqty,
                  stock.uom,FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                  stock.refx,stock.linex,stock.status,stock.stageid, sa.subactivity,bq.subactid
            from prhead as head 
            left join projectmasterfile as p on p.line = head.projectid 
            left join prstock as stock on stock.trno = head.trno 
            left join item on item.itemid = stock.itemid 
            left join hsostock as bq on bq.line = stock.linex and bq.trno=stock.refx
            left join subactivity as sa on sa.line = bq.subactivity
            where head.doc='JR' and head.lockdate is not null and stock.status = 0 and 
                  (stock.rrqty-stock.rqty)<>0 and head.trno =?";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $rows = $config['params']['rows'];
    $user = $config['params']['user'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    foreach ($rows as $key) {
      $config['params']['row']['trno'] = $rows[0]['trno'];
      if ($key['status'] == '1' && $key['rrqty'] == 0) {
        $data = $this->data($config);
        return ['status' => false, 'msg' => 'Please enter approve quantity.', 'data' => $data];
      }
      $key['rrqty'] = $this->othersClass->sanitizekeyfield("rrqty", $key['rrqty']);
      $this->coreFunctions->execqry('update prstock set rrqty=?,qty =?,ext=qty*rrcost,status =?,editby=?,editdate=? where trno=? and line=?', 'update', [$key['rrqty'], $key['rrqty'], $key['status'], $user, $current_timestamp, $key['trno'], $key['line']]);
      if (floatval($key['refx']) != 0) {
        $this->setserveditems($key['refx'], $key['linex']);
      }
      $path = 'App\Http\Classes\modules\construction\\jr';
      $config['params']['trno'] = $rows[0]['trno'];
      app($path)->updateprojmngmt($config, $key['stageid']);
    }
    $data = $this->data($config);
    return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data];
  } //end function

  private function setserveditems($refx, $linex)
  {
    $qry1 = "select stock.qty from prhead as head left join prstock as 
      stock on stock.trno=head.trno where head.doc='JR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select hprstock.qty from hprhead left join hprstock on hprstock.trno=
      hprhead.trno where hprhead.doc='JR' and hprstock.refx=" . $refx . " and hprstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }
} //end class
