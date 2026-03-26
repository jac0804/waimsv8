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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class seminar
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Seminar';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'seminar';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['title', 'description', 'dateid', 'product', 'location', 'presenter', 'attendeecount', 'semtime', 'semtype', 'remarks', 'endsemtime', 'enddate'];
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
      'load' => 2594,
      'additem' => 2848,
      'saveallentry' => 2849,
      'print' => 2850,
      'save' => 2851,
      'delete' => 2852,
      'new' => 2875
    ];
    return $attrib;
  }

  public function createTab($config)
  {
    $save = $this->othersClass->checkAccess($config['params']['user'], 2851);
    $delete = $this->othersClass->checkAccess($config['params']['user'], 2852);

    $action = 0;
    $title = 1;
    $description = 2;
    $dateid = 3;
    $enddate = 4;
    $product = 5;
    $location = 6;
    $presenter = 7;
    $attendeecount = 8;
    $time = 9;
    $endtime = 10;
    $type = 11;
    $rem = 12;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'title', 'description', 'dateid', 'enddate', 'product', 'location', 'presenter', 'attendeecount', 'semtime', 'endsemtime', 'semtype', 'remarks']]];
    $stockbuttons = ['addattendee'];

    if ($save) {
      array_push($stockbuttons, 'save');
    }
    if ($delete) {
      array_push($stockbuttons, 'delete');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$title]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$location]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$location]['type'] = 'input';

    $obj[0][$this->gridname]['columns'][$title]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$product]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$location]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$presenter]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$attendeecount]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$time]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$endtime]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$type]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$description]['type'] = 'cinput';
    $obj[0][$this->gridname]['columns'][$description]['maxlength'] = '500';
    $obj[0][$this->gridname]['columns'][$title]['type'] = 'cinput';
    $obj[0][$this->gridname]['columns'][$title]['maxlength'] = '150';

    $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Start Date';
    $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'End Date';
    $obj[0][$this->gridname]['columns'][$time]['label'] = 'Start Time';
    $obj[0][$this->gridname]['columns'][$endtime]['label'] = 'End Time';

    $obj[0][$this->gridname]['columns'][$type]['label'] = 'Type';
    $obj[0][$this->gridname]['columns'][$type]['lookupclass'] = 'lookupsemtype';

    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
    $obj[0][$this->gridname]['columns'][0]['btns']['addattendee']['checkfield'] = 'newtrans';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $additem = $this->othersClass->checkAccess($config['params']['user'], 2848);
    $saveallentry = $this->othersClass->checkAccess($config['params']['user'], 2849);
    $print = $this->othersClass->checkAccess($config['params']['user'], 2850);

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
    $data['dateid'] = date('Y-m-d');
    $data['enddate'] = date('Y-m-d');
    $data['product'] = '';
    $data['location'] = '';
    $data['presenter'] = '';

    $data['attendeecount'] = '';
    $data['semtime'] = '00:00';
    $data['endsemtime'] = '00:00';
    $data['semtype'] = '';
    $data['remarks'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    $data['newtrans'] = 'true';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,'false' as newtrans";
    foreach ($this->fields as $key => $value) {
      switch ($value) {
        case 'dateid':
          $qry = $qry . ',date(dateid) as dateid';
          break;
        case 'enddate':
          $qry = $qry . ',date(enddate) as enddate';
          break;
        case 'semtime':
          $qry = $qry . ',time(semtime) as semtime';
          break;
        case 'endsemtime':
          $qry = $qry . ',time(endsemtime) as endsemtime';
          break;
        default:
          $qry = $qry . ',' . $value;
          break;
      }
    }

    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $data['dateid'] = date("Y-m-d", strtotime($data['dateid']));
    $data['enddate'] = date("Y-m-d", strtotime($data['enddate']));
    $data['semtime'] = substr($data['dateid'], 0, 10) . ' ' . $data['semtime'];
    $data['endsemtime'] = substr($data['enddate'], 0, 10) . ' ' . $data['endsemtime'];


    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog(
          $line,
          $config,
          ' CREATE - TITLE: ' . $data['title']
            . ', DESCRIPTION: ' . $data['description']
            . ', DATE: ' . $data['dateid']
            . ', END DATE: ' . $data['enddate']
            . ', PRODUCT: ' . $data['product']
            . ', LOCATION: ' . $data['location']
            . ', PRESENTER: ' . $data['presenter']
            . ', ATTENDEE COUNT: ' . $data['attendeecount']
            . ', TIME: ' . $data['semtime']
            . ', END TIME: ' . $data['endsemtime']
            . ', TYPE: ' . $data['semtype']
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
      ' DELETE - TITLE: ' . $row['title']
        . ', DESCRIPTION: ' . $row['description']
        . ', DATE: ' . $row['dateid']
        . ', END DATE: ' . $row['enddate']
        . ', PRODUCT: ' . $row['product']
        . ', LOCATION: ' . $row['location']
        . ', PRESENTER: ' . $row['presenter']
        . ', ATTENDEE COUNT: ' . $row['attendeecount']
        . ', TIME: ' . $row['semtime']
        . ', END TIME: ' . $row['endsemtime']
        . ', TYPE: ' . $row['semtype']
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
        $data2['dateid'] = date("Y-m-d", strtotime($data2['dateid']));
        $data2['enddate'] = date("Y-m-d", strtotime($data2['enddate']));
        $data2['semtime'] = substr($data2['dateid'], 0, 10) . ' ' . $data2['semtime'];
        $data2['endsemtime'] = substr($data2['enddate'], 0, 10) . ' ' . $data2['endsemtime'];


        if ($data[$key]['line'] == 0) {
          $id = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $id,
            $config,
            ' CREATE - GROUP NAME: ' . $data[$key]['title']
              . ', TITLE: ' . $data[$key]['description']
              . ', DATE: ' . $data[$key]['dateid']
              . ', END DATE: ' . $data[$key]['enddate']
              . ', PRODUCT: ' . $data[$key]['product']
              . ', LOCATION: ' . $data[$key]['location']
              . ', PRESENTER: ' . $data[$key]['presenter']
              . ', ATTENDEE COUNT: ' . $data[$key]['attendeecount']
              . ', TIME: ' . $data[$key]['semtime']
              . ', END TIME: ' . $data[$key]['endsemtime']
              . ', TYPE: ' . $data[$key]['semtype']
              . ', REMARKS: ' . $data[$key]['remarks']
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

      case 'lookupsemtype':
        return $this->lookupsemtype($config);
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

  public function lookupsemtype($config)
  {

    $title = 'Select Type';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'semtype' => 'semtype'
      )
    );


    $cols = [

      ['name' => 'semtype', 'label' => 'Type', 'align' => 'left', 'field' => 'semtype', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "select 'Open to all' as semtype
    union all
    select 'Exclusive to client' as semtype";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
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
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
    'PDFM' as print"
    );
  }

  public function reportdata($config)
  {
    $data = $this->report_default_query($config);

    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->reportDefaultLayout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->default_seminar_PDF($config, $data);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function report_default_query($config)
  {
    $filter  = "";
    $query = "select line, title, description, date(dateid) as dateid, product, location, presenter 
    from seminar";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
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
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Product', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Location', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Presenter', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

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
      $str .= $this->reporter->col($data['title'], '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['description'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['dateid'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['product'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['location'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['presenter'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
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

  private function default_seminar_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(120, 0, "TITLE", 'B', 'L', false, 0);
    PDF::MultiCell(120, 0, "DESCRIPTION", 'B', 'L', false, 0);
    PDF::MultiCell(120, 0, "DATE", 'B', 'L', false, 0);
    PDF::MultiCell(120, 0, "PRODUCT", 'B', 'L', false, 0);
    PDF::MultiCell(120, 0, "LOCATION", 'B', 'L', false, 0);
    PDF::MultiCell(120, 0, "PRESENTER", 'B', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
  }

  private function default_seminar_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_seminar_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $section_height = PDF::GetStringHeight(360, $data[$i]['title']);
      $section_desc_height = PDF::GetStringHeight(360, $data[$i]['description']);
      $max_height = max($section_height, $section_desc_height);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(120, 0, $data[$i]['title'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(120, $max_height, $data[$i]['description'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(120, $max_height, $data[$i]['dateid'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(120, $max_height, $data[$i]['product'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(120, $max_height, $data[$i]['location'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(120, $max_height, $data[$i]['presenter'], '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_seminar_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
} //end class
