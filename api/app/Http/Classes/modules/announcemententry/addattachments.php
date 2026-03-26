<?php

namespace App\Http\Classes\modules\announcemententry;

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
use App\Http\Classes\builder\lookupclass;
use Illuminate\Support\Facades\Storage;

class addattachments
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ATTACHMENTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'waims_attachments';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['categoryid', 'name'];
  public $showclosebtn = true;
  private $lookupclass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 1362
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $columns = ['action', 'ext', 'title'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    
    $stockbuttons = ['view', 'download'];
    
    if ($config['params']['doc'] != 'TK'){
      array_push($stockbuttons,'delete');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][0]['btns']['view']['action'] = 'viewfile';
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = 'FileType';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $addattachment = $this->othersClass->checkAccess($config['params']['user'], 5471);
   
    if($config['params']['companyid'] == 29){
       
       if ($config['params']['doc'] != 'TK' && $addattachment == '1') {
        $tbuttons = ['adddocument'];
        } else {
            $tbuttons = [];
        }
      
    }else{//kapag hindi 29
        $tbuttons = ['adddocument'];
      }
    
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = 'adddocument';
    
    switch ($config['params']['doc']){
      case "TM":
      case "TK":
        $obj[0]['lookupclass'] = ['table' => $this->table, 'field' => 'picture', 'fieldid' => 'line', 'folder' => 'waims_attachments', 'trno' => $config['params']['row']['trno'],'tmline' => $config['params']['row']['line']];
        break;
      case "DY":
        $obj[0]['lookupclass'] = ['table' => $this->table, 'field' => 'picture', 'fieldid' => 'line', 'folder' => 'waims_attachments', 'trno' => $config['params']['tableid'], 'tmline' => 0];
        break;
        default:
        $obj[0]['lookupclass'] = ['table' => $this->table, 'field' => 'picture', 'fieldid' => 'line', 'folder' => 'waims_attachments', 'trno' => $config['params']['row']['line']];
        break;
    }

    $obj[0]['label'] = 'Add Attachment';
    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['sourcerow']['line'];
    $tmline =0;
    if($config['params']['doc'] == 'TM'){
      $id = $config['params']['sourcerow']['trno'];
      $tmline = $config['params']['sourcerow']['line'];
    }
    $data = [];
    $data['trno'] = $id;
    $data['line'] = 0;
    $data['tmline'] = $tmline;
    $data['ext'] = '';
    $data['title'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function save($config)
  {
    return [];
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $mainfolder = '/images/';
    $qry = "select picture as value from " . $this->table . " where trno=? and line=? and doc=? order by line desc limit 1";
    $filename = $this->coreFunctions->datareader($qry, [$row['trno'], $row['line'], $config['params']['doc']]);
    if ($filename !== '') {
      $filename = str_replace($mainfolder, '', $filename);
      if (Storage::disk('sbcpath')->exists($filename)) {
        Storage::disk('sbcpath')->delete($filename);
      }
    }
    $qry = "delete from " . $this->table . " where trno=? and line=? and doc=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $config['params']['doc']]);
    $this->logger->sbcwritelog($row['trno'], $config, 'ATTACHMENT', 'DELETE TITLE - ' . $row['title']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line, $doc)
  {
    return [];
  }

  public function loaddata($config)
  {
    $doc = $config['params']['doc'];
    $addf=" and doc='".$doc."' ";
    $tableid = isset($config['params']['row']) ? $config['params']['row']['line'] : ($config['params']['tableid'] != 0 ? $config['params']['tableid'] : $config['params']['trno']);

    //$tmline =  isset($config['params']['row']) ? $config['params']['row']['line'] : (isset($config['params']['addedparams']['tmline']) ? $config['params']['addedparams']['tmline'] : 0);
    //$this->coreFunctions->logconsole($tmline.'-tmline--trno'. $tableid.'doc-'. $config['params']['doc'] );
    if($config['params']['doc'] == 'TM'){
      $tableid = isset($config['params']['row']) ? $config['params']['row']['trno'] : ($config['params']['tableid'] != 0 ? $config['params']['tableid'] : $config['params']['trno']);    
      $tmline =  isset($config['params']['row']) ? $config['params']['row']['line'] : (isset($config['params']['addedparams']['tmline']) ? $config['params']['addedparams']['tmline'] : 0);
      $addf = " and doc in ('TK','TM') and tmline = ".$tmline;
    }

    if($config['params']['doc']=='TK'){
      $tableid = isset($config['params']['row']) ? $config['params']['row']['trno'] : (isset($config['params']['addedparams']['trno']) ? $config['params']['addedparams']['trno'] : 0);
      $tmline =  isset($config['params']['row']) ? $config['params']['row']['line'] : (isset($config['params']['addedparams']['tmline']) ? $config['params']['addedparams']['tmline'] : 0);
      $addf = " and doc in ('TK','TM') and tmline = ".$tmline;
    }

    $qry = "select 'notice' as type, md5(trno) as trno2, md5(line) as line2, trno, line, title, picture as picture, substring_index(picture,'.',-1) as ext, '' as bgcolor from " . $this->table . " where trno=?  ".$addf." order by line";
    
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    $data = $this->getFileTypes($data);
    $this->coreFunctions->logconsole($qry);
    return $data;
  }

  public function getFileTypes($data)
  {
    foreach ($data as $d) {
      switch ($d->ext) {
        case 'JPG':
        case 'JPEG':
        case 'PNG':
        case 'GIF':
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
          $d->filetype = 'image';
          break;
        case 'pdf':
        case 'PDF':
          $d->filetype = 'pdf';
          break;
        default:
          $d->filetype = 'others';
          break;
      }
    }
    return $data;
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
          $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['name']);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['name']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function
} //end class
