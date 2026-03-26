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
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrygeneralitem
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'GENERAL ITEM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'generalitem';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['barcode', 'uom', 'itemname', 'brandid', 'groupid', 'subgroup', 'modelid', 'classid', 'company', 'sizeid'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2910,
      'save' => 2910
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $action = 0;
    $barcode = 1;
    $itemname = 2;
    $uom = 3;
    $brandname = 4;
    $groupname = 5;
    $subgroup = 6;
    $company = 7;
    $modelname = 8;
    $classname = 9;
    $sizeid = 10;

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'barcode', 'itemname', 'uom', 'brandname', 'groupname', 'subgroup',
          'company', 'modelname', 'classname', 'sizeid'
        ]
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "itemname";
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$uom]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$brandname]['label'] = "Brand Name";
    $obj[0][$this->gridname]['columns'][$brandname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$brandname]['lookupclass'] = "lookupbrand";
    $obj[0][$this->gridname]['columns'][$brandname]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$brandname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$groupname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$groupname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$groupname]['lookupclass'] = "lookupgroup";
    $obj[0][$this->gridname]['columns'][$groupname]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$groupname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$company]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$modelname]['label'] = "Model Name";
    $obj[0][$this->gridname]['columns'][$modelname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$modelname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$modelname]['lookupclass'] = "lookupmodel";
    $obj[0][$this->gridname]['columns'][$modelname]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$modelname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$classname]['label'] = "Class Name";
    $obj[0][$this->gridname]['columns'][$classname]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$classname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$classname]['lookupclass'] = "lookupclass";
    $obj[0][$this->gridname]['columns'][$classname]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$classname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$sizeid]['label'] = "Size";
    $obj[0][$this->gridname]['columns'][$sizeid]['type'] = "editlookup";
    $obj[0][$this->gridname]['columns'][$sizeid]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$sizeid]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$sizeid]['lookupclass'] = "lookupsize";
    $obj[0][$this->gridname]['columns'][$sizeid]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$sizeid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['barcode'] = '';
    $data['uom'] = '';
    $data['itemname'] = '';
    $data['brandid'] = 0;
    $data['brandname'] = '';
    $data['groupid'] = 0;
    $data['groupname'] = '';
    $data['subgroup'] = '';
    $data['modelid'] = 0;
    $data['modelname'] = '';
    $data['classid'] = 0;
    $data['classname'] = '';
    $data['company'] = '';
    $data['sizeid'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ', genitem.' . $value;
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
        if ($data[$key]['line'] == 0) {
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $line,
            $config,
            ' CREATE - '
              . ' BARCODE: ' . $data[$key]['barcode']
              . ' ITEMNAME: ' . $data[$key]['itemname']
              . ' UOM: ' . $data[$key]['uom']
              . ' BRAND: ' . $data[$key]['brandname']
              . ' GROUP: ' . $data[$key]['groupname']
              . ' SUB GROUP: ' . $data[$key]['subgroup']
              . ' COMPANY: ' . $data[$key]['company']
              . ' MODEL: ' . $data[$key]['modelname']
              . ' CLASS: ' . $data[$key]['classname']
              . ' SIZE: ' . $data[$key]['sizeid']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
    if ($row['line'] == 0) {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog(
          $line,
          $config,
          ' CREATE - '
            . ' BARCODE: ' . $row['barcode']
            . ' ITEMNAME: ' . $row['itemname']
            . ' UOM: ' . $row['uom']
            . ' BRAND: ' . $row['brandname']
            . ' GROUP: ' . $row['groupname']
            . ' SUB GROUP: ' . $row['subgroup']
            . ' COMPANY: ' . $row['company']
            . ' MODEL: ' . $row['modelname']
            . ' CLASS: ' . $row['classname']
            . ' SIZE: ' . $row['sizeid']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['barcode']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ", brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    classi.cl_name as classname,
    '' as bgcolor ";
    $qry = "select " . $select . " 
    from " . $this->table . " as genitem
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join model_masterfile as model on model.model_id = genitem.modelid
    left join item_class as classi on classi.cl_id = genitem.classid
    where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $filtersearch = "";
    $searcfield = $this->fields;
    $limit = "1000";

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . 'genitem.' . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . 'genitem.' . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $select = $this->selectqry();
    $select = $select . ", brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    classi.cl_name as classname,
    '' as bgcolor ";
    $qry = "select " . $select . " 
    from " . $this->table . " as genitem
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join model_masterfile as model on model.model_id = genitem.modelid
    left join item_class as classi on classi.cl_id = genitem.classid
    where 1=1 " . $filtersearch . " 
    order by line 
    limit " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'lookupbrand':
        return $this->lookupbrand($config);
        break;
      case 'lookupgroup':
        return $this->lookupgroup($config);
        break;
      case 'lookupmodel':
        return $this->lookupmodel($config);
        break;
      case 'lookupclass':
        return $this->lookupclass($config);
        break;
      case 'lookupsize':
        return $this->lookupsize($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupbrand($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Brand',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['brandid' => 'brandid', 'brandname' => 'brandname']
    );

    $cols = array(
      array('name' => 'brandname', 'label' => 'Brand Name', 'align' => 'left', 'field' => 'brandname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select brandid, brand_desc as brandname 
      from frontend_ebrands";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupgroup($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Group',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['groupid' => 'groupid', 'groupname' => 'groupname']
    );

    $cols = array(
      array('name' => 'groupname', 'label' => 'Group Name', 'align' => 'left', 'field' => 'groupname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select stockgrp_id as groupid, stockgrp_name as groupname 
      from stockgrp_masterfile";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupmodel($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Model',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['modelid' => 'modelid', 'modelname' => 'modelname']
    );

    $cols = array(
      array('name' => 'modelname', 'label' => 'Model Name', 'align' => 'left', 'field' => 'modelname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select model_id as modelid, model_name as modelname 
      from model_masterfile";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupclass($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Class',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['classid' => 'classid', 'classname' => 'classname']
    );

    $cols = array(
      array('name' => 'classname', 'label' => 'Class Name', 'align' => 'left', 'field' => 'classname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select cl_id as classid, cl_name as classname 
      from item_class";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupsize($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Size',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['sizeid' => 'sizeid']
    );

    $cols = array(
      array('name' => 'sizeid', 'label' => 'Size', 'align' => 'left', 'field' => 'sizeid', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select distinct sizeid
      from " . $this->table . "";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  // -> Print Function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti, afti usd
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $select = $this->selectqry();
    $select = $select . ", brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    classi.cl_name as classname,
    '' as bgcolor ";
    $qry = "select " . $select . " 
    from " . $this->table . " as genitem
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join model_masterfile as model on model.model_id = genitem.modelid
    left join item_class as classi on classi.cl_id = genitem.classid";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_gen_item_PDF($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_gen_item_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [1000, 1000]);
    PDF::SetMargins(20, 20);

    if ($companyid == 3) { //conti
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(1000, 20, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(1000, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 20, "Barcode", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Itemname", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "UOM", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Brand Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Group Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Sub Group Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Company", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Model Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Class Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Size", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1000, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_gen_item_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->rpt_gen_item_PDF_header_PDF($data, $filters);

    for ($i = 0; $i < count($data); $i++) {
      $itemname = PDF::GetStringHeight(100, $data[$i]['itemname']);
      $brandname = PDF::GetStringHeight(100, $data[$i]['brandname']);
      $groupname = PDF::GetStringHeight(100, $data[$i]['groupname']);
      $subgroup = PDF::GetStringHeight(100, $data[$i]['subgroup']);
      $company = PDF::GetStringHeight(100, $data[$i]['company']);
      $modelname = PDF::GetStringHeight(100, $data[$i]['modelname']);
      $classname = PDF::GetStringHeight(100, $data[$i]['classname']);

      $max_height = max($itemname, $brandname, $groupname, $subgroup, $company, $modelname, $classname);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }

      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(100, $max_height, $data[$i]['barcode'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['uom'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['brandname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['groupname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['subgroup'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['company'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['modelname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['classname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, $data[$i]['sizeid'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, $max_height, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->rpt_gen_item_PDF_header_PDF($data, $filters);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


} //end class
