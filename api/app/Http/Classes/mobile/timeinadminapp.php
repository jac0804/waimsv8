<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use App\Http\Classes\mobile\modules\timeinadminapp\admin;
use Illuminate\Support\Facades\Storage;

class timeinadminapp {
  private $buttonClass;
  private $fieldClass;
  private $admin;

  public function __construct() {
    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
    $this->admin = new admin;
    $this->company = env('appcompany', 'sbc');
  }
  public function getAddConfigCol() {
    return [];
  }

  public function getSettings() {
    $settings = [
      ['istablegrid'=>false],
      ['hasupload'=>false],
      ['istextlookup'=>false],
      ['menutype'=>'none'],
      ['hascsv'=>false],
      ['ismultilogin'=>false],
      ['nologin'=>false],
      ['manualloginlayout'=>true],
      ['hasregistration'=>false],
      ['storagetype'=>'itemscanner']
    ];
    return $settings;
  }
  
  public function getDownloads () {
    $downloads = [
      ['name'=>'accounts', 'label'=>'Download Accounts', 'func'=>'downloadTimeinAccounts'],
      // ['name'=>'logs', 'label'=>'Logs', 'func'=>'loadTimeinoutLogs']
    ];
    if($this->company == 'sbc2') {
      array_splice($downloads, 1, null, [['name'=>'downloaduserimages', 'label'=>'Download User Images', 'func'=>'downloadUserImages']]);
      array_push($downloads, ['name'=>'devicename', 'label'=>'Site Location', 'func'=>'setuplocation']);
    }
    return $downloads;
  }

  public function manualLoginLayout () {
    $fields = ['email', 'password'];
    $loginFields = $this->fieldClass->create($fields);
    $mlfields = [];
    array_push($mlfields, ['fields'=>$loginFields]);
    $btns = ['login'];
    $loginBtns = $this->buttonClass->create($btns);
    data_set($loginBtns, 'login.func', 'timeinAdminAppLogin');
    $mlbuttons = [];
    array_push($mlbuttons, ['buttons'=>$loginBtns]);
    return ['fields'=>$mlfields, 'buttons'=>$mlbuttons];
  }

  public function getMainMenu () {
    $menu = [
      ['type'=>'timeinadminapp', 'name'=>'admin', 'label'=>'Admin', 'doc'=>'admin', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true]
    ];
    return $menu;
  }

  public function getLoginTabs () {
    return [];
  }

  public function getLoginContent () {
    return [];
  }

  public function getFooterButtons() {
    return [];
  }

  public function getLayouts($menu) {
    $layouts = [];
    $funcs = [];
    if(count($menu) > 0) {
      foreach($menu as $m) {
        $layouts[$m['doc']] = $this->$m['doc']->getLayout();
        array_push($funcs, ['doc'=>$m['doc'], 'func'=>$this->$m['doc']->getFunc()]);
      }
    }
    return ['layouts'=>$layouts, 'funcs'=>$funcs];
  }

  public function getDBTables() {
    $db = [
      'new'=>[
        [
          'tablename'=>'useraccess2',
          'createQry'=>'create table if not exists useraccess2(line integer PRIMARY KEY AUTOINCREMENT, id, email, name, password, dlock datetime, isactive integer default 0)'
        ], [
          'tablename'=>'log',
          'createQry'=>'create table if not exists log(line integer PRIMARY KEY AUTOINCREMENT, id, dateid, timein, timeout, loginPic, logoutPic, isok integer default 0, isok2 integer default 0, uploaddate, uploaddate2)'
        ], [
          'tablename'=>'userimg',
          'createQry'=>'create table if not exists userimg(line integer PRIMARY KEY AUTOINCREMENT, id, img)'
        ], [
          'tablename'=>'guardlog',
          'createQry'=>'create table if not exists guardlog(line integer PRIMARY KEY AUTOINCREMENT, name, timein, timeout, loginPic, uploaddate)'
        ]
      ],
      'addcol'=>[
        ['tablename'=>'log', 'column'=>'sitelocation'],
        ['tablename'=>'log', 'column'=>'isok2 integer default 0'],
        ['tablename'=>'log', 'column'=>'uploaddate2']
      ],
      'dropcol'=>[],
      'addindex'=>[]
    ];
    return $db;
  }
}