<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\mobile\modules\ordering\items;
use App\Http\Classes\mobile\modules\ordering\orders;
use App\Http\Classes\mobile\modules\ordering\reports;
use App\Http\Classes\mobile\modules\ordering\reports2;

class ordering {
  private $items;
  private $orders;
  private $reports;
  private $reports2;

  public function __construct() {
    $this->items = new items;
    $this->orders = new orders;
    $this->reports = new reports;
    $this->reports2 = new reports2;
  }

  public function getAddConfigCol() {
    return [
      ['col'=>'uomlock', 'type'=>'integer'],
      ['col'=>'lastorderno', 'type'=>''],
      ['col'=>'fontsize', 'type'=>'']
    ];
  }

  public function getSettings() {
    $settings = [
      ['istablegrid'=>false],
      ['hasupload'=>false],
      ['istextlookup'=>false],
      ['menutype'=>'footer'],
      ['hascsv'=>true],
      ['printreceipt'=>env('printreceipt', true)],
      ['downloadAllUsers'=>env('downloadAllUsers', false)],
      ['custDownloadType'=>env('custDownloadType', 'area')]
    ];
    return $settings;
  }

  public function getLoginTabs () {
    return [];
  }
  
  public function getDownloads () {
    $downloads = [
      ['name'=>'items', 'label'=>'Download Items', 'func'=>'downloadItems'],
      ['name'=>'customer', 'label'=>'Download Customers', 'func'=>'downloadClient'],
      ['name'=>'uom', 'label'=>'Download UOM', 'func'=>'downloadUOM'],
      ['name'=>'itembal', 'label'=>'Download Item Balance', 'func'=>'downloadItemBal'],
      ['name'=>'signaturepad', 'label'=>'Signature Pad Sample', 'func'=>'showSignaturePad']
    ];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [
      ['name'=>'items', 'label'=>'Items', 'doc'=>'items', 'icon'=>'add_shopping_cart', 'layout'=>'customform', 'isdefault'=>true],
      ['name'=>'orders', 'label'=>'Orders', 'doc'=>'orders', 'icon'=>'receipt', 'layout'=>'customform'],
      ['name'=>'reports', 'label'=>'Sales Listing', 'doc'=>'reports', 'icon'=>'table_chart', 'layout'=>'customform'],
      ['name'=>'reports2', 'label'=>'Sales Report Graph', 'doc'=>'reports2', 'icon'=>'bar_chart', 'layout'=>'chartform']
    ];
    return $menu;
  }

  public function getFooterButtons() {
    return [
      ['name'=>'sync', 'label'=>'Sync to Server', 'icon'=>'sync', 'func'=>'syncOrders', 'functype'=>'global', 'show'=>true],
      ['name'=>'cart', 'label'=>'Cart', 'icon'=>'shopping_cart', 'hasbadge'=>true, 'badgecolor'=>'red', 'badgedata'=>'cartICount', 'func'=>'loadCart', 'functype'=>'global', 'show'=>true]
    ];
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
          'tablename'=>'useraccess',
          'createQry'=>'create table if not exists useraccess(userid integer, accessid integer, username, password, name, pincode, pincode2, wh)',
        ], [
          'tablename'=>'users',
          'createQry'=>'create table if not exists users(idno integer, attributes)'
        ], [
          'tablename'=>'itimages',
          'createQry'=>'create table if not exists itimages(codeid integer primary key, pic)'
        ], [
          'tablename'=>'uom',
          'createQry'=>'create table if not exists uom(line integer, itemid integer, uom, factor, amt, isdefault integer)'
        ], [
          'tablename'=>'customers',
          'createQry'=>'create table if not exists customers(clientid integer, client, clientname, addr, tel, isinactive, terms, flr, brgy, area, province, region)'
        ], [
          'tablename'=>'item',
          'createQry'=>'create table if not exists item(itemid integer, barcode, itemname, amt, newamt, disc, uom, qty, newuom, factor, newfactor, rem, brand, part, plgrp, isinactive, groupid, category, model, sizeid, country, seq integer, newdisc, camt, istaxable, amt2, famt, amt4, amt5, amt6, class, hasitem)'
        ], [
          'tablename'=>'cart',
          'createQry'=>'create table if not exists cart(line integer primary key autoincrement, itemid integer, isamt, amt, isqty, iss, ext, disc, uom, factor, rem)'
        ], [
          'tablename'=>'warehouse',
          'createQry'=>'create table if not exists warehouse(whid, wh, whname, addr, isinactive, isdefault)'
        ], [
          'tablename'=>'center',
          'createQry'=>'create table if not exists center(centercode, userid integer, centername, warehouse, warehousename)'
        ], [
          'tablename'=>'transhead',
          'createQry'=>'create table if not exists transhead(trno integer primary key autoincrement, orderno, userid, center, doc, dateid, itemcount, total, client, rem, terms, shipto, station, branch, cash, card, credit, debit, cheque, eplus, online, loyaltypoints, smac, voucher, tendered, change, transtype, ishold, wh, stdisc, stdiscamt, clientname, acctno, cardtype, batch, approval, checktype, bankname, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt, voiddate, voidby, isvoid, transtime)'
        ], [
          'tablename'=>'transhistoryhead',
          'createQry'=>'create table if not exists transhistoryhead(trno integer, docno, userid, center, doc, dateid, itemcount, total, client, datesynced, rem, terms, shipto, station, branch, cash, card, credit, debit, cheque, eplus, online, loyaltypoints, smac, voucher, tendered, change, transtype, wh, stdisc, stdiscamt, clientname, acctno, cardtype, batch, approval, checktype, bankname, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt, voiddate, voidby, isvoid, transtime)'
        ], [
          'tablename'=>'transhistorystock',
          'createQry'=>'create table if not exists transhistorystock(line integer primary key autoincrement, userid, center, orderno, barcode, itemname, isamt, amt, qty, isqty, iss, total, uom, factor, rem, disc, qa, isdiplomat, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt)'
        ], [
          'tablename'=>'transstock',
          'createQry'=>'create table if not exists transstock(line integer primary key autoincrement, userid, center, orderno, barcode, itemname, isamt, amt, qty, isqty, iss, total, uom, factor, rem, disc, qa, isdiplomat, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt)'
        ], [
          'tablename'=>'itemstat',
          'createQry'=>'create table if not exists itemstat(itemid integer, qty)'
        ], [
          'tablename'=>'updatetable',
          'createQry'=>'create table if not exists updatetable(id integer primary key autoincrement, itemdate, customerdate, uomdate, itimagesdate)'
        ], [
          'tablename'=>'terms',
          'createQry'=>'create table if not exists terms(line integer primary key autoincrement, terms, days)'
        ], [
          'tablename'=>'payments',
          'createQry'=>'create table if not exists payments(orderno, amt, paytype, station, clientname, acctno, cardtype, batch, approval, checktype, bankname)'
        ], [
          'tablename'=>'cntnum',
          'createQry'=>'create table if not exists cntnum(osseq integer, siseq integer, prefix)'
        ], [
          'tablename'=>'cashiersale',
          'createQry'=>'create table if not exists cashiersale(cashier, station, dateid, amt, cash, cheque, card, nvat, vatamt, vatex, disc, voidamt, ctrvoid, returnamt, ctrreturn, amt2, ra, cr, discsr, sramt, lp, voucher, pwdamt, empdisc, debit, eplus, smac, onlinedeals, vipdisc, oddisc, smacdisc, localtax, vatdisc, gross, loadwallet, loadamt)'
        ], [
          'tablename'=>'cashiersalehistory',
          'createQry'=>'create table if not exists cashiersalehistory(cashier, station, dateid, amt, cash, cheque, card, nvat, vatamt, vatex, disc, voidamt, ctrvoid, returnamt, ctrreturn, amt2, ra, cr, discsr, sramt, lp, voucher, pwdamt, empdisc, debit, eplus, smac, onlinedeals, vipdisc, oddisc, smacdisc, localtax, vatdisc, gross, loadwallet, loadamt)'
        ], [
          'tablename'=>'financial',
          'createQry'=>'create table if not exists financial(station, amt, amt2, cash, cheque, card, nvat, vatamt, vatex, voidamt, ctrvoid, returnamt, ctrreturn, disc, cr, discsr, sramt, dateid, lp, voucher, pwdamt, empdisc, debit, eplus, smac, onlinedeals, vipdisc, oddisc, smacdisc, localtax, gross, loadwallet, loadamt)'
        ], [
          'tablename'=>'journal',
          'createQry'=>'create table if not exists journal(station, amt, amt2, cash, cheque, card, nvat, vatamt, vatex, voidamt, ctrvoid, returnamt, ctrreturn, disc, cr, discsr, dateid, lp, voucher, printdate, pwdamt, empdisc, rlcctr, svccharge, debit, eplus, smac, onlinedeals, vipdisc, oddisc, smacdisc, localtax, isok, branch, vatdisc)'
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