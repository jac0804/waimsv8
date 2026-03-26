<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\taskentry\historicalcomments;

class commenthistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Comments';
  public $gridname = 'multigrid2';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $grid;
  private $logger;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->grid = new historicalcomments;
  }

  public function createTab($config)
  {
  
    $tab = [
      $this->gridname => ['action' => 'taskentry', 'lookupclass' => 'historicalcomments', 'label' => 'LIST']
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    // var_dump($config['params']);
    // break;
    $trno = isset($config['params']['row']['tasktrno']) ? $config['params']['row']['tasktrno'] : (isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0);
    $line = isset($config['params']['row']['taskline']) ? $config['params']['row']['taskline'] : (isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0);


    $otherTrnoField = '';
    $otherTrnoVal = 0;

    if ($config['params']['row']['doc'] == 'DY' && $trno == 0) { //add return notes from checker in manual daily task
      $otherTrnoField = 'dytrno';
      $otherTrnoVal = $config['params']['row']['trno'];
    }

    $qry = "select '$trno' as tmtrno,'$line' as tmline,'' as createby,'' as createdate,'' as rem, '" . $otherTrnoField . "' as othertrnofield, " . $otherTrnoVal . " as othertrnoval";
   
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function loaddata($config)
  {
    return [];
  } //end function

} //end class
