<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewinstructiontab
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Instruction';
  public $gridname = 'tableentry';
  private $fields = ['shipid', 'billid'];
  private $table = 'client';

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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");


    $fields = ['lblshipping', 'yourref','dtcno','rem2', 'instructions', 'ispartial', 'isshipmentnotif', 'shipmentnotif'];

    if (!$isposted) {
      if ($config['params']['doc'] == 'QS') { // quotation for save button
        array_push($fields, 'refresh');
      }
    }

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'lblshipping.label', 'Instruction');
    data_set($col1, 'instructions.type', 'textarea');
    data_set($col1, 'rem2.label', 'PCF Details');
    data_set($col1, 'rem2.readonly',true);
    data_set($col1, 'yourref.type', 'cinput');
    data_set($col1, 'yourref.name', 'inspo');
    data_set($col1, 'yourref.maxlength', '50');
    data_set($col1, 'yourref.label', 'Reference #');
    data_set($col1, 'yourref.readonly', true);
    data_set($col1, 'instructions.readonly', true);
    data_set($col1, 'ispartial.readonly', true);
    data_set($col1, 'ispartial.class', 'sbccsreadonly');
    data_set($col1, 'refresh.label', 'Save');
    data_set($col1, 'shipmentnotif.readonly', true);
    data_set($col1, 'isshipmentnotif.readonly', true);
    data_set($col1, 'dtcno.type', 'input');
    data_set($col1, 'dtcno.readonly', true);

    if (!$isposted) {
      if ($config['params']['doc'] == 'QS') { // quotation for save button
        data_set($col1, 'yourref.readonly', false);
        data_set($col1, 'instructions.readonly', false);
        data_set($col1, 'ispartial.readonly', false);
        data_set($col1, 'shipmentnotif.readonly', false);
        data_set($col1, 'refresh.label', 'Save');
      }
    }

    $fields = ['docno', 'ourref', 'db', 'cr', 'bal'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'docno.label', 'CR#');
    data_set($col2, 'docno.type', 'input');
    data_set($col2, 'docno.readonly', true);
    data_set($col2, 'ourref.label', 'Payment Type');
    data_set($col2, 'ourref.type', 'input');
    data_set($col2, 'db.label', 'Amount Paid');
    data_set($col2, 'cr.label', 'EWT');



    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['clientid'];

    $head = 'headinfotrans';
    $hhead = 'hheadinfotrans';

    switch ($doc) {
      case 'RF':
      case 'VS':
      case 'VT':
        $tbl = strtolower($doc) . 'head';
        $htbl = 'h' . strtolower($doc) . 'head';
        break;
    }

    $select = "
      select ifnull(hi.trno, 0) as trno, ifnull(hi.inspo, '') as inspo, ifnull(hi.ispartial, '0') as ispartial, 
      ifnull(hi.instructions, '') as instructions, ifnull(hi.period, '') as period, 
      ifnull(hi.isvalid, 0) as isvalid, ifnull(hi.ovaliddate, '') as ovaliddate,
      ifnull(hi.termsdetails, '') as termsdetails, ifnull(hi.proformainvoice, '') as proformainvoice, 
      ifnull(hi.proformadate, '') as proformadate,ifnull(isshipmentnotif,'') as isshipmentnotif,ifnull(shipmentnotif,'') as shipmentnotif,ifnull(px.dtcno,'') as dtcno,hi.dtctrno,hi.rem2";


    switch ($doc) {
      case 'QS':
      case 'QT':
      case 'SQ':
      case 'AO':
      case 'PO':
      case 'VS':
      case 'VT':

        $qry = "" . $select . "
        from " . $head . " as hi
        left join hpxhead as px on px.trno = hi.dtctrno
        where hi.trno = ?
        union all
        " . $select . "
        from " . $hhead . " as hi
        left join hpxhead as px on px.trno = hi.dtctrno
        where hi.trno = ?";

        if ($doc == "SQ") {
          $sotrno = $config['params']['clientid'];
          $trno = $this->coreFunctions->getfieldvalue("hqshead", "trno", "sotrno=?", [$sotrno]);
        }

        $companyid = $config['params']['companyid'];
        if ($companyid == 10 || $companyid == 12) { //afti
          if ($doc == "PO") {
            $potrno = $config['params']['clientid'];

            $sotrno = $this->coreFunctions->datareader("
            select sotrno as value from pohead where trno = ?
            union all
            select sotrno as value from hpohead where trno = ?
            limit 1", [$potrno, $potrno]);

            $trno = $this->coreFunctions->getfieldvalue("hqshead", "trno", "sotrno=?", [$sotrno]);
          }
        }

        switch ($doc) {
          case "AO":
            $sotrno = $config['params']['clientid'];
            $trno = $this->coreFunctions->getfieldvalue("hsrhead", "trno", "sotrno=?", [$sotrno]);
            $qttrno = $this->coreFunctions->datareader("select qtrno as value from hsrhead where trno=?", [$trno]);
            $data = $this->coreFunctions->opentable($qry, [$qttrno, $qttrno]);
            break;
          case "VS":
            $vtrno = $config['params']['clientid'];
            $isposted = $this->othersClass->isposted2($vtrno, "transnum");
            if ($isposted) {
              $sotrno = $this->coreFunctions->getfieldvalue("hvshead", "sotrno", "trno=?", [$vtrno]);
            } else {
              $sotrno = $this->coreFunctions->getfieldvalue("vshead", "sotrno", "trno=?", [$vtrno]);
            }

            $trno = $this->coreFunctions->getfieldvalue("hsrhead", "trno", "sotrno=?", [$sotrno]);
            $qttrno = $this->coreFunctions->datareader("select qtrno as value from hsrhead where trno=?", [$trno]);
            $data = $this->coreFunctions->opentable($qry, [$qttrno, $qttrno]);
            break;
          case "VT":
            $vtrno = $config['params']['clientid'];
            $isposted = $this->othersClass->isposted2($vtrno, "transnum");
            if ($isposted) {
              $sotrno = $this->coreFunctions->getfieldvalue("hvthead", "sotrno", "trno=?", [$vtrno]);
            } else {
              $sotrno = $this->coreFunctions->getfieldvalue("vthead", "sotrno", "trno=?", [$vtrno]);
            }
            $trno = $this->coreFunctions->getfieldvalue("hqshead", "trno", "sotrno=?", [$sotrno]);
            $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
            break;
          default:
            $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
            break;
        }


        if (empty($data)) {
          $data = $this->coreFunctions->opentable("
          select 
          '" . $trno . "' as trno, 
          '' as inspo, 
          '0' as ispartial, 
          '' as instructions, 
          '' as period, 
          '' as isvalid, 
          '' as ovaliddate,
          '' as termsdetails, 
          '' as proformainvoice, 
          '' as proformadate,
          '' as docno,
          '' as ourref,'' as isshipmentnotif,'' as shipmentnotif,
          0 as db,'' as dtcno,0 as dtctrno");
        }

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $tbls = '';
        $qttbl = '';
        if ($isposted) {
          $tbl = 'h' . strtolower($doc) . 'head';
          $tbls = 'h' . strtolower($doc) . 'stock';
          $qttbl = 'hqtstock';

          if ($doc == "SQ" || $doc == "PO" || $doc == "VT") {
            $tbl = 'hqshead';
            $tbls = 'hqsstock';
            $qttbl = 'hqtstock';
          }

          if ($doc == "AO" || $doc == 'VS') {
            $tbl = 'hsrhead';
            $tbls = 'hqsstock';
            $qttbl = 'hqtstock';
          }
        } else {
          $tbl = strtolower($doc) . 'head';
          $tbls = strtolower($doc) . 'stock';
          $qttbl = 'qtstock';

          if ($doc == "SQ" || $doc == "VT") {
            $tbl = 'hqshead';
            $tbls = 'hqsstock';
            $qttbl = 'hqtstock';
          }

          if ($doc == "AO" || $doc == 'VS') {
            $tbl = 'hsrhead';
            $tbls = 'hqsstock';
            $qttbl = 'hqtstock';
          }
        }


        if ($doc == 'AO') {
          $total = $this->coreFunctions->getfieldvalue($tbls, "sum(iss*amt)", "trno=?", [$qttrno]);
          $tax = $this->coreFunctions->getfieldvalue($tbl, "tax", "trno=?", [$trno]);
          $total = $total + $this->coreFunctions->getfieldvalue($qttbl, "sum(iss*amt)", "trno=?", [$qttrno]);
        } else {
          $total = $this->coreFunctions->getfieldvalue($tbls, "sum(iss*amt)", "trno=?", [$trno]);
          $tax = $this->coreFunctions->getfieldvalue($tbl, "tax", "trno=?", [$trno]);
          $total = $total + $this->coreFunctions->getfieldvalue($qttbl, "sum(iss*amt)", "trno=?", [$trno]);
        }


        if ($tax != 0) {
          $total = round($total,2);
          $total = round($total * 1.12, 2);
        }


        if ($doc == "AO" || $doc == 'VS') {
          $qttrno = $this->coreFunctions->datareader("select qtrno as value from hsrhead where trno=?", [$trno]);
          if ($qttrno != 0) {
            $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db from (select head.crref as docno,head.ourref,sum(detail.cr) as db
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join coa on coa.acnoid = detail.acnoid 
            where detail.qttrno = ?  and coa.alias in ('AR5','PD1')
            group by head.crref,head.ourref
            union all
            select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join coa on coa.acnoid = detail.acnoid 
            where detail.qttrno = ? and coa.alias in ('AR5','PD1')
            group by head.crref,head.ourref) as a  ", [$qttrno, $qttrno]);

            $ewt = $this->coreFunctions->datareader("select ifnull(sum(db),0) as value from (select sum(detail.db - detail.cr) as db
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join coa on coa.acnoid = detail.acnoid 
            where detail.qttrno = ?  and coa.alias in ('WT2','ARWT')
            group by head.crref,head.ourref
            union all
            select sum(detail.db - detail.cr) as db from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join coa on coa.acnoid = detail.acnoid 
            where detail.qttrno = ? and coa.alias in ('WT2','ARWT')
            group by head.crref,head.ourref) as a  ", [$qttrno, $qttrno]);
          } else {
            $data2 = [];
          }
        } else {
          $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db 
          from (select head.crref as docno,head.ourref,sum(detail.cr) as db
          from lahead as head 
          left join ladetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid 
          where detail.qttrno = ?  and coa.alias in ('AR5','PD1')
          group by head.crref,head.ourref
          union all
          select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid 
          where detail.qttrno = ? and coa.alias in ('AR5','PD1')
          group by head.crref,head.ourref) as a  ", [$trno, $trno]);

          $ewt = $this->coreFunctions->datareader("select ifnull(sum(db),0) as value from (select sum(detail.db - detail.cr) as db
          from lahead as head 
          left join ladetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid 
          where detail.qttrno = ?  and coa.alias in ('WT2','ARWT')
          union all
          select ifnull(sum(detail.db - detail.cr),0) as db
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid 
          where detail.qttrno = ? and coa.alias in ('WT2','ARWT')) as a
          ", [$trno, $trno]);
        }


        if (!empty($data2)) {
          if ($data2[0]->db != 0) {
            $data[0]->docno = $data2[0]->docno;
            $data[0]->ourref = $data2[0]->ourref;
            $data[0]->db = number_format($data2[0]->db - floatval($ewt), 2);
            $data[0]->cr = number_format(floatval($ewt), 2);
            $data[0]->bal = number_format($total - $data2[0]->db, 2);
          }
        }

        return $data;
        break;
      case 'SJ':
        if ($doc == "SJ") {
          $sjtrno = $config['params']['clientid'];
          $trno = $this->coreFunctions->datareader("
            select refx as value from lastock where trno = ?
            union all
            select refx as value from glstock where trno = ?
            limit 1", [$sjtrno, $sjtrno]);
        }

        $qry = "" . $select . "
        from " . $head . " as hi
        left join hpxhead as px on px.trno = hi.dtctrno
        where hi.trno = ?
        union all
        " . $select . "
        from " . $hhead . " as hi
        left join hpxhead as px on px.trno = hi.dtctrno
        where hi.trno = ?";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        if (empty($data)) {
          $data = $this->coreFunctions->opentable("
          select 
          '" . $trno . "' as trno, 
          '' as inspo, 
          '0' as ispartial, 
          '' as instructions, 
          '' as period, 
          '' as isvalid, 
          '' as ovaliddate,
          '' as termsdetails, 
          '' as proformainvoice, 
          '' as proformadate,
          '' as docno,
          '' as ourref,'' as isshipmentnotif,'' as shipmentnotif,
          0 as db");
        }

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $tbls = '';
        $qttbl = '';
        if ($isposted) {
          $tbl = 'hqshead';
          $tbls = 'hqsstock';
          $qttbl = 'hqtstock';
        } else {
          $tbl = 'qshead';
          $tbls = 'qsstock';
          $qttbl = 'qtstock';
        }

        $total = $this->coreFunctions->getfieldvalue($tbls, "sum(amt*iss)", "trno=?", [$trno]);
        $tax = $this->coreFunctions->getfieldvalue($tbl, "tax", "trno=?", [$trno]);
        $total = $total + $this->coreFunctions->getfieldvalue($qttbl, "sum(amt*iss)", "trno=?", [$trno]);

        if ($tax != 0) {
          $total = round($total * 1.12, 2);
        }

        $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db from 
        (select head.crref as docno,head.ourref,sum(detail.cr) as db
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('AR5')
        group by head.crref,head.ourref
        union all
        select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('AR5')
        group by head.crref,head.ourref) as a ", [$trno, $trno]);

        if (!empty($data2)) {
          if ($data2[0]->docno != "") {
            $data[0]->docno = $data2[0]->docno;
            $data[0]->ourref = $data2[0]->ourref;
            $data[0]->db = number_format($data2[0]->db, 2);
            $data[0]->bal = number_format($total - $data2[0]->db, 2);
          }
        }

        return $data;
        break;
      default:
        $qry = "" . $select . "
        from " . $hhead . " as hi
        left join hqshead as qs on qs.trno = hi.trno
        left join " . $tbl . " as sq on sq.trno = qs.sotrno
        left join hpxhead as px on px.trno = hi.dtctrno
        where sq.trno = ?
        union all
        " . $select . "
        from " . $hhead . "  as hi
        left join hqshead as qs on qs.trno = hi.trno
        left join " . $htbl . " as sq on sq.trno = qs.sotrno
        left join hpxhead as px on px.trno = hi.dtctrno
        where sq.trno = ?";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        if (empty($data)) {
          $data = $this->coreFunctions->opentable("
          select 
          '" . $trno . "' as trno, 
          '' as inspo, 
          '0' as ispartial, 
          '' as instructions, 
          '' as period, 
          '' as isvalid, 
          '' as ovaliddate,
          '' as termsdetails, 
          '' as proformainvoice, 
          '' as proformadate,
          '' as docno,
          '' as ourref,'' as isshipmentnotif,'' as shipmentnotif,
          0 as db,''as dtcno,0 as dtctrno");
        }

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $tbls = '';
        $qttbl = '';
        if ($isposted) {
          $tbl = 'h' . strtolower($doc) . 'head';
          $tbls = 'h' . strtolower($doc) . 'stock';
          $qttbl = 'hqtstock';
        } else {
          $tbl = strtolower($doc) . 'head';
          $tbls = strtolower($doc) . 'stock';
          $qttbl = 'qtstock';
        }

        $total = $this->coreFunctions->getfieldvalue($tbls, "sum(amt*iss)", "trno=?", [$trno]);
        $total = $total + $this->coreFunctions->getfieldvalue($qttbl, "sum(amt*iss)", "trno=?", [$trno]);
        $qtrno = $this->coreFunctions->getfieldvalue("hqshead", "trno", "sotrno=?", [$trno]);

        $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db from 
        (select head.crref as docno,head.ourref,sum(detail.cr) as db
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('AR5')
        group by head.crref,head.ourref
        union all
        select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('AR5')
        group by head.crref,head.ourref) as a  ", [$qtrno, $qtrno]);

        if (!empty($data2)) {
          if ($data2[0]->docno != "") {
            $data[0]->docno = $data2[0]->docno;
            $data[0]->ourref = $data2[0]->ourref;
            $data[0]->db = number_format($data2[0]->db, 2);
            $data[0]->bal = number_format($total - $data2[0]->db, 2);
          }
        }

        return $data;
        break;
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

    $data = [
      'trno' => $trno,
      'inspo' => $config['params']['dataparams']['inspo'],
      'ispartial' =>  $config['params']['dataparams']['ispartial'],
      'instructions' => $config['params']['dataparams']['instructions'],
      'isshipmentnotif' => $config['params']['dataparams']['isshipmentnotif'],
      'shipmentnotif' => $config['params']['dataparams']['shipmentnotif'],
      'dtctrno' => $config['params']['dataparams']['dtctrno']
    ];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($this->othersClass->isposted2($trno, 'transnum')) {
      return ['status' => false, 'msg' => 'Failed to save; already posted.'];
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $qry = "select trno as value from headinfotrans where trno = ? LIMIT 1";
    $count = $this->coreFunctions->datareader($qry, [$trno]);

    if ($count != '') {
      $this->coreFunctions->sbcupdate("headinfotrans", $data, ['trno' => $trno]);
      if($data['dtctrno'] !=0){
        $poref = $this->coreFunctions->getfieldvalue("qshead","yourref","trno=?",[$trno]);
        $this->coreFunctions->execqry("update hpxhead set potrno =". $trno.",poref ='".$poref."' where trno =?","update", [$data['dtctrno']]);
      }
    } else {
      $this->coreFunctions->insertGetId("headinfotrans", $data);

      $this->logger->sbcwritelog(
        $trno,
        $config,
        'CREATE INSTRUCTION',
        ' REF: ' . $data['inspo']
          . ', INSTRUCTION: ' . $data['instructions']
          . ', PARTIAL: ' . $data['ispartial']
          . ', SHIPMENTNOTIF: ' . $data['isshipmentnotif']
          . ', NOTIFICATION: ' . $data['shipmentnotif']
      );
      if($data['dtctrno'] !=0){
        $poref = $this->coreFunctions->getfieldvalue("qshead","yourref","trno=?",[$trno]);
        $this->coreFunctions->execqry("update hpxhead set potrno =". $trno.",poref ='".$poref."' where trno =?",'update', [$data['dtctrno']]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => []];
  }
}
