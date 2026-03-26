<?php

namespace App\Http\Classes\modules\dashboard;

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

class event
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EVENTS and HOLIDAYS';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:900px;max-width:900px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $columns = ['action', 'ext', 'title'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = ['view', 'download'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][0]['btns']['view']['action'] = 'viewfile';
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = 'FileType';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = ['title', 'rem'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'rem.type', 'textarea');
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {

    switch ($config['params']['type']) {
      case 'e':
        $sql = 'select title,rem from waims_event where md5(line)=?';
        break;
      case 'h':
        $sql = 'select title,rem from waims_holiday where md5(line)=?';
        break;
      case 'hl':
       $sql = "select `description` as title,'' as rem from holiday where md5(line)=?";
        break;
      case 'hll':
        $sql = "select `description` as title,'' as rem from holidayloc where md5(line)=?";
        break;
      ;
    }
    return $this->coreFunctions->opentable($sql, [$config['params']['dataid']]);
  }

  public function data($config)
  {
    $trno = $config['params']['data']['line'];
    $doc = '';
    if ($config['params']['type'] == 'h') {
      $doc = 'ENTRYHOLIDAY';
    } else {
      $doc = 'ENTRYEVENT';
    }
    $qry = "select 'notice' as type, md5(trno) as trno2, md5(line) as line2, trno, line, title, picture as picture, substring_index(picture,'.',-1) as ext from waims_attachments where md5(trno)=? and doc=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $doc]);
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

  public function loaddata($config)
  {
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
  }
} //end class
