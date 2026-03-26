<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class entrychangeitem
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Change Item';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'item';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [
    'itemid', 'barcode', 'itemname',
    'groupid', 'part', 'model',
    'brand', 'class', 'sizeid', 'uom',
    'amt', 'cost', 'amt2', 'amt4', 'famt', 'partno'
  ];
  public $showclosebtn = false;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupClass = new lookupClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $barcode = 1;
    $partno = 2;
    $itemname = 3;
    $uom = 4;
    $amt = 5;
    $dollaramt = 6;
    $tpdollar = 7;
    $tppeso = 8;
    $project = 9;
    $brand = 10;
    $class = 11;
    $group = 12;
    $part = 13;
    $model = 14;
    $size = 15;

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'barcode', 'partno', 'itemname', 'uom', 'amt', 'amt2', 'famt', 'amt4', 'stock_projectname',
          'brandname', 'classname', 'stock_groupname',
          'partname', 'modelname', 'sizeid',
          'classid', 'brandid', 'stock_groupid', 'partid', 'modelid'
        ]
      ]
    ];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "max-width:5%;width:5%;";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';

    $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$brand]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$brand]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$class]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$class]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$group]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$group]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$part]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$part]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$model]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$model]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$size]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$size]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][16]['type'] = "input";
    $obj[0][$this->gridname]['columns'][17]['type'] = "input";

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";
        $obj[0][$this->gridname]['columns'][$partno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$project]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amt]['label'] = "PHP Amount";
        $obj[0][$this->gridname]['columns'][$dollaramt]['label'] = "Dollar Amount";
        $obj[0][$this->gridname]['columns'][$tppeso]['label'] = "TP Peso";
        $obj[0][$this->gridname]['columns'][$partno]['label'] = "SKU/Part No.";
        $obj[0][$this->gridname]['columns'][$part]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$size]['type'] = "coldel";

        break;
      default:
        $obj[0][$this->gridname]['columns'][$dollaramt]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tpdollar]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tppeso]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "coldel";
        break;
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry()
  {
    $qry = "itemid";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',item.' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['itemid'] == 0) {
          $itemid = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['itemid' => $data[$key]['itemid']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($this->coreFunctions->sbcupdate($this->table, $data, ['itemid' => $row['itemid']]) == 1) {
      $returnrow = $this->loaddataperrecord($row['itemid']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  } //end function

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $tbl = $this->table;
    $select = $select . ",'' as bgcolor, ifnull(brand.brandid,0) as brandid, brand.brand_desc as brandname,
                      ifnull(class.cl_id,0) as classid, class.cl_name as classname,
                      groups.stockgrp_name as stock_groupname, ifnull(groups.stockgrp_id,0) as groupid,
                      ifnull(part.part_id,0) as part, part.part_name as partname,
                      ifnull(model.model_id,0) as model, model.model_name as modelname,ifnull(p.line,0) as projectid,p.name as stock_projectname";

    $qry = "select " . $select . " from " . $tbl . "  
            left join frontend_ebrands as brand on brand.brandid = item.brand
            left join item_class as class on class.cl_id = item.class
            left join stockgrp_masterfile as groups on groups.stockgrp_id = item.groupid
            left join part_masterfile as part on part.part_id = item.part
            left join model_masterfile as model on model.model_id = item.model
            left join projectmasterfile as p on p.line = item.projectid
            where itemid=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $tbl = $this->table;
    $company = $config['params']['companyid'];
    $limit = ' limit 5000';
    if ($company == 10 || $company == 12) { //afti, afti usd
      $limit = 'limit 25';
    }
    $filtersearch = "";
    $condition  = "";
    $searcfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'brand.brand_desc', 'class.cl_name', 'groups.stockgrp_name', 'part.part_name', 'model.model_name'];

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $select = $select . ",'' as bgcolor, ifnull(brand.brandid,0) as brandid, brand.brand_desc as brandname,
                      ifnull(class.cl_id,0) as classid, class.cl_name as classname,
                      groups.stockgrp_name as stock_groupname, ifnull(groups.stockgrp_id,0) as groupid,
                      ifnull(part.part_id,0) as part, part.part_name as partname,
                      ifnull(model.model_id,0) as model, model.model_name as modelname,ifnull(p.line,0) as projectid,p.name as stock_projectname";
    $qry = "select " . $select . " from " . $tbl . " 
            left join frontend_ebrands as brand on brand.brandid = item.brand
            left join item_class as class on class.cl_id = item.class
            left join stockgrp_masterfile as groups on groups.stockgrp_id = item.groupid
            left join part_masterfile as part on part.part_id = item.part
            left join model_masterfile as model on model.model_id = item.model
            left join projectmasterfile as p on p.line = item.projectid
            where 1=1 " . $filtersearch . "
            order by barcode $limit";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupbrand':
        return $this->lookupbrand($config);
        break;
      case 'lookupclass':
        return $this->lookupclass($config);
        break;
      case 'lookupgroup':
        return $this->lookupgroup($config);
        break;
      case 'lookuppart':
        return $this->lookuppart($config);
        break;
      case 'lookupmodel':
        return $this->lookupmodel($config);
        break;
      case 'lookupsize':
        return $this->lookupsize($config);
        break;
    }
  }

  public function lookupbrand($config)
  {

    $plotting = array('brand' => 'brandid', 'brandname' => 'brand');
    $plottype = 'plotgrid';
    $title = 'List of Brand';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'brand', 'label' => 'Brand', 'align' => 'left', 'field' => 'brand', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    $qry = "select brandid , brand_desc as brand from frontend_ebrands";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupclass($config)
  {

    $plotting = array('classname' => 'classic', 'class' => 'classid');

    $plottype = 'plotgrid';
    $title = 'List of Class';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'classic', 'label' => 'Class', 'align' => 'left', 'field' => 'classic', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    $qry = "select 0 as classid,'' as classic ,'' as fontcolor,'' as skincolor
          UNION ALL 
          select item_class.cl_id as classid, item_class.cl_name as classic,item_class.fontcolor,item_class.skincolor from item_class 
           where item_class.cl_name<>''";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupgroup($config)
  {
    $plotting = array('groupid' => 'groupid', 'stock_groupname' => 'stockgrp');

    $plottype = 'plotgrid';
    $title = 'List of Group';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'stockgrp', 'label' => 'Itemname', 'align' => 'left', 'field' => 'stockgrp', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    $qry = "select 0 as groupid , '' as code , '' as stockgrp 
          UNION ALL 
          select stockm.stockgrp_id as groupid,stockm.stockgrp_code as code,stockm.stockgrp_name as stockgrp 
          from stockgrp_masterfile as stockm where stockm.stockgrp_name<>''";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuppart($config)
  {
    $plotting = array('part' => 'partid', 'partname' => 'partname');

    $plottype = 'plotgrid';
    $title = 'List of Part';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'partname', 'label' => 'Part', 'align' => 'left', 'field' => 'partname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    $qry = "select part_id as partid, part_name as partname
          from part_masterfile";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupmodel($config)
  {
    $plotting = array('modelname' => 'modelname', 'model' => 'modelid');
    $plottype = 'plotgrid';
    $title = 'List of Model';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'modelname', 'label' => 'Model', 'align' => 'left', 'field' => 'modelname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select model.model_id as modelid, 
          model.model_code as modelcode,
          model.model_name as modelname 
          from model_masterfile as model";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupsize($config)
  {
    $plotting = array('sizeid' => 'sizeid');
    $plottype = 'plotgrid';
    $title = 'List of Body';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'sizeid', 'label' => 'Size', 'align' => 'left', 'field' => 'sizeid', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select distinct sizeid  from item ";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


} //end class
