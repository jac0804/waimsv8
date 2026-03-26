<?php

namespace App\Http\Classes\modules\crm;

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

class exhibit
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Exhibit';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'exhibit';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['title', 'description', 'startdate', 'enddate', 'product', 'location', 'remarks'];
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
    $attrib = [
      'load' => 2595,
      'additem' => 2853,
      'saveallentry' => 2854,
      'print' => 2855,
      'save' => 2856,
      'delete' => 2857,
      'new' => 2875
    ];
    return $attrib;
  }

  public function createTab($config)
  {
    $save = $this->othersClass->checkAccess($config['params']['user'], 2856);
    $delete = $this->othersClass->checkAccess($config['params']['user'], 2857);

    $action = 0;
    $title = 1;
    $description = 2;
    $startdate = 3;
    $enddate = 4;
    $product = 5;
    $location = 6;
    $remarks = 7;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'title', 'description', 'startdate', 'enddate', 'product', 'location', 'remarks']]];
    $stockbuttons = ['addattendee'];

    if ($save) {
      array_push($stockbuttons, 'save');
    }
    if ($delete) {
      array_push($stockbuttons, 'delete');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$title]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][$startdate]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$product]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$location]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$title]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$location]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$location]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Remarks';
    $obj[0][$this->gridname]['columns'][0]['btns']['addattendee']['checkfield'] = 'newtrans';

    $obj[0][$this->gridname]['columns'][$description]['type'] = 'cinput';
    $obj[0][$this->gridname]['columns'][$description]['maxlength'] = '500';
    $obj[0][$this->gridname]['columns'][$title]['type'] = 'cinput';
    $obj[0][$this->gridname]['columns'][$title]['maxlength'] = '150';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $additem = $this->othersClass->checkAccess($config['params']['user'], 2853);
    $saveallentry = $this->othersClass->checkAccess($config['params']['user'], 2854);
    $print = $this->othersClass->checkAccess($config['params']['user'], 2855);

    $tbuttons = [];
    if ($additem) {
      array_push($tbuttons, 'addrecord');
    }
    if ($saveallentry) {
      array_push($tbuttons, 'saveallentry');
    }
    if ($print) {
      array_push($tbuttons, 'print');
    }
    array_push($tbuttons, 'masterfilelogs');

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['title'] = '';
    $data['description'] = '';
    $data['startdate'] = date('Y-m-d');
    $data['enddate'] = date('Y-m-d');
    $data['product'] = '';
    $data['location'] = '';
    $data['remarks'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    $data['newtrans'] = 'true';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line, title, description, left(startdate, 10) as startdate, left(enddate, 10) as enddate, product, location,remarks,'false' as newtrans";
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog(
          $line,
          $config,
          ' CREATE - TITLE: ' . $data['title']
            . ', DESCRIPTION: ' . $data['description']
            . ', FROM: ' . $data['startdate']
            . ', TO: ' . $data['enddate']
            . ', PRODUCT: ' . $data['product']
            . ', LOCATION: ' . $data['location']
            . ', REMARKS: ' . $data['remarks']
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

    $this->logger->sbcmasterlog(
      $row['line'],
      $config,
      ' CREATE - GROUP NAME: ' . $row['title']
        . ', TITLE: ' . $row['title']
        . ', DESCRIPTION: ' . $row['description']
        . ', FROM: ' . $row['startdate']
        . ', TO: ' . $row['enddate']
        . ', PRODUCT: ' . $row['product']
        . ', LOCATION: ' . $row['location']
        . ', REMARKS: ' . $row['remarks']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $limit = '';
    $search = isset($config['params']['filter']) ? $config['params']['filter'] : '';
    if ($company == 10 || $company == 12) { //afti, afti usd
      if ($search == '') {
        $limit = 'limit 25';
      }
    }
    $qry = "select " . $select . " from " . $this->table . " order by line $limit";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    unset($data['newtrans']);
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $id = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $id,
            $config,
            ' CREATE - TITLE: ' . $data2['title']
              . ', DESCRIPTION: ' . $data2['description']
              . ', FROM: ' . $data2['startdate']
              . ', TO: ' . $data2['enddate']
              . ', PRODUCT: ' . $data2['product']
              . ', LOCATION: ' . $data2['location']
              . ', REMARKS: ' . $data2['remarks']
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

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }



  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
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

  public function stockstatusposted($config)
  {
    $path = 'App\Http\Classes\modules\tableentry\entryattendee';
    return app($path)->createprofile($config);
  }

  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
    'default' as print"
    );
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY

    $filter   = "";

    $query = "select line, title, description, date(startdate) as startdate, date(enddate) as enddate, product, location 
    from exhibit";


    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '11';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SEMINAR LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Title', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Description', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Start Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('End Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Product', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Location', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->title, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->description, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->startdate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->enddate, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->product, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->location, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
} //end class
