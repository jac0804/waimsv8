<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\mobile\modules\collection\regtenants;
use App\Http\Classes\mobile\modules\collection\ambulant;
use App\Http\Classes\mobile\modules\collection\summary;
use App\Http\Classes\mobile\modules\collection\colstat;
use App\Http\Classes\mobile\modules\collection\coladmin;
use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class collection {
  private $regtenants;
  private $ambulant;
  private $summary;
  private $colstat;
  private $coladmin;
  private $buttonClass;
  private $fieldClass;

  public function __construct() {
    $this->regtenants = new regtenants;
    $this->ambulant = new ambulant;
    $this->summary = new summary;
    $this->colstat = new colstat;
    $this->coladmin = new coladmin;
    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
  }
  public function getAddConfigCol() {
    return [
      ['col'=>'collectorid', 'type'=>''],
      ['col'=>'username', 'type'=>''],
      ['col'=>'password', 'type'=>''],
      ['col'=>'printtype', 'type'=>''],
      ['col'=>'operationtype', 'type'=>''],
      ['col'=>'printer', 'type'=>''],
      ['col'=>'collectiondate', 'type'=>''],
      ['col'=>'printerlen', 'type'=>''],
      ['col'=>'center', 'type'=>'']
    ];
  }

  public function getSettings() {
    $settings = [
      ['istablegrid'=>false],
      ['hasupload'=>false],
      ['istextlookup'=>false],
      ['menutype'=>'left'],
      ['hascsv'=>false],
      ['printreceipt'=>env('printreceipt', true)],
      ['downloadAllUsers'=>env('downloadAllUsers', false)],
      ['custDownloadType'=>env('custDownloadType', 'area')],
      ['ismultilogin'=>true]
    ];
    return $settings;
  }
  
  public function getDownloads () {
    $downloads = [];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [
      ['type'=>'operation', 'name'=>'regtenants', 'label'=>'Regular Tenants', 'doc'=>'regtenants', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true],
      ['type'=>'operation', 'name'=>'ambulant', 'label'=>'Ambulant', 'doc'=>'ambulant', 'icon'=>'table_chart', 'layout'=>'customform'],
      ['type'=>'operation', 'name'=>'summary', 'label'=>'Summary', 'doc'=>'summary', 'icon'=>'table_chart', 'layout'=>'customform'],
      ['type'=>'operation', 'name'=>'colstat', 'label'=>'Collection Status', 'doc'=>'colstat', 'icon'=>'table_chart', 'layout'=>'customform'],
      ['type'=>'admin', 'name'=>'admin', 'label'=>'Admin', 'doc'=>'coladmin', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true]
    ];
    return $menu;
  }

  public function getLoginTabs () {
    $loginTabs = [
      ['name'=>'admin', 'label'=>'Administrator', 'func'=>''],
      ['name'=>'operation', 'label'=>'Operation', 'func'=>'operationTab']
    ];
    return $loginTabs;
  }

  public function getLoginContent () {
    $loginFields = [];
    $loginButtons = [];
    $fields = ['username', 'password'];
    $buttons = ['login'];
    $adminFields = $this->fieldClass->create($fields);
    $adminButtons = $this->buttonClass->create($buttons);
    data_set($adminButtons, 'login.func', 'adminLogin');
    $fields = ['username', 'password'];
    $buttons = ['login'];
    $operationFields = $this->fieldClass->create($fields);
    data_set($operationFields, 'username.readonly', true);
    $operationButtons = $this->buttonClass->create($buttons);
    data_set($operationButtons, 'login.func', 'operationLogin');
    array_push($loginFields, ['form'=>'admin', 'fields'=>$adminFields]);
    array_push($loginButtons, ['form'=>'admin', 'buttons'=>$adminButtons]);
    array_push($loginFields, ['form'=>'operation', 'fields'=>$operationFields]);
    array_push($loginButtons, ['form'=>'operation', 'buttons'=>$operationButtons]);
    return ['fields'=>$loginFields, 'buttons'=>$loginButtons];
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
          'tablename'=>'client',
          'createQry'=>'create table if not exists client(line integer primary key autoincrement, clientid integer, client text default "", clientname text default "", loc text default "", rem text default "", dailyrent decimal(18,2) default "0.00", dcusa decimal(18,2) default "0.00", rentdue decimal(19,2) default "0.00", outar decimal(19,2) default "0.00", outcusa decimal(19,2) default "0.00", cusadue decimal(19,2) default "0.00", center text default "", outelec decimal(19,2) default "0.00", outwater decimal(19,2) default "0.00", phase text default "", section text default "", erate decimal(19,2) default "0.00", wrate decimal(19,2) default "0.00", ebeginning decimal(19,2) default "0.00", wbeginning decimal(19,2) default "0.00", last_ebeginning decimal(19,2) default "0.00", last_eending decimal(19,2) default "0.00", last_erate decimal(19,2) default "0.00", last_wbeginning decimal(19,2) default "0.00", last_wending decimal(19,2) default "0.00", last_wrate decimal(19,2) default "0.00", noRent integer default 0, noCusa integer default 0)'
        ], [
          'tablename'=>'clientarea',
          'createQry'=>'create table if not exists clientarea(line integer primary key autoincrement, clientid integer default 0, phase text default "", section text default "", sectionname text default "", center text default "")'
        ], [
          'tablename'=>'dailycollection',
          'createQry'=>'create table if not exists dailycollection(line integer primary key autoincrement, clientid integer default 0, amount decimal(18,2) default "0.00", status text default "", dateid datetime default null, center text default "", `type` text default "", remarks text default "", collectorid integer default 0, isNegative integer default 0, transtime datetime default null, phase text default "", section text default "")'
        ], [
          'tablename'=>'hdailycollection',
          'createQry'=>'create table if not exists hdailycollection(line integer, clientid integer default 0, amount decimal(18,2) default "0.00", status text default "", dateid datetime default null, center text default "", `type` text default "", remarks text default "", collectorid integer default 0, isNegative integer default 0, transtime datetime default null, phase text default "", section text default "")'
        ], [
          'tablename'=>'reading',
          'createQry'=>'create table if not exists reading(line integer primary key autoincrement, beginning decimal(18,2) default 0, ending decimal(18,2) default 0, consumption decimal(18,2) default 0, rate decimal(18,2) default 0, clientid integer default 0, dateid text default "", remarks text default "", `type` text default "", collectorid integer default 0)'
        ], [
          'tablename'=>'hreading',
          'createQry'=>'create table if not exists hreading(line integer, beginning decimal(18,2) default 0, ending decimal(18,2) default 0, consumption decimal(18,2) default 0, rate decimal(18,2) default 0, clientid integer default 0, dateid text default "", remarks text default "", `type` text default "", collectorid integer default 0)'
        ], [
          'tablename'=>'useraccess',
          'createQry'=>'create table if not exists useraccess(userid integer, accessid integer, username, password, name, pincode, pincode2, wh)',
        ], [
          'tablename'=>'users',
          'createQry'=>'create table if not exists users(idno integer, attributes)'
        ], [
          'tablename'=>'errlogs',
          'createQry'=>'create table if not exists errlogs(id integer primary key autoincrement, description, data, dateid, user)'
        ]
      ],
      'addcol'=>[
        ['tablename'=>'useraccess', 'column'=>'wh']
      ],
      'dropcol'=>[],
      'addindex'=>[
        'create index idx_item on item (itemid, barcode, uom)',
        'create index idx_cart on cart (itemid, uom)',
        'create index idx_uom on uom (itemid, uom)',
        'create index idx_itemstat on itemstat (itemid)',
        'create index idx_transhead on transhead (trno, orderno)',
        'create index idx_transstock on transstock (orderno)'
      ]
    ];
    return $db;
  }
}