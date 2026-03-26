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

class notice
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NOTICE';
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
    $this->modulename .= ' - ' . $config['params']['row']['clientname'];
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

    $fields = ['dateid', 'title', 'rem'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'rem.type', 'textarea');
    data_set($col1, 'dateid.style', 'width:150px;whiteSpace: normal;min-width:150px;');
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select dateid,title,rem from waims_notice where line=?', [$config['params']['row']['line']]);
  }

  public function data($config)
  {
    $trno = $config['params']['row']['line'];
    $qry = "select 'notice' as type, md5(trno) as trno2, md5(line) as line2, trno, line, title, picture as picture, substring_index(picture,'.',-1) as ext from waims_attachments where trno=? and doc=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, 'ENTRYNOTICE']);
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
