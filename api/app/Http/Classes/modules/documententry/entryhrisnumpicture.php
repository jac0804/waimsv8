<?php

namespace App\Http\Classes\modules\documententry;

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
use Illuminate\Support\Facades\Storage;

class entryhrisnumpicture
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADD DOCUMENTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'hrisnum_picture';
  private $othersClass;
  public $style = 'width:100%;min-width:100%;';
  private $fields = ['trno', 'line', 'title', 'picture'];
  public $showclosebtn = false;
  private $logger;
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';



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

    $attrib = array(
      'load' => 1730,
      'attach' => 1731,
      'download' => 1732,
      'delete' => 1733
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted2($trno, "hrisnum");
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'ext', 'title', 'encodedby', 'encodeddate']]];
    $stockbuttons = [];

    $viewallow = $this->othersClass->checkAccess($config['params']['user'], 1730);
    $downloadallow = $this->othersClass->checkAccess($config['params']['user'], 1732);
    $delallow = $this->othersClass->checkAccess($config['params']['user'], 1733);

    if ($viewallow == '1') {
      array_push($stockbuttons, 'view');
    }

    if ($downloadallow == '1') {
      array_push($stockbuttons, 'download');
    }

    if (!$isposted) {
      if ($delallow == '1') {
        array_push($stockbuttons, 'delete');
      }
    }


    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    if (isset($obj[0]['inventory']['columns'][0]['btns']['view'])) {
      $obj[0]['inventory']['columns'][0]['btns']['view']['action'] = 'viewfile';
    }

    $obj[0]['inventory']['columns'][1]['label'] = 'FileType';
    $obj[0]['inventory']['columns'][1]['align'] = 'left';
    $obj[0]['inventory']['columns'][1]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px;';
    $obj[0]['inventory']['columns'][0]['style'] = 'width: 50px;whiteSpace: normal;min-width:20px;max-width:50px;';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted2($trno, "hrisnum");

    $allow = $this->othersClass->checkAccess($config['params']['user'], 1731);


    $tbuttons = [];
    $obj = [];
    if (!$isposted) {
      if ($allow == '1') {
        $tbuttons = ['adddocument'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = 'adddocument';
        $obj[0]['lookupclass'] = ['table' => $this->table, 'field' => 'picture', 'fieldid' => 'trno', 'folder' => $config['params']['doc']];
        $obj[0]['label'] = 'Add Document';
      }
    }

    return $obj;
  }

  public function isreloadhead($config)
  {
    return true;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry()
  {
    $qry = "'hp' as type,md5(trno) as trno2,md5(line) as line2,trno,line,title,picture,
    left(encodeddate, 10) as encodeddate,encodedby,substring_index(picture, '.', -1) as ext";

    return $qry;
  }

  public function saveallentry($config)
  {
  } // end function  

  public function save($config)
  {
  } //end function

  public function delete($config)
  {
    $mainfolder = '/images/';
    $row = $config['params']['row'];
    $qry = "select picture as value from " . $this->table . " where trno=? and line=? order by line desc limit 1";
    $filename = $this->coreFunctions->datareader($qry, [$row['trno'], $row['line']]);
    if ($filename !== '') {
      $filename = str_replace($mainfolder, '', $filename);
      if (Storage::disk('sbcpath')->exists($filename)) {
        Storage::disk('sbcpath')->delete($filename);
      }
    }
    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    $this->logger->sbcwritelog($row['trno'], $config, 'ATTACHMENT', 'DELETE TITLE - ' . $row['title']);
    $isreloadhead = $this->isreloadhead($config);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'isreloadhead' => $isreloadhead];
  }


  private function loaddataperrecord($empid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where  trno=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$empid, $line]);
    $data = $this->getFileTypes($data);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where  trno=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    $data = $this->getFileTypes($data);
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

  public function lookupsetup($config)
  {
  }

  public function lookupcallback($config)
  {
  } // end function
































} //end class
