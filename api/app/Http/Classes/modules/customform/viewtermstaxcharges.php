<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewtermstaxcharges
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Terms, Taxes and Charges';
  public $gridname = 'tableentry';
  private $fields = ['dvattype', 'terms', 'termsdetails', 'taxdef'];
  private $table = 'headinfotrans';
  public $tablenum = 'transnum';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';

  public $style = 'width:100%;max-width:70%;';
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

  public function getAttrib()
  {
    $attrib = array(
      'load' => 22,
      'edit' => 23,
    );
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    $fields = ['dvattype', 'taxdef', 'terms', 'termsdetails'];

    if (!$isposted) {
      if ($config['params']['doc'] == 'QS') { // quotation for save button
        array_push($fields, 'refresh');
      }
    }

    $allowedit = $this->othersClass->checkAccess($config['params']['user'], 3688);
    $allowterms = $this->othersClass->checkAccess($config['params']['user'], 4162);

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dvattype.type', 'input');
    data_set($col1, 'dvattype.readonly', true);
    data_set($col1, 'terms.readonly', true);
    data_set($col1, 'terms.type', 'input');
    data_set($col1, 'termsdetails.readonly', true);

    if ($allowedit <> '1') {
      data_set($col1, 'taxdef.readonly', true);
    }
    $this->coreFunctions->LogConsole($allowterms);
    if (!$isposted) {
      if ($config['params']['doc'] == 'QS') { // quotation for save button
        if ($allowterms <> 1) {
          data_set($col1, 'terms.type', 'lookup');
          data_set($col1, 'terms.lookupclass', 'customterms');
        } else {
          data_set($col1, 'terms.type', 'lookup');
          data_set($col1, 'terms.lookupclass', 'customtermsaccess');
          data_set($col1, 'termsdetails.readonly', false);
        }

        data_set($col1, 'dvattype.lookupclass', 'customvattype');
        data_set($col1, 'dvattype.label', 'Taxes And Charge');
        data_set($col1, 'refresh.label', 'save');
        data_set($col1, 'dvattype.type', 'lookup');
      }
    }

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['clientid'];

    $head = 'headinfotrans';
    $hhead = 'hheadinfotrans';

    $qstbl = 'qshead';
    $hqstbl = 'hqshead';

    if ($doc == 'AO') {
      $qstbl = 'srhead';
      $hqstbl = 'hsrhead';
    }


    $select = "select h.trno, ifnull(h.terms,'') as terms, ifnull(hi.termsdetails,'') as termsdetails, ifnull(h.tax,0) as tax, ifnull(h.vattype,'') as vattype,ifnull(hi.taxdef,0) as taxdef, concat(ifnull(h.tax,0),'~',ifnull(h.vattype,'')) as dvattype
        ";

    switch ($doc) {
      case 'QT':
        $qry = " " . $select . "
                from " . $qstbl . " as h 
                left join " . $head . " as  hi on hi.trno = h.trno 
                where h.trno=?
                union all
                " . $select . "
                from " . $hqstbl . " as h 
                left join " . $hhead . " as  hi on hi.trno = h.trno 
                where h.trno=? ";
        break;
      case 'QS':
        $select = "select h.trno, ifnull(h.terms,'') as terms, ifnull(h.termsdetails,'') as termsdetails, ifnull(h.tax,0) as tax, ifnull(h.vattype,'') as vattype,ifnull(hi.taxdef,0) as taxdef, 
            concat(ifnull(h.tax,0),'~',ifnull(h.vattype,'')) as dvattype";
        $qry = " " . $select . "
                  from " . $qstbl . " as h 
                  left join " . $head . " as  hi on hi.trno = h.trno 
                  where h.trno=?
                  union all
                  " . $select . "
                  from " . $hqstbl . " as h 
                  left join " . $hhead . " as  hi on hi.trno = h.trno 
                  where h.trno=? ";
        break;

      case 'AO':
        $qry = " " . $select . "
              from " . $hqstbl . " as h 
              left join " . $hhead . " as  hi on hi.trno = h.qtrno
              left join sshead as sq on sq.trno = h.sotrno
              where sq.trno=?
              union all
              " . $select . "
              from " . $hqstbl . " as h 
              left join " . $hhead . " as  hi on hi.trno = h.trno 
              left join hsshead as sq on sq.trno = h.sotrno
              where sq.trno=? ";

        break;

      default:
        $select = "select h.trno, ifnull(h.terms,'') as terms, ifnull(h.termsdetails,'') as termsdetails, ifnull(h.tax,0) as tax, ifnull(h.vattype,'') as vattype,ifnull(hi.taxdef,0) as taxdef, concat(ifnull(h.tax,0),'~',ifnull(h.vattype,'')) as dvattype
        ";
        $qry = " " . $select . "
                from " . $hqstbl . " as h 
                left join " . $hhead . " as  hi on hi.trno = h.trno
                left join sqhead as sq on sq.trno = h.sotrno
                where sq.trno=?
                union all
                " . $select . "
                from " . $hqstbl . " as h 
                left join " . $hhead . " as  hi on hi.trno = h.trno 
                left join hsqhead as sq on sq.trno = h.sotrno
                where sq.trno=? ";
        break;
    }

    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    if (empty($data)) {
      $data =  $this->coreFunctions->opentable("select $trno as trno, '' as vattype, 0 as tax,
            '' as terms, '' as termsdetails, '' as dvattype, 0 as taxdef ");
    }
    return $data;
  }

  public function getheaddata($config, $doc)
  {
    return [];
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
    $doc = $config['params']['doc'];

    $trno = $config['params']['dataparams']['trno'];
    $tax = $config['params']['dataparams']['tax'];
    $taxdef = $config['params']['dataparams']['taxdef'];
    $vattype = $config['params']['dataparams']['vattype'];
    $terms = $config['params']['dataparams']['terms'];
    $termsdetails = $config['params']['dataparams']['termsdetails'];
    $editby = $config['params']['user'];
    $editdate = $this->othersClass->getCurrentTimeStamp();

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['status' => false, 'msg' => 'Failed to save; already posted.'];
    }


    if ($termsdetails == '') {
      $termsdetails =  $config['params']['dataparams']['terms'];
    }

    $data = [
      'trno' => $trno,
      'termsdetails' => $termsdetails,
      'taxdef' => $taxdef,
      'editby' => $editby,
      'editdate' => $editdate

    ];

    $data2 = [
      'trno' => $trno,
      'vattype' => $vattype,
      'tax' => $tax,
      'terms' => $terms,
      'editby' => $editby,
      'editdate' => $editdate
    ];

    $tablename = 'headinfotrans';

    switch ($doc) {
      case 'QS':
        $headtable = 'qshead';
        $data2['termsdetails'] = $termsdetails;
        unset($data['termsdetails']);
        break;
      case 'AO':
        $headtable = 'hsrhead';
        $data2['termsdetails'] = $termsdetails;
        unset($data['termsdetails']);
        break;
      default:
        $headtable = $doc . 'head';
        break;
    }

    if (!$this->checkdata($trno, $tablename)) {
      $this->coreFunctions->sbcinsert($tablename, $data);
      $this->coreFunctions->sbcupdate($headtable, $data2, ['trno' => $trno]);

      if ($doc == "QS") {
        $this->logger->sbcwritelog(
          $trno,
          $config,
          'TERMS AND TAXES',
          ' TAX DEF: ' . $data['taxdef']
        );
      } else {
        $this->logger->sbcwritelog(
          $trno,
          $config,
          'TERMS AND TAXES',
          ' TERMS DETAILS: ' . $data['termsdetails']
            . ', TAX DEF: ' . $data['taxdef']
        );
      }
    } else {
      $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno]);
      $this->coreFunctions->sbcupdate($headtable, $data2, ['trno' => $trno]);
    }
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => []];
  }

  public function checkdata($trno, $tablename)
  {
    $data =  $this->coreFunctions->opentable("select trno from " . $tablename . " where trno = ? ", [$trno]);
    if ($data) {
      return true;
    } else {
      return false;
    }
  }
}
