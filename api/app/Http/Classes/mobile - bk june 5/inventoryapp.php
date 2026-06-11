<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use App\Http\Classes\mobile\modules\inventoryapp\inventory;

class inventoryapp {
  private $buttonClass;
  private $fieldClass;
  private $inventory;

  public function __construct() {
    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
    $this->company = env('appcompany', 'mbs');
    $this->inventory = new inventory;
  }
  public function getAddConfigCol() {
    return ['col'=>'pcdate', 'type'=>''];
  }

  public function getSettings() {
    $primaryColor = 'blue-10';
    if ($this->company == 'ulitc') $primaryColor = 'red-10';
    $settings = [
      ['istablegrid'=>false],
      ['hasupload'=>true],
      ['istextlookup'=>false],
      ['menutype'=>'footer'],
      ['hascsv'=>false],
      ['ismultilogin'=>false],
      ['nologin'=>false],
      ['hasregistration'=>true],
      ['hasstation'=>true],
      ['storagetype'=>'itemscanner'],
      ['downloadAllUsers'=>env('downloadAllUsers', true)],
      ['hasaccesschecking'=>false],
      ['hasprinting'=>false],
      ['primarycolor'=>$primaryColor]
    ];
    return $settings;
  }
  
  public function getDownloads () {
    $downloads = [
      ['name'=>'items', 'label'=>'Download Items', 'func'=>'downloadInventoryItems'],
      ['name'=>'onlinebranchaddr', 'label'=>'Online/Branch Address', 'func'=>'onlinebranchAddress'],
      ['name'=>'pcdate', 'label'=>'Physical Count Date', 'func'=>'setPCDate'],
      ['name'=>'getbackendversion', 'label'=>'Check backend version', 'func'=>'checkBackendVersion'],
      ['name'=>'userslist', 'label'=>'Users List', 'func'=>'usersList']
      // ['name'=>'viewheadtable', 'label'=>'View Head Table', 'func'=>'viewheadtable'],
      // ['name'=>'viewhheadtable', 'label'=>'View HHead Table', 'func'=>'viewhheadtable'],
      // ['name'=>'viewstocktable', 'label'=>'View Stock Table', 'func'=>'viewstocktable'],
      // ['name'=>'viewhstocktable', 'label'=>'View HStock Table', 'func'=>'viewhstocktable']
    ];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [
      ['type'=>'inventory', 'name'=>'inventory', 'label'=>'Inventory', 'doc'=>'inventory', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true]
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
          case 'inventory':
            $layouts['inventory'] = $this->inventory->getLayout();
            array_push($funcs, ['doc'=>'inventory', 'func'=>$this->inventory->getFunc()]);
            break;
        }
      }
    }
    return ['layouts'=>$layouts, 'funcs'=>$funcs];
  }

  public function getDBTables() {
    $db = [
      'new'=>[[
          'tablename'=>'head',
          'createQry'=>'create table if not exists head(trno integer PRIMARY KEY AUTOINCREMENT, wh, loc, brand, dateid)'
        ], [
          'tablename'=>'hhead',
          'createQry'=>'create table if not exists hhead(trno, wh, loc, brand, dateid, uploaded)'
        ], [
          'tablename'=>'stock',
          'createQry'=>'create table if not exists stock(trno, line integer PRIMARY KEY AUTOINCREMENT, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid)'
        ], [
          'tablename'=>'hstock',
          'createQry'=>'create table if not exists hstock(trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid)'
        ], [
          'tablename'=>'useraccess',
          'createQry'=>'create table if not exists useraccess(userid integer, accessid integer, username, password, name, pincode, pincode2, wh)',
        ], [
          'tablename'=>'users',
          'createQry'=>'create table if not exists users(idno integer, attributes)'
        ], [
          'tablename'=>'wh',
          'createQry'=>'create table if not exists wh(client, clientname, generated, uploaded, filename, branch)'
        ], [
          'tablename'=>'item',
          'createQry'=>'create table if not exists item(itemid, barcode, partno, itemname, uom, brand, amt, bal)'
        ], [
          'tablename'=>'itembal',
          'createQry'=>'create table if not exists itembal(itemid, bal, wh)'
        ], [
          'tablename'=>'clientitem',
          'createQry'=>'create table if not exists clientitem(wh, barcode, sku)'
        ], [
          'tablename'=>'errlogs',
          'createQry'=>'create table if not exists errlogs(id integer primary key autoincrement, description, data, dateid, user)'
        ], [
          'tablename'=>'soldqtyitems',
          'createQry'=>'create table if not exists soldqtyitems(line integer primary key autoincrement, wh, dateid, barcode, soldqty)'
        ], [
          'tablename'=>'tempwh',
          'createQry'=>'create table if not exists tempwh(client, clientname)'
        ], [
          'tablename'=>'consolidated',
          'createQry'=>'create table if not exists consolidated(line integer primary key autoincrement, itemid, barcode, qty, isuploaded)'
        ], [
          'tablename'=>'fconsolidated',
          'createQry'=>'create table if not exists fconsolidated(line integer, itemid, barcode, qty, isuploaded)'
        ], [
          'tablename'=>'mbssettings',
          'createQry'=>'create table if not exists mbssettings(name, value)'
        ]
      ],
      'addcol'=>[
        ['tablename'=>'head', 'column'=>'dateid'],
        ['tablename'=>'hhead', 'column'=>'dateid'],
        ['tablename'=>'stock', 'column'=>'seq'],
        ['tablename'=>'hstock', 'column'=>'seq'],
        ['tablename'=>'hhead', 'column'=>'uploaded'],
        ['tablename'=>'stock', 'column'=>'wh'],
        ['tablename'=>'stock', 'column'=>'dateid'],
        ['tablename'=>'hstock', 'column'=>'wh'],
        ['tablename'=>'hstock', 'column'=>'dateid'],
        ['tablename'=>'wh', 'column'=>'generated'],
        ['tablename'=>'wh', 'column'=>'uploaded'],
        ['tablename'=>'wh', 'column'=>'filename'],
        ['tablename'=>'item', 'column'=>'bal'],
        ['tablename'=>'item', 'column'=>'partno'],
        ['tablename'=>'wh', 'column'=>'branch'],
        ['tablename'=>'consolidated', 'column'=>'itemid'],
        ['tablename'=>'fconsolidated', 'column'=>'itemid'],
        ['tablename'=>'config', 'column'=>'branchaddr']
      ],
      'dropcol'=>[],
      'addindex'=>[]
    ];
    return $db;
  }
}