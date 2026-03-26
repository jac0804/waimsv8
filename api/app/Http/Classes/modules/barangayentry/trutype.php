<?php

namespace App\Http\Classes\modules\barangayentry;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\sbcscript\sbcscript;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;


class trutype
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRU Type Setup';
  public $gridname = 'inventory';
      private $companysetup;
  private $coreFunctions;
  private $table = 'reqcategory';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['description','istru'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;
  private $reportheader;

    public function __construct()  //to call functions from other files
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
    $this->reportheader = new reportheader;
  }

    public function getAttrib()
  {
    $attrib = array(
      'load' => 5621
    );
    return $attrib;
  }

    public function createTab($config)
  {

    $columns = [
     'action', 'description'
    ];

    foreach ($columns as $key => $value) {
     $$value = $key; //declare
    }

    $stockbuttons = ['save', 'delete'];
    $tab = [
        $this->gridname=> [
            'gridcolumns' => $columns
        ]
    ];
    
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$description]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    return $obj;
  }

    public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry','whlog']; // tab button
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }
    public function loaddata($config)
  {
    $searcfield = $this->fields;
    $filtersearch = "";
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
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where istru = 1 " . $filtersearch . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function selectqry()
  {
      $qry = "line";
      foreach ($this->fields as $key => $value) {
          $qry = $qry . ',' . $value;
      }
      return $qry;
  }

    public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['description'] = '';
    $data['istru'] = 1;
    $data['bgcolor'] = 'bg-green-2';
    return $data;
  }

    public function saveallentry($config)
  {
    $data = $config ['params']['data'];
    $qry = "Select max(line) as maxLine from  {$this->table}";
    $res = $this->coreFunctions->opentable($qry);
    $maxLine = (!empty($res) && !empty($res[0]->maxLine))? (int)$res[0]->maxLine : 0 ;
    foreach ($data as $key => $value) {
      $data2 = [];
      if (!empty($data[$key]['bgcolor'])){
        foreach ($this->fields as $key2 => $field) {
        $value = isset($data[$key][$field]) ? $data[$key][$field] :null;
        $data2[$field] = $this->othersClass->sanitizekeyfield($field,$value);
      }
      if (empty(trim($data2['description']))) {
        return ['status' => false, 'msg' => 'Saving failed. Please complete the empty description.'];
      }
      $lineValue = isset($data[$key]["line"]) ? $data[$key]['line'] : 0;
        if ($lineValue === 0){
          $maxLine++;
          // $data2['line'] = $maxLine;
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . (isset($data[$key]['description']) ? $data[$key]['description'] : '')
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2,['line' => $data[$key]['line']]
          );
        }
      }
    }
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved Successfully.','data' => $returndata]; 
  }

    public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px; max-width:1000px;'
    );
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => 'true', 'style' => 'font-size:16px;'),
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

    public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch($lookupclass2){
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status'=> false, 'msg' => 'Action' . $config['params']['actions'].'is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

    public function save($config)  //stockgrid button 
  {
      $data = [];
      $row = $config['params']['row'];
      $companyid = $config['params']['companyid'];
      foreach ($this->fields as $key => $value) {
          $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
      }

      if ($row['line'] == 0 && $row['description'] != '') {
          $qry = "select description from reqcategory where description = '" . $row['description'] . "' limit 1";
          $opendata = $this->coreFunctions->opentable($qry);
          $resultdata = json_decode(json_encode($opendata), true);
          if (!empty($resultdata[0]['description'])) {
              if (trim($resultdata[0]['description']) == trim($row['description'])) {
                  return ['status' => false, 'msg' => ' TRU ( ' . $resultdata[0]['description'] . ' ) is already exist', 'data' => [$resultdata]];
              }
          }
      }

      if (trim($row['description']) == '') {
          return ['status' => false, 'msg' => 'TRU Type description is empty'];
      }

      if ($row['line'] == 0) {

          $line = $this->coreFunctions->insertGetId($this->table, $data);
          if ($line != 0) {
              $returnrow = $this->loaddataperrecord($line);
              $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['description']);
              return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
          } else {
              return ['status' => false, 'msg' => 'Saving failed.'];
          }

      } else {

          if ($row['line'] != 0 && $row['description'] != '') {
              $qry = "select description,line from reqcategory where description = '" . $row['description'] . "' limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata = json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['description'])) {
                  if (trim($resultdata[0]['description']) == trim($row['description'])) {
                      if ($row['line'] == $resultdata[0]['line']) {
                          goto update;
                      }
                      return ['status' => false, 'msg' => ' TRU Type ( ' . $resultdata[0]['description'] . ' ) is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
                  } else {
                    update:
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];

                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line']]);
                    $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $row['description']);
                  }
              } else {
                  goto update;
              }
          }

          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['editby'] = $config['params']['user'];

          if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
              $returnrow = $this->loaddataperrecord($row['line']);
              return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
          } else {
              return ['status' => false, 'msg' => 'Saving failed.'];
          }

      }
  } 

    public function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . " where line =? ";
    $data = $this->coreFunctions->opentable(
      $qry,
      [$line]
    );
    return $data;
  }

    public function delete($config) // needed restrictions
{
    $row = $config['params']['row'];

    $qry = "delete from " . $this->table . " where line = ?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $count = $this->coreFunctions->datareader($qry, [$row['description']]);


    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['description']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
}

}

