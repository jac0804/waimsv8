<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;

class viewacctginfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;
  private $sqlquery;
  private $logger;

  public $modulename = 'OTHER DETAILS';
  public $gridname = 'inventory';
  private $fields = ['acno', 'acnoname', 'isewt', 'isvat', 'isvewt', 'ewtcode', 'ewtrate', 'projectid', 'branch', 'deptid'];
  private $table = 'detailinfo';

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
    $this->sqlquery = new sqlquery;
  }

  public function createHeadField($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];

    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];

      if ($companyid == 16 && $doc == 'CV') { //ati
        $released = $this->coreFunctions->getfieldvalue('cntnuminfo', "releasedate", "trno=?", [$trno]);
      }
      $this->modulename = 'OTHER DETAILS - ' . $config['params']['row']['acnoname'];
    } else {
      $trno = $config['params']['dataparams']['trno'];
      $this->modulename = 'OTHER DETAILS - ' . $config['params']['dataparams']['acnoname'];
    }

    $fields = ['lblrem', 'rem'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    if ($companyid == 16 || $doc == 'CV') { //ati
      array_push($fields, 'ref', 'si2', 'void');
    }

    if ($doc == 'CV' || $doc == 'PV') {
      array_push($fields, 'isewt', 'isvewt', 'isvat', 'ewtcode', 'ewtrate');
    }

    if ($companyid == 24) { //goodfound
      if ($doc == 'PV') {
        $fields = ['lblrem', 'rem', 'isewt', 'isvewt', 'isexcess', 'isvat', 'ewtcode', 'ewtrate'];
      }

      if ($doc == 'GJ') {
        $fields = ['lblrem', 'rem', 'isewt', 'isexcess', 'isvat', 'ewtcode', 'ewtrate'];
      }
    }


    if ($companyid == 10) { //afti
      if ($doc == 'CR') {
        array_push($fields, 'isewt', 'ewtcode', 'ewtrate');
      }

      if ($doc != 'PV') {
        array_push($fields, 'projectname', 'branchname', 'deptname');
      } else {
        array_push($fields, 'branchname', 'deptname');
      }
    }
    if ($companyid == 16 && $doc == 'CV') { //ati
      array_push($fields, 'checkno', 'justify', 'omdocno');
    }
    if (!$isposted) {
      array_push($fields, 'refresh');
    }

    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 16 && $doc == 'CV') { //ati
      if ($released == NULL) {
        data_set($col1, 'checkno.readonly', false);
      } else {
        data_set($col1, 'checkno.readonly', true);
        data_set($col1, 'justify.type', 'input');
        data_set($col1, 'soref.readonly', true);
      }
    }
    if ($companyid == 16 || $doc == 'CV') { //ati
      data_set($col1, 'ref.label', 'Payment Reference');
      data_set($col1, 'ref.maxlength', 45);
    }

    if ($doc == 'CV' || $doc == 'PV' || $doc == 'CR') {
      data_set($col1, 'isvat.readonly', false);
      data_set($col1, 'isvewt.readonly', false);
      data_set($col1, 'ewtcode.style', 'width:250px;whiteSpace: normal;min-width:250px;');
      data_set($col1, 'ewtrate.style', 'width:250px;whiteSpace: normal;min-width:250px;');
    }

    if ($companyid == 24) { //goodfound
      if ($doc == 'PV') {
        data_set($col1, 'isvat.readonly', false);
        data_set($col1, 'isvewt.readonly', false);
        data_set($col1, 'isexcess.readonly', false);
        data_set($col1, 'ewtcode.style', 'width:250px;whiteSpace: normal;min-width:250px;');
        data_set($col1, 'ewtrate.style', 'width:250px;whiteSpace: normal;min-width:250px;');
      }

      if ($doc == 'GJ') {
        data_set($col1, 'isvat.readonly', false);
        data_set($col1, 'isvewt.readonly', false);
        data_set($col1, 'isexcess.readonly', false);
        data_set($col1, 'ewtcode.style', 'width:250px;whiteSpace: normal;min-width:250px;');
        data_set($col1, 'ewtrate.style', 'width:250px;whiteSpace: normal;min-width:250px;');
      }
    }

    if ($companyid == 10) { //afti
      data_set($col1, 'projectname.label', 'Item Group');
      data_set($col1, 'projectname.type', 'lookup');
      data_set($col1, 'projectname.lookupclass', 'dtproject');
      data_set($col1, 'projectname.action', 'lookupproject');
      data_set($col1, 'projectname.style', 'width:250px;whiteSpace: normal;min-width:250px;');
      data_set($col1, 'deptname.type', 'lookup');
      data_set($col1, 'deptname.lookupclass', 'ddeptname');
      data_set($col1, 'deptname.action', 'lookupclient');
      data_set($col1, 'deptname.style', 'width:250px;whiteSpace: normal;min-width:250px;');
    }

    data_set($col1, 'refresh.label', 'update');
    data_set($col1, 'rem.type', 'wysiwyg');
    data_set($col1, 'rem.class', 'csrem');
    data_set($col1, 'rem.readonly', false);


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

    return $this->getheaddata($config, $trno, $line, $config['params']['doc']);
  }

  public function getheaddata($config, $trno, $line, $doc)
  {
    $companyid = $config['params']['companyid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    $tbl = '';
    if ($isposted) {
      $tablename = 'hdetailinfo';
      $tbl = 'gldetail';
    } else {
      $tablename = 'detailinfo';
      $tbl = 'ladetail';
    }
    $fields = "";
    $joins = "";
    $groups = "";
    if ($companyid == 16 && $doc == 'CV') { //ati
      $fields = ", ifnull((select group_concat(omdocno,'') from (
            select concat(ifnull(group_concat(distinct omh.docno),''),' SO#:',ifnull(group_concat(distinct so.sono),'')) as omdocno, cv.trno, cv.line
            from cvitems as cv 
            left join omstock as om on om.reqtrno=cv.reqtrno and om.reqline=cv.reqline
            left join omhead as omh on omh.trno=om.trno
            left join omso as so on so.trno=om.trno and so.line=om.line
            where ifnull(om.trno,0) is not null group by cv.trno, cv.line
            union all
            select concat(ifnull(group_concat(distinct omh.docno),''),' SO#:',ifnull(group_concat(distinct so.sono),'')) as omdocno, cv.trno, cv.line
            from cvitems as cv 
            left join homstock as om on om.reqtrno=cv.reqtrno and om.reqline=cv.reqline
            left join homhead as omh on omh.trno=om.trno
            left join homso as so on so.trno=om.trno and so.line=om.line
            where ifnull(om.trno,0) is not null and omh.docno is not null group by cv.trno, cv.line
            union all
            select concat(ifnull(group_concat(distinct omh.docno),''),' SO#:',ifnull(group_concat(distinct so.sono),'')) as omdocno, cv.trno, cv.line
            from hcvitems as cv 
            left join homstock as om on om.reqtrno=cv.reqtrno and om.reqline=cv.reqline
            left join homhead as omh on omh.trno=om.trno
            left join homso as so on so.trno=om.trno and so.line=om.line
            where ifnull(om.trno,0) is not null group by cv.trno, cv.line            
            union all
			      select concat(ifnull(group_concat(distinct omh.docno),''),' SO#:',ifnull(group_concat(distinct so.sono),'')) as omdocno, cv.trno, cv.line
            from ladetail as cv left join lahead as h on h.trno=cv.trno
            left join glstock as s on s.trno=cv.refx
			      left join omstock as om on om.reqtrno=s.reqtrno and om.reqline=s.reqline
            left join omhead as omh on omh.trno=om.trno
            left join omso as so on so.trno=om.trno and so.line=om.line
            where h.doc='CV' and om.trno is not null and so.sono<>'' group by cv.trno, cv.line, h.trno
            union all
			      select concat(ifnull(group_concat(distinct omh.docno),''),' SO#:',ifnull(group_concat(distinct so.sono),'')) as omdocno, cv.trno, cv.line
            from gldetail as cv left join glhead as h on h.trno=cv.trno 
            left join glstock as s on s.trno=cv.refx
			      left join homstock as om on om.reqtrno=s.reqtrno and om.reqline=s.reqline
            left join homhead as omh on omh.trno=om.trno
            left join homso as so on so.trno=om.trno and so.line=om.line
            where h.doc='CV' and om.trno is not null and so.sono<>'' group by cv.trno, cv.line, h.trno
            ) as osi where osi.trno=d.trno and osi.omdocno is not null),'') as omdocno";
    }


    $qry = "select ifnull(i.trno,0) as trno, ifnull(i.line,0) as line,d.trno as dtrno,d.line as dline, i.rem, 0 as isnew,cast(d.isewt as char) as isewt,cast(d.isvewt as char) as isvewt,cast(d.isvat as char) as isvat,
            d.ewtcode,d.ewtrate,ifnull(b.clientname,'') as branchname,ifnull(dpt.clientname,'') as deptname,d.deptid,d.branch,d.projectid ,ifnull(p.name,'') as projectname,cast(d.isexcess as char) as isexcess, i.ref, i.si2, 
            cast(d.void as char) as void, d.refx, d.linex, coa.acno,i.checkno,i.justify $fields
            from " . $tbl . " as d  
            left join " . $tablename . " as i on d.trno = i.trno and d.line = i.line 
            left join client as b on b.clientid = d.branch 
            left join client as dpt on dpt.clientid = d.deptid
            left join projectmasterfile as p on p.line = d.projectid
            left join coa on coa.acnoid=d.acnoid
            $joins
            where d.trno=? and d.line=?
            
            $groups";

    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

    if (!empty($data)) {

      if ($companyid == 10 || $companyid == 12 || $companyid == 24) { //afti,afti usd,goodfound
        $refx = $this->coreFunctions->datareader("select refx as value from ladetail where trno = ? and line = ?", [$trno, $line]);
        $ewt = $this->coreFunctions->datareader("select ewtrate as value from glhead where trno = ?", [$refx]);
        $vat = $this->coreFunctions->datareader("select tax as value from glhead where trno = ?", [$refx]);
      }
      return $data;
    } else {
      $data = [];
      $row['rem'] = '';
      $row['trno'] = $trno;
      $row['line'] = $line;
      $row['isnew'] = 1;
      $row['isewt'] = '0';
      $row['isvewt'] = '0';
      $row['isvat'] = '0';
      $row['isexcess'] = '0';
      $row['void'] = '0';
      $row['ewtcode'] = '';
      $row['ewtrate'] = 0;
      $row['ref'] = '';

      switch ($companyid) {
        case 10: //afti
          $row['deptid'] = '0';
          $row['deptname'] = '';
          $row['projectid'] = '0';
          $row['projectname'] = '';
          $row['branch'] = '0';
          $row['branchname'] = '';
          break;

        case 16: //ati
          $row['checkno'] = '';
          $row['justify'] = '';
          break;
      }

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
    $companyid = $config['params']['companyid'];
    $isnew = $config['params']['dataparams']['isnew'];
    $trno = $config['params']['dataparams']['trno'];
    $line = $config['params']['dataparams']['line'];
    $rem = $this->othersClass->sanitizekeyfield('rem', $config['params']['dataparams']['rem']);

    $acno = isset($config['params']['dataparams']['acno']) ? $config['params']['dataparams']['acno'] : '';
    $refx = isset($config['params']['dataparams']['refx']) ? $config['params']['dataparams']['refx'] : 0;
    $linex = isset($config['params']['dataparams']['linex']) ? $config['params']['dataparams']['linex'] : 0;

    $isewt = $config['params']['dataparams']['isewt'];
    $isvat = $config['params']['dataparams']['isvat'];
    $ewtcode = $config['params']['dataparams']['ewtcode'];
    $ewtrate = $config['params']['dataparams']['ewtrate'];
    $isvewt = $config['params']['dataparams']['isvewt'];
    $isexcess = $config['params']['dataparams']['isexcess'];
    $void = isset($config['params']['dataparams']['void']) ? $config['params']['dataparams']['void'] : 0;
    $ref = isset($config['params']['dataparams']['ref']) ? $config['params']['dataparams']['ref'] : '';
    $si2 = isset($config['params']['dataparams']['si2']) ? $config['params']['dataparams']['si2'] : '';

    switch ($companyid) {
      case 10: //afti
        $projectid = $config['params']['dataparams']['projectid'];
        $branch = $config['params']['dataparams']['branch'];
        $deptid = $config['params']['dataparams']['deptid'];
        break;

      case 16: //ati
        $checkno = $config['params']['dataparams']['checkno'];
        $justify = $config['params']['dataparams']['justify'];
        break;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'rem' => $rem,
      'ref' => $ref,
      'si2' => $si2

    ];

    $data2 = [
      'isvewt' => $isvewt,
      'isewt' => $isewt,
      'isvat' => $isvat,
      'ewtcode' => $ewtcode,
      'ewtrate' => $ewtrate,
      'isexcess' => $isexcess,
      'void' => $void
    ];

    switch ($companyid) {
      case 10: //afti
        $data2['branch'] = $branch;
        $data2['projectid'] = $projectid;
        $data2['deptid'] = $deptid;
        break;

      case 16: //ati
        $data['checkno'] = $checkno;
        $data['justify'] = $justify;
        break;
    }

    $tablename = 'detailinfo';
    $tbl = 'ladetail';

    if ($isvewt !== '0' && ($isewt !== '0' || $isvat !== '0')) {
      $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
      return ['status' => false, 'msg' => $msg, 'data' => []];
    }

    foreach ($data as $key => $v) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($rem !== '' && $trno == 0 && $line == 0) {
      $data['trno'] = $config['params']['dataparams']['dtrno'];
      $data['line'] = $config['params']['dataparams']['dline'];

      $this->coreFunctions->sbcinsert($tablename, $data);
      $this->logger->sbcwritelog(
        $data['trno'],
        $config,
        'DETAILINFO',
        'ADD - Line:' . $data['line']
          . ' Notes:' . $rem
      );
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
    }

    if ($trno == 0 && $line == 0) {
      $trno = $config['params']['dataparams']['dtrno'];
      $line = $config['params']['dataparams']['dline'];
    }

    $this->coreFunctions->sbcupdate($tbl, $data2, ['trno' => $trno, 'line' => $line]);

    if ($void) {

      $qry = "insert into voiddetail (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,
                          encodeddate,encodedby,editdate,editby,ref,checkno,rem,clearday,pdcline,
                          projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,
                          void,branch,deptid)
            select d.postdate,d.trno,d.line,d.acnoid,
            ifNull(client.client,''),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
            d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
            d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid
            from lahead as h
            left join " . $tbl . " as d on d.trno=h.trno
            left join client on client.client=d.client
            where  d.trno=? and d.line =?
      ";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno, $line]);
      if ($result) {

        $docno = $this->coreFunctions->getfieldvalue("lahead", "docno", "trno=?", [$trno]);
        $cvref = $this->coreFunctions->execqry("update cvitems as cv 
                      left join hpostock as s on s.trno=cv.refx and s.line=cv.linex 
                      left join hstockinfotrans as xinfo on xinfo.trno=s.reqtrno and xinfo.line=s.reqline
                      set xinfo.cvref='" . $docno . " - Voided' where cv.trno=?", 'update', [$trno]);


        if ($cvref) {
          $this->coreFunctions->execqry("delete from " . $tbl . " where trno =? and line =?", 'delete', [$trno, $line]);
          $this->coreFunctions->execqry("update cvitems as cv left join hpostock as s on s.trno=cv.refx and s.line=cv.linex set s.cvtrno=0 where cv.trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from cvitems where trno =? and line =?", 'delete', [$trno, $line]);

          if ($refx != 0) {
            if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
              $this->coreFunctions->sbcupdate($tbl, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
              $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
              $return = false;
            }
          }

          $this->logger->sbcwritelog($trno, $config, 'VOID', 'AccountID: ' . $acno);

          $qry = "select d.line
                from ladetail as d
                left join (select line from voiddetail where trno = $trno) as vd on vd.line=d.line
                where trno = $trno";

          $this->coreFunctions->LogConsole($qry);
          $chkline = $this->coreFunctions->opentable($qry);

          if (empty($chkline)) {
            $this->coreFunctions->execqry("update cntnum set statid = 39 where trno = ?", "update", [$trno]);
          }
        }
      }
    }


    $doc = $config['params']['doc'];
    $modtype = $config['params']['moduletype'];
    $path = 'App\Http\Classes\modules\\' . strtolower($modtype) . '\\' . strtolower($doc);
    $config['params']['trno'] = $trno;
    $detail = app($path)->opendetail($trno, $config);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'reloadgriddata' => ['accounting' => $detail]];
  }
}
