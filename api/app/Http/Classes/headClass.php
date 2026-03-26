<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use Psy\Exception\BreakException;

use Mail;
use App\Mail\SendMail;

class headClass
{

  protected $othersClass;
  protected $coreFunctions;
  protected $logger;
  protected $companysetup;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->logger = new Logger;
    $this->companysetup = new companysetup;
  } //end fn


  public function lockunlock($config)
  {
    $msg = "";
    $status = false;
    $data = [];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $action = $config['params']['action'];
    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $table = $config['docmodule']->head;
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $table . ' where trno=?', [$trno]);

    if ($action == 'lock') {
      switch (strtolower($doc)) {
        case 'px':
          $pcfadmin = $this->othersClass->checkAccess($config['params']['user'], 5389);
          if ($pcfadmin == 0) {
            $t = date("H:i");
            if ($t > '16:45') {
              return ['msg' => "Lock failed; submission is until 4:45 pm only.", 'status' => false, 'islocked' => false, 'isposted' => false];
            }
          }

          $totallist = $this->coreFunctions->datareader("select trno as value from pxstock where ext = 0 and trno = ?", [$trno], '', true);
          if ($totallist <> 0) {
            return ['msg' => "Lock failed; zero list price is not allowed.", 'status' => false, 'islocked' => false, 'isposted' => false];
          }

          break;
        case 'sa':
        case 'sb':
        case 'sc':
        case 'sg':
        case 'sh':
        case 'sj':
          $stocktable = $config['docmodule']->stock;
          $checkstockdata = $this->coreFunctions->getfieldvalue($stocktable, "trno", "trno=?", [$trno]);

          if ($doc != 'sh') {
            if (empty($checkstockdata)) {
              return ['msg' => "Lock failed; please add item.", 'status' => false, 'islocked' => false, 'isposted' => false];
            }
          }

          $zeroqty = $this->coreFunctions->opentable("select trno from $stocktable where trno=? and isqty=0", [$trno]);
          if (!empty($zeroqty)) {
            return ['msg' => "Lock failed; please check item(s) with zero quantity.", 'status' => false, 'islocked' => false, 'isposted' => false];
          }
          break;

        case 'sd':
        case 'se':
        case 'sf':
          $checkprice = $this->coreFunctions->opentable("select item.barcode, item.itemname, s.isamt, i.amt2 
            from lastock as s left join stockinfo as i on i.trno=s.trno and i.line=s.line left join item on item.itemid=s.itemid
            where s.trno=? and s.amt<i.amt2 and s.isapprove=0 limit 1", [$trno]);
          if (!empty($checkprice)) {
            return ['msg' => "Lock failed; the amount set for the " . $checkprice[0]->itemname . " is less than the lowest amount.", 'status' => false, 'islocked' => false, 'isposted' => false];
          }

          $zeroqty = $this->coreFunctions->opentable("select trno from lastock where trno=? and isqty=0", [$trno]);
          if (!empty($zeroqty)) {
            return ['msg' => "Lock failed; please check item(s) with zero quantity.", 'status' => false, 'islocked' => false, 'isposted' => false];
          }
          break;

        case 'vr':
          $statusid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
          if ($statusid != 0 && $statusid != 13 && $statusid != 14 && $statusid != 16) {
            $statusname = $this->coreFunctions->getfieldvalue("trxstatus", "status", "line=?", [$statusid]);
            return ['status' => false, 'msg' => 'Cannot lock; the status is ' . $statusname];
          }
          break;

        case 'oq':
          $statusid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
          if ($statusid != 10) {
            return ['status' => false, 'msg' => 'Lock transaction is only applicable in the For Approval status.'];
          }
          break;

        case 'rr':
          if ($config['params']['companyid'] == 16) { //ATI
            $this->coreFunctions->execqry("update lastock as s left join hstockinfotrans as prs on prs.trno=s.reqtrno and prs.line=s.reqline set prs.isrr=1 where s.trno=" . $trno);
          }
          break;

        case 'af': // 

          $qryselect = "select 
          info.lname as 'Last Name',
          info.fname as 'First Name',
          info.mname as 'Middle Name',
          if(head.planid = 0, '', head.planid) as 'Plan Type',
          ifnull(ag.client,'') as 'Agent',
          info.gender as 'Gender',
          info.civilstat as 'Civil Status',
          date_format(info.bday,'%m/%d/%Y') as 'Birthday',
          info.nationality as 'Nationality',
          info.pob as 'Place of Birth',

          info.addressno as 'Residence Address #',
          info.street as 'Residence Street',
          info.city as 'Residence City',
          info.province as 'Residence province',
          info.country as 'Residence Country',
          info.zipcode as 'Residence Zipcode',
          info.brgy as 'Residence Brgy',

          info.paddressno as 'Permanent Address #',
          info.pstreet as 'Permanent Street',
          info.pcity as 'Permanent City',
          info.pprovince as 'Permanent Province',
          info.pcountry as 'Permanent Country',
          info.pzipcode as 'Permanent Zipcode',
          info.pbrgy as 'Permanent Brgy',

          info.ispassport,
          info.isdriverlisc,
          info.isprc,
          info.isseniorid,
          info.isotherid,
          info.idno as 'Number/Type',
          info.expiration as 'Expiration',

          info.isemployment,
          info.isbusiness,
          info.isinvestment,
          info.isothersource,
          info.othersource,

          info.isemployed,
          info.isselfemployed,
          info.isofw,
          info.isretired,
          info.iswife,
          info.isnotemployed,

          info.tin as 'Tin',
          info.sssgsis as 'SSS/GSIS',

          info.lessten,
          info.tenthirty,
          info.thirtyfifty,
          info.fiftyhundred,
          info.hundredtwofifty,
          info.twofiftyfivehundred,
          info.fivehundredup,
        
          info.employer as 'Employer',
          info.otherplan as 'Other Planholder',

          info.isbene,

          head.fname as 'Payor\'s First Name',
          head.mname as 'Payor Middle Name',
          head.lname as 'Payor Last Name',
          head.addressno as 'Payor Address #',
          head.street as 'Payor Street',          
          head.city as 'Payor City',
          head.province as 'Payor Province',
          head.country as 'Payor Country',
          head.zipcode as 'Payor Zipcode',
          head.brgy as 'Payor Brgy',
          head.terms as 'Payment Terms',
          head.yourref as 'Method',
          head.contactno as 'Primary Contact #',
          head.contactno2 as 'Alternative Contact #',
          head.email as 'Email'

          ";

          $qry = $qryselect . " from eahead as head
          left join eainfo as info on head.trno = info.trno
          left join client on head.client = client.client
          left join client as ag on ag.client = head.agent
          left join plantype as pt on pt.line = head.planid
          where head.trno = ? 
          union all " . $qryselect . " from heahead as head
          left join heainfo as info on head.trno = info.trno
          left join client on head.client = client.client
          left join client as ag on ag.client = head.agent
          left join plantype as pt on pt.line = head.planid
          where head.trno = ? ";
          $aftdata = $this->coreFunctions->opentable($qry, [$trno, $trno]);

          foreach ($aftdata as $key => $col) {
            foreach ($col as $key2 => $value) {
              if (strval($value) == "" && $key2 != 'othersource') {
                return ['msg' => "Cannot lock; please fill $key2", 'status' => false, 'islocked' => false, 'isposted' => false];
              }
            }

            if ($col->ispassport == 0 && $col->isdriverlisc == 0 && $col->isprc == 0 && $col->isotherid == 0 && $col->isseniorid == 0) {
              return ['msg' => "Cannot lock; please check at least one government ID.", 'status' => false, 'islocked' => false, 'isposted' => false];
            }

            if ($col->isemployment == 0 && $col->isbusiness == 0 && $col->isinvestment == 0 && $col->isothersource == 0) {
              return ['msg' => "Cannot lock; please check at least one source of income.", 'status' => false, 'islocked' => false, 'isposted' => false];
            } else {
              if ($col->isothersource == 1) {
                if ($col->othersource == '') {
                  return ['msg' => "Cannot lock; please provide another (specify) source of income.", 'status' => false, 'islocked' => false, 'isposted' => false];
                }
              }
            }

            // if ($col->lessten == 0 && $col->tenthirty == 0 && $col->thirtyfifty == 0 && $col->fiftyhundred == 0 && $col->hundredtwofifty == 0 && $col->twofiftyfivehundred == 0 && $col->fivehundredup == 0) {
            //   return ['msg' => "Cannot Lock, Please check atleast 1 Monthly Income", 'status' => false, 'islocked' => false, 'isposted' => false];
            // }

            if ($col->isemployed == 0 && $col->isselfemployed == 0 && $col->isofw == 0 && $col->isretired == 0 && $col->iswife == 0 && $col->isnotemployed == 0) {
              return ['msg' => "Cannot lock; please check at least one occupation.", 'status' => false, 'islocked' => false, 'isposted' => false];
            }

            if ($col->isbene == 0) {
              $benedata = $this->coreFunctions->opentable("select name, age, address, relation  from beneficiary where trno = ?", [$trno]);
              if ($benedata) { // if no bene
                foreach ($benedata as $keyx => $colx) {
                  foreach ($colx as $keyx2 => $valuex) {
                    if (strval($valuex) == "") {
                      return ['msg' => "Cannot lock; please fill out $keyx2 beneficiaries.", 'status' => false, 'islocked' => false, 'isposted' => false];
                    }
                  }
                }
              } else {
                return ['msg' => "Cannot lock; please fill out beneficiaries.", 'status' => false, 'islocked' => false, 'isposted' => false];
              }
            }
          } // end foreach


          $checkattachments = $this->coreFunctions->opentable("select count(*) as acount from transnum_picture where trno=?", [$trno]);
          if ($checkattachments[0]->acount <= 0) {
            return ['msg' => 'Cannot lock; attachments are empty.', 'status' => false, 'islocked' => false, 'isposted' => false];
          }

          $plangrpid = $this->coreFunctions->getfieldvalue($table, "plangrpid", "trno=?", [$trno]);
          $amount = $this->coreFunctions->datareader("select pt.amount as value from plantype as pt left join " . $table . " as head on head.planid = pt.line and head.plangrpid = pt.plangrpid where head.trno = ?", [$trno]);

          if (!$this->othersClass->getplanlimit($plangrpid, floatval($amount), $trno)) {
            $allowoverride = $this->othersClass->checkAccess($config['params']['user'], 1729);
            if (!$allowoverride) {
              return ['status' => false, 'msg' => 'Transactions cannot be locked. Above the plan limit.', 'islocked' => false, 'isposted' => false];
            }
          }
          break;
        case 'cr':
          if ($companyid == 34) { //evergreen
            $checkattachments = $this->coreFunctions->opentable("select count(*) as acount from cntnum_picture where trno=?", [$trno]);
            if ($checkattachments[0]->acount <= 0) {
              return ['msg' => 'Cannot lock; attachments are empty.', 'status' => false, 'islocked' => false, 'isposted' => false];
            }
          }
          break;
        case 'hq':
          // do not remove this comments
          // approval process for personnel requisition
          // once locked, automatic for approval (noted by manager/supervisor)
          // if there's recommended approval, only the recommended approver will be notified once noted by approved
          // else the general manager/supervisor will be notified
          // do not remove this comments

          // if ($this->companysetup->getistodo($config['params'])) {
          //   $this->coreFunctions->sbcupdate("hrisnum", ['statid' => 19], ['trno' => $trno]);
          // }
          $userid = $config['params']['adminid'];
          $personelid = $this->coreFunctions->datareader('select em.clientid as value from ' . $table . ' 
                                                        left join client as em on em.client=' . $table . '.personnel where trno=?', [$trno]);
          if ($userid == $personelid) {
            $notedid = $this->coreFunctions->datareader('select notedid as value from ' . $table . ' where trno=?', [$trno], '', true);
            if ($notedid != 0) {

              $this->logger->sbcwritelog($trno, $config, 'LOCKED', 'For Approval - Noted by Manager/Supervisor');

              if ($companyid == 58) { // cdo
                $url = 'App\Http\Classes\modules\hris\\' . 'hq';
                $this->othersClass->insertUpdatePendingapp($trno, 0, 'HQ', $data, $url, $config, $notedid, false, true);
              }
            }
          }

          $this->coreFunctions->sbcupdate("hrisnum", ['statid' => 19], ['trno' => $trno]);
          break;
        case 'hi':
          if ($companyid == 58) { //cdo
            $url = 'App\Http\Classes\modules\hris\\' . 'hi';
            $notedid = $this->coreFunctions->datareader('select notedid as value from ' . $table . ' where trno=?', [$trno]);
            $this->othersClass->insertUpdatePendingapp($trno, 0, 'HI', $data, $url, $config, $notedid, false, true);
            $this->coreFunctions->sbcupdate("hrisnum", ['statid' => 19], ['trno' => $trno]);
          }
          break;
        case 'hd':
          if ($companyid == 58) { //cdohris notice of disciplinary action -insert sa pendingapp ng person involved

            $expl = $this->coreFunctions->datareader('select explanation as value from ' . $table . ' where trno=?', [$trno]);

            if ($expl == '') {
              $url = 'App\Http\Classes\modules\hris\\' . 'hd';
              $empid = $this->coreFunctions->datareader('select empid as value from ' . $table . ' where trno=?', [$trno]);
              $this->othersClass->insertUpdatePendingapp($trno, 0, 'HD', $data, $url, $config, $empid, false, true);
            }
          }
          break;

        case 'hc':
          if ($companyid == 58) { //cdo
            $this->coreFunctions->execqry("delete from pendingapp where doc='HC' and trno=" . $trno);
            $headid = $this->coreFunctions->datareader('select empheadid as value from ' . $table . ' where trno=?', [$trno], '', true);
            if ($headid != 0) {
              $url = 'App\Http\Classes\modules\hris\\' . 'hc';
              $pendingapp = $this->othersClass->insertUpdatePendingapp($trno, 0, 'HC', $data, $url, $config, $headid, false, true, "HEAD");
              if (!$pendingapp['status']) {
                return ['msg' => 'Failed to lock the clearance.', 'status' => false, 'islocked' => false, 'isposted' => false];
              }
            }
          }
          break;

        case 'pv':
          if ($companyid == 29) { // sbc
            $url = 'App\Http\Classes\modules\payable\\' . 'pv';
            $resultpendingapp = $this->othersClass->insertUpdatePendingapp($trno, 0, $doc, $data, $url, $config, 0, false, true);
            if ($resultpendingapp['status']) {
              $this->logger->sbcwritelog($trno, $config, 'HEAD', 'For Posting ' . $docno, '', 1);
            } else {
            }
          }
          break;
      }

      $date = $this->othersClass->getCurrentTimeStamp(); //date("Y-m-d H:i:s");
      $this->locking($table, $user, $trno, $date, $doc, $config);
      $this->logger->sbcwritelog($trno, $config, 'LOCKED', $docno . (isset($config['params']['locktype']) ? " (" . $config['params']['locktype'] . ")" : ''));
      switch (strtolower($doc)) {
        case 'af':
          $msg = "Document was successfully reviewed.";
          break;
        case 'lp':
          $msg = "Document was successfully approved.";
          break;
        case 'po':
          $autosendemail = $this->companysetup->getautosendemail($config['params']);
          if ($autosendemail) {
            $config['params']['dataid'] = $trno;
            $config['params']['dataparams'] = ['checked' => 'waw', 'approved' => 'wew', 'received' => 'wow', 'print' => 'PDFM', 'prepared' => 'wiw'];
            $pdf = $config['docmodule']->reportdata($config);
            $emailbody = "<html>
              <body>
                <a href='" . env('APP_URL') . "/apitrans?action=" . md5('post') . "&trno=" . md5($trno) . "&doc=" . $doc . "&moduletype=" . $config['params']['moduletype'] . "&user=" . md5($user) . "&companyid=" . $companyid . "'>Click here to post transaction.</a>
              </body>
            </html>";
            $emailinfo = [
              'email' => 'zerojad08@gmail.com',
              'view' => 'emails.welcome',
              'msg' => $emailbody,
              'title' => 'Purchase Order',
              'filename' => 'po',
              'pdf' => $pdf['report'],
              'newformat' => true,
              'subject' => 'Purchase Order Transaction #: ' . $docno . ' Ready for Posting',
              'name' => 'Name 1'
            ];
            Mail::to('zerojad08@gmail.com')->send(new SendMail($emailinfo));
          }
          break;
        default:
          $msg = "Document was successfully locked.";
          break;
      }
    } else { //unlock
      switch (strtolower($doc)) {
        case 'sd':
        case 'se':
        case 'sf':
          return ['msg' => "Unlock failed; please contact your Inventory Controller about this process.", 'status' => false, 'islocked' => false, 'isposted' => false];
          break;
        case 'vr':
          return ['msg' => "Unlock failed; only the approver can unlock or disapprove this transaction.", 'status' => false, 'islocked' => true, 'isposted' => false];
          break;
        case 'rr':
          if ($config['params']['companyid'] == 16) { //ATI
            $this->coreFunctions->execqry("update lastock as s left join hstockinfotrans as prs on prs.trno=s.reqtrno and prs.line=s.reqline set prs.isrr=0 where s.trno=" . $trno);
          }
          break;
        case 'po':
          break;
        case 'hq':
          $status1 = $this->coreFunctions->getfieldvalue('personreq', 'status1', 'trno=?', [$trno]);
          if ($status1 != '') {
            $status = false;
            $msg = "Cannot unlocked. The Manager/Supervisor already approved this transaction";
            return ['msg' => $msg, 'status' => $status, 'islocked' => true, 'isposted' => false];
          } else {
            $statup = $this->coreFunctions->sbcupdate("hrisnum", ['statid' => 0], ['trno' => $trno]);
            if ($statup) {
              $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno, 'delete');
            }
          }

          break;
        case 'hi':
          $status1 = $this->coreFunctions->getfieldvalue('incidenthead', 'status1', 'trno=?', [$trno]);
          if ($status1 != '') {
            $status = false;
            $msg = "Cannot unlocked. The HR Manager has already clicked the For Approval button.";
            return ['msg' => $msg, 'status' => $status, 'islocked' => true, 'isposted' => false];
          } else {
            $statup = $this->coreFunctions->sbcupdate("hrisnum", ['statid' => 0], ['trno' => $trno]);
            if ($statup) {
              $this->coreFunctions->execqry("delete from pendingapp where doc='HI' and trno=" . $trno, 'delete');
            }
          }
          break;
        case 'hd':
          if ($companyid == 58) { //cdohris notice of disciplinary action -delete sa pendingapp ng person involved
            $this->coreFunctions->execqry("delete from pendingapp where doc='HD' and trno=" . $trno, 'delete');
          }
          break;
        case 'pv':
          if ($companyid == 29) { // sbc
            $this->coreFunctions->execqry("delete from pendingapp where doc='PV' and trno=" . $trno, 'delete');
          }
          break;
      }

      $this->locking($table, $user, $trno, null, $doc, $config);
      $this->logger->sbcwritelog($trno, $config, 'UNLOCKED', $docno);
      $msg = "Document successfully unlocked.";
    }
    $status = true;

    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    switch (strtolower($doc)) {
      case 'vr':
        return ['msg' => $msg, 'status' => $status, 'islocked' => $islocked, 'isposted' => $isposted, 'backlisting' => true];
        break;
      default:
        return ['msg' => $msg, 'status' => $status, 'islocked' => $islocked, 'isposted' => $isposted];
        break;
    }
  }


  public function locking($table, $user, $trno, $date, $doc, $config)
  {
    if ($doc != 'VR') {
      $this->coreFunctions->sbcupdate($table, ['lockdate' => $date, 'lockuser' => $date == null ? '' : $user], ['trno' => $trno]);
    }

    if ($config['params']['companyid'] == 16 && $config['params']['doc'] != 'VR') { //ati
      return;
    }

    switch ($doc) {
      case 'VR':
        $status = $date == null ? 0 : 10;
        $this->coreFunctions->sbcupdate("transnum", ['statid' => $status], ['trno' => $trno]);
        $statusname = $this->coreFunctions->getfieldvalue("trxstatus", "status", "line=?", [10]);
        $this->logger->sbcwritelog2($trno, $user, "LOCKED", $statusname, "transnum_log");
        break;
      case 'WB':
        $status = $date == null ? '' : 'FORKLIFT';
        $this->coreFunctions->sbcupdate("cntnum", ['status' => $status], ['trno' => $trno]);
        break;

      case 'SD':
      case 'SE':
      case 'SF':
      case 'SH':
        $status = $date == null ? '' : 'FOR PICKING';
        $ctrldate = null;
        $ctrlby = '';
        if ($status != '') {
          $ctrldate = $this->othersClass->getCurrentTimeStamp();
          $ctrlby = $user;
        }
        $data = [];
        $data['status'] = $status;
        $data['crtldate'] = $ctrldate;
        $data['crtlby'] = $ctrlby;
        $this->coreFunctions->sbcupdate("cntnum", $data, ['trno' => $trno]);
        break;
      default:
        $lockstatid = 19;
        if ($config['params']['companyid'] == 24) { //goodfound
          if ($config['params']['doc'] == 'SJ') {
            $packdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "packdate", "trno=?", [$trno]);
            if ($packdate != '') $lockstatid = 41;
          }
        }

        $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, ['statid' => $date == null ? 0 : $lockstatid], ['trno' => $trno]);
        break;
    }
  } //end fn































































  //v**********************************************************8
  // function above already used *****************************


}
