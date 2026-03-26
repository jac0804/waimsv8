<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class reconstruct
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  private $logger;
  public $modulename = 'Reconstruction';
  public $gridname = 'customformacctg';
  private $fields = ['terms', 'interestrate', 'fma2', 'fma1', 'bal', 'recondate'];

  public $tablenum = 'cntnum';
  private $table = 'cntnuminfo';
  private $htable = 'hcntnuminfo';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';


  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

  public $style = 'width:50%;max-width:50%;';
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
    $attrib = array('load' => 4606, 'edit' => 4607);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $bal = $this->coreFunctions->datareader("select sum(bal) as value from (select ar.bal from arledger as ar left join cntnum as num on num.trno = ar.trno where num.trno = " . $trno . " 
                                            union all 
                                            select ar.bal from arledger as ar left join cntnum as num on num.trno = ar.trno where num.recontrno = " . $trno . ") as a");
    $fields = ['recondate', 'terms', 'bal', 'fma1'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'recondate.type', 'date');
    data_set($col1, 'recondate.label', 'Date');
    data_set($col1, 'recondate.readonly', false);
    data_set($col1, 'fma1.readonly', false);
    data_set($col1, 'bal.readonly', false);
    data_set($col1, 'fma1.label', 'Monthly Amortization');
    data_set($col1, 'terms.lookupclass', 'customterms');

    if ($bal == 0) {
      data_set($col1, 'bal.readonly', true);
    }


    $fields = ['interestrate', 'fma2'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'fma2.readonly', false);
    data_set($col2, 'fma2.label', 'Factor');
    data_set($col2, 'interestrate.label', 'Interest Rate(%)');


    $fields = ['reload', 'refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.label', 'Generate JV');
    data_set($col3, 'reload.label', 'Compute MA');
    data_set($col3, 'refresh.action', 'refresh');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function cntnuminfo_qry($trno)
  {
    $qry = "select  $trno as sjtrno,trno,0 as interestrate,0 as downpayment,0 as fmiscfee,0 as fma2,
    0 as penalty,sum(rebate) as rebate,0 as fma1,format(sum(arbal),2) as bal,sum(arbal) as arbal,'' as terms,recondate from(
    select ar.trno, 0 as interestrate,0 as downpayment,0 as fmiscfee,0 as fma2,
    0 as penalty,i.rebate,0 as fma1,format(sum(ar.bal),2) as bal,sum(ar.bal) as arbal,'' as terms, now() as recondate
    from arledger as ar left join coa on coa.acnoid = ar.acnoid
    left join cntnum on cntnum.trno = ar.trno
    left join hcntnuminfo as i on i.trno = ar.trno
    where ar.trno=? and coa.alias ='AR1' 
    group by ar.trno,i.rebate
    union all
    select ar.trno, 0 as interestrate,0 as downpayment,0 as fmiscfee,0 as fma2,
    0 as penalty,i.rebate as rebate,0 as fma1,format(sum(ar.bal),2) as bal,sum(ar.bal) as arbal,'' as terms, now() as recondate
    from arledger as ar left join coa on coa.acnoid = ar.acnoid
    left join cntnum on cntnum.trno = ar.trno
    left join hcntnuminfo as i on i.trno = ar.trno
    where cntnum.recontrno=? and coa.alias ='AR1' group by ar.trno,recondate,i.rebate) as a where arbal<>0 group by trno,recondate";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    // $this->coreFunctions->logconsole($qry);
    // $this->coreFunctions->LogConsole("ano baaa");
    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['dataparams']['trno'];

    $data = $this->cntnuminfo_qry($trno);
    if (empty($data)) {
      return $this->coreFunctions->opentable("select  $trno as sjtrno,0 as trno,0 as interestrate,0 as downpayment,0 as fmiscfee,0 as fma2,
      0 as penalty,0 as rebate,0 as fma1,0 as bal,0 as arbal,'' as terms,now() as recondate");
    }

    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $this->modulename = 'Financing Calculator';

    $interest = 0;
    $principal = 1;
    $payment = 2;
    $tab = [$this->gridname => ['gridcolumns' => ['interest', 'principal', 'payment']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$interest]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$principal]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$payment]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$interest]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$principal]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$payment]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['totalfield'] = [];
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
    $action = $config['params']['action2'];
    $trno  = $config['params']['dataparams']['trno'];

    switch ($action) {
      case 'refresh';
        return $this->save($config);
        break;

      default:
        return $this->compute($config);
        break;
    }
  }

  private function save($config)
  {
    $trno  = $config['params']['dataparams']['trno'];
    $sjtrno  = $config['params']['dataparams']['sjtrno'];
    $head = $config['params']['dataparams'];


    if ($head['fma1'] == 0) {
      return ['status' => false, 'msg' => 'Please compute MA first.'];
    }

    if ($head['bal'] == 0) {
      return ['status' => false, 'msg' => 'Already fully paid.'];
    }

    $crunposted = $this->coreFunctions->getfieldvalue("ladetail", "trno", "refx=?", [$trno]);
    $this->coreFunctions->LogConsole($crunposted);
    if (floatval($crunposted) != 0) {
      return ['status' => false, 'msg' => 'Please check, all payments should be posted to continue.'];
    }

    foreach ($this->fields as $key2) {
      $info[$key2] = $head[$key2];
      $info[$key2] = $this->othersClass->sanitizekeyfield($key2, $info[$key2]);
    }

    $d = [];
    $det = [];
    $di = [];
    $dinfo = [];
    $i = 1;
    $prevbal = 0;
    $principal = 0;
    $financeamt = 0;
    $ma = 0;
    $status = true;
    $msg = '';
    $total = 0;
    $totalint = 0;
    $arbal = $head['arbal'];

    $clientid = $this->coreFunctions->getfieldvalue("glhead", "clientid", "trno=?", [$trno]);
    $client = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$clientid]);
    $clientname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$clientid]);

    $intacnoid = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'SA3'"); //unearnedinterest
    $arintacnoid = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'AR2'"); //ar interest
    $maacnoid = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'AR1'"); //artrade
    $sjdocno =  $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$sjtrno]);
    $rebate = $this->coreFunctions->getfieldvalue("hcntnuminfo", 'rebate', "trno=?", [$sjtrno]);
    $path = 'App\Http\Classes\modules\accounting\gj';
    $gjtrno = $this->othersClass->generatecntnum($config, app($path)->tablenum, 'GJ', 'GJ', 0, 0, 'GJ');

    if ($gjtrno != -1) {
      $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$gjtrno]);

      $headdata = ['trno' => $gjtrno, 'doc' => 'GJ', 'docno' => $docno, 'dateid' => $info['recondate'], 'terms' => $info['terms'], 'client' => $client, 'clientname' => $clientname, 'yourref' => $sjdocno, 'rem' => 'To reconstruct ' . $sjdocno];
      $infodata = ['trno' => $gjtrno, 'fma1' => $info['fma1'], 'fma2' => $info['fma2'], 'interestrate' => $info['interestrate'], 'bal' => $info['bal'], 'recondate' => $info['recondate']];

      $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $headdata);
      if ($inserthead) {
        $insertinfo = $this->coreFunctions->sbcinsert("cntnuminfo", $infodata);

        if ($insertinfo) {
          $this->coreFunctions->execqry("delete from ladetail where trno = ?", 'delete', [$gjtrno]);

          $financeamt = $info['bal'];
          $runningfa = $financeamt;
          $ma =  $info['fma1']; //round($info['fma2']*$financeamt,0,PHP_ROUND_HALF_UP);
          $prevbal = $financeamt;
          //monthly ma
          $balmons = $this->coreFunctions->getfieldvalue('terms', 'days', 'terms = ?', [$info['terms']]);
          $maid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
          $dateid = $this->coreFunctions->getfieldvalue('arledger', 'dateid', 'trno = ? and acnoid = ? and bal<>0', [$trno, $maid], 'dateid');

          $rdate = strtotime($dateid);
          $pdate =  date("Y-m-d", $rdate);

          for ($y = 1; $y <= $balmons; $y++) {
            $interest = round($prevbal * ($info['interestrate'] / 100), 2);
            $principal = round($ma - $interest, 2);

            if ($runningfa < $principal) {
              $principal = $runningfa;
            }

            $locale = 'en_US';
            $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);

            //principal          
            $config['params']['data']['trno'] = $gjtrno;
            $config['params']['data']['acno'] = $maacnoid;
            $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$maacnoid'");
            $config['params']['data']['db'] = $principal;
            $config['params']['data']['cr'] = 0;
            $config['params']['data']['fcr'] = 0;
            $config['params']['data']['fdb'] = 0;
            $config['params']['data']['postdate'] = $pdate;
            $config['params']['data']['client'] = $client;
            $config['params']['data']['refx'] = 0;
            $config['params']['data']['linex'] = 0;
            $config['params']['data']['ref'] = $sjdocno . '*';
            $config['params']['data']['rem'] = $nf->format($y) . ' MA';
            $config['params']['trno'] = $gjtrno;
            $return = app($path)->additem('insert', $config);
            $total = $total + $principal;

            if ($return['status']) {
              $config['params']['data']['trno'] = $gjtrno;
              $config['params']['data']['acno'] = $arintacnoid;
              $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$arintacnoid'");
              $config['params']['data']['db'] = $interest;
              $config['params']['data']['cr'] = 0;
              $config['params']['data']['fcr'] = 0;
              $config['params']['data']['fdb'] = 0;
              $config['params']['data']['postdate'] = $pdate;
              $config['params']['data']['client'] = $client;
              $config['params']['data']['refx'] = 0;
              $config['params']['data']['linex'] = 0;
              $config['params']['data']['ref'] = $sjdocno . '*';
              $config['params']['data']['rem'] = $nf->format($y) . ' Interest';
              $config['params']['trno'] = $gjtrno;
              $return = app($path)->additem('insert', $config, true);
              $totalint = $totalint + $interest;
            }

            //detailinfo
            $di['trno'] = $gjtrno;
            $di['line'] = $i;
            $di['interest'] = 0;
            $di['principal'] = $principal;
            $di['payment'] = 0;
            array_push($dinfo, $di);
            $i += 1;

            $di['trno'] = $gjtrno;
            $di['line'] = $i;
            $di['interest'] = round($interest, 2);
            $di['principal'] = 0;
            $di['payment'] = 0;
            array_push($dinfo, $di);

            $prevbal = $prevbal - $principal;
            $runningfa = $runningfa - $principal;

            $pdate = date("Y-m-d", strtotime("+$y month", $rdate));

            //rebate
            if ($rebate != 0) {
              $arrbacnoid = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'AR5'"); //arrebate
              $aprbacnoid = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'AP3'"); //aprebate

              if ($return['status']) {
                $config['params']['data']['trno'] = $gjtrno;
                $config['params']['data']['acno'] = $arrbacnoid;
                $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$arrbacnoid'");
                $config['params']['data']['db'] = $rebate;
                $config['params']['data']['cr'] = 0;
                $config['params']['data']['fcr'] = 0;
                $config['params']['data']['fdb'] = 0;
                $config['params']['data']['postdate'] = $pdate;
                $config['params']['data']['client'] = $client;
                $config['params']['data']['refx'] = 0;
                $config['params']['data']['linex'] = 0;
                $config['params']['data']['ref'] = $sjdocno . '*';
                $config['params']['data']['rem'] = $nf->format($y) . ' Rebate';
                $config['params']['trno'] = $gjtrno;
                $return = app($path)->additem('insert', $config, true);
              }

              if ($return['status']) {
                $config['params']['data']['trno'] = $gjtrno;
                $config['params']['data']['acno'] = $aprbacnoid;
                $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$arrbacnoid'");
                $config['params']['data']['db'] = 0;
                $config['params']['data']['cr'] = $rebate;
                $config['params']['data']['fcr'] = 0;
                $config['params']['data']['fdb'] = 0;
                $config['params']['data']['postdate'] = $pdate;
                $config['params']['data']['client'] = $client;
                $config['params']['data']['refx'] = 0;
                $config['params']['data']['linex'] = 0;
                $config['params']['data']['ref'] = $sjdocno . '*';
                $config['params']['data']['rem'] = $nf->format($y) . ' Rebate';
                $config['params']['trno'] = $gjtrno;
                $return = app($path)->additem('insert', $config, true);
              }
              $i += 3;
            } else {
              $i += 2;
            }
          }

          //unearned int.
          $config['params']['data']['trno'] = $gjtrno;
          $config['params']['data']['acno'] = $intacnoid;
          $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$intacnoid'");
          $config['params']['data']['db'] = 0;
          $config['params']['data']['cr'] = $totalint;
          $config['params']['data']['fcr'] = 0;
          $config['params']['data']['fdb'] = 0;
          $config['params']['data']['postdate'] = $pdate;
          $config['params']['data']['client'] = $client;
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['ref'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['trno'] = $gjtrno;
          $return = app($path)->additem('insert', $config, true);


          //apply ar
          $artab = $this->coreFunctions->opentable("select ar.trno,ar.line,ar.db,ar.cr,ar.bal,ar.dateid,ar.docno,coa.alias from arledger as ar left join coa on coa.acnoid = ar.acnoid  where coa.alias <>'ARDP' and ar.trno =? and ar.bal<>0
      union all
      select ar.trno,ar.line,ar.db,ar.cr,ar.bal,ar.dateid,ar.docno,coa.alias from apledger as ar left join coa on coa.acnoid = ar.acnoid  where coa.alias ='AP3' and ar.trno =? and ar.bal<>0", [$trno, $trno]);

          if (!empty($artab)) {
            foreach ($artab as $key => $value) {
              $config['params']['data']['trno'] = $gjtrno;
              $config['params']['data']['acno'] = $maacnoid;
              $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$maacnoid'");
              if ($artab[$key]->db != 0) {
                $config['params']['data']['db'] = 0;
                $config['params']['data']['cr'] = $artab[$key]->bal;
              } else {
                $config['params']['data']['db'] = $artab[$key]->bal;
                $config['params']['data']['cr'] = 0;
              }
              $config['params']['data']['fcr'] = 0;
              $config['params']['data']['fdb'] = 0;
              $config['params']['data']['postdate'] = $artab[$key]->dateid;
              $config['params']['data']['client'] = $client;
              $config['params']['data']['refx'] = $artab[$key]->trno;
              $config['params']['data']['linex'] = $artab[$key]->line;
              $config['params']['data']['ref'] = $artab[$key]->docno;
              $config['params']['data']['rem'] = '';
              $config['params']['trno'] = $gjtrno;
              $return = app($path)->additem('insert', $config, true);

              if ($artab[$key]->alias == 'AR2') { //uinterest
                $config['params']['data']['trno'] = $gjtrno;
                $config['params']['data']['acno'] = $intacnoid;
                $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$intacnoid'");
                if ($artab[$key]->db != 0) {
                  $config['params']['data']['db'] = $artab[$key]->bal;
                  $config['params']['data']['cr'] = 0;
                } else {
                  $config['params']['data']['db'] = 0;
                  $config['params']['data']['cr'] = $artab[$key]->bal;
                }
                $config['params']['data']['cr'] = 0;
                $config['params']['data']['fcr'] = 0;
                $config['params']['data']['fdb'] = 0;
                $config['params']['data']['postdate'] = $artab[$key]->dateid;
                $config['params']['data']['client'] = $client;
                $config['params']['data']['refx'] = 0;
                $config['params']['data']['linex'] = 0;
                $config['params']['data']['ref'] = '';
                $config['params']['data']['rem'] = '';
                $config['params']['trno'] = $gjtrno;
                $return = app($path)->additem('insert', $config, true);
              }
            }
          }

          $this->coreFunctions->LogConsole($arbal);
          if ($total <> $arbal) {
            $diff = $total - $arbal;
            if (round($diff, 2) <> 0) {
              $otheracno = $this->coreFunctions->getfieldvalue("coa", "acno", "alias = 'SA6'");
              $ladate = $this->coreFunctions->getfieldvalue(app($path)->head, "dateid", "trno=?", [$gjtrno]);
              $config['params']['data']['trno'] = $gjtrno;
              $config['params']['data']['acno'] = $otheracno;
              $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno = '$otheracno'");
              if ($diff > 0) {
                $config['params']['data']['db'] = 0;
                $config['params']['data']['cr'] = $diff;
              } else {
                $config['params']['data']['cr'] = 0;
                $config['params']['data']['db'] = abs($diff);
              }

              $config['params']['data']['fcr'] = 0;
              $config['params']['data']['fdb'] = 0;
              $config['params']['data']['postdate'] = $ladate;
              $config['params']['data']['client'] = $client;
              $config['params']['data']['refx'] = 0;
              $config['params']['data']['linex'] = 0;
              $config['params']['data']['ref'] = '';
              $config['params']['data']['rem'] = '';
              $config['params']['trno'] = $gjtrno;
              $return = app($path)->additem('insert', $config, true);
            }
          }


          //detailinfo
          if (!$this->coreFunctions->sbcinsert('detailinfo', $dinfo)) {
            $return = ['status' => false, 'msg' => "Error in Detail info"];
          }
        } else {
          return ['status' => 'false', 'msg' => 'Error on saving cntnuminfo.'];
        }
      } else {
        return ['status' => 'false', 'msg' => 'Error on saving head.'];
      }
    }

    $txtdata = $this->coreFunctions->opentable("select i.trno,head.terms, i.interestrate,i.fma2,
    i.fma1,i.bal,i.bal as arbal,i.recondate
    from cntnuminfo as i left join lahead as head on head.trno = i.trno
    where i.trno = " . $gjtrno);
    $this->coreFunctions->execqry("update cntnum set recontrno=" . $sjtrno . " where trno =  " . $gjtrno);
    $this->coreFunctions->execqry("update cntnum set refrecon=" . $gjtrno . " where trno =  " . $trno);

    $data = $this->coreFunctions->opentable("select format(interest,2) as interest,format(principal,2) as principal,format(payment,2) as payment from detailinfo where trno =  " . $gjtrno);
    if ($return['status']) {
      $post = $this->othersClass->posttransacctg($config);
      if ($post['status']) {
        return ['status' => $return['status'], 'msg' => $docno . ' successfully created.', 'txtdata' => $txtdata, 'data' => $data];
      } else {
        return $post;
      }
    } else {
      return ['status' => $return['status'], 'msg' => $return['msg'], 'txtdata' => $txtdata, 'data' => $data];
    }
  }


  public function compute($config)
  {

    $trno = $config['params']['dataparams']['trno'];
    $sjtrno = $config['params']['dataparams']['sjtrno'];
    $head = $config['params']['dataparams'];

    if ($head['terms'] == "" || $head['interestrate'] == 0 || $head['fma2'] == 0) {
      return ['status' => false, 'msg' => 'Please complete details'];
    }

    if ($head['bal'] == 0) {
      return ['status' => false, 'msg' => 'Already fully paid.'];
    }

    $d = [];
    $det = [];
    $di = [];
    $dinfo = [];
    $i = 1;
    $prevbal = 0;
    $principal = 0;
    $prevbalh = 0;
    $prevball = 0;
    $prevprincipalcol = 0;
    $financeamt = 0;
    $ma = $this->othersClass->sanitizekeyfield("amt", $head['fma1']);
    $status = true;
    $msg = '';
    $total = 0;
    $ar = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");

    $balmons = $this->coreFunctions->getfieldvalue('terms', 'days', 'terms = ?', [$head['terms']]);
    $dateid = $this->coreFunctions->getfieldvalue('arledger', 'dateid', 'trno = ? and acnoid =?', [$trno, $ar], 'dateid desc');

    $financeamt = $this->othersClass->sanitizekeyfield("amt", $head['bal']);

    if ($head['fma1'] == 0) {
      $ma = round($financeamt * $head['fma2'], 2);
    }
    $prevbal = $financeamt;

    $rdate = strtotime($dateid);

    for ($y = 1; $y <= $balmons; $y++) {
      //detailinfo
      $interest = $prevbal * ($head['interestrate'] / 100);
      $principal = $ma - $interest;

      $di['trno'] = $trno;
      $di['line'] = $i;
      $di['interest'] = number_format(round($interest, 2), 2);
      $di['principal'] = number_format(round($principal, 2), 2);
      $di['payment'] = 0;
      $prevbal = $prevbal - $principal;
      $total = $total + $ma;
      array_push($dinfo, $di);
      $i += 1;
    }

    $txtdata = $this->coreFunctions->opentable("select " . $sjtrno . " as sjtrno," . $trno . " as trno,'" . $head['terms'] . "' as terms, " . $head['interestrate'] . " as interestrate," . $head['fma2'] . " as fma2,
      format(" . $ma . ",2) as fma1,format(" . $financeamt . ",2) as bal,sum(ar.bal) as arbal,'" . $head['recondate'] . "' as recondate
      from arledger as ar left join coa on coa.acnoid = ar.acnoid
      where ar.trno=$trno and coa.alias ='AR1'");


    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $dinfo, 'txtdata' => $txtdata, 'qry' => ''];
  }
}
