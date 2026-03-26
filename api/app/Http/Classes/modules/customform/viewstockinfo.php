<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewstockinfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $logger;
  private $warehousinglookup;

  public $modulename = 'OTHER DETAILS';
  public $gridname = 'inventory';
  private $fields = ['barcode', 'itemname'];
  private $table = 'stockrem';

  public $tablelogs = 'table_log';

  public $style = 'width:100%;max-width:80%;';
  public $issearchshow = true;
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

  public function createHeadField($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
      if ($config['params']['doc'] == 'PW') {
        $this->modulename = $config['params']['row']['cat_name'] . ' - ' . $config['params']['row']['subcat_name'];
      } else {
        $this->modulename = 'OTHER DETAILS - ' . $config['params']['row']['itemname'];
      }
    } else {
      $trno = $config['params']['dataparams']['trno'];
      if ($config['params']['doc'] == 'PW') {
        $this->modulename = $config['params']['dataparams']['cat_name'] . ' - ' . $config['params']['dataparams']['subcat_name'];
      } else {
        $this->modulename = 'OTHER DETAILS - ' . $config['params']['dataparams']['itemname'];
      }
    }
    $company = $config['params']['companyid'];

    switch ($config['params']['doc']) {
      case 'PO':
      case 'QS':
      case 'SR':
      case 'OP':
      case 'PR':
      case 'SO':
      case 'PC':
      case 'QT':
      case 'PW':
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        break;

      default:
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        break;
    }

    switch ($company) {
      case 17: //unihome 
      case 28: //XCOMP
      case 29: //sbc
      case 36: //ROZLAB
      case 39: //CBBSI
      case 61: //bytesized
        $fields = ['lblitemdesc', 'itemdesc'];
        break;

      default:
        if ($config['params']['doc'] == 'PW') {
          $fields = [['isqty']];
        } else {
          $fields = ['lblrem', 'rem'];
        }
        break;
    }

    if (!$isposted) {
      array_push($fields, 'refresh');
    }

    $col1 = $this->fieldClass->create($fields);
    switch ($company) {
      case 17: //unihome
      case 28: //XCOMP
      case 29: //cdo
      case 36: //ROZLAB
      case 39: //CBBSI
      case 61: //bytesized
        data_set($col1, 'refresh.label', 'update');
        break;
      default:
        data_set($col1, 'refresh.label', 'update');
        data_set($col1, 'rem.type', 'wysiwyg');
        data_set($col1, 'rem.class', 'csrem');
        data_set($col1, 'rem.readonly', false);
        break;
    }

    if ($isposted) {
      data_set($col1, 'rem.readonly', true);
    }

    if ($config['params']['doc'] == 'PW') {
      data_set($col1, 'isqty.label', 'Previous Reading (m)');
    }

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];
    } else {
      $trno = $config['params']['dataparams']['trno'];
      $line = $config['params']['dataparams']['line'];
    }

    return $this->getheaddata($trno, $line, $config['params']['doc'], $config);
  }

  public function getheaddata($trno, $line, $doc, $config)
  {
    $tablename = '';
    $tbl = '';
    switch ($doc) {
      case 'PO':
      case 'QS':
      case 'SQ':
      case 'SR':
      case 'OP':
      case 'PR':
      case 'SO':
      case 'PC':
      case 'QT':
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        if ($isposted) {
          $tablename = 'hstockinfotrans';
        } else {
          $tablename = 'stockinfotrans';
        }

        $qry = "select trno, line, rem,itemdesc, 0 as isnew from " . $tablename . " where trno=? and line=?";
        break;
      case 'PW';
        $qry = "select stock.trno, stock.line, stock.isqty3, 
        FORMAT(ifnull((select s.isqty3 from hpwstock as s left join hpwhead as h on h.trno=s.trno where h.dateid<head.dateid and s.subcat2=subcat2.line order by h.dateid desc limit 1),0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        subcat.name as cat_name, subcat2.name as subcat_name from pwstock as stock left join pwhead as head on head.trno=stock.trno
        left join subpowercat as subcat on subcat.line=stock.subcat left join subpowercat2 as subcat2 on subcat2.line=stock.subcat2 where stock.trno=? and stock.line=?";
        break;

      default: //LA transactions
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        $itemdesc = 'i.itemdescription';
        switch ($doc) {
          case 'RR':
          case 'SJ':
          case 'MI':
          case 'ST':
          case 'AJ':
            switch ($config['params']['companyid']) {
              case 28: //XCOMP
              case 29: //sbc
              case 61: //bytesized
                $itemdesc = 'stock.itemdesc';
                break;
            }
            break;
        }

        if ($isposted) {
          $tablename = 'hstockinfo';
          $tbl = 'glstock';
        } else {
          $tablename = 'stockinfo';
          $tbl = 'lastock';
        }

        $qry = "select stock.trno, stock.line, stock.rem," . $itemdesc . " as itemdesc, 0 as isnew from " . $tablename . " as stock left join " . $tbl . " as la on la.trno = stock.trno and la.line = stock.line
         left join iteminfo as i on i.itemid = la.itemid where stock.trno=? and stock.line=?";
        break;
    }

    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    if (!empty($data)) {
      return $data;
    } else {
      $data = [];
      $row['rem'] = '';
      $row['itemdesc'] = '';
      $row['trno'] = $trno;
      $row['line'] = $line;
      $row['isnew'] = 1;
      array_push($data, $row);
      return $data;
    }
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
    $trno = $config['params']['dataparams']['trno'];
    $line = $config['params']['dataparams']['line'];

    if ($config['params']['doc'] == 'PW') {
      $isqty = $config['params']['dataparams']['isqty3'] - $this->othersClass->sanitizekeyfield("qty", $config['params']['dataparams']['isqty']);

      $data = [
        'isqty2' => $this->othersClass->sanitizekeyfield("qty", $config['params']['dataparams']['isqty']),
        'isqty' =>  $isqty,
        'iss' =>  $isqty,
        'editdate' =>  $this->othersClass->getCurrentTimeStamp(),
        'editby' => $config['params']['user']
      ];
      $this->coreFunctions->sbcupdate("pwstock", $data, ['trno' => $trno, 'line' => $line]);
      $this->logger->writelog($config['params']['doc'], $trno, "STOCK", "Reload previous reading", $config['params']['user']);
    } else {
      $isnew = $config['params']['dataparams']['isnew'];
      $rem = $this->othersClass->sanitizekeyfield('rem', $config['params']['dataparams']['rem']);
      $itemdesc = $this->othersClass->sanitizekeyfield('itemdesc', $config['params']['dataparams']['itemdesc']);

      $data = [
        'trno' => $trno,
        'line' => $line,
        'rem' => $rem,
        'itemdesc' => $itemdesc
      ];

      $tablename = '';
      switch ($config['params']['doc']) {
        case 'PO':
        case 'QS':
        case 'SR':
        case 'OP':
        case 'PR':
        case 'SO':
        case 'PC':
        case 'QT':
          $tablename = 'stockinfotrans';
          break;

        default: //LA transactions
          $tablename = 'stockinfo';
          break;
      }

      if ($isnew) {

        if (!$this->checkdata($trno, $line, $tablename)) {
          $this->coreFunctions->sbcinsert($tablename, $data);
          $this->logger->sbcwritelog(
            $trno,
            $config,
            'STOCKINFO',
            'ADD - Line:' . $line
              . ' Notes:' . $rem
          );
        } else {
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
        }
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
      }
    }

    $txtdata = $this->paramsdata($config);


    $doc = $config['params']['doc'];
    $modtype = $config['params']['moduletype'];
    $path = 'App\Http\Classes\modules\\' . strtolower($modtype) . '\\' . strtolower($doc);
    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'reloadgriddata' => ['inventory' => $stock]];
  }

  private function checkdata($trno, $line, $tblname)
  {
    $qry = "select trno from " . $tblname . " where trno = ? and line = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  } // end fn

}
