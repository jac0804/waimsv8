<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use App\Http\Classes\mobile\modules\sapint\rr;
use App\Http\Classes\mobile\modules\sapint\rl;
use App\Http\Classes\mobile\modules\sapint\rm;
use App\Http\Classes\mobile\modules\sapint\fg;
use App\Http\Classes\mobile\modules\sapint\tr;
use App\Http\Classes\mobile\modules\sapint\tm;
use App\Http\Classes\mobile\modules\sapint\dr;

class sapint {
  private $buttonClass;
  private $fieldClass;
  private $rr;
  private $rl;
  private $rm;
  private $fg;
  private $tr;
  private $tm;
  private $dr;

  public function __construct() {
    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
    $this->company = env('appcompany', 'sbc');
    $this->rr = new rr;
    $this->rl = new rl;
    $this->rm = new rm;
    $this->fg = new fg;
    $this->tr = new tr;
    $this->tm = new tm;
    $this->dr = new dr;
  }
  public function getAddConfigCol() {
    return [];
  }

  public function getSettings() {
    $settings = [
      ['istablegrid'=>false],
      ['hasupload'=>false],
      ['istextlookup'=>false],
      ['menutype'=>'left'],
      ['hascsv'=>false],
      ['ismultilogin'=>false],
      ['nologin'=>false],
      ['hasregistration'=>true],
      ['hasstation'=>false],
      ['storagetype'=>'itemscanner'],
      ['downloadAllUsers'=>env('downloadAllUsers', false)],
      ['hasaccesschecking'=>true],
      ['hasprinting'=>false],
      ['primarycolor'=>'indigo-10']
    ];
    return $settings;
  }
  
  public function getDownloads () {
    // $downloads = [
    //   ['name'=>'accounts', 'label'=>'Download Accounts', 'func'=>'downloadTimeinAccounts']
    // ];
    $downloads = [];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [
      ['type'=>'sapint', 'name'=>'rr', 'label'=>'Receiving Raw Materials', 'doc'=>'rr', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>1, 'isdefault'=>true],
      ['type'=>'sapint', 'name'=>'rl', 'label'=>'Releasing Raw Materials to Production', 'doc'=>'rl', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>2],
      ['type'=>'sapint', 'name'=>'rm', 'label'=>'Receiving Raw Materials to Production', 'doc'=>'rm', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>3],
      ['type'=>'sapint', 'name'=>'fg', 'label'=>'Receiving Finish Goods from Production', 'doc'=>'fg', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>4],
      ['type'=>'sapint', 'name'=>'tr', 'label'=>'Releasing from Production - Inventory Transfer Request FG', 'doc'=>'tr', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>5],
      ['type'=>'sapint', 'name'=>'tm', 'label'=>'Receiving to Warehouse from Production - FG', 'doc'=>'tm', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>6],
      ['type'=>'sapint', 'name'=>'dr', 'label'=>'Dispatching', 'doc'=>'dr', 'icon'=>'table_chart', 'layout'=>'customform', 'access'=>7]
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
        switch($m['doc']) {
          case 'rr':
            $layouts['rr'] = $this->rr->getLayout();
            array_push($funcs, ['doc'=>'rr', 'func'=>$this->rr->getFunc()]);
            break;
          case 'rl':
            $layouts['rl'] = $this->rl->getLayout();
            array_push($funcs, ['doc'=>'rl', 'func'=>$this->rl->getFunc()]);
            break;
          case 'rm':
            $layouts['rm'] = $this->rm->getLayout();
            array_push($funcs, ['doc'=>'rm', 'func'=>$this->rm->getFunc()]);
            break;
          case 'fg':
            $layouts['fg'] = $this->fg->getLayout();
            array_push($funcs, ['doc'=>'fg', 'func'=>$this->fg->getFunc()]);
            break;
          case 'tr':
            $layouts['tr'] = $this->tr->getLayout();
            array_push($funcs, ['doc'=>'tr', 'func'=>$this->tr->getFunc()]);
            break;
          case 'tm':
            $layouts['tm'] = $this->tm->getLayout();
            array_push($funcs, ['doc'=>'tr', 'func'=>$this->tm->getFunc()]);
            break;
          case 'dr':
            $layouts['dr'] = $this->dr->getLayout();
            array_push($funcs, ['doc'=>'dr', 'func'=>$this->dr->getFunc()]);
            break;
        }
        // $layouts[$m['doc']] = $this->$m['doc']->getLayout();
        // array_push($funcs, ['doc'=>$m['doc'], 'func'=>$this->$m['doc']->getFunc()]);
      }
    }
    return ['layouts'=>$layouts, 'funcs'=>$funcs];
  }

  public function getDBTables() {
    $db = [
      'new'=>[
        [
          'tablename'=>'log',
          'createQry'=>'create table if not exists log(line integer PRIMARY KEY AUTOINCREMENT, id, dateid, timein, timeout, loginPic, logoutPic, isok integer default 0, uploaddate)'
        ], [
          'tablename'=>'head',
          // 'createQry'=>'create table if not exists head(trno integer PRIMARY KEY, docno, doc, dateid, client, clientname, yourref, uploaded)',
          'createQry'=>'create table if not exists head(trno integer, docno, doc, dateid, client, clientname, yourref, ourref, wh)'
        ], [
          'tablename'=>'stock',
          // 'createQry'=>'create table if not exists stock(trno, line, docno, barcode, itemname, qty, batchcode, uom, wh, wht, suppno, printdate, printby, poline, iscomplete)'
          'createQry'=>'create table if not exists stock(trno, line, rtrno, rline, rrrefx, rrlinex, barcode, itemname, batchcode, qty, uom, printdate, printby, iscomplete, printed, wht, uploaded, doc)'
        ], [
          'tablename'=>'detail',
          // 'createQry'=>'create table if not exists detail(trno, line, docno, sline, barcode, batchcode, pickscanneddate, pickscannedby, qtyreleased, isverified, qty, uom, wh, printdate, printby, rmline)'
          'createQry'=>'create table if not exists detail(trno, line integer PRIMARY KEY AUTOINCREMENT, sline, rtrno, rline, rrrefx, rrlinex, pickscanneddate, pickscannedby, qtyreleased, isverified, barcode, batchcode, uploaded, printdate, printby, printed, doc, dline)'
        ], [
          'tablename'=>'tempdetail',
          // 'createQry'=>'create table if not exists tempdetail(trno, line, barcode, batchcode, rmline)'
          'createQry'=>'create table if not exists tempdetail(trno, line, sline, barcode, batchcode)'
        ], [
          'tablename'=>'useraccess',
          'createQry'=>'create table if not exists useraccess(userid integer, accessid integer, username, password, name, pincode, pincode2, wh)',
        ], [
          'tablename'=>'users',
          'createQry'=>'create table if not exists users(idno integer, attributes)'
        ]
      ],
      'addcol'=>[
        ['tablename'=>'tempdetail', 'column'=>'batchcode'],
        ['tablename'=>'detail', 'column'=>'barcode'],
        ['tablename'=>'detail', 'column'=>'batchcode'],
        ['tablename'=>'detail', 'column'=>'uploaded'],
        ['tablename'=>'detail', 'column'=>'printdate'],
        ['tablename'=>'detail', 'column'=>'printby'],
        ['tablename'=>'detail', 'column'=>'printed'],
        ['tablename'=>'stock', 'column'=>'printed'],
        ['tablename'=>'stock', 'column'=>'wht'],
        ['tablename'=>'stock', 'column'=>'uploaded'],
        ['tablename'=>'head', 'column'=>'wh'],
        ['tablename'=>'stock', 'column'=>'doc'],
        ['tablename'=>'detail', 'column'=>'doc'],
        ['tablename'=>'detail', 'column'=>'dline']
      ],
      'dropcol'=>[],
      'addindex'=>[]
    ];
    return $db;
  }
}