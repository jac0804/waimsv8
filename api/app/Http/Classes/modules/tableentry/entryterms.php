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
use App\Http\Classes\builder\lookupClass;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryterms
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TERMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'terms';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['terms', 'days', 'isdp', 'orderno', 'isnotallow', 'interest', 'pfnf', 'nf'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;
  private $lookupClass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
    $this->lookupClass = new lookupClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 598
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 55: // afli
        $tab = [
          $this->gridname => [
            'gridcolumns' => ['action', 'terms', 'days', 'interest', 'pfnf', 'nf']
          ]
        ];
        break;
      case 16: // ati
        $tab = [
          $this->gridname => [
            'gridcolumns' => ['action', 'terms', 'days', 'orderno']
          ]
        ];
        break;
      case 10: // afti
      case 12: // afti usd
        $tab = [
          $this->gridname => [
            'gridcolumns' => ['action', 'terms', 'days', 'isdp', 'isnotallow']
          ]
        ];
        break;
      default:
        $tab = [
          $this->gridname => [
            'gridcolumns' => ['action', 'terms', 'days', 'interest']
          ]
        ];
        break;
    }

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    if ($companyid == 0) { //main
      $obj[0][$this->gridname]['columns'][3]['style'] = "width:70px;whiteSpace: normal;min-width:70px;";
      $obj[0][$this->gridname]['columns'][3]['label'] = "Interest Rate";
      $obj[0][$this->gridname]['columns'][4]['label'] = "Professional Fee & Notarial Fee";
    }

    if ($companyid == 34) { //evergreen
      $obj[0][$this->gridname]['columns'][2]['label']  = 'Months';
    }

    if ($companyid == 55) { //afli
      $obj[0][$this->gridname]['columns'][2]['label']  = 'Months';
      $obj[0][$this->gridname]['columns'][4]['label']  = 'PF';
    }

    if ($companyid == 40) { //cdo
      $obj[0][$this->gridname]['columns'][2]['label']  = 'Days/Months';
    }
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
    $data['terms'] = '';
    $data['days'] = 0;
    $data['orderno'] = 0;
    $data['isdp'] = 'false';
    $data['isnotallow'] = 'false';
    $data['interest'] = 0;
    $data['pfnf'] = 0;
    $data['nf'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      if ($value == 'interest' || $value == 'pfnf' || $value == 'nf') {
        $qry = $qry . ',format(' . $value . ',2) as ' . $value;
      } else {
        $qry = $qry . ',' . $value;
      }
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $msg = '';
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $data2['createby'] = $config['params']['user'];
          $exist = $this->coreFunctions->getfieldvalue("terms","line","terms = ?",[$data[$key]['terms']],'',true);
          if($exist != 0){
            return ['status' => false, 'msg' => $data[$key]['terms'].' Terms already exist', 'data' => []];
          }
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['terms']);
        } else {
          $oldterms = $this->coreFunctions->getfieldvalue("terms", "terms", "line=?", [$data[$key]['line']]);
          if ($oldterms != $data2['terms']) {

            $check = $this->checkisused($oldterms, "update");
            if ($check['status']) {
              $msg .= $check['msg'] . '. ';
            } else {
              $data2['editby'] = $config['params']['user'];
              $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
              $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
            }
          } else {
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    ExitHere:
    $returndata = $this->loaddata($config);
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => true, 'msg' => $msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $exist = $this->coreFunctions->getfieldvalue("terms","line","terms = ?",[$data['terms']],'',true);
      if($exist != 0){
        return ['status' => false, 'msg' => $data['terms'].' Terms already exist'];
      }
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['terms']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {

      $oldterms = $this->coreFunctions->getfieldvalue("terms", "terms", "line=?", [$row['line']]);
      if ($oldterms != $data['terms']) {

        $check = $this->checkisused($oldterms, "update");
        if ($check['status']) {
          return ['status' => false, 'msg' => $check['msg']];
        }
      }

      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
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
      'title' => 'Term Logs',
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

  private function checkisused($terms, $action)
  {

    $data = $this->coreFunctions->opentable("select * from (
            select clientname as name, 'Customer/Supplier' as type from client where terms=?
            union all 
            select docno as name, 'Transaction' as type from lahead where terms=?
            union all 
            select docno as name, 'Transaction' as type from glhead where terms=?
            union all
            select docno as name, 'Transaction' as type from sohead where terms=?
            union all
            select docno as name, 'Transaction' as type from pohead where terms=?
            union all
            select docno as name, 'Transaction' as type from hsohead where terms=?
            union all
            select docno as name, 'Transaction' as type from hpohead where terms=?
            union all
            select docno as name, 'Transaction' as type from hqshead where terms=?
            union all
            select docno as name, 'Transaction' as type from qshead where terms=?)
            as x  limit 1", [$terms, $terms, $terms, $terms, $terms, $terms, $terms, $terms, $terms]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Cannot ' . $action . ' terms ' . $terms . '. Already used in ' . $data[0]->type . ' ' . $data[0]->name];
    } else {
      return ['status' => false];
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    if (!empty($row['terms'])) {
      $check = $this->checkisused($row['terms'], "delete");
      if ($check['status']) {
        $check['status'] = false;
        return $check;
      }
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['terms']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    if (!empty($data)) {
      if ($data[0]->isdp == 1) {
        $data[0]->isdp = 'true';
      } else {
        $data[0]->isdp = 'false';
      }

      if ($data[0]->isnotallow == 1) {
        $data[0]->isnotallow = 'true';
      } else {
        $data[0]->isnotallow = 'false';
      }
    }
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $limit = '';
    $orderno = '';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';

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

    if ($search != "") {
      $l = '';
    } else {
      $l = $limit;
    }

    if ($company == 16) {
      $orderno = 'orderno,';
    }

    $qry = "select " . $select . " from " . $this->table . " where 1=1 " . $filtersearch . " order by $orderno line $l";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      foreach ($data as $d) {
        if ($d->isdp == 1) {
          $d->isdp = 'true';
        } else {
          $d->isdp = 'false';
        }

        if ($d->isnotallow == 1) {
          $d->isnotallow = 'true';
        } else {
          $d->isnotallow = 'false';
        }
      }
    }
    return $data;
  }

  // -> print function
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select line, terms, days from terms
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_terms_masterfile_layout($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TERMS MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Terms', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Days', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_terms_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['terms'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['days'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn


} //end class
