<?php

namespace App\Http\Classes\modules\tableentry;

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

class viewsupplierinvoicerr
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RR DOCUMENTS';
  public $gridname = 'inventory';
  public $tablelogs = 'table_log';
  private $logger;
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
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2240, 'view' => 2240);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno']]];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    return $this->getdata($trno);
  }

  public function getdata($trno)
  {
    $qry = "select docno, trno from cntnum where svnum=? order by docno";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $docno = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$trno]);

    $row = $config['params']['row'];
    $this->coreFunctions->execqry('update cntnum set svnum=0 where trno=?', 'update', [$row['trno']]);

    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'DELETE RR - ' . $row['docno']);
    $this->logger->sbcwritelog($row['trno'], $config, 'DETAILS', 'UNTAGGED INVOICE - ' . $docno);

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
} //end class
