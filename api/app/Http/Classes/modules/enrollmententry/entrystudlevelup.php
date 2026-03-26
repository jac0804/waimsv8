<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\lookup\enrollmentlookup;

class entrystudlevelup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT LEVEL UP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_sootherfees';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'cline', 'acnoid', 'isamt'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'client', 'clientname', 'docno', 'dateid']]];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:40px;whiteSpace: normal;min-width:160px;"; //action
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:40px;whiteSpace: normal;min-width:130px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:40px;whiteSpace: normal;min-width:160px;"; //action
    $obj[0][$this->gridname]['columns'][1]['type'] = "input";
    $obj[0][$this->gridname]['columns'][1]['label'] = "Code";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:40px;whiteSpace: normal;min-width:260px;"; //action
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['label'] = "Student Name";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addstudents'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'lookupaddstudents':
        return $this->enrollmentlookup->lookupaddstudents($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $tableid = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $data = [];
    $data2 = [];

    foreach ($row  as $key2 => $value) {

      $clientid = $value['clientid'];
      $docno = $value['docno'];
      $client = $value['client'];
      $clientname = $value['clientname'];
      $dateid = $value['dateid'];

      $qry = "update  glhead as head left join client on client.clientid=head.clientid left join glsubject as stock on stock.trno=head.trno
          left join en_glhead as h on h.trno=stock.screfx left join en_glhead as hs on hs.docno=h.curriculumdocno
          left join en_glyear as ys on ys.trno=hs.trno and ys.year=head.yr
          left join en_studentinfo on en_studentinfo.clientid=client.clientid
          set en_studentinfo.levelup=ys.levelup
          where head.doc='ER' and stock.screfx=? and client.clientid=?";

      $this->coreFunctions->execqry($qry, 'update', [$tableid, $clientid]);


      $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
      $datahead = $this->coreFunctions->opentable($qry, [$tableid]);

      foreach ($datahead as $key => $value) {
        if (!empty($datahead[$key]->trno)) {
          $docno = $datahead[$key]->docno;
          $trno = $datahead[$key]->trno;
          $curriculumdocno = $datahead[$key]->curriculumdocno;
          $adviserid = $datahead[$key]->adviserid;
          $courseid = $datahead[$key]->courseid;
          $periodid = $datahead[$key]->periodid;
          $syid = $datahead[$key]->syid;
          $semid = $datahead[$key]->semid;
          $sectionid = $datahead[$key]->sectionid;
          $yr = $datahead[$key]->yr;

          $qry =  "select distinct head.sotrno,head.trno," . $trno . " as sctrno,'" . $curriculumdocno . "' as curriculumdocno,client.clientid,head.docno,head.dateid,client.client,client.clientname,client.clientid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
              where head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and head.semid=? and head.sectionid=? and head.yr=? and client.clientid=?  and subject.screfx=?";

          $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr, $clientid, $tableid]);
          array_push($data2, $data[0]);
        }
      }

      $config['params']['row']['client'] = $client;
      $config['params']['row']['clientname'] = $clientname;
      $config['params']['row']['docno'] = $docno;
      $config['params']['row']['dateid'] = $dateid;
    }

    // $data = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data2];
  } // end function



  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];

    $docno = $config['params']['row']['docno'];
    $client = $config['params']['row']['client'];
    $sctrno = $config['params']['tableid'];

    $sotrno = $this->coreFunctions->datareader("select sotrno as value from glhead where docno=?", [$docno]);
    $trno = $this->coreFunctions->datareader("select trno as value from glhead where docno=?", [$docno]);
    $clientid = $this->coreFunctions->datareader("select clientid as value from client where client=?", [$client]);

    $qry = "update glsubject set screfx=0,sclinex=0,schedstarttime=null,schedendtime=null,roomid=0,bldgid=0,schedday='',instructorid=0 where trno=?";
    $this->coreFunctions->execqry($qry, 'update', [$trno]);

    $qry = "update en_glsubject set refx=0,linex=0,schedstarttime=null,schedendtime=null,roomid=0,bldgid=0,schedday='',instructorid=0 where trno=?";
    $this->coreFunctions->execqry($qry, 'update', [$sotrno]);

    // $qry = "delete from en_scurriculum where clientid=?";
    // $this->coreFunctions->execqry($qry,'delete',[$clientid]);

    $data = $this->coreFunctions->opentable("select trno,line from en_glsubject where trno=?", [$sctrno]);
    foreach ($data as $key => $value) {
      $screfx = $data[$key]->trno;
      $sclinex = $data[$key]->line;
    }

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private  function selectqry()
  {
    return " select stock.trno,stock.line,f.feesdesc as rem,f.feescode,stock.feestype,coa.acno,coa.acnoname,stock.feesid,stock.acnoid,stock.isamt,
    '' as bgcolor,
    '' as errcolor  ";
  }

  public function loaddata($config)
  {

    $tableid = $config['params']['tableid'];

    $qry =  "select distinct head.trno,head.docno,head.dateid,client.client,client.clientname,h.curriculumdocno,ys.levelup
    from glhead as head left join client on client.clientid=head.clientid left join glsubject as stock on stock.trno=head.trno
    left join en_glhead as h on h.trno=stock.screfx left join en_glhead as hs on hs.docno=h.curriculumdocno left join en_glyear as ys on ys.trno=hs.trno and ys.year=head.yr
    left join en_studentinfo as si on si.clientid=client.clientid
    where head.doc='ER' and stock.screfx=? and si.levelup=ys.levelup and si.levelup=ys.levelup ";

    //  and head.assessref='' and head.syid=0 and head.periodid=0 and head.courseid=0 and head.semid=0 and head.sectionid=0 and head.yr=0;
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }
} //end class