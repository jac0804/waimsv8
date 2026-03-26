<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;

class viewprinfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;
  private $sqlquery;
  private $logger;

  public $modulename = 'View PR Info:';
  public $gridname = 'inventory';
  private $fields = [];
  private $table = '';

  public $tablelogs = 'table_log';
  public $style = 'width:100%;max-width:80%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
  }

  public function createHeadField($config)
  {
    $fields = [['lblctrlno', 'ctrlno'], ['lblitemname', 'itemdesc'], ['lblspecs', 'specs'], ['lbluom', 'uom'], ['lblrequestor', 'requestorname']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'ctrlno.readonly', true);
    data_set($col1, 'itemdesc.readonly', true);
    data_set($col1, 'itemdesc.label', '');
    data_set($col1, 'specs.label', '');
    data_set($col1, 'uom.label', '');
    data_set($col1, 'requestorname.label', '');
    data_set($col1, 'itemdesc.type', 'textarea');
    data_set($col1, 'itemdesc.height', '1rem');
    data_set($col1, 'specs.readonly', true);
    data_set($col1, 'uom.readonly', true);
    data_set($col1, 'requestorname.readonly', true);
    data_set($col1, 'requestorname.type', 'textarea');
    $fields = [['lbldepartment', 'deptname'], ['lblprojname', 'clientname'], ['lblpono', 'podesc'], ['lblpodocno', 'podocno']];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'deptname.readonly', true);
    data_set($col2, 'deptname.label', '');
    data_set($col2, 'clientname.label', '');
    data_set($col2, 'podesc.label', '');
    data_set($col2, 'podesc.readonly', true);
    data_set($col2, 'podesc.type', 'textarea');
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {

    $trno = $config['params']['row']['reqtrno'];
    $line = $config['params']['row']['reqline'];
    $select = "select head.docno,hc.docno,hpo.docno as podocno,info.ctrlno,info.itemdesc,ifnull(info.specs,'') as specs,ifnull(info.unit,'') as uom,
        ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as deptname,head.clientname,
        ifnull(po.sano,'') as podesc from hprhead as head
        left join hprstock as stock on stock.trno=head.trno
        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join client as dept on dept.clientid = head.deptid
        left join clientsano as po on po.line=head.pono
        left join hcdstock as hcd on hcd.reqtrno=stock.trno and hcd.reqline=stock.line
        left join hcdhead as hc on hc.trno=hcd.trno
        left join hpostock as hpos on hpos.cdrefx=hcd.trno and hpos.cdlinex=hcd.line
        left join hpohead as hpo on hpo.trno=hpos.trno
        where  stock.trno=$trno and stock.line=$line";
    $data = $this->coreFunctions->opentable($select);
    return $data;
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }
}
