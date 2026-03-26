<?php

namespace App\Http\Classes\modules\fixedasset;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class postdep
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DEPRECIATION POSTING';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $tableentryClass;
  private $logger;
  private $table = 'fasched';
  public $head = "lahead";
  public $hhead = "glhead";
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablenum = "cntnum";
  private $othersClass;
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  public $style = 'width:100%;';
  private $fields = ['code', 'codename', 'alias', 'type', 'uom', 'seq', 'qty', 'acnoid', 'istax'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->tableentryClass = new tableentryClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2237,
      'post' => 2238
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    $columns = ['isposted', 'dateid', 'amt', 'docno', 'itemname', 'rem'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $sortcolumns = ['isposted', 'dateid', 'amt', 'docno', 'itemname', 'rem'];

    $tab = [$this->gridname => ['gridcolumns' => $columns, 'sortcolumns' => $sortcolumns]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;

    if ($companyid == 48) { //seastar
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";
    } else {
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
      $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Description";
      $obj[0][$this->gridname]['columns'][$rem]['type'] = "coldel";
    }

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:180px;whiteSpace: normal;min-width:180px;";

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry', 'print'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'POST';

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 48: //seastar
        $qry = "select fa.line,fa.rrtrno,fa.rrline,glhead.docno,fa.amt,'' as itemname,date(fa.dateid) as dateid,'false' as isposted, glhead.rem  ";
        break;
      default:
        $qry = "select fa.line,fa.rrtrno,fa.rrline,glhead.docno,fa.amt,item.itemname,fa.dateid,false as isposted, glhead.rem  ";
        break;
    }
    return $qry;
  }

  public function save($config)
  {
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $return = [];
    foreach ($data as $key => $value) {
      if ($data[$key]['isposted'] == "true") {
        $return = $this->generatejv($config, $data[$key]['line']);
      }
    } // foreach

    $returndata = $this->loaddata($config);
    if (!empty($return) && $return['status'] == false) {
      return ['status' => $return['status'], 'msg' => $return['msg'], 'data' => $returndata];
    } else {
      return ['status' => true, 'msg' => 'Successfully posted.', 'data' => $returndata];
    }
  } // end function 


  private function generatejv($config, $line)
  {
    $companyid = $config['params']['companyid'];

    $leftjoin = '';
    $expense = 'item.expense';
    $revenue = 'item.revenue';
    switch ($companyid) {
      case 48: //seastar
        $expense = 'coa2.acno as expense';
        $revenue = 'coa.acno as revenue';
        $leftjoin = ' left join hcntnuminfo as info on info.trno=glhead.trno left join coa on coa.acnoid=info.depcr left join coa as coa2 on coa2.acnoid=info.depdb';
        break;
    }


    $qry = "select fa.line,fa.rrtrno,fa.rrline,fa.clientid,glhead.docno,fa.amt,item.itemname,fa.dateid,false as isposted,fa.projectid,fa.subproject,fa.stageid,client.client," . $revenue . "," . $expense . ",client.clientname,glhead.rem,glhead.yourref  
    from fasched as fa left join glhead on glhead.trno=fa.rrtrno left join item on item.itemid= fa.itemid left join client on client.clientid = fa.clientid " . $leftjoin . " where fa.line=?";

    $data = $this->coreFunctions->opentable($qry, [$line]);
    if (!empty($data)) {
      $doc = 'GJ';
      $docno = 'GJ';
      $pref = 'GJ';
      $center = $config['params']['center'];
      $user = $config['params']['user'];

      if (empty($center) || $center == '') {
        return ['status' => false, 'msg' => 'Cannot continue, No center selected...'];
      }

      $insertcntnum = 0;
      $docno = $this->othersClass->sanitize($docno, 'STRING');
      $docnolength = $this->companysetup->getdocumentlength($config['params']);
      $table = "cntnum";
      while ($insertcntnum == 0) {
        $seq = $this->othersClass->getlastseq($pref, $config, $table);
        if ($seq == 0 || empty($pref)) {
          if (empty($pref)) {
            $pref = strtoupper($docno);
          }
          $seq = $this->othersClass->getlastseq($pref, $config, $table);
        }

        $poseq = $pref . $seq;
        $newdocno = $this->othersClass->PadJ($poseq, $docnolength);

        // $insertcntnum = $this->insertcntnumaj();
        if (!empty($center) || $center != '') {
          $col = [];
          $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $doc, 'center' => $center];
          $table = "cntnum";
          $insertcntnum =  $this->coreFunctions->insertGetId($table, $col);
          $i = +1;
        } else {
          $insertcntnum = -1;
        } //end if empty center
      }
      $newtrno = $insertcntnum;
      $newdocno = $this->coreFunctions->getfieldvalue($table, 'docno', "trno=?", [$newtrno]);

      $yourref = $data[0]->docno;
      $ourref = '';
      $remark = 'Auto generated document for depreciation.';
      switch ($companyid) {
        case 48: //seastar
          $yourref = $data[0]->yourref;
          $ourref = $data[0]->docno;
          $remark = 'To record depreciation - ' . $data[0]->rem;
          break;
      }

      $qry = "insert into lahead (trno,doc,docno,dateid,client,clientname,yourref,ourref,rem,terms,cur,forex,projectid,subproject)
                                      values('" . $newtrno . "','GJ','" . $newdocno . "', '" . $data[0]->dateid . "','" . $data[0]->client . "','" . $data[0]->clientname . "', '" . $yourref . "','" . $ourref . "', '" . $remark . "', '',
                                      'P', 1, " . $data[0]->projectid . "," . $data[0]->subproject . ")";

      if ($this->coreFunctions->execqry($qry, "insert")) { //lahead

        $this->logger->sbcwritelog($newtrno, $config, 'CREATE', $newdocno . ' FROM Dep.sched -' . $data[0]->docno, 'table_log');

        $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno='\\\\" . $data[0]->expense . "'");
        $qry = "insert into ladetail (trno,line,acnoid,client,db,cr,ref,fatrno,postdate,projectid,subproject,stageid)values
        (" . $newtrno . ",1," . $acnoid . ",'" . $data[0]->client . "'," . $data[0]->amt . ",0,'" . $data[0]->docno . "'," . $data[0]->line . ",'" . $data[0]->dateid . "'," . $data[0]->projectid . "," . $data[0]->subproject . "," . $data[0]->stageid . ")";

        if ($this->coreFunctions->execqry($qry, "insert")) {
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno='\\\\" . $data[0]->revenue . "'");
          $qry = "insert into ladetail (trno,line,acnoid,client,db,cr,ref,fatrno,postdate,projectid,subproject,stageid)values
          (" . $newtrno . ",2," . $acnoid . ",'" . $data[0]->client . "',0," . $data[0]->amt . ",'" . $data[0]->docno . "'," . $data[0]->line . ",'" . $data[0]->dateid . "'," . $data[0]->projectid . "," . $data[0]->subproject . "," . $data[0]->stageid . ")";
          if ($this->coreFunctions->execqry($qry, "insert")) {
            $config['params']['trno'] = $newtrno;
            $ret = $this->othersClass->posttransacctg($config);

            if ($ret['status']) {
              $this->coreFunctions->execqry("update fasched set jvtrno = " . $newtrno . ",posteddate = now(),postedby='" . $user . "' where line = " . $line, "update");
              return ['status' => true, 'msg' => 'Depreciation has been posted.'];
            } else {
              $this->coreFunctions->execqry("delete from cntnum where trno = " . $newtrno, "delete");
              $this->coreFunctions->execqry("delete from lahead where trno = " . $newtrno, "delete");
              $this->coreFunctions->execqry("delete from ladetail where trno = " . $newtrno, "delete");
              return ['status' => false, 'msg' => $ret['msg'] . 'Error on posting depreciation...'];
            }
          } else {
            $this->coreFunctions->execqry("delete from ladetail where trno = " . $newtrno, "delete");
            $this->coreFunctions->execqry("delete from lahead where trno = " . $newtrno, "delete");
            $this->coreFunctions->execqry("delete from cntnum where trno = " . $newtrno, "delete");
            return ['status' => false, 'msg' => 'Error creating accumulated depreciation entry...'];
          }
        } else {
          $this->coreFunctions->execqry("delete from ladetail where trno = " . $newtrno, "delete");
          $this->coreFunctions->execqry("delete from lahead where trno = " . $newtrno, "delete");
          $this->coreFunctions->execqry("delete from cntnum where trno = " . $newtrno, "delete");
          return ['status' => false, 'msg' => 'Error creating depreciation expense entry...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from cntnum where trno = " . $newtrno, "delete");
        return ['status' => false, 'msg' => 'Error creating head...'];
      }
    }
  }

  public function delete($config)
  {
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];

    $select = $this->selectqry($config);
    $select = $select . ", '' as bgcolor ";
    switch ($companyid) {
      case 48: //seastar
        $qry = $select . " from fasched as fa left join glhead on glhead.trno=fa.rrtrno where fa.jvtrno = 0 and fa.dateid<=date_format(now(),'%Y-%m-%d') order by fa.dateid";
        break;
      default:
        $qry = $select . " from fasched as fa left join glhead on glhead.trno=fa.rrtrno left join item on item.itemid= fa.itemid where fa.jvtrno = 0 and fa.dateid<=date_format(now(),'%Y-%m-%d') order by fa.dateid";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
