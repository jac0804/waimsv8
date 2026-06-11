<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\mobile\modules\production\receivingreport;
use App\Http\Classes\mobile\modules\production\transfertocgl;
use App\Http\Classes\mobile\modules\production\transfertoccl;
use App\Http\Classes\mobile\modules\production\transfertocba;
use App\Http\Classes\mobile\modules\production\transfertoga;
use App\Http\Classes\mobile\modules\production\cglentry;
use App\Http\Classes\mobile\modules\production\cclentry;
use App\Http\Classes\mobile\modules\production\cbaentry;
use App\Http\Classes\mobile\modules\production\gaentry;
use App\Http\Classes\mobile\modules\production\cglexit;
use App\Http\Classes\mobile\modules\production\cclexit;
use App\Http\Classes\mobile\modules\production\gaexit;
use App\Http\Classes\mobile\modules\production\cbaexit;
use App\Http\Classes\mobile\modules\production\dispatch;
use App\Http\Classes\mobile\modules\production\dispatchexit;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use App\Http\Classes\coreFunctions;

class production {
  private $receivingreport;
  private $transfertocgl;
  private $transfertoccl;
  private $transfertocba;
  private $transfertoga;
  private $cglentry;
  private $cclentry;
  private $cbaentry;
  private $gaentry;
  private $cglexit;
  private $cclexit;
  private $gaexit;
  private $cbaexit;
  private $dispatch;
  private $dispatchexit;

  private $buttonClass;
  private $fieldClass;
  private $coreFunctions;

  public function __construct() {
    $this->receivingreport = new receivingreport;
    $this->transfertocgl = new transfertocgl;
    $this->transfertoccl = new transfertoccl;
    $this->transfertocba = new transfertocba;
    $this->transfertoga = new transfertoga;
    $this->cglentry = new cglentry;
    $this->cclentry = new cclentry;
    $this->cbaentry = new cbaentry;
    $this->gaentry = new gaentry;
    $this->cglexit = new cglexit;
    $this->cclexit = new cclexit;
    $this->gaexit = new gaexit;
    $this->cbaexit = new cbaexit;
    $this->dispatch = new dispatch;
    $this->dispatchexit = new dispatchexit;

    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
    $this->coreFunctions = new coreFunctions;
  }
  public function getAddConfigCol() {
    return [
      ['col'=>'username', 'type'=>''],
      ['col'=>'password', 'type'=>''],
      ['col'=>'printerlen', 'type'=>'']
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
      ['custDownloadType'=>env('custDownloadType', 'area')]
    ];
    return $settings;
  }
  
  public function getDownloads () {
    $downloads = [
      ['name'=>'items', 'label'=>'Download Items', 'func'=>'downloadItems'],
      ['name'=>'others', 'label'=>'Download Other Details', 'func'=>'downloadOthers']
    ];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [];
    $menus = $this->coreFunctions->opentable("select attribute as access, constdesc as name from attributes where iscont=1");
    if (!empty($menus)) {
      foreach($menus as $mkey => $m) {
        if ($mkey == 0) {
          array_push($menu, ['type'=>'production', 'name'=>str_replace(' ', '', $m->name), 'label'=>$m->name, 'doc'=>str_replace(' ', '', $m->name), 'icon'=>'', 'layout'=>'customform', 'isdefault'=>true, 'access'=>$m->access]);
        } else {
          array_push($menu, ['type'=>'production', 'name'=>str_replace(' ', '', $m->name), 'label'=>$m->name, 'doc'=>str_replace(' ', '', $m->name), 'icon'=>'', 'layout'=>'customform', 'access'=>$m->access]);
        }
      }
    }
    // $menu = [
    //   ['type'=>'operation', 'name'=>'regtenants', 'label'=>'Regular Tenants', 'doc'=>'regtenants', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true],
    //   ['type'=>'operation', 'name'=>'ambulant', 'label'=>'Ambulant', 'doc'=>'ambulant', 'icon'=>'table_chart', 'layout'=>'customform'],
    //   ['type'=>'operation', 'name'=>'summary', 'label'=>'Summary', 'doc'=>'summary', 'icon'=>'table_chart', 'layout'=>'customform'],
    //   ['type'=>'operation', 'name'=>'colstat', 'label'=>'Collection Status', 'doc'=>'colstat', 'icon'=>'table_chart', 'layout'=>'customform'],
    //   ['type'=>'admin', 'name'=>'admin', 'label'=>'Admin', 'doc'=>'coladmin', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true]
    // ];
    return $menu;
  }

  public function getLoginTabs () {
    $loginTabs = [];
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
        $aw = strtolower($m['doc']);
        if (class_exists('App\\Http\\Classes\\mobile\\modules\\production\\'.$aw)) {
          $layouts[$aw] = $this->$aw->getLayout();
          array_push($funcs, ['doc'=>$aw, 'func'=>$this->$aw->getFunc()]);
        }
      }
    }
    return ['layouts'=>$layouts, 'funcs'=>$funcs];
  }

  public function getDBTables() {
    $db = [
      'new'=>[
        [
          'tablename'=>'item',
          'createQry'=>'create table if not exists item(itemid integer primary key, barcode, itemname, groupid, class, thickness, profile, width decimal(18,3) default "0.000", iscgl integer default 0, isccl integer default 0, iscba integer default 0, isga integer default 0, bmt decimal(18,3) default "0.000")'
        ], [
          'tablename'=>'ehead',
          'createQry'=>'create table if not exists ehead(trno integer primary key, doc, bref, docno, client, clientname, wh, whname, dateid, lcno, ourref, isselected integer default 0, prdno, ctr integer default 0)'
        ], [
          'tablename'=>'paintsupplier',
          'createQry'=>'create table if not exists paintsupplier(code, paintsupplier)'
        ], [
          'tablename'=>'exitinfos',
          'createQry'=>'create table if not exists exitinfos(class, thickness, groupid, profile)'
        ], [
          'tablename'=>'myexitdata',
          'createQry'=>'create table if not exists myexitdata(myline integer primary key autoincrement, trno integer, line integer, bundleno, designation, color, rrqty decimal(18,3) default "0.000", class, groupid, thickness, width decimal(18,3) default "0.000", coating decimal(18,3) default "0.000", gauge, qty decimal(18,3) default "0.000", prd, sc, clientname, length decimal(18,3) default "0.000", profile, paintcode, skbarcode, strtype, strshift, uom, dpr, fg, consumed decimal(18,3) default "0.000", remarks, barcode, itemname, weight)'
        ], [
          'tablename'=>'designations',
          'createQry'=>'create table if not exists designations(line integer, designation)'
        ], [
          'tablename'=>'colors',
          'createQry'=>'create table if not exists colors(line integer, color)'
        ], [
          'tablename'=>'myexit',
          'createQry'=>'create table if not exists myexit(trno integer, line integer, docno, bref, barcode, isexit integer default 0, itemname, bundleno, designation, color, rrqty decimal(18,3) default "0.000", childcode, class, groupid, coating decimal(18,3) default "0.000", thickness, paintcode, gauge, prd, sc, profile, clientname, length decimal(18,3) default "0.000", width decimal(18,3) default "0.000", skbarcode, dpr, fg)'
        ], [
          'tablename'=>'myentry',
          'createQry'=>'create table if not exists myentry(trno integer, line integer, docno, bref, barcode, itemname, bundleno, childcode, isentry integer default 0, ismanual integer default 0, rem, scandate, frrefx integer default 0, frlinex integer default 0)'
        ], [
          'tablename'=>'tblareg',
          'createQry'=>'create table if not exists tblareg(serialkey, devname, _id integer default 0, stationname)'
        ], [
          'tablename'=>'profile',
          'createQry'=>'create table if not exists profile(doc, psection, pvalue)'
        ], [
          'tablename'=>'stock',
          'createQry'=>'create table if not exists stock(trno integer, line integer, barcode, itemname, itemno decimal(18,3) default "0.000", itemcoilcnt decimal(18,3) default "0.000", bundleno, itemlen decimal(18,3) default "0.000", itemnetweight decimal(18,3) default "0.000", itemgrossweight decimal(18,3) default "0.000", rrqty decimal(18,3) default "0.000", scannedcode, isscanned integer default 0, rem, dr, ref, sorefx integer default 0, solinex integer default 0, frrefx integer default 0, frlinex integer default 0, isentry integer default 0, ismanual integer default 0, scanneddate, scannedby)'
        ], [
          'tablename'=>'head',
          'createQry'=>'create table if not exists head(trno integer primary key, doc, bref, docno, client, clientname, wh, whname, dateid, lcno, ourref, yourref, isselected integer default 0, isposted integer default 0, prdno, isok integer default 0, redl integer default 0, ctr integer default 0, scanneddate, scannedby)'
        ], [
          'tablename'=>'client',
          'createQry'=>'create table if not exists client(line integer primary key autoincrement, clientid integer, client text default "", clientname text default "", loc text default "", rem text default "", dailyrent decimal(18,2) default "0.00", dcusa decimal(18,2) default "0.00", rentdue decimal(19,2) default "0.00", outar decimal(19,2) default "0.00", outcusa decimal(19,2) default "0.00", cusadue decimal(19,2) default "0.00", center text default "", outelec decimal(19,2) default "0.00", outwater decimal(19,2) default "0.00", phase text default "", section text default "", erate decimal(19,2) default "0.00", wrate decimal(19,2) default "0.00", ebeginning decimal(19,2) default "0.00", wbeginning decimal(19,2) default "0.00", last_ebeginning decimal(19,2) default "0.00", last_eending decimal(19,2) default "0.00", last_erate decimal(19,2) default "0.00", last_wbeginning decimal(19,2) default "0.00", last_wending decimal(19,2) default "0.00", last_wrate decimal(19,2) default "0.00", noRent integer default 0, noCusa integer default 0)'
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
        'create index idx_item on item (itemid, barcode)'
      ]
    ];
    return $db;
  }
}