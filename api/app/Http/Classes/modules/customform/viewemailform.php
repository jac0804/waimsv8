<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Illuminate\Support\Facades\Storage;
use Exception;

class viewemailform
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $logger;

  public $modulename = 'Send Email';
  public $style = 'width:100%;max-width:70%;';
  public $gridname = 'tableentry';
  public $issearchshow = false;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $fields = ['email', 'emailcc', 'emailsubject', 'emailbody', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.label', 'send');
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $sql = "select " . $trno . " as trno, 'zerojad08@gmail.com' as email, '' as emailcc, 'waw' as emailsubject, 'wew' as emailbody";
    $data = $this->coreFunctions->opentable($sql);
    return $data;
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
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

  public function loaddata($config)
  {
    $dataparams = $config['params']['dataparams'];
    $doc = $config['params']['doc'];
    $moduletype = $config['params']['moduletype'];
    $classname = 'App\\Http\\Classes\\modules\\' . $moduletype . '\\' . $doc;
    $docmodule = new $classname();
    $table = '';
    switch ($docmodule->tablenum) {
      case 'transnum':
        $table = 'transnum_picture';
        break;
      case 'cntnum':
        $table = 'cntnum_picture';
        break;
      case 'docunum':
        $table = 'docunum_picture';
        break;
    }
    $cc = [];
    if ($dataparams['emailcc'] != '') {
      $cc = preg_replace('/\s+/', '', $dataparams['emailcc']);
      $cc = explode(',', $cc);
    }
    $attachments = [];
    $hasattachment = false;
    if ($table != '') {
      $att = $this->coreFunctions->opentable("select picture from " . $table . " where trno=" . $dataparams['trno']);
      $mainfolder = '/images/';
      if (!empty($att)) {
        $hasattachment = true;
        foreach ($att as $a) {
          $filename = str_replace($mainfolder, '', $a->picture);
          if (Storage::disk('sbcpath')->exists($filename)) {
            $file = Storage::disk('sbcpath')->get($filename);
            array_push($attachments, ['file' => $file, 'filename' => $a->picture, 'filetype' => Storage::disk('sbcpath')->mimeType($filename)]);
          }
        }
      }
    }

    $emailinfo = [
      'from' => 'jad@gck.com.ph',
      'email' => $dataparams['email'],
      'view' => 'emails.welcome',
      'title' => $docmodule->modulename,
      'cc' => $cc,
      'subject' => $dataparams['emailsubject'],
      'body' => $dataparams['emailbody'],
      'hasattachment' => $hasattachment,
      'attachments' => $attachments
    ];
    $config['params']['dataparams'] = json_encode($config['params']['dataparams']);
    return $this->othersClass->sbcsendemail($config, $emailinfo);
  }
}
