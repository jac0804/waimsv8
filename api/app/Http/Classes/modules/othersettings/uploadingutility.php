<?php

namespace App\Http\Classes\modules\othersettings;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Datetime;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;
use Hamcrest\Type\IsNumeric;
use Symfony\Component\VarDumper\VarDumper;
use App\Http\Classes\sbcscript\sbcscript;

class uploadingutility
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Uploading Utility';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  private $logger;
  private $sbcscript;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array('view' => 2584, 'save' => 2584);
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {

    return [];
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function sbcscript($config)
  {
    return $this->sbcscript->uploadingutility($config);
  }

  public function createHeadField($config)
  {
    $fields = ['optionuploading'];

    if ($this->companysetup->getisupdatabasetable($config['params'])) {
      array_push($fields, 'clientname');
    }

    array_push($fields, 'uploadexcel');

    $col1 = $this->fieldClass->create($fields);
    switch ($config['params']['companyid']) {
      case 50: //unitech
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
        ));
        break;
      case 47: //kstar
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
        ));
        break;
      case 40: //cdo
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Upload PNP/CSR', 'value' => 'updatepnpcsr', 'color' => 'green']
        ));
        break;
      case 16:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Item', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Fixed Asset', 'value' => 'newfams', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Employees', 'value' => 'newemployee', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Fixed Asset', 'value' => 'updatefams', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Upload Item Issuance (FAMS)', 'value' => 'newitemissuance', 'color' => 'green']
        ));
        break;
      case 6:
        data_set($col1, 'optionuploading.options', array(['label' => 'Update Price', 'value' => 'updateprice', 'color' => 'green']));
        break;

      case 10:
      case 12:
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'Upload New Item', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Upload Price List Php', 'value' => 'uploadpricephp', 'color' => 'green'],
          ['label' => 'Upload Price List USD', 'value' => 'uploadpriceusd', 'color' => 'green'],
          ['label' => 'Upload Transfer Price USD', 'value' => 'uploadtpusd', 'color' => 'green'],
          ['label' => 'Upload New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'Upload New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'Upload Address', 'value' => 'uploadaddress', 'color' => 'green'],
          ['label' => 'Upload Contact Person', 'value' => 'uploadcontact', 'color' => 'green'],
        ));
        break;

      case 14:
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Prices', 'value' => 'updateprice', 'color' => 'green']
        ));
        break;

      case 19:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New Agent', 'value' => 'newagent', 'color' => 'green'],
          ['label' => 'New Employees', 'value' => 'newemployee', 'color' => 'green'],
          ['label' => 'New Truck', 'value' => 'newtruck', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
          ['label' => 'New Supplier Items', 'value' => 'newsupplieritems', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Agent', 'value' => 'updateagent', 'color' => 'green'],
          ['label' => 'Update Employee', 'value' => 'updateemployee', 'color' => 'green'],
          ['label' => 'Update Items Price List', 'value' => 'updateprice', 'color' => 'green'],
        ));
        break;

      case 21:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New Agent', 'value' => 'newagent', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Items Price List', 'value' => 'updateprice', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Agent', 'value' => 'updateagent', 'color' => 'green']
        ));
        break;

      case 27: //NTE
      case 36: //ROZLAB
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Default UOM', 'value' => 'updateuom', 'color' => 'green']
        ));
        data_set($col1, 'uploadexcel.access', 'save');
        break;

      case 28:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New Agent', 'value' => 'newagent', 'color' => 'green'],
          ['label' => 'New Employees', 'value' => 'newemployeepayroll', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Agent', 'value' => 'updateagent', 'color' => 'green']
        ));
        break;

      case 35:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Meter', 'value' => 'newmeter', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'Update Meter', 'value' => 'updatemeter', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
        ));
        break;

      case 37:
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Upload UOM Price', 'value' => 'newuom', 'color' => 'green']
        ));
        break;

      case 43: //mighty
        data_set($col1, 'optionuploading.options',  array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New Agent', 'value' => 'newagent', 'color' => 'green'],
          ['label' => 'New Employees', 'value' => 'newemployeepayroll', 'color' => 'green'],
          ['label' => 'New UOM', 'value' => 'newuom', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Update Agent', 'value' => 'updateagent', 'color' => 'green'],
          ['label' => 'Update Employee', 'value' => 'updateemployeepayroll', 'color' => 'green']
        ));
        break;

      case 56: //homeworks
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
          ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
          ['label' => 'New Branch', 'value' => 'newbranch', 'color' => 'green'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
          //['label' => 'Update AIMS ID', 'value' => 'updateaimsid', 'color' => 'green'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
          ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green'],
          ['label' => 'Upload Price List', 'value' => 'updatepricelist', 'color' => 'green']
        ));
        break;

      case 58: //cdo-hris
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'New Employees', 'value' => 'newemployeepayroll', 'color' => 'green'],
          ['label' => 'Update Employees', 'value' => 'updateemployeepayroll', 'color' => 'green'],
          ['label' => 'Upload Rates', 'value' => 'updateemployeerate', 'color' => 'green'],
          ['label' => 'Upload Code of Conduct', 'value' => 'uploadcoc', 'color' => 'green']
        ));
        break;

      case 62: //onesky
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'New Employees', 'value' => 'newemployeepayroll', 'color' => 'green'],
          ['label' => 'Update Employees', 'value' => 'updateemployeepayroll', 'color' => 'green'],
          ['label' => 'Upload Rates', 'value' => 'updateemployeerate', 'color' => 'green'],
          ['label' => 'Upload Allowance', 'value' => 'newallowance', 'color' => 'green']
        ));
        break;

      case 60: //transpower
        data_set($col1, 'optionuploading.options', array(
          ['label' => 'New Items', 'value' => 'newitem', 'color' => 'primary'],
          ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'primary'],
          ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'primary'],
          ['label' => 'New Agent', 'value' => 'newagent', 'color' => 'primary'],
          ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'primary'],
          ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'primary'],
          ['label' => 'Update Agent', 'value' => 'updateagent', 'color' => 'primary'],
          ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'primary']
        ));
        break;
    }

    if ($this->companysetup->getisupdatabasetable($config['params'])) {
      array_push($col1["optionuploading"]["options"], ['label' => 'Upload Database Table', 'value' => 'uploaddbtable', 'color' => 'green']);

      data_set($col1, 'clientname.label', 'Table Name');
      data_set($col1, 'clientname.readonly', false);
    }

    data_set($col1, 'uploadexcel.access', 'save');

    $col2 = [];
    switch ($config['params']['companyid']) {
      case 14:
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwhexcel', 'downloadsupplierexcel', 'downloadrrexcel', 'downloadpcexcel', 'downloaduomexcel', 'downloadpricelistexcel'];
        break;
      case 10:
      case 12:
        $fields = ['downloaditemexcel', 'dlexcelpricelistphp', 'dlexcelpricelistusd', 'dlexcelpricelisttp', 'downloadcustomerexcel', 'downloadsupplierexcel', 'downloadcontact', 'downloadaddress'];
        break;

      case 6:
        $fields = [];
        break;

      case 16:
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloademployeeexcel', 'downloadsupplierexcel'];
        break;

      case 35:
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwnexcel'];
        break;

      case 43: //mighty
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwhexcel', 'downloadsupplierexcel', 'downloademployeeexcel'];
        break;
      case 47: //kstar
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwhexcel', 'downloadsupplierexcel', 'downloadcustomerexcelmaster', 'downloaditemexcelmaster'];
        break;

      case 58: //cdo-hris
      case 62: //onesky
        $fields = [];
        break;

      case 56; //homeworks
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwhexcel', 'downloadsupplierexcel', 'downloadpricelistexcel'];
        break;

      case 60: //transpower
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadsupplierexcel', 'downloadagentexcel', 'downloaditemexcelmaster'];
        break;

      default:
        $fields = ['downloaditemexcel', 'downloadcustomerexcel', 'downloadwhexcel', 'downloadsupplierexcel'];
        break;
    }

    switch ($config['params']['companyid']) {
      case 19:
        array_push($fields, 'downloadagentexcel', 'downloademployeeexcel', 'downloadsupplieritemexcel');
        break;
      case 28:
        array_push($fields, 'downloadagentexcel', 'downloademployeeexcel');
        break;

      case 27: //NTE
      case 36: //ROZLAB
        array_push($fields, 'downloaduomexcel');
        break;
      case 23:
      case 41:
      case 52: //technolab
        array_push($fields, 'downloaditemexcelmaster');
        break;
      case 40: //cdo
        array_push($fields, 'downloadpnpcsrexcelmaster');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 35) data_set($col2, "downloaditemexcel.label", "DONWLOAD METER TEMPLATE");

    $fields = [];
    switch ($config['params']['companyid']) {
      case 19: //housegem
        array_push($fields, 'downloaditemexcelmaster', 'downloaduomexcelmaster', 'downloadcustomerexcelmaster', 'downloademployeeexcelmaster', 'downloadpricelistexcelmaster');
        break;
      case 37: //mega crystal
        array_push($fields, 'downloaditemuomexcelmaster');
        break;
      case 21; //kinggeorge
        array_push($fields, 'downloaditemexcelmaster', 'downloaduomexcelmaster', 'downloadpricelistexcelmaster', 'downloadcustomerexcelmaster');
        break;
      case 50: //unitech
        array_push($fields, 'downloaditemexcelmaster');
        break;
      case 56: //homeworks
        $fields = ['update'];
        break;
    }

    $col3 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 56) {
      data_set($col3, "update.label", "UPDATE PRICE LIST");
      data_set($col3, "update.action", "updatepricelist");
      data_set($col3, "update.confirmlabel", "Do you want to update item price?");
      data_set($col3, "update.confirm", true);
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("select 'newitem' as utype, '' as clientname");
    return $data[0];
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {

    $action = $config['params']["action2"];
    switch ($action) {
      case 'uploadexcel':
        $type = $config['params']['dataparams']['utype'];
        switch ($type) {
          case 'newitem':
            return ['status' => true, 'msg' => 'Upload finished.'];
            break;
          default:
            return ['status' => false, 'msg' => 'Invalid uploading type.'];
            break;
        }
        break;
      case 'updatepricelist':
        return $this->updateItemPrice($config['params']);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  public function stockstatusposted($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $action = $config['params']["action"];
    switch ($action) {
      case 'uploadexcel':

        switch ($config['params']['dataparams']['utype']) {
          case 'uploadpricephp':
          case 'uploadpriceusd':
          case 'uploadtpusd':
            return $this->updatepricefromexcel($config);
            break;
          case 'updatepnpcsr':
            return $this->updatepnpcsr($config);
            break;
          case 'newitemissuance':
            return $this->uploadtransaction($config['params']['dataparams']['utype'], $config['params']['data'], $config);
            break;
          default:
            return $this->insertdatafromexcel($config['params']['dataparams']['utype'], $config['params']['data'], $config);
        }
        break;
      case 'downloaditemexcel':
      case 'downloaditemexcelmaster':
      case 'downloaditemuomexcelmaster':
      case 'downloaduomexcelmaster':
      case 'downloadcustomerexcel':
      case 'downloadcustomerexcelmaster':
      case 'downloademployeeexcel':
      case 'downloademployeeexcelmaster':
      case 'downloadwhexcel':
      case 'downloadsupplierexcel':
      case 'downloadsupplieritemexcel':
      case 'downloadagentexcel':
      case 'downloadcontact':
      case 'downloadaddress':
      case 'downloadrrexcel':
      case 'downloadpcexcel':
      case 'downloaduomexcel':
      case 'downloadpricelistexcel':
      case 'downloadpricelistexcelmaster':
      case 'downloadwnexcel':
      case 'downloadpnpcsrexcelmaster':
        $result = $this->setupexceltemplate($config);
        $result['filename'] = str_replace("download", "", $action) . 'Template';
        return $result;
        break;
      case 'dlexcelpricelistphp':
        return ['status' => true, 'msg' => 'PHP template ready to Download.', 'name' => 'item', 'data' => [['barcode' => '', 'phpamt' => '']]];
        break;
      case 'dlexcelpricelistusd':
        return ['status' => true, 'msg' => 'USD template ready to Download.', 'name' => 'item', 'data' => [['barcode' => '', 'usdamt' => '']]];
        break;
      case 'dlexcelpricelisttp':
        return ['status' => true, 'msg' => 'Transfer Price template ready to Download.', 'name' => 'item', 'data' => [['barcode' => '', 'tpamt' => '']]];
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $action . ')'];
        break;
    } // end switch
  }


  public function setupexceltemplate($config)
  {
    ini_set('memory_limit', '-1');

    $action = $config['params']['action'];
    switch ($action) {
      case 'downloaditemexcel':
        switch ($config['params']['companyid']) {
          case 10:
          case 12:
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [[
                'partno' => '',
                'pref' => '',
                'itemname' => '',
                'project' => '',
                'uom' => '',
                'brand' => '',
                'model' => '',
                'description' => '',
                'accessories' => '',
                'isoutsource' => '',
                'isservice' => '',
                'isserial' => '',
                'noninventory' => ''
              ]]
            ];
            break;

          case 14:
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'ShortDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Bin' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Department' => '',
                  'Generic' => '',
                  'Principal' => '',
                  'Vatable' => ''
                ]
              ]
            ];
            break;

          case 16:
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Specs' => '',
                  'IsGeneric' => '',
                  'IsAsset' => '',
                  'SKU' => '',
                  'Department' => '',
                  'SerialNo' => ''
                ]
              ]
            ];
            break;

          case 19:
            return [
              'status' => true,
              'msg' => 'Item Master ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'ASSET' => '',
                  'EXPENSE' => '',
                  'REVENUE' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Group' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Wholesale' => '',
                  'Price_A' => '',
                  'Price_B' => '',
                  'Price_C' => '',
                  'Price_D' => '',
                  'Discount1' => '',
                  'ActualWeight' => '',
                  'InActive' => '',
                  'IsOfficeSupplies' => '',
                  'NonInventory' => '',
                  'IsImport' => ''
                ]
              ]
            ];
            break;

          case 35:
            return [
              'status' => true,
              'msg' => 'Meter template ready to Download',
              'name' => 'meter',
              'data' => [
                [
                  'MeterNo' => '',
                  'Project' => '',
                  'Address' => ''
                ]
              ]
            ];
            break;
          case 39: // CBBSI
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Bin' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Groupid' => '',
                  'Price1' => '',
                  'baseprice' => '',
                  'Discount1' => '',
                  'noninventory' => ''
                ]
              ]
            ];
            break;
          case 22:
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Class' => '',
                  'Brand' => '',
                  'Category 1' => '',
                  'Category 2' => '',
                  'Category 3' => '',
                  'Size' => '',
                  'Color' => '',
                  'PartNo' => '',
                  'Model' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => ''
                ]
              ]
            ];
            break;
          case 43: //mighty
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Asset/Truck' => ''
                ]
              ]
            ];
            break;
          case 40: //cdo
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'PartNo' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Group' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Superseding' => '',
                  'isserial' => ''
                ]
              ]
            ];
            break;
          case 47: //kstar
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'unitofmeasurement' => '',
                  'standard' => '',
                  'distributor' => '',
                  'floorprice' => '',
                  'min' => '',
                  'max' => '',
                  'qty/ctn' => '',
                  'cbm' => ''
                ]
              ]
            ];
            break;
          case 56; //homeworks
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'ShortDescription' => '',
                  'UOM' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'PartNo' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Brand' => '',
                  'Group' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Channel' => '',
                  'Body' => '',
                  'Size' => '',
                  'Color' => '',
                  'Asset' => '',
                  'Liability' => '',
                  'Revenue' => '',
                  'Expense' => '',
                  'IsPOSItem' => '',
                  'IsPrintable' => '',
                  'Vatable' => '',
                  'IsImport' => '',
                  'AveCost' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Price2' => '',
                  'Discount2' => ''
                ]
              ]
            ];
            break;
          case 60: //transpower
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ITEM_CODE' => '',
                  'FULL_ITEM_NAME' => '',
                  'MAIN_CATEGORY' => '',
                  'SUB_CATEGORY' => '',
                  'BRAND' => '',
                  'DESCRIPTION_1' => '',
                  'DESCRIPTION_2' => '',
                  'UNIT' => '',
                  'INVOICE_PRICE' => '',
                  'INVOICE_DISC' => '',
                  'NET_INVOICE' => '',
                  'BASE_PRICE' => '',
                  'BASE_DISC' => '',
                  'WHOLESALE_PRICE' => '',
                  'WHOLESALE_DISC' => '',
                  'NET_WHOLESALE' => '',
                  'COST' => '',
                  'COST_DISC' => '',
                  'NET_COST' => '',
                  'DISTRIBUTOR' => '',
                  'DISTRIBUTOR_DISC' => '',
                  'NET_DISTRIBUTOR' => '',
                  'LOWEST_PRICE' => '',
                  'LOWEST_DISC' => '',
                  'NET_LOWEST' => '',
                  'DR_PRICE' => '',
                  'DR_DISC' => '',
                  'NET_DR' => '',
                  'MINIMUM' => '',
                  'MAXIMUM' => '',
                  'START_WIRE_MTR' => '',
                  'END_WIRE_MTR' => '',
                  'ITEM_WIRE_TAG' => '',
                  'REVERSE_WIRE_TAG' => '',
                  'INACTIVE_ITEM_TAG' => ''
                ]
              ]
            ];
            break;
          case 50: //unitech
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => '',
                  'Price2' => '',
                  'Discount2' => ''
                ]
              ]
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'Item template ready to Download',
              'name' => 'item',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Category' => '',
                  'SubCategory' => '',
                  'Brand' => '',
                  'Model' => '',
                  'Classification' => '',
                  'Color' => '',
                  'Size' => '',
                  'PartNo' => '',
                  'SupplierCode' => '',
                  'SupplierName' => '',
                  'UOM' => '',
                  'Price1' => '',
                  'Discount1' => ''
                ]
              ]
            ];
            break;
        }
        break;

      case 'downloaditemexcelmaster':
        return $this->getitemmaster($config);
        break;

      case 'downloaditemuomexcelmaster':
        return $this->getitemuommaster($config);
        break;

      case 'downloaduomexcelmaster':
        return $this->getuommaster($config);
        break;

      case 'downloademployeeexcelmaster':
      case 'downloadcustomerexcelmaster':
        return $this->getclientmaster($config);
        break;
      case 'downloadpnpcsrexcelmaster':
        return [
          'status' => true,
          'msg' => 'PNP/CSR template ready to Download',
          'name' => 'PNPCSR',
          'data' => [['engineno' => '', 'pnpno' => '', 'csrno' => '', 'datecreate' => '']]
        ];
        break;

      case 'downloaduomexcel':
        switch ($config['params']['companyid']) {
          case 27: //NTE
          case 36: //ROZLAB
            return [
              'status' => true,
              'msg' => 'UOM template ready to Download',
              'name' => 'UOM',
              'data' => [['barcode' => '', 'uom' => '', 'factor' => '', 'isdefault' => '']]
            ];
            break;
          case 21: //kinggeorge
            return [
              'status' => true,
              'msg' => 'UOM template ready to Download',
              'name' => 'UOM',
              'data' => [['barcode' => '', 'uom' => '', 'factor' => '', 'issalesdef' => '']]
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'UOM template ready to Download',
              'name' => 'UOM',
              'data' => [['barcode' => '', 'uom' => '', 'factor' => '']]
            ];
            break;
        }
        break;

      case 'downloadpricelistexcel':
        switch ($config['params']['companyid']) {
          case 56: //homeworks
            return [
              'status' => true,
              'msg' => 'Price List template ready to Download',
              'name' => 'UOM',
              'data' => [
                [
                  'ItemCode' => '',
                  'ItemDescription' => '',
                  'Remarks' => '',
                  'Price1' => '',
                  'Price2' => '',
                  'Cost' => '',
                  'Discount' => '',
                  'StartDate' => '',
                  'EndDate' => '',
                  'BranchCode' => '',
                ]
              ]
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'Price List template ready to Download',
              'name' => 'UOM',
              'data' => [
                [
                  'Barcode' => '',
                  'ItemDescription' => '',
                  'Retail' => '',
                  'Discount_R' => '',
                  'Wholesale' => '',
                  'Discount_W' => '',
                  'Price_A' => '',
                  'Discount_A' => '',
                  'Price_B' => '',
                  'Discount_B' => '',
                  'Price_C' => '',
                  'Discount_C' => '',
                  'Price_D' => '',
                  'Discount_D' => '',
                  'Price_E' => '',
                  'Discount_E' => '',
                  'Price_F' => '',
                  'Discount_F' => '',
                  'Price_G' => '',
                  'Discount_G' => ''
                ]
              ]
            ];
            break;
        }
        break;

      case 'downloadpricelistexcelmaster':
        return $this->getpricelistmaster($config);
        break;

      case 'downloadcustomerexcel';
        switch ($config['params']['companyid']) {
          case 10:
          case 12:
            return [
              'status' => true,
              'msg' => 'Customer template ready to Download',
              'name' => 'customer',
              'data' => [[
                'CustomerCode' => '',
                'CustomerName' => '',
                'customergroup' => '',
                'territory' => '',
                'orgstructure' => '',
                'busstyle' => '',
                'industry' => '',
                'tin' => '',
                'currency' => '',
                'terms' => '',
                'vattype' => '',
                'CreditLimit' => '',
                'creditdaysbasedon' => '',
                'creditdays' => '',
                'activity' => '',
                'notes' => ''
              ]]
            ];
            break;
          case 19: //housegem
            return [
              'status' => true,
              'msg' => 'Customer template ready to Download',
              'name' => 'customer',
              'data' => [[
                'CustomerCode' => '',
                'CustomerName' => '',
                'Address' => '',
                'Address2' => '',
                'ShippingAddress' => '',
                'TelephoneNumber' => '',
                'FaxNumber' => '',
                'Email' => '',
                'ContactPerson' => '',
                'Area' => '',
                'Province' => '',
                'Region' => '',
                'CreditLimit' => '',
                'Category' => '',
                'OwnerName' => '',
                'OwnerContact' => '',
                'BusinessType' => '',
                'TIN' => '',
                'Terms' => '',
                'PriceGroup' => '',
                'Status' => 'ACTIVE'
              ]],
              'filename' => 'customerTemplate'
            ];
            break;
          case 47: //kstar
            return [
              'status' => true,
              'msg' => 'Customer template ready to Download',
              'name' => 'customer',
              'data' => [[
                'CustomerCode' => '',
                'CustomerName' => '',
                'Address' => '',
                'TelephoneNumber' => '',
                'FaxNumber' => '',
                'Email' => '',
                'ContactPerson' => '',
                'Area' => '',
                'Province' => '',
                'Region' => '',
                'CreditLimit' => '',
                'Category' => '',
                'OwnerName' => '',
                'OwnerContact' => '',
                'BusinessType' => '',
                'TIN' => '',
                'Terms' => '',
                'PriceGroup' => '',
                'Agent_name' => '',
                'Trucking' => ''
              ]],
              'filename' => 'customerTemplate'
            ];
            break;
          case 60: //transpower
            return [
              'status' => true,
              'msg' => 'Customer template ready to Download',
              'name' => 'customer',
              'data' => [[
                'CustomerCode' => '',
                'CustomerName' => '',
                'Address' => '',
                'Tin' => '',
                'TelephoneNumber' => '',
                'FaxNumber' => '',
                'Email' => '',
                'ContactPerson' => '',
                'Area' => '',
                'Province' => '',
                'Region' => '',
                'CreditLimit' => '',
                'Terms' => '',
                'AgentCode' => '',
                'GroupID' => '',
                'Start' => '',
                'Status' => '',
                'RegisteredName' => '',
                'Tel2/Other' => ''
              ]],
              'filename' => 'customerTemplate'
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'Customer template ready to Download',
              'name' => 'customer',
              'data' => [[
                'CustomerCode' => '',
                'CustomerName' => '',
                'Address' => '',
                'Address2' => '',
                'TelephoneNumber' => '',
                'FaxNumber' => '',
                'Email' => '',
                'ContactPerson' => '',
                'Area' => '',
                'Province' => '',
                'Region' => '',
                'CreditLimit' => '',
                'Category' => '',
                'OwnerName' => '',
                'OwnerContact' => '',
                'BusinessType' => '',
                'TIN' => '',
                'Terms' => '',
                'PriceGroup' => '',
                'Status' => 'ACTIVE'
              ]],
              'filename' => 'customerTemplate'
            ];
            break;
        }

        break;

      case 'downloadwhexcel':
        return [
          'status' => true,
          'msg' => 'Warehouse template ready to Download',
          'name' => 'warehouse',
          'data' => [[
            'WarehouseCode' => '',
            'WarehouseName' => ''
          ]]
        ];
        break;

      case 'downloademployeeexcel':

        switch ($config['params']['companyid']) {
          case 16:
            $col = [
              'EmployeeNo' => '',
              'EmployeeName' => '',
              'Address' => '',
              'Department' => '',
              'Email' => '',
              'ContactPerson' => '',
              'Mobile' => '',
              'IsPassenger' => '',
              'IsDriver' => '',
              'IsApprover' => ''
            ];
            break;
          case 19:
            $col = [
              'EmployeeCode' => '',
              'EmployeeName' => '',
              'Address' => '',
              'Email' => '',
              'ContactPerson' => '',
              'Mobile' => '',
              'IsChecker' => '',
              'IsDriver' => '',
              'IsHelper' => ''
            ];
            break;
          case 28:
            $col = [
              'EmployeeCode' => '',
              'LastName' => '',
              'FirstName' => '',
              'MiddleName' => '',
              'Address' => '',
              'DivisionCode' => '',
              'Role' => '',
              'ModeOfPayment' => '',
              'ClassRate' => '',
              'Level' => '',
              'PayGroup' => '',
              'Shift' => '',
              'SSS' => '',
              'HDMF' => '',
              'PHIP' => '',
              'TIN' => '',
              'is_SSS' => '',
              'is_HDMF' => '',
              'is_PHIP' => '',
              'is_TIN' => '',
              'Remarks' => '',
              'Birthday' => '',
              'DateHired' => '',
              'BankAccount' => '',
              'Status' => '',
              'Salary' => '',
              'RateEffectivity' => '',
              'Email' => ''
            ];
            break;
          case 43: //mighty
            $col = [
              'EmployeeCode' => '',
              'LastName' => '',
              'FirstName' => '',
              'MiddleName' => '',
              'Address' => '',
              'DivisionCode' => '',
              'Role' => '',
              'ModeOfPayment' => '',
              'ClassRate' => '',
              'Level' => '',
              'PayGroup' => '',
              'Shift' => '',
              'SSS' => '',
              'HDMF' => '',
              'PHIP' => '',
              'TIN' => '',
              'is_SSS' => '',
              'is_HDMF' => '',
              'is_PHIP' => '',
              'is_TIN' => '',
              'Remarks' => '',
              'Birthday' => '',
              'DateHired' => '',
              'BankAccount' => '',
              'Status' => '',
              'Salary' => '',
              'RateEffectivity' => '',
              'Email' => '',
              'Bio_Id' => '',
              'Project_Code' => '',
              'EmpStatus' => ''
            ];
            break;
          default:
            $col = [
              'EmployeeCode' => '',
              'EmployeeName' => '',
              'Address' => '',
              'Department' => '',
              'Email' => '',
              'ContactPerson' => '',
              'Mobile' => '',
              'IsPassenger' => '',
              'IsDriver' => '',
              'IsApprover' => ''
            ];
            break;
        }

        $columns = [$col];

        return [
          'status' => true,
          'msg' => 'Employee template ready to Download',
          'name' => 'employee',
          'data' => $columns
        ];
        break;

      case 'downloadsupplierexcel':
        switch ($config['params']['companyid']) {
          case '10':
          case '12':
            return [
              'status' => true,
              'msg' => 'Supplier template ready to Download',
              'name' => 'supplier',
              'data' => [[
                'SupplierCode' => '',
                'SupplierName' => '',
                'vattype' => '',
                'taxcode' => '',
                'terms' => '',
                'currency' => '',
                'orgstructure' => '',
                'tin' => '',
                'busstyle' => '',
                'notes' => ''
              ]]
            ];
            break;
          case '60': // transpower
            return [
              'status' => true,
              'msg' => 'Supplier template ready to Download',
              'name' => 'supplier',
              'data' => [[
                'code' => '',
                'clientname' => '',
                'address' => '',
                'telno' => '',
                'faxno' => '',
                'email' => '',
                'contact' => '',
                'mobile/tel2' => '',
                'tin' => '',
                'status' => 'RegisteredName'
              ]]
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'Supplier template ready to Download',
              'name' => 'item',
              'data' => [[
                'SupplierCode' => '',
                'SupplierName' => '',
                'Address' => '',
                'TelephoneNumber' => '',
                'FaxNumber' => '',
                'Email' => '',
                'ContactPerson' => '',
                'TIN' => ''
              ]]
            ];
            break;
        }

        break;
      case 'downloadsupplieritemexcel':
        return [
          'status' => true,
          'msg' => 'Supplier item template ready to Download',
          'name' => 'item',
          'data' => [[
            'SupplierCode' => '',
            'SupplierName' => '',
            'ItemCode' => '',
            'ItemDescription' => '',
          ]]
        ];
        break;

      case 'downloadagentexcel':
        switch ($config['params']['companyid']) {
          case 60: //transpower
            return [
              'status' => true,
              'msg' => 'Agent template ready to Download',
              'name' => 'agent',
              'data' => [[
                'AgentCode' => '',
                'AgentName' => '',
                'Address' => '',
                'Tin' => '',
                'TelNo' => '',
                'FaxNo' => '',
                'Mobile' => '',
                'E-mail' => '',
                'Contact' => ''
              ]]
            ];
            break;
          default:
            return [
              'status' => true,
              'msg' => 'Agent template ready to Download',
              'name' => 'agent',
              'data' => [[
                'AgentCode' => '',
                'AgentName' => '',
                'Group' => '',
                'PriceGroup' => ''
              ]]
            ];
            break;
        }
        break;

      case 'downloadcontact':
        return [
          'status' => true,
          'msg' => 'Contact Person template ready to Download',
          'name' => 'item',
          'data' => [[
            'ClientID' => '',
            'Salutation' => '',
            'Fname' => '',
            'Mname' => '',
            'lname' => '',
            'Email' => '',
            'Contactno' => '',
            'bday' => '',
            'mobile' => '',
            'department' => '',
            'designation' => '',
            'billdefault' => '',
            'shipdefault' => ''
          ]]
        ];
        break;
      case 'downloadaddress':
        return [
          'status' => true,
          'msg' => 'Address template ready to Download',
          'name' => 'item',
          'data' => [[
            'ClientID' => '',
            'Billing' => '',
            'Shipping' => '',
            'AddressTitle' => '',
            'AddrType' => '',
            'AddrLine1' => '',
            'AddrLine2' => '',
            'City' => '',
            'Province' => '',
            'Country' => '',
            'Zipcode' => '',
            'Phone' => '',
            'Faxnumber' => '',
            'billdefault' => '',
            'shipdefault' => ''
          ]]
        ];
        break;

      case 'downloadrrexcel':
        return [
          'status' => true,
          'msg' => 'RR template ready to Download',
          'name' => 'item',
          'data' => [[
            'itemcode' => '',
            'description' => '',
            'qty' => '',
            'cost' => '',
            'uom' => '',
            'location' => '',
            'expiry' => ''
          ]]
        ];
        break;

      case 'downloadpcexcel':
        return [
          'status' => true,
          'msg' => 'PC template ready to Download',
          'name' => 'item',
          'data' => [[
            'itemcode' => '',
            'qty' => '',
            'uom' => '',
            'location' => '',
            'expiry' => ''
          ]]
        ];
        break;

      case 'downloadwnexcel':
        return [
          'status' => true,
          'msg' => 'Water Connection template ready to Download',
          'name' => 'waterconenction',
          'data' => [[
            'Customer Code' => '',
            'Trans Date' => '',
            'Meter No' => '',
            'Connection Date' => '',
            'Beg Reading' => ''
          ]]
        ];
        break;
      default:
        return ['status' => false, 'msg' => $action . ' template is not exists'];
        break;
    }
  }

  public function getitemmaster($config)
  {
    $prevaction = $config['params']['action'];
    $companyid = $config['params']['companyid'];
    $config['params']['action'] = 'downloaditemexcel';
    $template = $this->setupexceltemplate($config);

    $returndata = [];

    if ($template['status']) {
      $fieldnames = '';

      $tablename = 'item';

      foreach ($template['data'][0] as $key => $value) {
        $fieldname = $this->getequivalentfieldname(trim($key), $tablename, '', $config);
        if ($fieldname != "") {
          if ($fieldnames == '') {
            if ($fieldname == 'min' || $fieldname == 'max') {
              $fieldnames =  'il.' . $fieldname;
            } else {
              $fieldnames = 'item.' . $fieldname;
            }
          } else {
            if ($fieldname == 'min' || $fieldname == 'max') {
              $fieldnames = $fieldnames . ',' . 'il.' . $fieldname;
            } else {
              $fieldnames = $fieldnames . ',' . 'item.' . $fieldname;
            }
          }
        }
      }

      $data = [];

      if ($fieldname != '') {
        ini_set('max_execution_time', -1);
        if ($companyid ==  47) { //kstar
          $qry = "select " . $fieldnames . " from item left join itemlevel as il on il.itemid = item.itemid order by item.barcode ";
        } else {
          $qry = "select " . $fieldnames . " from item order by barcode ";
        }

        $data = $this->coreFunctions->opentable($qry);
        $data = json_decode(json_encode($data), true);

        foreach ($data as $k => $val) {

          $arr = array_keys($val);
          foreach ($arr as $ar) {
            switch ($tablename) {
              case 'item':
                switch ($ar) {
                  case 'class':
                  case 'groupid':
                  case 'model':
                  case 'part':
                  case 'brand':
                  case 'category':
                  case 'subcat':
                  case 'projectid':
                  case 'supplier':
                    $check = $this->isexist($ar, $val[$ar], 'id', $tablename, "name");
                    if ($check) {
                      $data[$k][$ar]  = $check;
                    } else {
                      $data[$k][$ar]  = '';
                    }
                    break;
                }
                break;
            }
          }
        }

        foreach ($data as $k2 => $val2) {
          $row = [];
          foreach ($template['data'][0] as $k3 => $value) {
            $fieldname = $this->getequivalentfieldname(trim($k3), $tablename, '', $config);
            if ($fieldname != "") {
              $row[trim($k3)] = $data[$k2][$fieldname];
            }
          }
          array_push($returndata, $row);
        }
      }
    }
    $config['params']['action'] =  $prevaction;

    return ['status' => true, 'msg' => 'Download Item master', 'data' =>  $returndata];
  }

  public function getuommaster($config)
  {
    $prevaction = $config['params']['action'];
    $config['params']['action'] = 'downloaduomexcel';
    $template = $this->setupexceltemplate($config);

    $returndata = [];

    if ($template['status']) {
      $fieldnames = '';

      $tablename = 'uom';

      foreach ($template['data'][0] as $key => $value) {
        $fieldname = $this->getequivalentfieldname(trim($key), $tablename, '', $config);

        if ($fieldname != "") {
          if ($fieldname == 'barcode') {
            $tablename = 'item';
          } else {
            $tablename = 'uom';
          }

          if ($fieldnames == '') {

            $fieldnames =  $tablename . '.' . $fieldname;
          } else {
            $fieldnames = $fieldnames . ',' . $tablename . '.' . $fieldname;
          }
        }
      }

      $data = [];

      if ($fieldname != '') {

        $qry = "select " . $fieldnames . " from item left join uom on uom.itemid=item.itemid order by item.barcode ";
        $data = $this->coreFunctions->opentable($qry);
        $data = json_decode(json_encode($data), true);

        foreach ($data as $k2 => $val2) {
          $row = [];
          foreach ($template['data'][0] as $k3 => $value) {
            $fieldname = $this->getequivalentfieldname(trim($k3), $tablename, '', $config);
            if ($fieldname != "") {
              $row[trim($k3)] = $data[$k2][$fieldname];
            }
          }
          array_push($returndata, $row);
        }
      }
    }
    $config['params']['action'] =  $prevaction;

    return ['status' => true, 'msg' => 'Download UOM master', 'data' =>  $returndata];
  }

  public function getitemuommaster($config)
  {
    $prevaction = $config['params']['action'];
    $config['params']['action'] = 'downloaditemexcel';
    $template = $this->setupexceltemplate($config);

    $returndata = [];

    if ($template['status']) {
      $fieldnames = '';

      $tablename = 'item';

      foreach ($template['data'][0] as $key => $value) {
        switch (trim($key)) {
          case 'Price1':
          case 'Discount1':
            unset($template['data'][0][$key]);
            break;
        }

        $fieldname = $this->getequivalentfieldname(trim($key), $tablename, '', $config);
        if ($fieldname == 'uom') continue;

        if ($fieldname != "") {
          if ($fieldnames == '') {
            $fieldnames =  'item.' . $fieldname;
          } else {
            $fieldnames = $fieldnames . ',item.' . $fieldname;
          }
        }
      }

      $data = [];

      if ($fieldname != '') {

        $qry = "select " . $fieldnames . ", uom.uom, uom.factor, uom.amt, uom.amt2, uom.famt, uom.isinactive as uom_inactive, uom.isdefault as default_in, uom.isdefault2 as default_out 
        from item left join uom on uom.itemid=item.itemid order by item.barcode ";
        $data = $this->coreFunctions->opentable($qry);
        $data = json_decode(json_encode($data), true);

        foreach ($data as $k => $val) {

          $arr = array_keys($val);
          foreach ($arr as $ar) {
            switch ($tablename) {
              case 'item':
                switch ($ar) {
                  case 'class':
                  case 'groupid':
                  case 'model':
                  case 'part':
                  case 'brand':
                  case 'category':
                  case 'subcat':
                  case 'projectid':
                  case 'supplier':
                    $check = $this->isexist($ar, $val[$ar], 'id', $tablename, "name");
                    if ($check) {
                      $data[$k][$ar]  = $check;
                    } else {
                      $data[$k][$ar]  = '';
                    }
                    break;
                }
                break;
            }
          }
        }

        $template['data'][0]['Factor'] = 0;
        $template['data'][0]['Retail'] = 0;
        $template['data'][0]['Wholesale'] = 0;
        $template['data'][0]['Price_A'] = 0;
        $template['data'][0]['UOM_Inactive'] = 0;
        $template['data'][0]['Default_In'] = 0;
        $template['data'][0]['Default_Out'] = 0;

        foreach ($data as $k2 => $val2) {
          $row = [];
          foreach ($template['data'][0] as $k3 => $value) {
            $fieldname = $this->getequivalentfieldname(trim($k3), $tablename, '', $config);
            if ($fieldname != "") {
              $row[trim($k3)] = $data[$k2][$fieldname];
              switch ($fieldname) {
                case 'uom_inactive':
                case 'default_in':
                case 'default_out':
                  if ($row[trim($k3)] == 0) $row[trim($k3)] = '';
                  break;
              }
            }
          }
          array_push($returndata, $row);
        }
      }
    }
    $config['params']['action'] =  $prevaction;

    return ['status' => true, 'msg' => 'Download Item master', 'data' =>  $returndata];
  }

  public function getpricelistmaster($config)
  {
    $prevaction = $config['params']['action'];
    $config['params']['action'] = 'downloadpricelistexcel';
    $template = $this->setupexceltemplate($config);

    $returndata = [];

    if ($template['status']) {
      $fieldnames = '';

      $tablename = 'item';

      foreach ($template['data'][0] as $key => $value) {
        $fieldname = $this->getequivalentfieldname(trim($key), $tablename, '', $config);
        if ($fieldname != "") {
          if ($fieldnames == '') {
            $fieldnames =  $fieldname;
          } else {
            $fieldnames = $fieldnames . ',' . $fieldname;
          }
        }
      }

      $data = [];

      $label = '';
      if ($fieldname != '') {

        $qry = "select " . $fieldnames . " from item order by itemname ";
        $label = 'PriceList';

        $data = $this->coreFunctions->opentable($qry);
        $data = json_decode(json_encode($data), true);

        foreach ($data as $k2 => $val2) {
          $row = [];
          foreach ($template['data'][0] as $k3 => $value) {
            $fieldname = $this->getequivalentfieldname(trim($k3), $tablename, '', $config);
            if ($fieldname != "") {
              $row[trim($k3)] = $data[$k2][$fieldname];
            }
          }
          array_push($returndata, $row);
        }
      }
    }
    $config['params']['action'] =  $prevaction;

    return ['status' => true, 'msg' => 'Download ' .  $label . ' master', 'data' =>  $returndata];
  }

  public function getclientmaster($config)
  {
    $prevaction = $config['params']['action'];
    switch ($prevaction) {
      case 'downloademployeeexcelmaster':
        $config['params']['action'] = 'downloademployeeexcel';
        break;
      case 'downloadcustomerexcelmaster':
        $config['params']['action'] = 'downloadcustomerexcel';
        break;
    }
    $template = $this->setupexceltemplate($config);

    $returndata = [];

    if ($template['status']) {
      $fieldnames = '';

      $tablename = 'client';

      foreach ($template['data'][0] as $key => $value) {
        $fieldname = $this->getequivalentfieldname(trim($key), $tablename, '', $config);
        if ($fieldname != "") {
          if ($config['params']['companyid'] == 47) { //kstar
            if ($fieldname == 'agent') {
              $fieldname = 'a.clientname as agent';
            } else {
              $fieldname = 'c.' . $fieldname;
            }
          }

          if ($fieldnames == '') {
            $fieldnames =  $fieldname;
          } else {
            $fieldnames = $fieldnames . ',' . $fieldname;
          }
        }
      }

      $data = [];

      $label = '';
      if ($fieldname != '') {

        switch ($prevaction) {
          case 'downloademployeeexcelmaster':
            $qry = "select " . $fieldnames . " from client where isemployee=1 order by client ";
            $label = 'Employee';
            break;
          case 'downloadcustomerexcelmaster':
            if ($config['params']['companyid'] == 47) { //kstar
              $qry = "select " . $fieldnames . " from client as c left join client as a on a.client = c.agent where c.iscustomer=1 order by c.client ";
            } else {
              $qry = "select " . $fieldnames . " from client where iscustomer=1 order by client ";
            }
            $label = 'Customer';
            break;
        }

        $data = $this->coreFunctions->opentable($qry);
        $data = json_decode(json_encode($data), true);

        foreach ($data as $k2 => $val2) {
          $row = [];
          foreach ($template['data'][0] as $k3 => $value) {
            $fieldname = $this->getequivalentfieldname(trim($k3), $tablename, '', $config);
            if ($fieldname != "") {
              $row[trim($k3)] = $data[$k2][$fieldname];
            }
          }
          array_push($returndata, $row);
        }
      }
    }
    $config['params']['action'] =  $prevaction;

    return ['status' => true, 'msg' => 'Download ' .  $label . ' master', 'data' =>  $returndata];
  }

  public function validatetemplate($config, $tabletype, $uploadtype = '')
  {
    ini_set('max_execution_time', -1);
    $companyid = $config['params']['companyid'];
    $validheader = [];

    $rawdata = $config['params']['data'][0];
    $rawdata = array_change_key_case($rawdata, CASE_LOWER);
    $rawheader = array_keys($rawdata);

    switch ($tabletype) {
      case 'item':

        switch ($uploadtype) {
          case 'newmeter':
          case 'updatemeter':
            return ['status' => true, 'msg' => ''];
            break;

          case 'newfams':
          case 'updatefams':
            array_push($validheader, "tagcode", "itemname", "uom");
            goto validatehere;
            break;

          case 'updateaimsid':
            $validheader = ["barcode", "aimsid"];
            goto validatehere;
            break;

          default:
            if ($companyid == 47) { //kstar
              $validheader = ["unitofmeasurement"];
            } else if ($companyid == 60) { //transpower
              $validheader = ['unit'];
            } else {
              $validheader = ["uom"];
            }

            break;
        }

        switch ($uploadtype) {
          case 'updateprice';
            $validheader = ["barcode"];
            break;

          default:
            switch ($companyid) {
              case 40: //cdo
                array_push($validheader, "itemdescription", "partno");
                break;
              case 10:
              case 12:
                array_push($validheader, "itemname", "partno");
                break;
              case 60: //transpower
                array_push($validheader, 'full_item_name', 'item_code');
                break;
              default:
                array_push($validheader, "itemdescription", "itemcode");
                break;
            }
            break;
        }
        break;
      case 'phpamt':
      case 'usdamt':
      case 'tpamt':
        array_push($validheader, "barcode");
        array_push($validheader, $tabletype);
        break;
      case 'pnpcsr':
        array_push($validheader, "engine", "pnpno", "csrno", "datecreate");
        break;

      case 'issueitem':
        array_push($validheader, "tagcode", "employeecode", "departmentcode");
        break;
    }

    validatehere:
    foreach ($validheader as $key) {
      if (!in_array(strtolower($key), $rawheader)) {
        return ['status' => false, 'msg' => 'Invalid template, missing field `' . $key . '`', 'valid' => $validheader, 'upload' => $rawheader];
      }
    }

    return ['status' => true, 'msg' => ''];
  }

  private function updatepricefromexcel($config)
  {
    $type = $config['params']['dataparams']['utype'];
    $rawdata = $config['params']['data'];
    $amtfield = '';
    $tabletype = '';
    switch ($type) {
      case 'uploadpricephp':
        $amtfield = 'amt';
        $tabletype = 'phpamt';
        break;
      case 'uploadpriceusd':
        $amtfield = 'amt2';
        $tabletype = 'usdamt';
        break;
      case 'uploadtpusd':
        $amtfield = 'famt';
        $tabletype = 'tpamt';
        break;
    }
    if ($amtfield != '') {
      $isvalidtempate = $this->validatetemplate($config, $tabletype);
      $i = 1;
      if ($isvalidtempate['status']) {
        foreach ($rawdata as $key => $value) {
          $this->coreFunctions->LogConsole($i . "/" . count($rawdata));
          $this->coreFunctions->sbcupdate('item', [$amtfield => $value[$tabletype]], ['partno' => $value['barcode']]);
          $i++;
        }
        return ['status' => true, 'msg' => 'Successfully uploaded.'];
      } else {
        return $isvalidtempate;
      }
    }
  }

  private function updatepnpcsr($config)
  {
    $rawdata = $config['params']['data'];
    $data = [];
    $msg = '';
    $status = true;

    foreach ($rawdata as $key => $value) {
      try {
        $engine = $this->coreFunctions->getfieldvalue("serialin", "serial", "serial = '" . $rawdata[$key]['engine'] . "'");
        if ($engine == '') {
          $status = false;
          $msg .= 'Failed to upload. Engine # ' . $rawdata[$key]['engine'] . ' does not exist. ';
          continue;
        }

        $pnp = $this->coreFunctions->getfieldvalue("serialin", "pnp", "serial = '" . $rawdata[$key]['engine'] . "'");
        $csr = $this->coreFunctions->getfieldvalue("serialin", "csr", "serial = '" . $rawdata[$key]['engine'] . "'");

        if ($pnp == "") {
          $data['pnp'] = isset($rawdata[$key]['pnpno']) ? $rawdata[$key]['pnpno'] : '';
        }

        if ($csr == "") {
          $data['csr'] = isset($rawdata[$key]['csrno']) ? $rawdata[$key]['csrno'] : '';
        }

        $datecreate = isset($rawdata[$key]['datecreate']) ? $rawdata[$key]['datecreate'] : '';

        if ($datecreate != '') {
          $UNIX_DATE = ($datecreate - 25569) * 86400;
          $data['dateid'] = gmdate("Y-m-d", $UNIX_DATE);
        }

        $return = $this->coreFunctions->sbcupdate("serialin", $data, ["serial" => $rawdata[$key]['engine']]);
        if ($return == 0) {
          $status = false;
          $msg .= 'Failed to upload. ';
          goto exithere;
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:

    return ['status' => $status, 'msg' => $msg];
  }


  private function uploadtransaction($type, $rawdata, $config)
  {
    ini_set('max_execution_time', -1);
    $status = true;
    $msg = '';
    $companyid = $config['params']['companyid'];

    $tabletype = '';
    switch ($type) {
      case 'newitemissuance':
        $tabletype = 'issueitem';
        $type = 'issueitem';
        break;
    }

    $table = "issueitem";
    if ($table == '') {
      $status = false;
      $msg = 'Undefined table name ' . $tabletype;
      goto exithere;
    }

    $isvalidtempate = $this->validatetemplate($config, $tabletype, $type);
    if (!$isvalidtempate['status']) {
      return $isvalidtempate;
    }

    $exceptpropercase = [];

    foreach ($rawdata as $key => $value) {
      $valtoinsert = [];
      $uniqueval = ''; //must be on first column              

      $blnValid = true;

      foreach ($value as $k => $val) {
        $fieldname = $this->getequivalentfieldname(trim($k), $table, $type, $config);
        $val = $this->othersClass->sanitizekeyfield($fieldname, $val, '', $companyid, $exceptpropercase);

        if (str_contains($fieldname, '__empty')) {
          continue;
        }

        $valtoinsert[$fieldname] = $this->checkfieldmasterfield($fieldname, $val, $table);
      }

      $valtoinsert['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$valtoinsert['barcode']], '', true);
      if (isset($valtoinsert['empid'])) {
        $valtoinsert['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['empid']], '', true);
      } else {
        $blnValid = false;
      }
      if (isset($valtoinsert['locid'])) {
        $valtoinsert['locid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['locid']], '', true);
      } else {
        $blnValid = false;
      }

      if ($blnValid) {
        $result = $this->generateIssueFams($valtoinsert, $config);
        if (!$result['status']) {
          $msg .= $result['msg'] . '<br>';
          $status = false;
        }
      } else {
        $msg .= 'Failed to generate issue item for ' . $valtoinsert['barcode'] . ' missing employee/location.' . '<br>';
        $status = false;
      }


      try {
      } catch (Exception $e) {
        $status = false;
        $msg .= "Failed to upload. Line: " . $e->getLine() . ". Exception error " . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    }
    return ['status' => $status, 'msg' => $msg];
  }

  private function insertdatafromexcel($type, $rawdata, $config)
  {
    ini_set('max_execution_time', -1);
    $status = true;
    $msg = '';
    $companyid = $config['params']['companyid'];
    $blnIsert = true;

    $blndbtable = false;

    $tabletype = '';
    switch ($type) {
      case 'uploaddbtable':
        if (isset($config['params']['dataparams']['clientname'])) {
          $tabletype = $config['params']['dataparams']['clientname'];
          $blndbtable = true;
        } else {
          $status = false;
          $msg = 'Specify table name to upload.';
          goto exithere;
        }
        break;
      case 'updatepnpcsr':
        $tabletype = 'pnpcsr';
        break;
      case 'uploadcoc':
        $tabletype = 'codehead';
        break;
      case 'newitem':
      case 'updateitem':
      case 'updateprice':
      case 'newmeter':
      case 'updatemeter':
      case 'newfams':
      case 'updatefams':
      case 'updateaimsid';
        $tabletype = 'item';
        break;
      case 'newcustomer':
      case 'updatecustomer':
        $tabletype = 'customer';
        break;
      case 'newemployee':
      case 'newemployeepayroll':
      case 'updateemployee':
      case 'updateemployeepayroll':
        $tabletype = 'employee';
        break;
      case 'newsupplier':
      case 'updatesupplier':
        $tabletype = 'supplier';
        break;
      case 'newagent':
      case 'updateagent':
        $tabletype = 'agent';
        break;
      case 'newwh':
      case 'updatewh':
        $tabletype = 'wh';
        break;
      case 'newtruck':
        $tabletype = 'truck';
        break;
      case 'uploadcontact':
        $tabletype = 'contactperson';
        break;
      case 'uploadaddress':
        $tabletype = 'billingaddr';
        break;
      case 'newuom':
      case 'updateuom':
        $tabletype = 'uom';
        break;
      case 'newbranch':
        $tabletype = 'branch';
        break;
      case 'newsupplieritems':
        $tabletype = 'supplieritem';
        break;
      case 'updatepricelist':
        $tabletype = 'pricelist';
        break;
      case 'updateemployeerate':
        $tabletype = 'ratesetup';
        break;
      case 'newallowance':
        $tabletype = 'allowsetup';
        break;
    }

    $isvalidtempate = $this->validatetemplate($config, $tabletype, $type);
    if (!$isvalidtempate['status']) {
      return $isvalidtempate;
    }

    switch ($type) {
      case 'updateitem':
      case 'updateaimsid':
      case 'updatecustomer':
      case 'updatesupplier':
      case 'updateagent':
      case 'updateemployee':
      case 'updatewh':
      case 'updatebranch':
      case 'updateprice':
      case 'updatemeter':
      case 'updateemployeepayroll':
        $blnIsert = false;
        break;
    }

    $table = $this->gettablename($tabletype, $tabletype, $blndbtable);
    if ($table == '') {
      $status = false;
      $msg = 'Undefined table name ' . $tabletype;
      goto exithere;
    }

    switch ($tabletype) {
      case 'uom':
        $uom_errmsg = '';

        $itemnotexist = [];
        foreach ($rawdata as $key => $vuom) {

          $tempdata = [];

          foreach ($vuom as $k1 => $val1) {
            $fieldname = $this->getequivalentfieldname(trim($k1), $table, $type, $config);

            if (str_contains($fieldname, '__empty')) {
              continue;
            }

            $tempdata[$fieldname] = $this->othersClass->sanitizekeyfield($fieldname, $val1, '', $companyid);
          }

          if (!isset($tempdata['barcode'])) {
            continue;
          }

          if (!isset($tempdata['factor'])) {
            $uom_errmsg = $uom_errmsg . $tempdata['barcode'] . ' invalid factor <br>';
            continue;
          }

          $valinsertuom = [
            'barcode' => $tempdata['barcode'],
            'uom' => $tempdata['uom']
          ];

          if ($type == 'newuom') $valinsertuom['factor'] = $tempdata['factor'];
          if (isset($tempdata['isdefault'])) $valinsertuom['isdefault'] = $tempdata['isdefault'];
          if (isset($tempdata['retail'])) $valinsertuom['amt'] = $tempdata['retail'];
          if (isset($tempdata['wholesale'])) $valinsertuom['amt2'] = $tempdata['wholesale'];
          if (isset($tempdata['others'])) $valinsertuom['famt'] = $tempdata['others'];
          if (isset($tempdata['uom_inactive'])) $valinsertuom['isinactive'] = $tempdata['uom_inactive'];
          if (isset($tempdata['default_in'])) $valinsertuom['isdefault'] = $tempdata['default_in'];
          if (isset($tempdata['default_out'])) $valinsertuom['isdefault2'] = $tempdata['default_out'];

          foreach ($valinsertuom as $k => $val) {
            $valinsertuom[$k] = $this->othersClass->sanitizekeyfield($k, $val, '', $companyid);;
          }
          $insertuom = $this->insertitemuom($valinsertuom, "barcode", $config, true);
          if (!$insertuom['status']) {
            $status = false;
            if (($key = array_search($valinsertuom['barcode'], $itemnotexist)) !== false) {
            } else {
              array_push($itemnotexist, $valinsertuom['barcode']);
            }
          }
        }

        if ($status) {
          if ($uom_errmsg != '') {
            return ['status' => false, 'msg' => $uom_errmsg];
          } else {
            return ['status' => true, 'msg' => 'Successfully uploaded'];
          }
        } else {
          if (!empty($itemnotexist)) {
            $msg = "Please create item(s): ";
            foreach ($itemnotexist as $key => $value) {
              $msg .= " " . $value . ",  ";
            }
          }
          goto exithere;
        }
        break;

      case 'supplieritem':
        $err_suppitem = '';
        foreach ($rawdata as $key => $supitem) {

          $blnValid = true;
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$supitem['ItemCode']], '', true);
          if ($itemid == 0) {
            $blnValid = false;
            $status = false;
            $err_suppitem .= ' Missing item ' . $supitem['ItemCode'];
          }

          $supplierid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$supitem['SupplierCode']], '', true);
          if ($supplierid == 0) {
            $blnValid = false;
            $status = false;
            $err_suppitem .= ' Missing supplier ' . $supitem['SupplierCode'];
          }

          if ($blnValid) {
            $exist = $this->coreFunctions->datareader("select itemid as value from supplieritem where itemid=? and clientid=?", [$itemid, $supplierid], '', true);
            if ($exist == 0) {
              $this->coreFunctions->sbcinsert("supplieritem", ['itemid' => $itemid, 'clientid' => $supplierid, 'createdate' => $this->othersClass->getCurrentTimeStamp(), 'createby' => $config['params']['user']]);
            }
          }
        }

        if ($status) {
          return ['status' => true, 'msg' => 'Successfully uploaded'];
        } else {
          return ['status' => false, 'msg' => $err_suppitem];
        }
        break;

      default:
        if ($blndbtable) { //no unique field for db table upload, all fields will be inserted
          $unique = '';
        } else {
          $unique = $this->getuniquefield($table, $config, $tabletype);
          if ($unique == '') {
            $status = false;
            $msg = 'Undefined unique field for table ' . $tabletype;
            goto exithere;
          }
        }


        break;
    }

    $exceptpropercase = [];
    if ($tabletype == 'truck') {
      $exceptpropercase = ['clientname'];
    }

    $skip1strow = false;
    switch ($type) {
      case 'newemployeepayroll':
      case 'updateemployeepayroll':
        switch ($companyid) {
          case 58: //cdo hris - 1st row serves as notes/columns detailed description
            $skip1strow = true;
            break;
        }
        break;
    }

    foreach ($rawdata as $key => $value) {
      if ($skip1strow) {
        if ($key == 0) {
          $this->coreFunctions->LogConsole('skip1strow');
          continue;
        }
      }

      $valtoinsert = [];
      $uniqueval = ''; //must be on first column
      $iteminfo = [];
      $clientinfo = [];
      $employeeinfo = [];
      $educationinfo = [];
      $contactinfo = [];
      $ratesetupinfo = [];
      $sectioninfo = [];
      $itemlevel = [];

      if ($type == 'newfams' || $type == 'updatefams') {
        unset($value['Assigned to Employee']); // used in fams template
        unset($value['Department Name']);
        unset($value['Location']);
        unset($value['Batch']);
      }

      if ($type == 'updatepricelist') {
        unset($value['ItemDescription']);
      }

      if ($type == 'updateemployeerate') {
        unset($value['LAST NAME']);
        unset($value['FIRST NAME']);
        unset($value['MIDDLE NAME']);
        unset($value['MODE OF PAYMENT']);
      }

      if ($type == 'newemployeepayroll') unset($value['DIRECT HEAD']); //remove due to not yet inserted some supervisor, process: upload new employees then update employees

      // $this->othersClass->logConsole('$value- ' . json_encode($value));

      try {
        $testcounter = 0;
        foreach ($value as $k => $val) {
          $fieldname = $this->getequivalentfieldname(trim($k), $table, $type, $config);
          $val = $this->othersClass->sanitizekeyfield($fieldname, $val, '', $companyid, $exceptpropercase, true);

          if (str_contains($fieldname, '__empty')) {
            continue;
          }

          if ($fieldname == '') {
            $this->othersClass->logConsole('blank ' . trim($k));
          } else {
            if ($blndbtable) goto escapeLinkTableHere;
            switch ($fieldname) {
              case 'agent':
              case 'supplier':
              case 'terms':
              case 'rev':
              case 'clientid':
              case 'branchid':
              case 'branchid2':
              case 'deptid':
              case 'divid':
              case 'sectid':
              case 'linkdept':
              case 'asset':
              case 'revenue':
              case 'expense':
              case 'salesreturn':
              case 'roleid':
              case 'roleid2':
              case 'paygroup':
              case 'shiftid':
              case 'projectid':
              case 'empstatus':
              case 'agent_name':
              case 'supervisorid':
              case 'supervisorid2':
              case 'jobid':
              case 'jobid2':
              case 'workcatid':
              case 'empid':

                if (strtoupper(trim($val)) == 'APPROVER') $valtoinsert['isapprover'] = 1;
                if (strtoupper(trim($val)) == 'SUPERVISOR') $valtoinsert['issupervisor'] = 1;

                $check = $this->linktoothertable($fieldname, trim($val), $k, $table, $uniqueval, $type); //used for data with code/name link, must be existing masterfile
                if (!$check['status']) {
                  if ($companyid == 39) {
                    if ($fieldname == 'supplier' && $table == 'item') {
                      $val = 0;
                    }
                  } else {
                    $status = false;
                    $msg .= $check['msg'] . '<br>';
                    // goto exithere;
                    goto NextLoopHere;
                  }
                } else {
                  if ($fieldname == 'agent_name' && $table == 'client') {
                    $val = $check['id'];
                    break;
                  }

                  if ($fieldname == 'supplier' && $table == 'item') {
                    $val = $check['id'];
                    break;
                  }
                  if ($fieldname == 'clientid') {
                    $val = $check['id'];
                    break;
                  }

                  if ($table == 'client' && $fieldname == 'terms') {
                    $val = $val;
                    break;
                  }
                  if ($table == 'client' && $fieldname == 'agent') {
                    $val = $val;
                    break;
                  }
                  switch ($fieldname) {
                    case 'asset':
                    case 'revenue':
                    case 'expense':
                    case 'salesreturn':
                      $val = $val;
                      break;
                    default:
                      $val = $check['id'];
                      break;
                  }
                }

                break;
              case 'status':
                $check = $this->clientstatus($fieldname, trim($val), $type);
                if ($config['params']['companyid'] == 60) { //transpower
                  if ($val != '') {
                    if (!$check['status']) {
                      $status = false;
                      $msg .= $check['msg'] . '<br>';
                      goto NextLoopHere;
                      // goto exithere;
                    }
                  }
                } else {
                  if (!$check['status']) {
                    $status = false;
                    $msg .= $check['msg'] . '<br>';
                    goto NextLoopHere;
                    // goto exithere;
                  }
                }
                break;

              case 'classrate':
              case 'paymode':
              case 'gender':
                $otherfixsetup = $this->otherfixsetup($fieldname, trim($val), $type, trim($k), $uniqueval);
                if (!$otherfixsetup['status']) {
                  $status = false;
                  $msg .= $otherfixsetup['msg'] . '<br>';
                  // goto exithere;
                  goto NextLoopHere;
                }
                $val = $otherfixsetup['val'];
                break;
            }

            if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12 || $config['params']['companyid'] == 36) {
              switch ($fieldname) {
                case 'vattype':
                  $check = $this->validfield($fieldname, trim($val));
                  if (!$check['status']) {
                    $status = false;
                    $msg = $check['msg'];
                    goto exithere;
                  }
                  break;
                case 'type':
                  if ($tabletype == 'item') {
                  } else {
                    $check = $this->validfield($fieldname, trim($val));
                    if (!$check['status']) {
                      $status = false;
                      $msg = $check['msg'];
                      goto exithere;
                    }
                  }

                  break;
              }
            }

            escapeLinkTableHere:
            if ($fieldname == $unique) {
              $val = $this->autoPadUniqueVal($config['params'], $val, $type);
              $uniqueval = $val;
            }


            if ($uniqueval == '') {
              // continue;
              if ($type == 'newfams' || $type == 'updatefams') {
                $msg .= "Invalid Tag Code for " . $value['Itemname'] . ' - ' . $value['SKU/Part No.'] . '<br>';
                $status = false;
              }

              if ($tabletype == 'customer' || $tabletype == 'supplier') {
                if ($companyid != 47) {
                  goto NextLoopHere;
                }
              } else {
                // if ($tabletype != 'contactperson') {
                //   goto NextLoopHere;
                // }
              }
            }


            if ($fieldname == 'agent_name' && $companyid == 47) {
              $valtoinsert['agent'] = $this->checkfieldmasterfield($fieldname, trim($val), $table); //with masterfile (auto-insert if not exists)            
            } else {
              if ($fieldname == 'projectid' && ($companyid == 10 || $companyid == 12)) {
                $valtoinsert[$fieldname] = $val; //with masterfile (auto-insert if not exists)        
              } else {
                $valtoinsert[$fieldname] = $this->checkfieldmasterfield($fieldname, trim($val), $table); //with masterfile (auto-insert if not exists)
              }
            }

            // $this->othersClass->logConsole('valtoinsert -- ' . json_encode($valtoinsert));

            if ($type == 'updateitem' && $blnIsert == false) {
              $valtoinsert['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$valtoinsert['barcode']], '', true);
              $uniqueval = 'itemid';
              $unique = 'itemid';
            }

            if ($tabletype == 'pricelist') {
              switch ($fieldname) {
                case 'barcode':
                  $valtoinsert['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$valtoinsert['barcode']], '', true);
                  $uniqueval = 'itemid';
                  unset($valtoinsert['barcode']);
                  unset($valtoinsert['itemname']);
                  break;
                case 'branchid':
                  if ($valtoinsert['branchid'] != 0) $valtoinsert['clientid'] = $valtoinsert['branchid'];
                  unset($valtoinsert['branchid']);
                  break;
              }
            }

            //$valtoinsert[$fieldname] = $this->checkfieldmasterfield($fieldname, trim($val), $table); //with masterfile (auto-insert if not exists)            

          }
        } //end looping of fields

        switch ($tabletype) {
          case 'customer':
            $valtoinsert['iscustomer'] = 1;
            if (isset($valtoinsert['class'])) {

              if ($valtoinsert['class'] == '') {
                $valtoinsert['class'] = 'R';
              } else {
                if ($companyid == 47) { //kitchenstar
                  $valtoinsert['class'] = 'R';
                  if ($valtoinsert['class'] == 'Wholesale') {
                    $valtoinsert['class'] = 'W';
                  }
                }
              }
            } else {
              $valtoinsert['class'] = 'R';
            }

            if ($blnIsert) {
              $valtoinsert['status'] = 'ACTIVE';
            }
            break;
          case 'supplier':
            $valtoinsert['issupplier'] = 1;
            if (isset($valtoinsert['isvat'])) { //added upon homeworks uploading
              if ($valtoinsert['isvat']) {
                $valtoinsert['vattype'] = 'VATABLE';
                $valtoinsert['tax'] = 12;
              } else {
                $valtoinsert['vattype'] = 'NON-VATABLE';
                $valtoinsert['tax'] = 0;
              }
            }
            break;
          case 'agent':
            $valtoinsert['isagent'] = 1;
            break;
          case 'wh':
            $valtoinsert['iswarehouse'] = 1;
            break;
          case 'employee':
            $valtoinsert['isemployee'] = 1;
            break;
          case 'truck':
            $valtoinsert['istrucking'] = 1;
            break;
          case 'branch':
            $valtoinsert['isbranch'] = 1;
            break;
        }

        //var_dump($valtoinsert);
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        switch ($tabletype) {
          case 'customer':
          case 'employee':
          case 'supplier':
          case 'agent':
          case 'wh':
          case 'item':
          case 'truck':
          case 'branch':
            $valtoinsert['editby'] = $config['params']['user'];
            $valtoinsert['editdate'] = $currentdate;
            $valtoinsert['dlock'] = $currentdate;
            if($tabletype=='item'){
              if($companyid == 60){//transpower
                if(isset($valtoinsert['disc'])){
                  $namt = $this->othersClass->computestock($valtoinsert['amt'], $valtoinsert['disc'], 1, 1);
                  $valtoinsert['namt'] = round($namt['ext'],2);
                }else{
                  $valtoinsert['namt'] =$valtoinsert['amt'];
                }

                if(isset($valtoinsert['disc2'])){
                  $namt2 = $this->othersClass->computestock($valtoinsert['amt2'], $valtoinsert['disc2'], 1, 1);
                  $valtoinsert['namt2'] = round($namt2['ext'],2);
                }else{
                  $valtoinsert['namt2'] =$valtoinsert['amt2'];
                }
                
                if(isset( $valtoinsert['disc3'])){
                  $nfamt = $this->othersClass->computestock($valtoinsert['famt'], $valtoinsert['disc3'], 1, 1);
                  $valtoinsert['nfamt'] = round($nfamt['ext'],2);
                }else{
                  $valtoinsert['nfamt'] =$valtoinsert['famt'];
                }

                if(isset( $valtoinsert['disc4'])){
                  $namt4 = $this->othersClass->computestock($valtoinsert['amt4'], $valtoinsert['disc4'], 1, 1);
                  $valtoinsert['namt4'] = round($namt4['ext'],2);
                }else{
                  $valtoinsert['namt4'] =$valtoinsert['amt4'];
                }
                
                if(isset( $valtoinsert['disc5'])){
                  $namt5 = $this->othersClass->computestock($valtoinsert['amt5'], $valtoinsert['disc5'], 1, 1);
                  $valtoinsert['namt5'] = round($namt5['ext'],2);
                }else{
                  $valtoinsert['namt5'] =$valtoinsert['amt5'];
                }

                if(isset($valtoinsert['disc6'])){
                  $namt6 = $this->othersClass->computestock($valtoinsert['amt6'], $valtoinsert['disc6'], 1, 1);
                  $valtoinsert['namt6'] = round($namt6['ext'],2);
                }else{
                  $valtoinsert['namt6'] =$valtoinsert['amt6'];
                }
                
                if(isset($valtoinsert['disc7'])){
                  $namt7 = $this->othersClass->computestock($valtoinsert['amt7'], $valtoinsert['disc7'], 1, 1);
                  $valtoinsert['namt7'] = round($namt7['ext'],2);
                }else{
                  $valtoinsert['namt7'] =$valtoinsert['amt7'];
                }
              }
            }
            break;
          case 'codehead':
            $valtoinsert['createby'] = $config['params']['user'];
            $valtoinsert['createdate'] = $currentdate;
            break;
          case 'ratesetup':
            $valtoinsert['createby'] = $config['params']['user'];
            $valtoinsert['createdate'] = $currentdate;
            $valtoinsert['dateid'] = isset($valtoinsert['dateid']) ? $valtoinsert['dateid'] : $this->othersClass->getCurrentDate();
            $valtoinsert['dateeffect'] = $valtoinsert['dateeffect'];
            $valtoinsert['dateend'] = '9999-12-31';
            if (isset($valtoinsert['classrate'])) {
            } else {
              $valtoinsert['type'] = $this->coreFunctions->getfieldvalue("employee", "classrate", 'empid=?', [$valtoinsert['empid']]);
            }
            break;
          case 'allowsetup':
            $valtoinsert['dateid'] = isset($valtoinsert['dateid']) ? $valtoinsert['dateid'] : $this->othersClass->getCurrentDate();
            $valtoinsert['dateeffect'] = $valtoinsert['dateeffect'];
            $valtoinsert['dateend'] = isset($valtoinsert['dateend']) ? $valtoinsert['dateend'] : '9999-12-31';
            $valtoinsert['type'] = $this->coreFunctions->getfieldvalue("employee", "classrate", 'empid=?', [$valtoinsert['empid']]);
            break;
        }

        //$this->coreFunctions->LogConsole(json_encode($valtoinsert));

        if ($type == 'newemployeepayroll' || $type == 'updateemployeepayroll') {
          if ($uniqueval != '') {
            $str_empmiddle = '';
            if (isset($valtoinsert['empmiddle'])) {
              $str_empmiddle = " " . $valtoinsert['empmiddle'];
            }
            // $this->coreFunctions->LogConsole(json_encode($valtoinsert));
            if (isset($valtoinsert['emplast']) && isset($valtoinsert['empfirst'])) {
              $valtoinsert['clientname'] = $valtoinsert['emplast'] . ", " . $valtoinsert['empfirst'] . $str_empmiddle;
            }
          }
        }

        if ($type == 'newmeter') {
          $valtoinsert['isnoninv'] = 1;
        }

        if ($type == 'newfams' || $tabletype == 'updatefams') {
          $valtoinsert['isfa'] = 1;
        }

        $tempvaltoinsert = $valtoinsert;

        foreach ($tempvaltoinsert as $vi => $viv) {
          $valtoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid, $exceptpropercase);

          if ($tabletype == 'item') {
            $iteminfo_field = $this->getiteminfofields($vi, $type);
            if ($iteminfo_field != '') {
              $iteminfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }
          }

          if ($tabletype == 'item') {
            $itemlevel_field = $this->getitemlevelfields($vi, $type);
            if ($itemlevel_field != '') {
              $itemlevel[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }
          }

          if ($tabletype == 'truck') {
            $clientinfo_field = $this->getclientinfofields($vi);
            if ($clientinfo_field != '') {
              $clientinfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }
          }

          if ($tabletype == 'codehead') {
            $sectioninfo_field = $this->getsectionfields($vi);
            if ($sectioninfo_field != '') {
              $sectioninfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }
          }

          if ($tabletype == 'employee') {
            $employeeinfo_field = $this->getemployeefields($vi);
            if ($employeeinfo_field != '') {
              $employeeinfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }

            $educationinfo_field = $this->getclientEducationfields($vi);
            if ($educationinfo_field != '') {
              $educationinfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }

            $contactinfo_field = $this->getclientContactfields($vi);
            if ($contactinfo_field != '') {
              $contactinfo[$vi] = $viv;
              unset($valtoinsert[$vi]);
            }


            if ($type == 'newemployeepayroll') {
              $ratesetupinfo_field = $this->getratesetupfields($vi);
              if ($ratesetupinfo_field != '') {
                $ratesetupinfo[$vi] = $viv;
                unset($valtoinsert[$vi]);
              }
            }
          }
        }


        if ($iteminfo) {
          $iteminfo[$unique] = $uniqueval;
        }

        if ($itemlevel) {
          $itemlevel[$unique] = $uniqueval;
        }

        if ($clientinfo) {
          $clientinfo[$unique] = $uniqueval;
        }

        if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
          if ($tabletype == 'item') {
            $acct = $this->coreFunctions->opentable("select assetid,liabilityid,revenueid,expenseid from projectmasterfile where line = ?", [$valtoinsert['projectid']]);
            if (!empty($acct)) {
              $valtoinsert['asset'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid=?", [$acct[0]->assetid]);
              $valtoinsert['liability'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid=?", [$acct[0]->liabilityid]);
              $valtoinsert['revenue'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid=?", [$acct[0]->revenueid]);
              $valtoinsert['expense'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid=?", [$acct[0]->expenseid]);
            } else {
              $valtoinsert['asset'] = 0;
              $valtoinsert['liability'] = 0;
              $valtoinsert['revenue'] = 0;
              $valtoinsert['expense'] = 0;
            }

            $valtoinsert['inhouse'] = $valtoinsert['itemname'];
          }
        }

        if ($table == 'client') {
          if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12 || $config['params']['companyid'] == 36) {
            if (isset($valtoinsert['vattype'])) {
              if ($valtoinsert['vattype'] == 'VATABLE') {
                $valtoinsert['tax'] = 12;
              } else {
                $valtoinsert['tax'] = 0;
              }
            }
          }

          if (isset($valtoinsert['status'])) {
            if ($valtoinsert['status'] == '') {
              $valtoinsert['status'] = 'ACTIVE';
            }
          }
        }

        if ($table == 'item' && $config['params']['companyid'] == 12) {
          $valtoinsert['islabor'] = 0;
        }

        if ($table == 'item' && $companyid == 16) {
          unset($valtoinsert['trno']);
          unset($valtoinsert['line']);
          unset($valtoinsert['doc']);
          unset($valtoinsert['docno']);
          unset($valtoinsert['prisasset']);
          unset($valtoinsert['prdepartment']);
          unset($valtoinsert['prcustomer']);
          if ($blnIsert) $valtoinsert['othcode'] = $valtoinsert['barcode'];
        }

        if ($table == 'item' && $companyid == 40) { //cdo
          $valtoinsert['shortname'] =  str_replace("-", "", $valtoinsert['partno']);
        }

        if ($type == 'updateemployeepayroll') {
          unset($valtoinsert['basicrate']);
        }

        $itemfactor = 0;
        $itemuom = '';
        if ($table == "item" && ($type == 'newitem' || $type == 'newfams')) {
          if (isset($valtoinsert['factor'])) {
            $itemfactor = $valtoinsert['factor'];
          }

          if (isset($valtoinsert['factor'])) { //remove uom if factor doesnt equal to 1, for template with factor field
            if ($valtoinsert['factor'] != 1) {
              $itemuom = $valtoinsert['uom'];
              unset($valtoinsert['uom']);
            }
          }
        }

        if ($table == "item") {
          unset($valtoinsert['pref']);
          unset($valtoinsert['factor']);
          unset($valtoinsert['uom_inactive']);
          unset($valtoinsert['default_in']);
          unset($valtoinsert['default_out']);
        }

        $insert = 1;
        $this->coreFunctions->LogConsole(floatval($blnIsert) . 'try to');
        if ($blnIsert) {
          $this->coreFunctions->LogConsole(json_encode($valtoinsert) . 'insert to');
          switch ($unique) {
            case 'partno':
              $partno_exist = $this->coreFunctions->getfieldvalue($table, $unique, $unique . '=?', [$uniqueval]);
              if (!$partno_exist) {
                $barcode = $this->generatebarcode($config, $valtoinsert);
                if (isset($valtoinsert['isoutsource'])) {
                  if ($valtoinsert['isoutsource'] == 1) {
                    $partno_exist = $this->coreFunctions->getfieldvalue($table, "barcode", "barcode=?", [$barcode]);
                    if (!$partno_exist) {
                      $valtoinsert['barcode'] = $barcode;
                    } else {
                      $msg = $msg . ' Barcode ' . $barcode . ' is already exists. ';
                    }
                  } else {
                    $valtoinsert['barcode'] = $barcode;
                  }
                } else {
                  $valtoinsert['barcode'] = $barcode;
                }
              } else {
                $msg = $msg . ' Part No ' . $uniqueval . ' is already exists. ';
              }
              break;
            case 'clientname':
              switch ($companyid) {
                case 16;
                  if ($tabletype == 'employee') {
                    $clientname_exist = $this->coreFunctions->getfieldvalue($table, $unique, $unique . '=?', [$uniqueval]);
                    if (!$clientname_exist) {
                      $clientcode = $this->generateclient($config, $valtoinsert, $tabletype);
                      $valtoinsert['client'] = $clientcode;
                    } else {
                      $msg = $msg . ' Employee Name ' . $uniqueval . ' is already exists. ';
                    }
                  }
                  break;
              }
              break;
            case 'client':
              if ($companyid == 47 || $companyid == 40) { //kstar
                if ($tabletype == 'customer' || $tabletype == 'supplier') {
                  $clientcode = $this->generateclient($config, $valtoinsert, $tabletype);
                  $valtoinsert['client'] = $clientcode;
                }
              }
              break;
          }

          $fieldfilter = $unique . '=?';
          $valuefilter = [$uniqueval];

          switch ($tabletype) {
            case 'pricelist':
            case 'ratesetup':
            case 'allowsetup':
              $exist = 0;
              break;
            default:
              if ($type == 'uploaddbtable') {
                $exist = 0;
              } else {
                $exist = $this->coreFunctions->getfieldvalue($table, $unique, $fieldfilter, $valuefilter);
              }
              break;
          }

          if ($tabletype == 'pricelist') {
            $valtoinsert['createby'] = $config['params']['user'];
            $valtoinsert['createdate'] = $currentdate;
            $valtoinsert['dlock'] = $currentdate;
          }

          if (!$exist) {
            switch ($tabletype) {
              case 'billingaddr':
              case 'contactperson':
                $insert = $this->coreFunctions->insertGetId($table, $valtoinsert);
                break;
              case 'pricelist':
                $this->coreFunctions->LogConsole(json_encode($valtoinsert));
                $insert = $this->coreFunctions->insertGetId($table, $valtoinsert);
                if ($insert) $valtoinsert['line'] = $insert;
                break;
              case 'ratesetup':
                if (isset($valtoinsert['classrate'])) {
                  $valtoinsert['type'] = $valtoinsert['classrate'];
                  unset($valtoinsert['classrate']);
                }

                $rateexist = $this->coreFunctions->datareader("select trno as value from ratesetup where empid=" . $valtoinsert['empid'] . " and year(dateend)=9999 and basicrate=" . $valtoinsert['basicrate'], [], '', true);
                if ($rateexist == 0) {
                  $insert = $this->coreFunctions->insertGetId($table, $valtoinsert);
                  if ($insert) {
                    $valtoinsert['rstrno'] = $insert;
                    $sqlrateupdate = "update ratesetup  set dateend='" . $valtoinsert['dateeffect'] . "' where empid='" . $valtoinsert['empid'] . "' and date(dateend)='9999-12-31' and trno<>" . $valtoinsert['rstrno'];
                    $this->coreFunctions->execqry($sqlrateupdate);
                  }
                } else {
                  $valtoinsert['rstrno'] = $rateexist;
                  // $this->coreFunctions->LogConsole('exists: ' . json_encode($valtoinsert));
                }
                break;
              case 'allowsetup':
                $result_allowance = $this->insertAllowance($valtoinsert, $config['params']['user'], true);
                if (!$result_allowance['status']) {
                  $status = false;
                  $msg .= $uniqueval . ', failed to upload allowance. ' . $result_allowance['msg'] . ' ';
                }
                break;
              default:

                $datatoinsert = $valtoinsert;

                if ($blndbtable) { //uploading database table
                  $datafilter = [];
                  foreach ($datatoinsert as $vi => $viv) {
                    $datatoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid);
                    $datafilter[$vi] = $viv;
                  }

                  $this->coreFunctions->execqry("delete from " . $table . " where " . $this->arraytostringwithdelimiter(array_keys($datafilter), "=? ", " AND "), 'delete', array_values($datafilter));
                } else {
                  if ($type == 'newemployeepayroll') {
                    $datatoinserttemp = [];
                    foreach ($valtoinsert as $vi => $viv) {
                      $valtoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid);
                      $getclientemployeefields = $this->getclientemployeefields($vi);
                      if ($getclientemployeefields != '') {
                        $datatoinserttemp[$vi] = $viv;
                      }
                    }
                    $datatoinsert = $datatoinserttemp;
                  }

                  if ($type == 'uploadcoc') {
                    $datatoinserttemp = [];
                    foreach ($valtoinsert as $vi => $viv) {
                      $valtoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid);
                      $getarticlefields = $this->getarticlefields($vi);
                      if ($getarticlefields != '') {
                        $datatoinserttemp[$vi] = $viv;
                      }
                    }
                    $datatoinsert = $datatoinserttemp;
                  }
                }

                $insert = $this->coreFunctions->sbcinsert($table, $datatoinsert);
                break;
            }
          }
        } else {
          $this->coreFunctions->LogConsole(json_encode($valtoinsert) . 'update to');
          if ($type == 'updateitem') { //check uom if already have transaction then unset            
            if ($companyid == 40) {
              $itemid = $this->coreFunctions->datareader("select itemid as value from item where partno=?", [$valtoinsert['partno']], '', true);
            } else {
              $itemid = $this->coreFunctions->datareader("select itemid as value from item where barcode=?", [$valtoinsert['barcode']], '', true);
            }

            if ($itemid != 0) {
              $olduom = $this->coreFunctions->datareader("select uom as value from item where barcode=?", [$valtoinsert['barcode']]);
              $transexist = $this->othersClass->checkuomtransaction($itemid, $olduom);
              if ($transexist) {
                unset($valtoinsert['uom']);
              }
            }
          }

          $datatoupdate = $valtoinsert;

          if ($type == 'updateemployeepayroll') {
            $datatoupdatetemp = [];
            foreach ($valtoinsert as $vi => $viv) {
              $valtoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid);
              $getclientemployeefields = $this->getclientemployeefields($vi);
              if ($getclientemployeefields != '') {
                $datatoupdatetemp[$vi] = $viv;
              }
            }
            $datatoupdate = $datatoupdatetemp;
          }

          if ($type == 'uploadcoc') {
            $datatoupdatetemp = [];
            foreach ($valtoinsert as $vi => $viv) {
              $valtoinsert[$vi] = $this->othersClass->sanitizekeyfield($vi, $viv, '', $companyid);
              $getarticlefields = $this->getarticlefields($vi);
              if ($getarticlefields != '') {
                $datatoupdatetemp[$vi] = $viv;
              }
            }
            $datatoupdate = $datatoupdatetemp;
          }

          //var_dump('uniquefield:'.$unique.'-'.$valtoinsert[$uniqueval]);
          // var_dump($datatoupdate);
          // return 0;

          $insert = $this->coreFunctions->sbcupdate($table, $datatoupdate, [$unique => $valtoinsert[$unique]]);
        }


        if ($insert != 0) {
          if ($tabletype == 'item') {
            if ($type == 'newitem' || $type == 'newfams') {
              if ($itemfactor != 0) {
                $valtoinsert['factor'] = $itemfactor;
              }
              if ($itemuom != '') {
                $valtoinsert['uom'] = $itemuom;
              }
              $result = $this->insertitemuom($valtoinsert, $unique, $config);
              if (!$result['status']) {
                //$this->coreFunctions->LogConsole(json_encode($valtoinsert));
                $status = false;
                $msg .= $result['msg'];
              }

              if ($iteminfo) {
                $result = $this->insertiteminfo($iteminfo, $unique, $type, $config);
                if (!$result['status']) {
                  $status = false;
                  $msg .= $result['msg'];
                }
              }

              if ($companyid == 47) { //kstar
                if ($itemlevel) {
                  $result = $this->insertitemlevel($itemlevel, $unique, $type, $config);
                  if (!$result['status']) {
                    $status = false;
                    $msg .= $result['msg'];
                  }
                }
              }
            } elseif ($type == 'updateitem' || $type == 'updatefams') {
              if ($itemuom != '') {
                $valtoinsert['uom'] = $itemuom;
              }
              if ($companyid == 37) { //mega
                $result = $this->insertitemuom($valtoinsert, $unique, $config);
                if (!$result['status']) {
                  $status = false;
                  $msg .= $result['msg'];
                }
              }

              if ($companyid == 16) {
                if ($iteminfo) {
                  $result = $this->insertiteminfo($iteminfo, $unique, $type, $config);
                  if (!$result['status']) {
                    $status = false;
                    $msg .= $result['msg'];
                  }
                }
              }

              if ($companyid == 47) { //kstar
                if ($itemlevel) {
                  $result = $this->insertitemlevel($itemlevel, $unique, $type, $config);
                  if (!$result['status']) {
                    $status = false;
                    $msg .= $result['msg'];
                  }
                }
              }
            }
          }

          if ($clientinfo) {
            $result = $this->insertclientinfo($clientinfo, $unique);
            if (!$result['status']) {
              $status = false;
              $msg .= $result['msg'];
            }
          }

          if ($type == 'newemployeepayroll') {
            if ($ratesetupinfo) {
              $result = $this->autogenerateratesetup($ratesetupinfo, $uniqueval);
              if (!$result['status']) {
                $status = false;
                $msg .= $result['msg'];
                $deleteclientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['client']]);
                $this->coreFunctions->execqry("delete from client where client=?", 'delete', [$valtoinsert['client']]);
                $this->coreFunctions->execqry("delete from employee where empid=?", 'delete', [$deleteclientid]);
              }
            }
          }

          if ($tabletype == 'billingaddr') {
            if ($valtoinsert['billdefault'] == 1) {
              $result = $this->coreFunctions->sbcupdate("client", ["billid" => $insert], ["clientid" => $valtoinsert['clientid']]);
            }

            if ($valtoinsert['shipdefault'] == 1) {
              $result = $this->coreFunctions->sbcupdate("client", ["shipid" => $insert], ["clientid" => $valtoinsert['clientid']]);
            }
          }

          if ($tabletype == 'contactperson') {
            if ($valtoinsert['billdefault'] == 1) {
              $result = $this->coreFunctions->sbcupdate("client", ["billcontactid" => $insert], ["clientid" => $valtoinsert['clientid']]);
            }

            if ($valtoinsert['shipdefault'] == 1) {
              $result = $this->coreFunctions->sbcupdate("client", ["shipcontactid" => $insert], ["clientid" => $valtoinsert['clientid']]);
            }
          }

          if ($insert) {
            if (!empty($employeeinfo)) {
              $employeeinfo['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['client']]);
              if ($employeeinfo['empid'] != 0) {

                if (isset($employeeinfo['tin'])) if ($employeeinfo['tin'] != '') $employeeinfo['chktin'] = 1;
                if (isset($employeeinfo['sss'])) if ($employeeinfo['sss'] != '') $employeeinfo['chksss'] = 1;
                if (isset($employeeinfo['phic'])) if ($employeeinfo['phic'] != '') $employeeinfo['chkphealth'] = 1;
                if (isset($employeeinfo['hdmf'])) if ($employeeinfo['hdmf'] != '') $employeeinfo['chkpibig'] = 1;

                foreach ($employeeinfo as $kemp => $valemp) {
                  $employeeinfo[$kemp] = $this->othersClass->sanitizekeyfield($kemp, $valemp, '', $companyid);;
                }

                $empid = $this->coreFunctions->getfieldvalue("employee", "empid", "empid=?", [$employeeinfo['empid']], '', true);
                if ($empid == 0) {
                  $insert = $this->coreFunctions->sbcinsert("employee", $employeeinfo);
                } else {
                  $employeeinfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
                  $employeeinfo['editby'] =  $config['params']['user'];
                  $this->coreFunctions->LogConsole(json_encode($employeeinfo));
                  $insert = $this->coreFunctions->sbcupdate("employee", $employeeinfo, ['empid' => $employeeinfo['empid']]);
                  $this->coreFunctions->LogConsole("udpate result: " . $insert);
                }

                if (!$insert) {
                  $status = false;
                  $msg .= 'Failed to insert employee other info ' . $valtoinsert['client'] . '. ';
                }
              }
            }

            if (!empty($educationinfo)) {
              $educationinfo['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['client']], '', true);
              if ($educationinfo['empid'] != 0) {

                if (isset($educationinfo['schooladdr'])) {
                  $educationinfo['address'] = $educationinfo['schooladdr'];
                  unset($educationinfo['schooladdr']);
                }

                if (!isset($educationinfo['sy'])) $educationinfo['sy'] = '';
                if (!isset($educationinfo['school'])) $educationinfo['school'] = '';

                $empid = $this->coreFunctions->getfieldvalue("education", "empid", "empid=? and school=? and sy=?", [$educationinfo['empid'], $educationinfo['school'], $educationinfo['sy']]);
                $empid = (($empid == "") ? 0 : $empid);
                if ($empid == 0) {
                  $insert = $this->coreFunctions->sbcinsert("education", $educationinfo);
                } else {
                  $insert = $this->coreFunctions->sbcupdate("education", $educationinfo, ['empid' => $educationinfo['empid'], 'school' => $educationinfo['school'], 'sy' => $educationinfo['sy']]);
                }

                if (!$insert) {
                  $status = false;
                  $msg .= 'Failed to insert employee education ' . $valtoinsert['client'] . '. ';
                }
              }
            }

            if (!empty($contactinfo)) {
              $contactinfo['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$valtoinsert['client']], '', true);
              if ($contactinfo['empid'] != 0) {
                $empid = $this->coreFunctions->getfieldvalue("contacts", "empid", "empid=? and contact1=?", [$contactinfo['empid'], $contactinfo['contact1']]);
                $empid = (($empid == "") ? 0 : $empid);
                if ($empid == 0) {
                  $insert = $this->coreFunctions->sbcinsert("contacts", $contactinfo);
                } else {
                  $insert = $this->coreFunctions->sbcupdate("contacts", $contactinfo, ['empid' => $contactinfo['empid'], 'contact1' => $contactinfo['contact1']]);
                }

                if (!$insert) {
                  $status = false;
                  $msg .= 'Failed to insert employee contacts ' . $valtoinsert['client'] . '. ';
                }
              }
            }

            if (!empty($sectioninfo)) {
              if (isset($sectioninfo['section'])) {
                $artid = $this->coreFunctions->getfieldvalue("codehead", "artid", $unique . "=?", [$uniqueval]);
                if ($artid != 0) {
                  $section_lineexist = $this->coreFunctions->getfieldvalue("codedetail", "line", "artid=? and section=?", [$artid, $sectioninfo['section']], '', true);

                  if ($section_lineexist == 0) {
                    $qry = "select line as value from codedetail where artid=? order by line desc limit 1";
                    $line = $this->coreFunctions->datareader($qry, [$artid], '', true);
                    $line = $line + 1;
                    $sectioninfo['artid'] = $artid;
                    $sectioninfo['line'] = $line;
                  }
                  $sectioninfo['description'] = $sectioninfo['sectiondescription'];
                  unset($sectioninfo['sectiondescription']);
                  if ($section_lineexist == 0) {
                    $this->coreFunctions->sbcinsert("codedetail",  $sectioninfo);
                  } else {
                    $this->coreFunctions->sbcupdate("codedetail",  $sectioninfo, ['artid' => $artid, 'line' => $section_lineexist]);
                  }
                }
              }
            }
          }

          if ($type == 'newemployeepayroll' || $type == 'updateemployeepayroll') {
            if ($status) $this->updateroleinfo($uniqueval);
          }

          if ($tabletype == 'pricelist') {
            $result_pricelist = $this->updatePriceListEffectivity($valtoinsert, $config['params']['user']);
          }

          if ($tabletype == 'ratesetup') {
            $result_allowance = $this->insertAllowance($valtoinsert, $config['params']['user'], false);
            if (!$result_allowance['status']) {
              $msg .= $uniqueval . ', failed to upload allowance. ' . $result_allowance['msg'] . ' ';
            }
          }
        } else {
          $status = false;
          $msg .= $uniqueval . ', failed to upload. ' . $this->coreFunctions->errmsg . ' ';
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= "(" . $uniqueval . ") Failed to upload. File: " . $e->getFile() . " Line: " . $e->getLine() . ". Exception error " . $e->getMessage();
        goto exithere;
      }


      NextLoopHere:
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    }
    return ['status' => $status, 'msg' => $msg];
  }

  private function gettablename($field, $type, $dbtable = false)
  {
    if ($dbtable) return $field;

    switch ($field) {
      case 'model':
        return 'model_masterfile';
        break;
      case 'part':
        return 'part_masterfile';
        break;
      case 'group':
      case 'groupid':
        return 'stockgrp_masterfile';
        break;
      case 'brand':
        return 'frontend_ebrands';
        break;
      case 'class':
        return 'item_class';
        break;
      case 'category':
        if ($type == 'item') {
          return 'itemcategory';
        } else {
          return 'category_masterfile';
        }
        break;
      case 'subcat':
        return 'itemsubcategory';
        break;
      case 'projectid':
        return 'projectmasterfile';
        break;
      case 'ewtid':
        return 'ewtlist';
        break;
      case 'forexid':
        return 'forex_masterfile';
        break;
      case 'customer':
      case 'supplier':
      case 'wh':
      case 'agent':
      case 'employee':
      case 'truck':
      case 'branch':
        return 'client';
        break;
      case 'item':
      case 'contactperson':
      case 'billingaddr':
      case 'uom':
      case 'deliverytype':
      case 'pnpcsr':
      case 'supplieritem':
      case 'pricelist':
      case 'ratesetup';
      case 'codehead':
      case 'allowsetup':
        return $field;
        break;
      default:
        return '';
        break;
    }
  }

  private function getuniquefield($id, $config, $type = '')
  {
    switch ($id) {
      case 'client':
        switch ($config['params']['companyid']) {
          case 16:
            if ($type == 'employee') {
              return 'clientname';
            } else {
              return 'client';
            }
            break;
          default:
            return 'client';
            break;
        }
        break;

      case 'item':
        switch ($config['params']['companyid']) {
          case 10:
          case 12:
          case 40: //cdo
            return 'partno';
            break;

          default:
            return 'barcode';
        }

        break;
      case 'contactperson':
      case 'billingaddr':
        return 'line';
        break;
      case 'pricelist':
        return 'barcode';
        break;
      case 'ratesetup':
      case 'allowsetup':
        return 'empid';
        break;
      case 'codehead':
        return 'code';
        break;
      default:
        return '';
        break;
    }
  }

  private function getequivalentfieldname($field, $table, $type, $config)
  {
    $companyid = $config['params']['companyid'];
    switch (strtolower($field)) {
      case 'trucking':
        return 'ship';
        break;
      case 'agent_name':
        if ($type == '') {
          return 'agent';
        } else {
          return strtolower($field);
        }

        break;
      case 'customercode';
      case 'agentcode':
      case 'warehousecode':
      case 'truckcode':
        if ($companyid == 60 && strtolower($field) == 'agentcode') {
          return 'agent';
        }
        return 'client';
        break;
      case 'branchcode':
        if ($table == 'pricelist') {
          return 'branchid';
        } else {
          return 'client';
        }
        break;

      case 'employee id no':
      case 'employee code':
        return 'client';
        break;

      case 'employee id no (reference)':
        return 'empnoref';
        break;

      case 'employeecode':
        if ($type == 'newfams' || $type == 'updatefams' || $type == 'issueitem' || $type == 'updateemployeerate' || $type == 'newallowance') {
          return 'empid';
        } else {
          return 'client';
        }
        break;

      case 'suppliercode':
        if ($table == 'item') {
          return 'supplier';
        } else {
          return 'client';
        }
        break;

      case 'suppliername':
        if ($table == 'item') {
          return 'suppname';
        } else {
          return 'clientname';
        }
        break;

      case 'article code':
        return 'code';
        break;

      case 'article description':
        return 'description';
        break;

      case 'section description':
        return 'sectiondescription';
        break;

      case 'first offense':
        return 'd1a';
        break;
      case 'no of days (1)':
        return 'd1b';
        break;
      case 'second offense':
        return 'd2a';
        break;
      case 'no of days (2)':
        return 'd2b';
        break;
      case 'third offense':
        return 'd3a';
        break;
      case 'no of days (3)':
        return 'd3b';
        break;
      case 'fourth offense':
        return 'd4a';
        break;
      case 'no of days (4)':
        return 'd4b';
        break;
      case 'fifth offense':
        return 'd5a';
        break;
      case 'no of days (5)':
        return 'd5b';
        break;

      case 'customername':
      case 'employeename':
      case 'agentname':
      case 'warehousename':
      case 'truckname':
      case 'branchname':
        return 'clientname';
        break;

      case 'branchprefix':
        return 'prefix';
        break;

      case 'lastname':
        return 'emplast';
        break;

      case 'firstname':
        return 'empfirst';
        break;

      case 'middlename':
        return 'empmiddle';
        break;

      case 'role':
        return 'roleid';
        break;

      case 'hired role':
        return 'roleid2';
        break;

      case 'modeofpayment':
        return 'paymode';
        break;

      case 'shift':
      case 'shiftcode':
        return 'shiftid';
        break;

      case 'is_sss':
        return 'chksss';
        break;

      case 'is_hdmf':
        return 'chkpibig';
        break;

      case 'is_phic':
        return 'chkphealth';
        break;

      case 'is_tin':
        return 'chktin';
        break;

      case 'birthday':
        return 'bday';
        break;

      case 'datehired':
      case 'date hired':
        return 'hired';
        break;

      case 'bankaccount':
      case 'atm no.':
        return 'bankacct';
        break;

      case 'salary':
      case 'current rate':
        return 'basicrate';
        break;

      case 'rateeffectivity':
        return 'dateeffect';
        break;

      case 'current responsibility allowance':
      case 'allowance':
        return 'allowance';
        break;

      case 'allowance code':
        return 'acno';
        break;

      case 'address':
      case 'addresstitle':
      case 'present address':
        if ($type == 'newmeter' || $type == 'updatemeter') {
          return 'shortname';
        }
        return 'addr';
        break;

      case 'address2':
        return 'addr2';
        break;

      case 'shippingaddress':
        return 'ship';
        break;

      case 'permanent address':
        return 'permanentaddr';
        break;

      case 'telephonenumber':
      case 'telno':
        return 'tel';
        break;

      case 'faxnumber':
      case 'faxno':
        return 'fax';
        break;

      case 'contactperson':
        return 'contact';
        break;

      case 'fbname':
        return 'acct';
        break;

      case 'creditlimit':
        return 'crlimit';
        break;

      case 'ownername':
        return 'owner';
        break;

      case 'ownercontact':
        return 'mobile';
        break;

      case 'businesstype':
        return 'bstyle';
        break;

      case 'busstyle':
        return 'category';
        break;

      case 'orgstructure':
        return 'type';
        break;

      case 'itemcode':
      case 'item_code':
        return 'barcode';
        break;

      case 'itemdescription':
      case 'full_item_name':
        return 'itemname';
        break;

      case 'shortdescription':
        return 'shortname';
        break;

      case 'classification':
        return 'class';
        break;

      case 'employee category':
        return 'workcatid';
        break;

      case 'department':
      case 'departmentcode':
        if ($table == 'client' || $type == 'newemployeepayroll' || $type == 'updateemployeepayroll') {
          return 'deptid';
        } elseif ($table == 'item') {
          if ($type == 'newfams' || $type == 'updatefams') {
            return 'locid';
          } else {
            return 'linkdept';
          }
        } elseif ($table == 'issueitem') {
          return 'locid';
        }
        break;


      case 'section':
        return 'sectid';
        break;

      case 'principal':
        return 'part';
        break;

      case 'generic':
      case 'description_2':
        return 'model';
        break;

      case 'unitofmeasurement':
      case 'unit':
        return 'uom';
        break;

      case 'price1':
        if ($type == 'updatepricelist') {
          return 'amount';
        } else {
          return 'amt';
        }
        break;

      case 'price2':
        if ($type == 'updatepricelist') {
          return 'amount2';
        } else {
          return 'amt2';
        }
        break;

      case 'retail':
      case 'standard':
      case 'base_price':
        return 'amt';
        break;

      case 'wholesale':
      case 'wholesale_price':
        return 'amt2';
        break;

      case 'distributor':
        if ($companyid == 60) { //transpower
          return 'famt';
        } else {
          return 'amt2';
        }
        break;

      case 'price_a':
        return 'famt';
        break;

      case 'price_b':
        return 'amt4';
        break;

      case 'price_c':
      case 'invoice_price':
        return 'amt5';
        break;

      case 'price_d':
      case 'lowest_price':
        return 'amt6';
        break;

      case 'price_e':
      case 'dr_price':
        return 'amt7';
        break;

      case 'price_f':
        return 'amt8';
        break;

      case 'price_g':
        return 'amt9';
        break;

      case 'baseprice':
        return 'amt16';
        break;

      case 'discount1':
      case 'discount_r':
      case 'discount':
      case 'base_disc':
        return 'disc';
        break;

      case 'discount2':
      case 'discount_w':
      case 'wholesale_disc':
        return 'disc2';
        break;

      case 'discount_a':
      case 'distributor_disc':
        return 'disc3';
        break;

      case 'discount_b':
      case 'cost_disc':
        return 'disc4';
        break;

      case 'discount_c':
      case 'invoice_disc':
        return 'disc5';
        break;

      case 'discount_d':
      case 'lowest_disc':
        return 'disc6';
        break;

      case 'discount_e':
      case 'dr_disc':
        return 'disc7';
        break;

      case 'discount_f':
        return 'disc8';
        break;

      case 'discount_g':
        return 'disc9';
        break;

      case 'subcategory':
      case 'sub category':
      case 'sub_category':
        return 'subcat';
        break;

      case 'size':
      case 'bin':
        if ($companyid == 39 && strtolower($field) == 'bin') {
          return 'body';
        } else {
          return 'sizeid';
        }
        break;

      case 'isservice':
        return 'islabor';
        break;

      case 'isasset':
      case 'isfa':
        return 'isfa';
        break;

      case 'description':
        return 'itemdescription';
        break;
      case 'mobile':
      case 'tel2/other':
        if ($table == 'contactperson') {
          return 'mobile';
        } else {
          return 'tel2';
        }
        break;

      case 'project':
      case 'project_code':
        return 'projectid';
        break;

      case 'taxcode':
        return 'ewtid';
        break;

      case 'group':
      case 'groupid':
        return 'groupid';
        break;
      case 'creditdaysbasedon':
        return 'crtype';
        break;
      case 'creditdays':
        return 'crdays';
        break;
      case 'customergroup':
        return 'groupid';
        break;
      case 'notes':
        return 'rem';
        break;
      case 'company';
        if ($type == 'newemployeepayroll' || $type == 'updateemployeepayroll') {
          return 'divid';
        } else {
          return 'company';
        }
        break;

      case 'remarks':
        switch ($type) {
          case 'newemployeepayroll':
          case 'updatepricelist':
          case 'updateemployeerate':
            return 'remarks';
            break;
          case 'newitem':
          case 'newfams':
            return 'itemrem';
            break;
          default:
            return 'rem';
            break;
        }
        break;

      case 'currency':
        return 'forexid';
        break;
      case 'phone':
        return 'contactno';
        break;
      case 'shipping':
        return 'isshipping';
        break;
      case 'billing':
        return 'isbilling';
        break;

      case 'vatable':
        return 'isvat';
        break;

      case 'employeeno':
        return 'idbarcode';
        break;

      case 'pricegroup':
      case 'description_1':
        return 'class';
        break;

      case 'ischecker':
        return 'uv_ischecker';
        break;

      case 'ishelper':
        return 'ispassenger';
        break;

      case 'ispositem':
        return 'ispositem';
        break;

      case 'isfinishedgood':
        return 'fg_isfinishedgood';
        break;

      case 'isprintable':
        return 'isprintable';
        break;

      case 'cis':
        return 'iscis';
        break;

      case 'inactive':
      case 'inactive_item_tag':
        return 'isinactive';
        break;

      case 'actualweight':
      case 'qty/ctn':
        return 'tqty';
        break;

      case 'noninventory':
        return 'isnoninv';
        break;

      case 'trucktype':
        return 'deliverytype';
        break;

      case 'capacityintons':
        return 'capacity';
        break;

      case 'specs':
        return 'shortname';
        break;

      case 'meterno':
        return 'barcode';
        break;

      case 'vat type':
        return 'vattype';
        break;

      case 'sku':
      case 'sku/part no.':
        return 'partno';
        break;

      case 'serialno':
        return 'serialno';
        break;

      case 'bio_id':
      case 'biometric id';
        return 'idbarcode';
        break;

      case 'asset/truck':
        return 'fg_isequipmenttool';
        break;

      case 'tagcode':
        return 'barcode';
        break;

      case 'general item code':
        return 'subcode';
        break;

      case 'sub group':
        return 'subgroup';
        break;

      case 'acquisition date':
        return 'dateacquired';
        break;
      case 'floorprice':
      case 'floor price':
        return 'foramt';
        break;
      case 'superseding':
        return 'body';
        break;
      case 'cbm':
        return 'dqty';
        break;

      case 'truckbrand':
        return 'classification';
        break;

      case 'deliverytype':
        return 'type';
        break;

      case 'tonnagecapacity':
        return 'capacity';
        break;

      case 'yearmodel':
        return 'type';
        break;

      case 'call sign':
        return 'callsign';
        break;

      case 'employee type':
        return 'empstatus';
        break;

      case 'work category':
      case 'orgsection':
        return 'sectid';
        break;

      case 'tax status':
        return 'teu';
        break;

      case 'no of dep':
        return 'nodeps';
        break;

      case 'contirbuting company for benefits':
        return 'contricomp';
        break;

      case 'type of rate':
        return 'classrate';
        break;

      case 'civil status':
        return 'status';
        break;

      case 'contact number':
        return 'mobileno';
        break;

      case 'tin no.':
        return 'tin';
        break;

      case 'sss no.':
        return 'sss';
        break;

      case 'hdmf no.':
        return 'hdmf';
        break;

      case 'phic no.':
        return 'phic';
        break;

      case 'branch code':
        return 'branchid';
        break;

      case 'hired branch code':
        return 'branchid2';
        break;

      case 'hired supervisor':
        return 'supervisorid2';
        break;

      case 'direct head':
        return 'supervisorid';
        break;

      case 'jobtitle':
      case 'job title':
        return 'jobid';
        break;

      case 'hired job title':
        return 'jobid2';
        break;

      case 'school address':
        return 'school address';
        break;

      case 'year graduated':
        return 'sy';
        break;

      case 'meal':
        return 'mealdeduc';
        break;

      case 'complete name of spouse / common law partner':
        return 'contact1';
        break;
      case 'main_category':
        return 'category';
        break;
      case 'cost':
        if ($companyid == 60) { //transpower
          return 'amt4';
        } else {
          return 'cost';
        }
        break;

      case 'barcode':
      case 'itemname':
      case 'uom':
      case 'class':
      case 'brand':
      case 'model':
      case 'category':
      case 'color';
      case 'partno':
      case 'isserial':
      case 'isoutsource':
      case 'isgeneric':
      case 'email':
      case 'area':
      case 'areacode':
      case 'province':
      case 'region':
      case 'accessories';
      case 'markup':
      case 'tin':
      case 'terms':
      case 'issubcon':
      case 'vattype':
      case 'industry':
      case 'territory':
      case 'activity':
      case 'agent':
      case 'ispassenger':
      case 'isdriver':
      case 'isapprover':
      case 'isagent':
      case 'asset':
      case 'revenue':
      case 'expense':
      case 'liability':
      case 'salesreturn':
      case 'start':
      case 'factor':
      case 'body':
      case 'level':
      case 'classrate':
      case 'paygroup':
      case 'sss':
      case 'hdmf':
      case 'phic':
      case 'status':
      case 'gender':
      case 'paymode':
      case 'jobtitle';
      case 'branch':
      case 'religion':
      case 'course':
      case 'school':

      case 'channel':
      case 'isimport':
      case 'avecost':
      case 'plateno':
      case 'isofficesupplies':
      case 'aimsid':
      case 'bank':
      case 'minimum':
      case 'maximum':

      case 'sssdef':
      case 'phildef':
      case 'pibigdef':
      case 'wtaxdef':
      case 'dyear':
      case 'chktin':
      case 'chksss':
      case 'chkphealth':
      case 'chkpibig':
      case 'meal':
      case 'atm':
      case 'regular':
      case 'resigned':
      case 'hired':
      case 'lastbatch':
        return strtolower($field);
        break;

      case 'net_invoice':
        return 'namt5';
        break;
      case 'net_wholesale':
        return 'namt2';
        break;
      case 'net_cost':
        return 'namt4';
        break;
      case 'net_distributor':
        return 'nfamt';
        break;
      case 'net_lowest':
        return 'namt6';
        break;
      case 'net_dr':
        return 'namt7';
        break;
      case 'start_wire_mtr':
        return 'startwire';
        break;
      case 'end_wire_mtr':
        return 'endwire';
        break;
      case 'item_wire_tag':
        return 'iswireitem';
        break;
      case 'reverse_wire_tag':
        return 'isreversewireitem';
        break;
      case 'code':
        return 'client';
        break;
      case 'mobile/tel2':
        return 'tel2';
        break;
      case 'registeredname':
        return 'registername';
        break;
      case 'e-mail':
        return 'email';
        break;
      default:
        if ($companyid == 22) {
          switch (strtolower($field)) {
            case 'category 1':
              return 'category';
              break;
            case 'category 2':
              return 'groupid';
              break;
            case 'category 3':
              return 'subcat';
              break;
          }
        } else {
          return strtolower($field);
        }

        break;
    }
  }

  private function linktoothertable($field, $value, $excelheader, $tablename, $uniqueval, $uploadtype)
  {
    $success = true;
    $msg = '';
    $qry = '';
    $item = 0;

    if ($value != '') {
      switch ($field) {
        case 'agent_name':
          $qry = "select client as value from client where isagent=1 and clientname='" . $value . "'";
          break;
        case 'agent':
          if ($tablename == 'item') {
            $qry = "select clientid as value from client where isagent=1 and client='" . $value . "'";
          } else {
            return ['status' => $success, 'msg' => $msg, 'id' => 0];
          }
          break;
        case 'deptid':
        case 'linkdept':
          if ($uploadtype == 'newemployeepayroll' || $uploadtype == 'updateemployeepayroll') {
            $qry = "select clientid as value from client where isdepartment=1 and clientname='" . $value . "'";
          } else {
            $qry = "select clientid as value from client where isdepartment=1 and client='" . $value . "'";
          }
          break;
        case 'supplier':
          $qry = "select clientid as value from client where issupplier=1 and client='" . $value . "'";
          break;
        case 'terms':
          $qry = "select line as value from terms where terms='" . $value . "'";
          break;
        case 'branchid':
        case 'branchid2':
          $qry = "select clientid as value from client where isbranch=1 and client='" . $value . "'";
          break;
        case 'divid':
          $qry = "select divid as value from division where `divname`='" . $value . "'";
          break;
        case 'sectid':
          $qry = "select sectid as value from section where `sectname`='" . $value . "'";
          // $qry = "select sectid as value from section where `sectcode`='" . $value . "'";
          break;
        case 'jobid':
        case 'jobid2':
          if (substr($value, 0, 2) == 'JT' && strlen($value) == 15) {
            $qry = "select line as value from jobthead where docno='" . $value . "'";
          } else {
            $qry = "select line as value from jobthead where jobtitle='" . $value . "'";
          }
          break;
        case 'workcatid':
          $qry = "select line as value from reqcategory where isreassigned=1 and category='" . $value . "'";
          break;
        case 'empid':
          if ($uploadtype == 'updateemployeerate' || $uploadtype == 'newallowance') {
            $qry = "select clientid as value from client where isemployee=1 and client='" . $value . "'";
          }
          break;
        case 'supervisorid':
        case 'supervisorid2':
          switch ($value) {
            case 'CHIEF EXECUTIVE OFFICER':
              return ['status' => true, 'id' => 0];
              break;
          }
          $qry = "select clientid as value from client where isemployee=1 and client='" . $value . "'";
          break;
        case 'asset':
        case 'revenue':
        case 'expense':
        case 'rev':
        case 'salesreturn':
          $value =  str_replace("\\", "", $value);
          $qry = "select acnoid as value from coa where acno='\\\\" . $value . "'";
          break;
        case 'clientid':
          $qry = "select clientid as value from client where (issupplier=1 or iscustomer=1) and client='" . $value . "'";
          break;
        case 'roleid':
        case 'roleid2':
          $qry = "select line as value from rolesetup where name='" . $value . "'";
          break;
        case 'paygroup':
          $qry = "select line as value from paygroup where code='" . $value . "'";
          break;
        case 'shiftid':
          $qry = "select line as value from tmshifts where shftcode='" . $value . "'";
          break;
        case 'projectid':
          $qry = "select line as value from projectmasterfile where `name`='" . $value . "'";
          break;
        case 'empstatus':
          $qry = "select line as value from empstatentry where `empstatus`='" . $value . "'";
          break;
      }
      if ($qry != '') {
        // $this->othersClass->logConsole($qry);
        $item = $this->coreFunctions->datareader($qry, [], '', true);
        if ($item != 0) {
          $success = true;
        } else {
          $success = false;
          $msg = $excelheader . ' ' . $value . ' is not exists. Please setup first ';
        }
      }
    }

    return ['status' => $success, 'msg' => '(' . $uniqueval . ') ' . $msg, 'id' => $item];
  }

  private function clientstatus($field, $val, $uploadtype = '')
  {
    if ($val == '') {
      return ['status' => true, 'msg' => '', 'val' => $val];
    }

    switch ($uploadtype) {
      case 'newemployeepayroll':
      case 'updateemployeepayroll':
        $stat = array('Single', 'Married', 'Divorced', 'Widowed', 'Living Common Law', 'Separated');
        break;
      default:
        $stat = array('ACTIVE', 'LAPSED', 'ABANDONED', 'SUSPENDED', 'LEGAL', 'CLOSED', 'HOLD', 'INACTIVE', 'REFER');
        break;
    }

    if ($this->othersClass->in_arrayi($val, $stat)) {
    } else {
      return ['status' => false, 'msg' => 'Invalid status ' . $val, 'val' => ''];
    }

    return ['status' => true, 'msg' => '', 'val' => $val];
  }

  private function otherfixsetup($field, $val, $uploadtype, $fieldlabel, $unique)
  {
    switch ($field) {
      case 'classrate':
        if ($val == '') goto returndefault;
        switch (strtolower($val)) {
          case 'monthly':
            $val = 'M';
            break;
          case 'daily':
            $val = 'D';
            break;
        }
        $stat = array('M', 'D');
        break;

      case 'gender':
        if ($val == '') goto returndefault;
        switch (strtolower($val)) {
          case 'm':
            $val = 'Male';
            break;
          case 'f':
            $val = 'Female';
            break;
          case 'gay':
          case 'lesbian':
            $val = 'LGBT';
            break;
        }
        $stat = array('Male', 'Female', 'LGBT');
        break;

      case 'paymode':
        switch (strtolower($val)) {
          case 'daily':
            $val = 'D';
            break;
          case 'monthly':
            $val = 'M';
            break;
          case 'semi-monthly':
          case 'semi':
          case 'semi monthly':
            $val = 'S';
            break;
          case 'weekly':
            $val = 'W';
            break;
          case 'piece rate':
            $val = 'P';
            break;
        }
        $stat = array('S', 'M', 'D', 'W', 'P');
        break;
    }

    if ($this->othersClass->in_arrayi($val, $stat)) {
    } else {
      return ['status' => false, 'msg' => '(' . $unique . ') Invalid ' . $fieldlabel . ' ' . $val, 'val' => ''];
    }

    returndefault:
    return ['status' => true, 'msg' => '', 'val' => $val];
  }


  private function validfield($field, $val)
  {
    $fieldname = '';
    $stat = [];
    switch (strtoupper($field)) {
      case 'VATTYPE':
        $stat = array('VATABLE', 'NON-VATABLE', 'ZERO-RATED');
        $fieldname = 'VAT Type ';
        break;
      case 'STATUS':
        $stat = array('ACTIVE', 'LAPSED', 'ABANDONED', 'SUSPENDED', 'LEGAL', 'CLOSED', 'HOLD', 'INACTIVE');
        $fieldname = 'Status ';
        break;
      case 'TYPE':
        $stat = array('Sole Proprietorship', 'Partnership', 'Corporation', '');
        $fieldname = 'Organizational Structure ';
        break;
    }

    if (in_array($val, $stat)) {
    } else {
      return ['status' => false, 'msg' => 'Invalid ' . $fieldname . $val, 'val' => ''];
    }

    return ['status' => true, 'msg' => '', 'val' => $val];
  }

  private function checkfieldmasterfield($field, $val, $tablename)
  {
    $result = $val;
    switch ($tablename) {
      case 'item':
        switch ($field) {
          case 'class':
          case 'groupid':
          case 'model':
          case 'part':
          case 'brand':
          case 'category':
          case 'subcat':
          case 'projectid':
            if ($val == "") {
              return 0;
            }
            getidhere:
            $check = $this->isexist($field, $val, 'name', $tablename);

            if ($check) {
              $result = $check;
            } else {
              $value = [$this->getfieldmastername($field, $tablename) => $val]; //substr($val,0,$max)   
              $this->coreFunctions->sbcinsert($this->gettablename($field, $tablename), $value);

              goto getidhere;
            }
            break;
        }
        break;
      case 'client':
        switch ($field) {
          case 'category':
          case 'forexid':
          case 'ewtid':
          case 'deliverytype':
            if ($val == "") {
              return 0;
            }
            getidherec:
            $check = $this->isexist($field, $val, 'name', $tablename);

            if ($check) {

              $result = $check;
            } else {
              $value = [$this->getfieldmastername($field, $tablename) => $val]; //substr($val, 0, 150)]; //substr($val,0,$max)
              $this->coreFunctions->sbcinsert($this->gettablename($field, $tablename), $value);

              goto getidherec;
            }
            break;
          case 'agent_name':
            $check = $this->isexist($field, $val, 'name', $tablename);
            break;
        }
        break;
    }
    $result = $this->padslashes($field, $result);
    $result = $this->formatdate($field, $result);
    return $result;
  }

  private function getfieldmastername($field, $tablename)
  {
    switch ($field) {
      case 'class':
        return 'cl_name';
        break;
      case 'part':
        return 'part_name';
        break;
      case 'model':
        return 'model_name';
        break;
      case 'groupid':
        return 'stockgrp_name';
        break;
      case 'brand':
        return 'brand_desc';
        break;
      case 'category':
        if ($tablename == 'item') {
          return 'name';
        } else {
          return 'cat_name';
        }
        break;
      case 'subcat':
      case 'projectid':
      case 'deliverytype':
        return 'name';
        break;
      case 'ewtid':
        return 'code';
        break;
      case 'forexid':
        return 'cur';
        break;
      case 'empstatus':
        return 'empstatentry';
        break;
    }
  }

  private function padslashes($field, $val)
  {
    $result = $val;
    switch ($field) {
      case 'asset':
      case 'revenue':
      case 'expense':
      case 'salesreturn':
        $result = "\\" . str_replace("\\", "", $val);
        break;
    }
    return $result;
  }

  private function formatdate($field, $value)
  {
    switch ($field) {
      case 'dateid':
        $date = date_create($value);
        return date_format($date, "Y-m-d H:i:s");
        break;

      default:
        return $value;
        break;
    }
  }

  /**
   * Convert an array of keys to a delimited string where each key has
   * an optional append string added to it.
   * Example: arraytostringwithdelimiter(['a','b'], '=?', ' AND ') -> "a=? AND b=?"
   *
   * @param array $arr
   * @param string $append string to append to each element (e.g. '=? ')
   * @param string $delimiter separator between elements (e.g. ' AND ')
   * @return string
   */
  private function arraytostringwithdelimiter($arr, $append = '', $delimiter = ', ')
  {
    if (!is_array($arr) || empty($arr)) return '';
    $parts = [];
    foreach ($arr as $k) {
      $k = trim($k);
      if ($k === '') continue;
      $parts[] = $k . $append;
    }
    return implode($delimiter, $parts);
  }


  private function isexist($field, $id, $type = 'id', $tablename, $returnfield = 'id')
  {
    $qry = '';
    switch ($type) {
      case 'id':
        switch ($field) {
          case 'model':
            $returnfieldval = ($returnfield == 'id') ? 'model_id' : 'model_name';
            $qry = "select " . $returnfieldval . " as value from model_masterfile where model_id=" . $id;
            break;
          case 'part':
            $returnfieldval = ($returnfield == 'id') ? 'part_id' : 'part_name';
            $qry = "select " . $returnfieldval . " as value from part_masterfile where part_id=" . $id;
            break;
          case 'group':
          case 'groupid':
            $returnfieldval = ($returnfield == 'id') ? 'stockgrp_id' : 'stockgrp_name';
            $qry = "select " . $returnfieldval . " as value from stockgrp_masterfile where stockgrp_id=" . $id;
            break;
          case 'brand':
            $returnfieldval = ($returnfield == 'id') ? 'brandid' : 'brand_desc';
            $qry = "select " . $returnfieldval . " as value from frontend_ebrands where brandid=" . $id;
            break;
          case 'class':
            $returnfieldval = ($returnfield == 'id') ? 'cl_id' : 'cl_name';
            $qry = "select " . $returnfieldval . " as value from item_class where cl_id=" . $id;
            break;
          case 'category':
            if ($tablename == 'item') {
              $returnfieldval = ($returnfield == 'id') ? 'line' : 'name';
              $qry = "select " . $returnfieldval . " as value from itemcategory where line=" . $id;
            } else {
              $returnfieldval = ($returnfield == 'id') ? 'cat_id' : 'cat_name';
              $qry = "select " . $returnfieldval . " as value from category_masterfile where cat_id=" . $id;
            }
            break;
          case 'projectid':
            $returnfieldval = ($returnfield == 'id') ? 'line' : 'name';
            $qry = "select " . $returnfieldval . " as value from projectmasterfile where line=" . $id;
            break;
          case 'empstatus':
            $returnfieldval = ($returnfield == 'id') ? 'line' : 'empstatus';
            $qry = "select " . $returnfieldval . " as value from empstatentry where line=" . $id;
            break;
          case 'subcat':
            $returnfieldval = ($returnfield == 'id') ? 'line' : 'name';
            $qry = "select " .  $returnfieldval . " as value from itemsubcategory where line=" . $id;
            break;
          case 'ewtid':
            $returnfieldval = ($returnfield == 'id') ? 'line' : 'code';
            $qry = "select " . $returnfieldval . " as value from ewtlist where `line`='" . $id . "'";
            break;
          case 'forexid':
            $qry = "select line as value from forex_masterfile where `line`='" . $id . "'";
            break;
          case 'supplier':
            if ($tablename == 'item') {
              $returnfieldval = ($returnfield == 'id') ? 'clientid' : 'client';
              $qry = "select " . $returnfieldval . " as value from client where clientid=" . $id;
            }
            break;
          case 'deliverytype':
            $returnfieldval = ($returnfield == 'id') ? 'line' : 'name';
            $qry = "select " . $returnfieldval . " as value from deliverytype where line=" . $id;
            break;
        }
        break;

      case 'name':
        switch ($field) {
          case 'agent_name':
            $qry = "select client as value from client where clientname='" . $id . "'";
            break;
          case 'class':
            $qry = "select cl_id as value from item_class where cl_name='" . $id . "'";
            break;
          case 'groupid':
            $qry = "select stockgrp_id as value from stockgrp_masterfile where stockgrp_name='" . $id . "'";
            break;
          case 'model':
            $qry = "select model_id as value from model_masterfile where model_name='" . $id . "'";
            break;
          case 'part':
            $qry = "select part_id as value from part_masterfile where part_name='" . $id . "'";
            break;
          case 'category':
            if ($tablename == 'item') {
              $qry = "select line as value from itemcategory where `name`='" . $id . "'";
            } else {
              $qry = "select cat_id as value from category_masterfile where cat_name='" . $id . "'";
            }
            break;
          case 'brand':
            $qry = "select brandid as value from frontend_ebrands where brand_desc='" . $id . "'";
            break;
          case 'projectid':
            $qry = "select line as value from projectmasterfile where `name`='" . $id . "'";
            break;
          case 'empstatus':
            $qry = "select line as value from empstatentry where `empstatus`='" . $id . "'";
            break;
          case 'subcat':
            $qry = "select line as value from itemsubcategory where `name`='" . $id . "'";
            break;
          case 'ewtid':
            $qry = "select line as value from ewtlist where `code`='" . $id . "'";
            break;
          case 'forexid':
            $qry = "select line as value from forex_masterfile where `cur`='" . $id . "'";
            break;
          case 'deliverytype':
            $qry = "select line as value from deliverytype where `name`='" . $id . "'";
            break;
        }

        break;
    }
    if ($qry == '') {
      return true;
    }
    $item = $this->coreFunctions->datareader($qry, [], '', true);
    if ($item != 0) {
      return $item;
    } else {

      return false;
    }
  }

  private function insertitemuom($params, $unique, $config, $direct = false)
  {
    $companyid = $config['params']['companyid'];

    $uom = '';
    $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", $unique . "=?", [$params[$unique]]);
    if (isset($params['uom'])) {
      $uom = $params['uom'];
    } else {
      // $this->coreFunctions->LogConsole(json_encode($params));
      return ['status' => false, 'msg' => 'Failed to insert uom factor. Missing UOM for item ' . $params[$unique] . (isset($params['itemname']) ? ' - ' . $params['itemname'] : '') . '<br>'];
    }

    if ($itemid) {
      $check = $this->coreFunctions->getfieldvalue("uom", "itemid", "itemid=? and uom=?", [$itemid, $uom]);

      $data = [
        'itemid' => $itemid,
        'uom' => $uom
      ];

      if (isset($params['amt'])) $data['amt'] = $params['amt'];
      if (isset($params['amt2'])) $data['amt2'] = $params['amt2'];
      if (isset($params['famt'])) $data['famt'] = $params['famt'];

      if (isset($params['isdefault2'])) $data['isdefault2'] = $params['isdefault2'];
      if (isset($params['isinactive'])) $data['isinactive'] = $params['isinactive'];

      if (!$check) {

        $data['factor'] = isset($params['factor']) ? $params['factor'] : 1;
        $data['kilos'] = 1;

        if (isset($params['isdefault'])) {
          $data['isdefault'] = $params['isdefault'];
        } else {
          switch ($companyid) {
            case 14: //MAJESTY
            case 17: // unihome
            case 36: //ROZLAB
            case 39: //CBBSI
              $data['isdefault'] = 1;
              break;
          }
        }
        $this->coreFunctions->sbcupdate("uom", ['isdefault' => 0], ['itemid' => $itemid]);
        $this->coreFunctions->sbcinsert("uom", $data);
      } else {

        if (isset($params['isdefault'])) $data['isdefault'] = $params['isdefault'];

        $this->coreFunctions->sbcupdate("uom", $data,  ["itemid" => $itemid, 'uom' => $uom]);
      }
      $this->coreFunctions->sbcupdate("item", ['dlock' => $this->othersClass->getCurrentTimeStamp()], ['itemid' => $itemid]);
    } else {
      return ['status' => false, 'msg' => 'Please create item ' . $params[$unique] . ' first'];
    }

    return ['status' => true, 'msg' => 'Failed to insert uom factor. Item ' . $unique . ' doesn`t exist'];
  }

  private function insertiteminfo($params, $unique, $type, $config)
  {
    // $this->coreFunctions->LogConsole(json_encode($params));
    $msg = '';
    $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", $unique . "=?", [$params[$unique]]);


    $fields = ['itemid' => $itemid];
    if ($itemid) {

      if (isset($params['serialno'])) {
        $fields['serialno'] = $params['serialno'];
      }

      switch ($type) {
        case 'newfams':
        case 'updatefams':
          if ($type == 'newfams') {
            $fields['isuploaded'] = 1;
          }
          if (isset($params['empid'])) {
            $fields['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$params['empid']], '', true);
          }
          if (isset($params['locid'])) {
            $fields['locid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$params['locid']], '', true);
          }

          if (isset($params['dateacquired'])) {
            $requestdate = $params['dateacquired'];

            if ($requestdate != '') {
              if (is_numeric($requestdate)) {
                $UNIX_DATE = ($requestdate - 25569) * 86400;
                $fields['dateacquired'] = gmdate("Y-m-d", $UNIX_DATE);
              } else {
                $fields['dateacquired'] = $this->othersClass->sanitizekeyfield("dateid", $requestdate);
              }
            } else {
              $fields['dateacquired'] = null;
            }
          }
          break;

        default:
          if (isset($params['itemdescription'])) {
            $fields['itemdescription'] = $params['itemdescription'];
          }
          if (isset($params['accessories'])) {
            $fields['accessories'] = $params['accessories'];
          }
          break;
      }

      $check = $this->coreFunctions->getfieldvalue("iteminfo", "itemid", "itemid=?", [$itemid], '', true);
      if ($check != 0) {
        $this->coreFunctions->sbcupdate("iteminfo", $fields, ['itemid' => $itemid]);
      } else {
        $this->coreFunctions->sbcinsert("iteminfo", $fields);
      }
    }

    return ['status' => true, 'msg' => $msg];
  }

  private function insertitemlevel($params, $unique, $type, $config)
  {
    // $this->coreFunctions->LogConsole(json_encode($params));
    $msg = '';
    $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", $unique . "=?", [$params[$unique]]);


    $fields = ['itemid' => $itemid];
    if ($itemid) {

      if (isset($params['min'])) {
        $fields['min'] = $params['min'];
      }

      if (isset($params['max'])) {
        $fields['max'] = $params['max'];
      }


      $check = $this->coreFunctions->getfieldvalue("itemlevel", "itemid", "itemid=?", [$itemid], '', true);
      if ($check != 0) {
        $this->coreFunctions->sbcupdate("itemlevel", $fields, ['itemid' => $itemid]);
      } else {
        $this->coreFunctions->sbcinsert("itemlevel", $fields);
      }
    }

    return ['status' => true, 'msg' => $msg];
  }

  public function generateIssueFams($fields, $pconfig)
  {
    $doc = 'FI';
    $pref = 'FI';
    $path = 'App\Http\Classes\modules\fams\fi';

    $config = [];
    $existTrno = 0;
    $trno = 0;

    $blnNew = false;

    try {
      $headrem = isset($fields['rem']) ? $fields['rem'] : '';

      //$this->othersClass->logConsole("select num.trno as value from issueitem as h left join transnum as num on num.trno=h.trno where num.postdate is null and h.clientid=" . $fields['empid'] . " and h.locid=" . $fields['locid']);
      $existTrno = $this->coreFunctions->datareader("select num.trno as value from issueitem as h left join transnum as num on num.trno=h.trno where num.postdate is null and h.clientid=? and h.locid=? and h.rem=?", [$fields['empid'], $fields['locid'], $headrem], '', true);

      $this->coreFunctions->LogConsole('existTrno - ' . $existTrno);

      //$this->othersClass->logConsole("select trno from issueitemstock where trno=" . $existTrno . " and itemid=" . $fields['itemid']);
      $existItem = $this->coreFunctions->getfieldvalue("issueitemstock", "trno", "trno=? and itemid=?", [$existTrno, $fields['itemid']], '', true);

      $this->coreFunctions->LogConsole('existItem - ' . $existItem);

      if ($existItem == 0) {

        if ($existTrno == 0) {
          $config = [];
          $config['params']['center'] = '001';
          $config['params']['user'] = $pconfig['params']['user'];
          $config['params']['companyid'] = $pconfig['params']['companyid'];
          $config['params']['doc'] = $doc;

          $trno = $this->othersClass->generatecntnum($config, app($path)->tablenum, $doc, $pref, $this->companysetup->documentlength, 0, '', true);

          if ($trno != -1) {
            $blnNew = true;
            $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);

            $head = [
              'trno' => $trno,
              'doc' => $doc,
              'docno' => $docno,
              'dateid' => date('Y-m-d'),
              'createdate' => date('Y-m-d'),
              'clientid' => $fields['empid'],
              'locid' => $fields['locid'],
              'rem' => $headrem,
              'isauto' => 1
            ];

            $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);
            if ($inserthead) {
              $this->logger->sbcwritelog2($trno, $pconfig['params']['user'], 'CREATE', $docno . ' - AUTO-GENERATED ', app($path)->tablelogs);
              AddStockHere:
              $qry = "select ifnull(max(line),0)+1 as value from " . app($path)->stock . " where trno=?";
              $line = $this->coreFunctions->datareader($qry, [$trno]);

              $stock = [
                'trno' => $trno,
                'line' => $line,
                'itemid' => $fields['itemid'],
                'rem' => isset($fields['location']) ? $fields['location'] : '',
              ];

              $insertstock = $this->coreFunctions->sbcinsert(app($path)->stock, $stock);
              if (!$insertstock) {
                return ['status' => false, 'msg' => 'Failed to insert stock.'];
              }
            }
          }
        } else {
          $trno = $existTrno;
          goto AddStockHere;
        }
      }
    } catch (Exception $e) {
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger($msg);
      $this->coreFunctions->LogConsole($msg);

      if ($blnNew) {
        $this->coreFunctions->execqry("delete from " . app($path)->tablenum . " where trno=" . $trno);
        $this->coreFunctions->execqry("delete from " . app($path)->head . " where trno=" . $trno);
      }

      return ['status' => false, 'msg' =>  $msg];
    }

    return ['status' => true, 'msg' => ''];
  }

  public function getiteminfofields($key, $type)
  {
    $arr = ['itemdescription', 'accessories', 'serialno'];
    switch ($type) {
      case 'newfams':
      case 'updatefams':
        $arr = ['company', 'serialno', 'subgroup', 'dateacquired', 'empid', 'locid'];
        break;
    }

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getitemlevelfields($key, $type)
  {
    $arr = ['min', 'max'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  private function insertclientinfo($params, $unique)
  {
    $msg = '';
    $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", $unique . "=?", [$params[$unique]]);
    if ($clientid) {
      $fields = ['clientid' => $clientid];
      if (isset($params['capacity'])) {
        $fields['capacity'] = $params['capacity'];
      }
      $check = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$clientid]);
      if (floatval($check) != 0) {
        $this->coreFunctions->sbcupdate("clientinfo", $fields, ['clientid' => $clientid]);
      } else {
        $this->coreFunctions->sbcinsert("clientinfo", $fields);
      }
    }

    return ['status' => true, 'msg' => $msg];
  }

  public function getclientinfofields($key)
  {
    $arr = ['capacity'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getclientEducationfields($key)
  {
    $arr = ['school', 'schooladrr', 'course', 'sy'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getclientContactfields($key)
  {
    $arr = ['contact1'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getclientemployeefields($key)
  {
    $arr = ['client', 'clientname', 'addr', 'isemployee', 'editby', 'editdate', 'dlock'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getarticlefields($key)
  {
    $arr = ['code', 'description'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getsectionfields($key)
  {
    $arr = ['line', 'section', 'sectiondescription', 'd1a', 'd1b', 'd2a', 'd2b', 'd3a', 'd3b', 'd4a', 'd4b', 'd5a', 'd5b'];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getemployeefields($key)
  {
    $arr = [
      'isapprover',
      'idbarcode',
      'emplast',
      'empfirst',
      'empmiddle',
      'roleid',
      'divid',
      'deptid',
      'sectid',
      'paymode',
      'classrate',
      'level',
      'paygroup',
      'shiftid',
      'sss',
      'hdmf',
      'phic',
      'tin',
      'chksss',
      'chkpibig',
      'chkphealth',
      'chktin',
      'remarks',
      'bday',
      'hired',
      'bank',
      'bankacct',
      'status',
      'projectid',
      'empstatus',
      'mobileno',
      'empnoref',
      'callsign',
      'gender',
      'branchid',
      'permanentaddr',
      'religion',
      'email',
      'nodeps',
      'supervisorid',
      'supervisorid2',
      'jobid',
      'workcatid',
      'isapprover',
      'issupervisor',
      'roleid2',
      'jobid2',
      'branchid2',
      'resigned',
      'lastbatch'
    ];

    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }

  public function getratesetupfields($key)
  {
    $arr = [
      'dateeffect',
      'empid',
      'basicrate',
      'classrate'
    ];
    if (in_array($key, $arr)) {
      return $key;
    }
    return '';
  }


  public function generatebarcode($config, $row)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $pref = '';
    $path = 'App\Http\Classes\modules\masterfile\stockcard';
    $isoutsource = 0;

    switch ($config['params']['companyid']) {
      case 10:
      case 12:
        $isoutsource = isset($row['isoutsource']) ? $row['isoutsource'] :  0;
        if ($isoutsource == 1) {
          $path = 'App\Http\Classes\modules\outsource\stockcard';
          if ($row['pref'] != '') {
            $pref = $row['pref'];
          } else {
            $pref = 'A';
          }
        } else {
          $pref = 'I';
        }
        break;

      default:
        $pref = app($path)->prefix;
        if (strlen($pref) == 0) {
          $pref = app($path)->prefix;
        }
        if (!$pref) {
          $prefixes = $this->othersClass->getPrefixes($pref, $config);
          $pref = isset($prefixes[0]) ? $prefixes[0] : $pref;
        }
        break;
    }

    $barcode2 = app($path)->getlastbarcode($pref);


    if ($isoutsource == 1) {
      $seq = $row['barcode'];
    } else {
      $seq = (substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
      $seq += 1;
    }

    if ($seq == 0 || empty($pref)) {
      if (empty($pref)) {
        $pref = strtoupper($barcode2);
      }
      $barcode2 =  app($path)->getlastbarcode($pref);
      $seq = (substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
      $seq += 1;
    }
    $poseq = $pref . $seq;

    $newbarcode = $this->othersClass->PadJ($poseq, $barcodelength);

    return $newbarcode;
  }

  public function generateclient($config, $row, $type)
  {
    $clientlength = $this->companysetup->getclientlength($config['params']);
    $pref = '';
    $path = 'App\Http\Classes\modules\masterfile\\' . $type;

    $pref = app($path)->prefix;
    if (strlen($pref) == 0) {
      $pref = app($path)->prefix;
    }

    $client2 = app($path)->getlastclient($pref);
    $seq = (substr($client2, $this->othersClass->SearchPosition($client2), strlen($client2)));
    if ($seq == '') $seq = 0;
    $seq += 1;

    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $clientlength);

    return $newclient;
  }

  public function autogenerateratesetup($params, $uniqueval)
  {

    $msg = '';
    $empid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$uniqueval]);
    if ($empid) {

      if (isset($params['basicrate'])) {
        if (floatval($params['basicrate']) != 0) {

          if (!isset($params['dateeffect'])) {
            return ['status' => false, 'msg' => "Please input valid rate effectivity for " . $uniqueval . ". "];
          }

          $sql = "update ratesetup  set dateend='" . $params['dateeffect'] . "' where empid='" . $empid . "' and date(dateend)='9999-12-31'";
          $result = $this->coreFunctions->execqry($sql, "update");

          if ($result) {
            $fields = [
              'empid' => $empid,
              'dateid' => $this->othersClass->getCurrentDate(),
              'dateeffect' => $params['dateeffect'],
              'dateend' => '9999-12-31',
              'basicrate' => $params['basicrate'],
              'type' => $params['classrate'],
              'remarks' => 'from uploading'
            ];
            if (isset($params['remarks'])) {
              $fields['remarks'] = $params['remarks'];
            }

            $result = $this->coreFunctions->sbcinsert("ratesetup", $fields);
            if (!$result) {
              return ['status' => false, 'msg' => "Failed to generate ratesetup for " . $uniqueval . ". "];
            }
          }
        }
      }
    }

    return ['status' => true, 'msg' => $msg];
  }

  public function updateroleinfo($empid)
  {
    $this->coreFunctions->execqry("update employee as emp left join client on client.clientid=emp.empid left join rolesetup as r on r.line=emp.roleid 
    set emp.divid=ifnull(r.divid,0), emp.deptid=ifnull(r.deptid,0), emp.sectid=ifnull(r.sectionid,0), emp.supervisorid=ifnull(r.supervisorid,0) where client.client='" . $empid . "' and emp.roleid<>0");
  }

  public function updatePriceListEffectivity($data, $user)
  {
    $clientid = isset($data['clientid']) ? $data['clientid'] : 0;

    // if ($clientid == 0) {

    $endYear = (new DateTime($data['enddate']))->format('Y');
    $startDate = (new DateTime($data['startdate']))->format('Y-m-d');
    $today = $this->othersClass->getCurrentDate();

    if ($endYear == 9990) {

      if ($today >= $startDate) {

        $insert = [];
        $insert['amt'] =  isset($data['amount']) ? $data['amount'] : 0;
        $insert['amt2'] = isset($data['amount2']) ? $data['amount2'] : 0;
        $insert['avecost'] =  isset($data['cost']) ? $data['cost'] : 0;

        $insert['editby'] = $user;
        $insert['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $insert['dlock'] = $this->othersClass->getCurrentTimeStamp();

        if ($this->coreFunctions->sbcupdate("item", $insert, ['itemid' => $data['itemid']])) {
          $this->logger->sbcwritelog2($data['itemid'], $user, 'ITEM', "Update price list (Utility)", "item_log");
        }
      }
    }

    $filterclient = " and clientid=0";
    if ($clientid <> 0) {
      $filterclient = " and clientid=" . $clientid;
    }

    $sql = "update pricelist set enddate=date_add('" . $startDate . "', interval -1 DAY), 
              dlock='" . $this->othersClass->getCurrentTimeStamp() . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $user . "'
              where itemid=" . $data['itemid'] . " and date(enddate)>='" . $startDate . "' and line<>" . $data['line'] . $filterclient;
    $this->coreFunctions->execqry($sql);
    // }
    // $this->coreFunctions->LogConsole(json_encode($data));
  }

  public function insertAllowance($data, $user, $allowanceonly)
  {
    // $this->coreFunctions->LogConsole('insertAllowance - ' . json_encode($data));
    if ($data['empid'] != 0) {

      $allowance = isset($data['allowance']) ? $data['allowance'] : 0;

      if (!$allowanceonly) {
        $sql = "update ratesetup  set dateend='" . $data['dateeffect'] . "' where empid='" . $data['empid'] . "' and year(dateend)=9999 and trno<>" . $data['rstrno'];
        $this->coreFunctions->execqry($sql, "update");
      } else {
        if ($allowance != 0) goto insertAllowanceHere;
      }

      if ($allowance != 0) {

        $allowanceexist = $this->coreFunctions->datareader("select trno as value from allowsetup where rstrno=" . $data['rstrno'], [], '', true);
        if ($allowanceexist == 0) {
          insertAllowanceHere:
          $insert = [];
          $insert['empid'] =  $data['empid'];
          $insert['dateid'] =  $data['dateid'];
          $insert['dateeffect'] =  $data['dateeffect'];
          $insert['dateend'] =  $data['dateend'];

          if ($allowanceonly) {
            $insert['acno'] = str_replace('\\', '', $data['acno']);
          } else {
            $insert['acno'] = 'PT31';
            $insert['rstrno'] =  $data['rstrno'];
          }

          $insert['acnoid'] = $this->coreFunctions->getfieldvalue("paccount", "line", "code=?", [$insert['acno']], '', true);
          $insert['allowance'] =  $data['allowance'];

          if ($insert['acnoid'] == 0) {
            return ['status' => false, 'msg' => 'Please setup the account code ' . $insert['acno'] . ' first.'];
          }

          if (!$this->coreFunctions->sbcinsert("allowsetup", $insert)) {
            return ['status' => false, 'msg' => $this->coreFunctions->getSQLError()];
          }
        }
      }
    }

    return ['status' => true, 'msg' => ''];
  }

  public function autoPadUniqueVal($params, $val, $uploadType)
  {
    $companyid = $params['companyid'];
    switch ($uploadType) {
      case 'newemployeepayroll':
      case 'updateemployeepayroll':
        switch ($companyid) {
          case 58: //cdohris
          case 62: // onesky-payroll
            if (Is_Numeric($val)) {
              $clientlength = $this->companysetup->getclientlength($params);
              $poseq = 'EM' . $val;
              return $this->othersClass->PadJ($poseq, $clientlength);
            }
            break;
        }
        break;
    }
    return $val;
  }

  private function updateItemPrice($params)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    try {
      $today = $this->othersClass->getCurrentDate();

      $item = $this->coreFunctions->opentable("select pl.itemid, count(pl.itemid) as ctr from pricelist as pl left join item on item.itemid=pl.itemid
                where '" . $today . "' between date(pl.startdate) and date(pl.enddate) and year(pl.enddate)<=9990 and pl.clientid=0
                and (pl.amount<>item.amt or pl.amount2<>item.amt2 or pl.cost<>item.avecost) group by pl.itemid having count(pl.itemid)>0");

      foreach ($item as $keyI => $valueI) {

        $price = $this->coreFunctions->opentable("select itemid, line, amount, amount2, cost, date(startdate) as startdate 
          from pricelist where itemid=" . $valueI->itemid . " and '" . $today . "' between date(startdate) and date(enddate) and year(enddate)<=9990 and clientid=0 order by enddate desc, startdate desc");

        foreach ($price as $key => $value) {
          $startDate = (new DateTime($value->startdate))->format('Y-m-d');

          $data = [];
          $data['amt'] = $value->amount;
          $data['amt2'] = $value->amount2;
          $data['avecost'] = $value->cost;

          $data['editby'] = $params['user'];
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['dlock'] = $this->othersClass->getCurrentTimeStamp();

          // $this->coreFunctions->LogConsole(json_encode($data));

          if ($this->coreFunctions->sbcupdate("item", $data, ['itemid' => $value->itemid])) {
            $this->logger->sbcwritelog2($value->itemid, $params['user'], 'ITEM', "Update price list (Utility)", "item_log");

            $sql = "update pricelist set enddate=date_add('" . $startDate . "', interval -1 DAY), 
              dlock='" . $this->othersClass->getCurrentTimeStamp() . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $params['user'] . "'
              where itemid=" . $value->itemid . " and date(enddate)>='" . $startDate . "' and clientid=0 and line<>" . $value->line;
            $this->coreFunctions->execqry($sql);
          }
          break;
        }
      }

      $item2 = $this->coreFunctions->opentable("select itemid from pricelist where year(enddate)=9990 and clientid=0 group by itemid having count(itemid)>1 order by itemid");
      foreach ($item2 as $key2 => $value2) {

        $price2 = $this->coreFunctions->opentable("select line,startdate from pricelist where itemid=" . $value2->itemid . " and clientid=0 order by startdate desc, enddate");
        if (!empty($price2)) {

          $sql = "update pricelist set enddate=date_add('" . $price2[0]->startdate . "', interval -1 DAY), 
              dlock='" . $this->othersClass->getCurrentTimeStamp() . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $params['user'] . "'
              where itemid=" . $value2->itemid . " and year(enddate)=9990 and clientid=0 and line<>" . $price2[0]->line;
          $this->coreFunctions->execqry($sql);

          $this->logger->sbcwritelog2($value2->itemid, $params['user'], 'ITEM', "Update price list (Utility)", "item_log");
        }
      }

      // $item3 = $this->coreFunctions->opentable("select pl.itemid, pl.line, pl.startdate, pl.enddate from pricelist as pl left join item on item.itemid=pl.itemid 
      //     where date(pl.createdate)='2025-07-28' and year(pl.enddate)<>9990 and pl.line>=132788;");
      // foreach ($item3 as $key3 => $value3) {

      //   $price3 = $this->coreFunctions->opentable("select line,startdate,enddate from pricelist where itemid=" . $value3->itemid . " and clientid=0 order by line desc limit 2");
      //   if (!empty($price3)) {

      //     $strEnd = '';
      //     $addon = '';

      //     $row = 1;
      //     foreach ($price3 as $keyp => $valuep) {
      //       if ($row == 1) {
      //         $sql = "update pricelist set dlock='" . $this->othersClass->getCurrentTimeStamp() . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $params['user'] . "' " . $addon . "
      //         where itemid=" . $value3->itemid . " and clientid=0 and line=" . $valuep->line;
      //         $this->coreFunctions->execqry($sql);
      //         goto nextDataHere;
      //       }

      //       if ($strEnd != '') {
      //         $addon = ", enddate=date_add('" . $strEnd . "', interval -1 DAY)";
      //       }
      //       $sql = "update pricelist set enddate=date_add('" . $valuep->startdate . "', interval -1 DAY), 
      //         dlock='" . $this->othersClass->getCurrentTimeStamp() . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $params['user'] . "' " . $addon . "
      //         where itemid=" . $value3->itemid . " and clientid=0 and line=" . $valuep->line;
      //       $this->coreFunctions->execqry($sql);

      //       nextDataHere:
      //       $strEnd = $valuep->startdate;
      //       $row += 1;
      //     }
      //   }

      //   $this->logger->sbcwritelog2($value3->itemid, $params['user'], 'ITEM', "Update price list (Utility)", "item_log");
      // }

      return ['status' => true, 'msg' => 'Update price finished.', 'action' => 'load'];
    } catch (Exception $e) {
      return ['status' => false, 'msg' => "Failed to update price. Exception error. Line: " . $e->getLine() . " - " . $e->getMessage()];
    }
  }
} //end class
