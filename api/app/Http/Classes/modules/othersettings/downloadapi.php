<?php

namespace App\Http\Classes\modules\othersettings;

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



class downloadapi
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DOWNLOADING UTILITY';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $fields = [];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3813
    );
    return $attrib;
  }


  public function createHeadbutton($config)
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

  public function createHeadField($config)
  {
    $fields = ['start', 'end'];

    $col1 = $this->fieldClass->create($fields);

    $fields = ['refresh', 'dlsales', 'dlsalesret', 'dlpurchase']; //,'dlpurchret'

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'Download Customer list');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select adddate(left(now(),10),-360) as start,
      left(now(),10) as end
    ");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'load':
        return $this->downloadcustomers($config);
        break;
      case 'dlsales':
        return $this->downloadtrans($config, 'SJ');
        break;
      case 'dlsalesret':
        return $this->downloadtrans($config, 'CM');
        break;
      case 'dlpurchase':
        return $this->downloadtrans($config, 'RR');
        break;
      case 'dlpurchret':
        return $this->downloadtrans($config, 'DM');
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  public function downloadcustomers($config)
  {
    $url = 'customer_list';
    $clist =  $this->othersClass->downloadapi($config, $url, '');
    $clist = $clist['records'];
    $data = [];
    $d = [];
    ini_set('max_execution_time', 0);
    if (!empty($clist)) {
      foreach ($clist as $k => $v) {
        //if($clist[$k]['external_marking']==0){
        //check if existing
        $exist = $this->coreFunctions->getfieldvalue("client", "client", "client=?", [$clist[$k]['customer_id']]);
        if (strlen($exist) == 0) {

          $d['client'] = $clist[$k]['customer_id'];
          $d['clientname'] = (is_null($clist[$k]['customer_name']) ? '' : $clist[$k]['customer_name']);
          $d['addr'] =  (is_null($clist[$k]['address1']) ? '' : $clist[$k]['address1']);
          $d['addr2'] =  (is_null($clist[$k]['address2']) ? '' : $clist[$k]['address2']);
          $d['tin'] = (is_null($clist[$k]['tin_no']) ? '' : $clist[$k]['tin_no']);
          $d['zipcode'] = (is_null($clist[$k]['zipcode']) ? '' : $clist[$k]['zipcode']);
          $d['iscustomer'] = 1;
          //array_push($data, $d);
          //$this->coreFunctions->sbcinsert('client', $d);
          if ($this->coreFunctions->sbcinsert('client', $d) != 1) {
            return ['status' => 'false', 'msg' => 'Please check '];
          } else {
            $ret = $this->othersClass->downloadapi($config, 'update_marking', 'TAG', ['id' => $clist[$k]['customer_id'], 'type' => 'customer']);
            if ($ret['status'] == 'ERROR') {
              return ['status' => 'false', 'msg' => 'Please check ' . $clist[$k]['customer_id']];
            }
          }
        }
        //} 
      }
    }
    return ['status' => 'true', 'msg' => 'Successfully downloaded', 'action' => 'load'];
  }

  public function downloadtrans($config, $doc)
  {
    // $dt = $this->coreFunctions->opentable("select reftrno from temptrans where doc='SJ' and trno =0");
    // if(!empty($dt)){
    //   foreach ($dt as $k => $v) {
    //     $ret = $this->generatetransaction($config, $doc, $dt[$k]->reftrno);
    //   }
    // }
    ini_set('max_execution_time', 0);
    $type = '';
    switch ($doc) {
      case 'SJ':
        $url = 'sales_list';
        $dt = $this->coreFunctions->opentable("select reftrno from temptrans where doc='SJ' and trno =0");
        if (!empty($dt)) {
          foreach ($dt as $k => $v) {
            $ret = $this->generatetransaction($config, $doc, $dt[$k]->reftrno);
          }
        }
        $type = 'sales';

        break;
      case 'CM':
        $url = 'sales_return_list';
        $dt = $this->coreFunctions->opentable("select reftrno from temptrans where doc='CM' and trno =0");
        if (!empty($dt)) {
          foreach ($dt as $k => $v) {
            $ret = $this->generatetransaction($config, $doc, $dt[$k]->reftrno);
          }
        }
        $type = 'return';
        break;
      case 'DM':
        $url = 'purchase_return_list';
        break;
      case 'RR':
        $url = 'receiving_list';
        $dt = $this->coreFunctions->opentable("select reftrno from temptrans where doc='RR' and trno =0");
        if (!empty($dt)) {
          foreach ($dt as $k => $v) {
            $ret = $this->generatetransaction($config, $doc, $dt[$k]->reftrno);
          }
        }
        $type = 'receiving';
        break;
      default:
        $url = 'update_marking';
        break;
    }
    $start = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
    $end = date("m/d/Y", strtotime($config['params']['dataparams']['end']));
    $clist =  $this->othersClass->downloadapi($config, $url, $doc, ['date1' => $start, 'date2' => $end]);
    $clist = $clist['records'];

    $data = [];
    $d = [];
    $ret = [];


    if (!empty($clist)) {
      foreach ($clist as $k => $v) {
        $exist = $this->coreFunctions->getfieldvalue("temptrans", "reftrno", "reftrno=? and iscancel = 0", [$clist[$k]['transaction_id']]);
        if (strlen($exist) == 0) {
          if ($doc == 'RR') {
            $clist[$k]['customer_id'] = 'SL00000001';
          }

          if (!is_null($clist[$k]['customer_id'])) {
            switch ($doc) {
              case 'SJ':
                $d['docno'] = $clist[$k]['sales_no'];
                $d['amount'] = (is_null($clist[$k]['tot_amount']) ? 0 : $clist[$k]['tot_amount']);
                $d['doc'] = 'SJ';
                $d['dateid'] =  $clist[$k]['date'];
                $d['client'] = $clist[$k]['customer_id'];
                break;
              case 'CM':
                $d['docno'] = $clist[$k]['return_no'];
                $d['amount'] = (is_null($clist[$k]['tot_amount']) ? 0 : $clist[$k]['tot_amount']);
                $d['doc'] = 'CM';
                $d['dateid'] =  $clist[$k]['date_time'];
                $d['ref'] = $clist[$k]['sales_no'];
                $d['client'] = $clist[$k]['customer_id'];
                break;
              case 'RR':
                $d['docno'] = $clist[$k]['receiving_no'];
                $d['doc'] = 'RR';
                $d['dateid'] =  $clist[$k]['date'];
                $d['client'] = 'SL00000001';
                break;
            }

            $d['cost'] =  (is_null($clist[$k]['tot_cost']) ? 0 : $clist[$k]['tot_cost']);
            $d['reftrno'] = $clist[$k]['transaction_id'];

            $this->coreFunctions->LogConsole('insert to temptranss');
            if ($this->coreFunctions->sbcinsert('temptrans', $d) != 1) {
              return ['status' => 'false', 'msg' => 'Please check '];
            } else {
              $this->logger->sbcwritelog($clist[$k]['transaction_id'], $config, 'DOWNLOAD', $clist[$k]['transaction_id'], "table_log");
              $ret = $this->generatetransaction($config, $doc, $clist[$k]['transaction_id']);
              if (!$ret['status']) {
                return ['status' => 'false', 'msg' => $ret['msg'] . ' Please check ' . $clist[$k]['transaction_id']];
              } else {
                $ret = $this->othersClass->downloadapi($config, $url, 'TAG', ['id' => $clist[$k]['transaction_id'], 'type' => $type]);
                if ($ret['status'] == 'ERROR') {
                  return ['status' => 'false', 'msg' => $ret['status'] . ':' . $ret['msg'] . ' Error in update Marking. Please check ' . $clist[$k]['transaction_id']];
                }
              }
            }
          }
        } else { //exist but not yet created
          $exist = $this->coreFunctions->getfieldvalue("temptrans", "reftrno", "reftrno=? and trno=0 and iscancel = 0", [$clist[$k]['transaction_id']]);
          if (strlen($exist) != 0) {
            $ret = $this->generatetransaction($config, $doc, $clist[$k]['transaction_id']);
            if (!$ret['status']) {
              return ['status' => 'false', 'msg' => $ret['msg'] . ' Please check ' . $clist[$k]['transaction_id']];
            }
          }
        }
      }
    }


    //updated transactions
    $this->coreFunctions->LogConsole('insert updated');
    $clist =  $this->othersClass->downloadapi($config, $url, 'EDIT', ['date1' => $start, 'date2' => $end, 'type' => $type]);
    $clist = $clist['records'];
    if (!empty($clist)) {
      foreach ($clist as $k => $v) {
        $exist = $this->coreFunctions->getfieldvalue("temptrans", "reftrno", "reftrno=? and iscancel = 1 and trno<>0", [$clist[$k]['transaction_id']]);
        if (strlen($exist) != 0) {
          // $exist = $this->coreFunctions->getfieldvalue("temptrans", "reftrno", "reftrno=? and iscancel = 0 and trno<>0", [$clist[$k]['transaction_id']]);
          // if(strlen($exist)==0){
          if ($clist[$k]['updated_rec'] != 1) {
            switch ($doc) {
              case 'SJ':
                $d['docno'] = $clist[$k]['sales_no'];
                $d['amount'] = (is_null($clist[$k]['tot_amount']) ? 0 : $clist[$k]['tot_amount']);
                $d['doc'] = 'SJ';
                $d['dateid'] =  $clist[$k]['date'];
                $d['client'] = $clist[$k]['customer_id'];
                break;
              case 'CM':
                $d['docno'] = $clist[$k]['return_no'];
                $d['amount'] = (is_null($clist[$k]['tot_amount']) ? 0 : $clist[$k]['tot_amount']);
                $d['doc'] = 'CM';
                $d['dateid'] =  $clist[$k]['date_time'];
                $d['ref'] = $clist[$k]['sales_no'];
                $d['client'] = $clist[$k]['customer_id'];
                break;
              case 'RR':
                $d['docno'] = $clist[$k]['receiving_no'];
                $d['doc'] = 'RR';
                $d['dateid'] =  $clist[$k]['date'];
                $d['client'] = 'SL00000001';
                break;
            }
            $d['cost'] =  (is_null($clist[$k]['tot_cost']) ? 0 : $clist[$k]['tot_cost']);
            $d['reftrno'] = $clist[$k]['transaction_id'];
            if ($this->coreFunctions->sbcinsert('temptrans', $d) != 1) {
              return ['status' => 'false', 'msg' => 'Please check '];
            } else {
              $this->logger->sbcwritelog($clist[$k]['transaction_id'], $config, 'REDOWNLOAD', $clist[$k]['transaction_id'], "table_log");
              $ret = $this->generatetransaction($config, $doc, $clist[$k]['transaction_id']);
              if (!$ret['status']) {
                return ['status' => 'false', 'msg' => $ret['msg'] . ' Please check(edited) ' . $clist[$k]['transaction_id']];
              } else {
                $ret = $this->othersClass->downloadapi($config, $url, 'UPDATE_READ', ['id' => $clist[$k]['transaction_id']]);
                if ($ret['status'] == 'ERROR') {
                  return ['status' => 'false', 'msg' => $ret['status'] . ':' . $ret['msg'] . ' Error in Update Read. Please check ' . $clist[$k]['transaction_id']];
                }
              }
            }
          }
        }
      }
    }

    return ['status' => 'true', 'msg' => 'Successfully downloaded', 'action' => 'load'];
  }

  private function generatetransaction($config, $doc, $id)
  {
    $data = [];
    $pref = '';
    $path = '';
    $docno = '';
    $head = [];
    $detail = [];
    $acctg = [];
    $amt = 0;

    $qry = "select t.docno as ref,t.ref as tref,t.amount,t.cost,t.dateid,t.client,c.clientname,t.reftrno,c.addr as address from temptrans as t left join client as c on c.client = t.client where t.iscancel = 0 and  t.trno =0 and t.reftrno = " . $id;
    $data = $this->coreFunctions->opentable($qry);

    if (!empty($data)) {
      switch ($doc) {
        case 'SJ':
          $pref = 'SJ';
          $path = 'App\Http\Classes\modules\bee\sj';
          $amt = $data[0]->amount;
          break;
        case 'CM':
          $pref = 'CM';
          $path = 'App\Http\Classes\modules\bee\cm';
          $amt = $data[0]->amount;
          break;
        case 'RR':
          $pref = 'RR';
          $path = 'App\Http\Classes\modules\bee\rr';
          $amt = $data[0]->cost;
          break;
        case 'DM':
          $pref = 'DM';
          $path = 'App\Http\Classes\modules\bee\dm';
          $amt = $data[0]->cost;
          break;
      }


      $trno = $this->othersClass->generatecntnum($config, app($path)->tablenum, $doc, $pref);

      if ($trno != -1) {
        $config['params']['trno'] = $trno;
        $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);

        $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'dateid' => $data[0]->dateid, 'yourref' => $data[0]->ref, 'ourref' => $data[0]->tref];

        $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);

        if ($inserthead) {
          $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno, app($path)->tablelogs);

          if (floatval($amt) != 0 || floatval($data[0]->cost) != 0) {
            switch ($doc) {
              case 'SJ':
                //$tax = ($data[0]->amount / 1.12) * .12;

                if (floatval($data[0]->amount) != 0) {
                  //ar
                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'db' => $data[0]->amount, 'cr' => 0, 'postdate' => $data[0]->dateid, 'ref' => $data[0]->ref];

                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                  //sales
                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => round($data[0]->amount, 2), 'db' => 0, 'postdate' => $data[0]->dateid];
                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
                }
                //tax
                // $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
                // $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => round($tax,2), 'db' => 0, 'postdate' => $data[0]->dateid];
                // $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                //cost
                if (floatval($data[0]->cost) != 0) {
                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'db' => $data[0]->cost, 'cr' => 0, 'postdate' => $data[0]->dateid];
                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['IN1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => $data[0]->cost, 'db' => 0, 'postdate' => $data[0]->dateid];
                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
                }
                break;
              case 'CM':
                //$tax = ($data[0]->amount / 1.12) * .12;
                //ar
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);
                $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => $data[0]->amount, 'db' => 0, 'postdate' => $data[0]->dateid, 'ref' => $data[0]->tref];

                $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                //sales
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);
                $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'db' => round($data[0]->amount, 2), 'cr' => 0, 'postdate' => $data[0]->dateid];
                $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                //tax
                // $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
                // $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => round($tax,2), 'db' => 0, 'postdate' => $data[0]->dateid];
                // $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                //cost
                if (floatval($data[0]->cost) != 0) {
                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => $data[0]->cost, 'db' => 0, 'postdate' => $data[0]->dateid];
                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                  $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['IN1']);
                  $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'db' => $data[0]->cost, 'cr' => 0, 'postdate' => $data[0]->dateid];
                  $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
                }
                break;

              case 'RR':
                //$tax = ($data[0]->amount / 1.12) * .12;
                //inv
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['IN1']);
                $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'db' => round($data[0]->cost, 2), 'cr' => 0, 'postdate' => $data[0]->dateid];
                $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                //ap
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP1']);
                $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client, 'cr' => $data[0]->cost, 'db' => 0, 'postdate' => $data[0]->dateid, 'ref' => $data[0]->ref];
                $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

                break;
            }
          }


          if (!empty($acctg)) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            foreach ($acctg as $key => $value) {
              foreach ($value as $key2 => $value2) {
                $acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
              }
              $acctg[$key]['editdate'] = $current_timestamp;
              $acctg[$key]['editby'] = $config['params']['user'];
              $acctg[$key]['encodeddate'] = $current_timestamp;
              $acctg[$key]['encodedby'] = $config['params']['user'];
              $acctg[$key]['trno'] = $config['params']['trno'];
              $acctg[$key]['db'] = round($acctg[$key]['db'], 2);
              $acctg[$key]['cr'] = round($acctg[$key]['cr'], 2);
            }
            if ($this->coreFunctions->sbcinsert('ladetail', $acctg) == 1) {
              $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS', app($path)->tablelogs);
            } else {
              $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED', app($path)->tablelogs);
              $this->coreFunctions->execqry("delete from cntnum where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from ladetail where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from lahead where trno=?", "delete", [$trno]);
              return ['status' => false, 'msg' => 'Error in Creating Detail'];
            }
          }


          $post = $this->othersClass->posttransacctg($config);
          if ($post['status']) {
            $this->coreFunctions->execqry("update temptrans set trno = " . $trno . " where iscancel =0 and reftrno = " . $id, "update");
            return ['status' => true, 'msg' => 'Created'];
          } else {
            return ['status' => false, 'msg' => $post['msg']];
          }
        } else {
          $this->coreFunctions->execqry("delete from cntnum where trno=?", "delete", [$trno]);
          return ['status' => false, 'msg' => 'Error in Creating Head'];
        }
      } else {
        return ['status' => false, 'msg' => 'Error in Creating Transaction'];
      }
    } else {
      return ['status' => false, 'msg' => 'No data found'];
    }
  }
} //end class
