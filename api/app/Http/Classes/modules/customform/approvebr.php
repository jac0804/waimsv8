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

class approvebr
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
    $allowapprove = $this->othersClass->checkAccess($config['params']['user'], 2270);
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['radiostatus', 'particulars', 'qty', 'uom', 'rrcost', 'ext', 'amount']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = ['particulars', 'qty'];
    $obj[0][$this->gridname]['columns'][0]['style'] = "text-align:left;width:40px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "text-align:left;width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['label'] = "Quantity";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['type'] = "input";
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
    $obj[0][$this->gridname]['columns'][4]['label'] = "Estimated Amount";
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][5]['label'] = "Total Amount";
    $obj[0][$this->gridname]['columns'][5]['type'] = "input";
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][6]['label'] = "Approved Budget";
    $obj[0][$this->gridname]['columns'][6]['style'] = "text-align:right;width:40px;whiteSpace: normal;min-width:90px;";


    if ($allowapprove == '0') {
      $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    }
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
    data_set($col1, 'refresh.action', 'approvebr');
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

    $qry = "select head.trno,stock.line,head.dateid,head.docno,p.name as project,'Budget Request' as clientname,head.doc,
    stock.particulars,stock.uom,FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,FORMAT(stock.ext," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext,FORMAT(stock.amount," . $this->companysetup->getdecimal('price', $config['params']) . ") as amount,case stock.approvedby when '' then false else true end as isapprove,stock.status 
    from brhead as head left join projectmasterfile as p on p.line = head.projectid 
    left join brstock as stock on stock.trno = head.trno where head.doc='BR' and head.lockdate is not null and stock.status =0 
     and head.trno =?";

    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $rows = $config['params']['rows'];
    $user = $config['params']['user'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    foreach ($rows as $key) {
      $amount = $this->othersClass->sanitizekeyfield("amount", $key['amount']);
      $this->coreFunctions->execqry('update brstock set amount=?, editby = ?,editdate=? where trno=? and line=?', 'update', [$amount, $user, $current_timestamp, $key['trno'], $key['line']]);
      $this->coreFunctions->execqry('update brstock set status = ?,approvedby =?,approveddate=? where trno=? and line=?', 'update', [$key['status'], $user, $current_timestamp, $key['trno'], $key['line']]);
    }
    $config['params']['row']['trno'] = $rows[0]['trno'];
    $data = $this->data($config);
    return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data];
  } //end function

































} //end class
