<?php
namespace App\Http\Classes\mobile;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use App\Http\Classes\mobile\modules\itemscanner\iadmin;

class itemscanner {
  private $buttonClass;
  private $fieldClass;
  private $iadmin;

  public function __construct() {
    $this->buttonClass = new mobileButtonClass;
    $this->fieldClass = new mobiletxtFieldClass;
    $this->iadmin = new iadmin;
  }
  public function getAddConfigCol() {
    return [
      ['col'=>'username', 'type'=>''],
      ['col'=>'password', 'type'=>''],
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
      ['ismultilogin'=>false],
      ['nologin'=>true],
      ['hasregistration'=>true],
      ['storagetype'=>'itemscanner']
    ];
    return $settings;
  }
  
  public function getDownloads () {
    $downloads = [];
    return $downloads;
  }

  public function getMainMenu () {
    $menu = [
      ['type'=>'itemscanner', 'name'=>'iadmin', 'label'=>'Admin', 'doc'=>'iadmin', 'icon'=>'table_chart', 'layout'=>'customform', 'isdefault'=>true]
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
          'tablename'=>'items',
          'createQry'=>'create table if not exists items(recordid integer primary key, assettype, assetno, subaccount, barcode, description, shortdesc, assignee, serial, location, division, cappdp, usefullife, capcost, accddepn, nbv, dateofbarcoding, time, user, remarks, scanneddate)'
        ]
      ],
      'dropcol'=>[],
      'addindex'=>[]
    ];
    return $db;
  }
}