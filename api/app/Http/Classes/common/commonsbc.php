<?php

namespace App\Http\Classes\common;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use Datetime;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\Logger;

use Carbon\Carbon;



class commonsbc
{

  private $coreFunctions;
  private $logger;
  private $companysetup;


  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->logger = new Logger;
    $this->companysetup = new companysetup;
  }

  function sanitize($str, $strtype)
  { //THIS FUNCTIONS STRIPS HTML TAGGING CHARATERS,STRIPS SLASHES AND ALSO REPLACES QUOTES WITH `
    switch ($strtype) {
      case 'ARRAY':
        $acnocodes = array('contra', 'acno', 'asset', 'liability', 'revenue', 'expense');
        $qty = array('isqty', 'isqty2', 'iss', 'rrqty', 'qty');

        foreach ($str as $key => $strval) {
          if (!in_array($key, $acnocodes)) {
            $str[$key] = strip_tags($str[$key]);
            $str[$key] = stripslashes($str[$key]);
            $str[$key] = str_replace("'", "´", $str[$key]);
            $str[$key] = str_replace('"', "”", $str[$key]);
          }
          if (in_array($key, $qty)) {
            if ($str[$key] == '' || empty($str[$key])) {
              $str[$key] = 0;
            }
          }
        } //end foreach
        return $str;
        break;
      default:
        $str = strip_tags($str);
        $str = stripslashes($str);
        $str = str_replace("'", "´", $str);
        $str = str_replace('"', "”", $str);
        return $str;
        break;
    } //END SWITCH CASE
  } //end function sanitize
  public function getlastseq($prefix, $config, $tablenum = '', $moduledoc = '', $pyear = 0, $posextraction = false, $pstation = '')
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    if (isset($config['params']['fixcenter'])) {
      $center = $config['params']['fixcenter'];
    }

    $station = $pstation;

    if ($moduledoc == '') {
      $moduledoc = $doc;
    }
    if ($tablenum != '') {
      $table = $tablenum;
    } else {
      $table = $config['docmodule']->tablenum;
    }

    $stationfilter = "";
    if ($station != '') {
      $stationfilter = " and station='" . $station . "'";
    }

    $yr = 0;
    if ($pyear != 0) {
      $yr = $pyear;
    } else {
      if (!$posextraction) {
        $yr = floatval($this->coreFunctions->datareader("select ifnull(yr,0) as value FROM profile where psection ='$moduledoc' and doc ='SED'"));
      }
    }

    if (!isset($config['params']['companyid'])) {
      if (floatval($yr) <> 0) {
        $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
      } else {
        $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'" . $stationfilter);
      }
    }

    switch ($config['params']['companyid']) {
      case 10:
        switch ($doc) {
          case 'SJ':
          case 'AI':
            $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where doc in ('SJ','AI') and center = '" . $center . "'");
            break;
          default:
            if (floatval($yr) <> 0) {
              $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
            } else {
              $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'");
            }
            break;
        }
        break;
      case 63: //ericco
        switch ($doc) {
          case 'ON':
          case 'CH':
            $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where doc in ('ON','CH') and center = '" . $center . "'");
            break;
          default:
            if (floatval($yr) <> 0) {
              $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
            } else {
              $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'");
            }
            break;
        }
        break;
      default:
        if (floatval($yr) <> 0) {
          $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
        } else {
          $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'" . $stationfilter);
        }
        break;
    }

    // if ($config['params']['companyid'] == 10) { //afti
    //   switch ($doc) {
    //     case 'SJ':
    //     case 'AI':
    //       $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where doc in ('SJ','AI') and center = '" . $center . "'");
    //       break;
    //     default:
    //       if (floatval($yr) <> 0) {
    //         $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
    //       } else {
    //         $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'");
    //       }
    //       break;
    //   }
    // } else {
    //   getlastseqhere:
    //   if (floatval($yr) <> 0) {
    //     $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix' and yr = " . $yr . " and center = '" . $center . "'");
    //   } else {
    //     $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from $table where bref='$prefix'  and center = '" . $center . "'" . $stationfilter);
    //   }
    // }


    return $seq[0]->seq;
  }

  public function generatecntnum($config, $tablename, $doc, $pref, $doclength = 0, $fixseq = 0, $moduledoc = '', $posextraction = false, $pyear = 0, $center = '')
  {
    $companyid = isset($config['params']['companyid']) ? $config['params']['companyid'] : 0;
    if ($companyid == 56) { //homeworks
      $pref1 = $pref;
      $brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $config['params']['center'] . "'");
      $pref = $pref1 . $brprefix;
    }

    $docno = $pref;
    if ($center == '') {
      $center = $config['params']['center'];
    }

    $user = $config['params']['user'];
    $station = isset($config['params']['station']) ? $config['params']['station'] : "";
    $yr = 0;

    if ($moduledoc == '') {
      $moduledoc = $doc;
    }

    $insertcntnum = 0;
    $docno = $this->sanitize($docno, 'STRING');
    if ($doclength == 0) {
      $docnolength = $this->companysetup->getdocumentlength($config['params']);
    } else {
      $docnolength = $doclength;
    }
    $table = $tablename;

    $counter = 0;
    while ($insertcntnum == 0) {
      if ($counter >= 5) {
        return -1;
      }

      $yr = 0;
      if (!$posextraction) {
        if ($this->companysetup->getdocyr($config['params'])) {
          $yr = $this->coreFunctions->datareader("select ifnull(yr,0) as value FROM profile where psection ='$moduledoc' and doc ='SED'");
        }
      }

      $seq = $this->getlastseq($pref, $config, $table, $moduledoc, $yr, $posextraction);
      if ($seq == 0 || empty($pref)) {
        if (empty($pref)) {
          $pref = strtoupper($docno);
        }
        $seq = $this->getlastseq($pref, $config, $table, $moduledoc, $yr, $posextraction);
      }

      if ($fixseq != 0) {
        $poseq = $pref . $fixseq;
        $seq = $fixseq;
      } else {
        $poseq = $pref . $seq;
      }

      if ($pyear != 0) $yr = $pyear;

      $newdocno = $this->PadJ($poseq, $docnolength, $yr);

      if (!empty($center) || $center != '') {
        $col = [];
        // check the other tables if these fields are existing
        $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $pref, 'center' => $center, 'yr' => $yr];
        if ($table == 'cntnum') {
          $col['station']  = $station;
        }
        $insertcntnum =  $this->coreFunctions->insertGetId($table, $col);
        $i = +1;
      } else {
        $insertcntnum = -1;
      }
      $counter += 1;
    }
    return $insertcntnum;
  }

  public function PadJ($PadString, $Len, $yr = 0)
  {
    if ($Len == 0) {
      return $PadString;
    }

    $suffix = $this->Getsuffix($PadString);
    $isno = $this->isnumber($suffix);
    $Prefix = strtoupper(substr($PadString, 0, $this->SearchPosition($PadString)));
    if ($Prefix == '') {
      $Prefix = $PadString;
    }
    $Number = (substr($PadString, $this->SearchPosition($PadString), strlen($PadString)));
    if ($Number == 0) {
      $Number = 1;
    }

    $yr = floatval($yr);

    if ((strlen($Prefix) + strlen($Number)) < $Len) {
      if ($isno) {
        if ($yr <> 0) {

          $Return = strtoupper($Prefix) . $yr . str_pad($Number, $Len - (strlen($Prefix) + strlen($yr)), '0', STR_PAD_LEFT);

          if (strlen($Return) > $Len) {
            here:
            if (substr($Number, 0, strlen($yr)) == $yr) {
              $Number2 = substr($Number, 5, strlen($Number) - strlen($yr));
              $Return = strtoupper($Prefix) . $yr . str_pad($Number2, $Len - (strlen($Prefix) + strlen($yr)), '0', STR_PAD_LEFT);
              $this->coreFunctions->logconsole($Return . 'Padj');
            } else {
              $Return = $PadString;
            }
          }
        } else {
          $Return = strtoupper($Prefix) . str_pad($Number, $Len - (strlen($Prefix)), '0', STR_PAD_LEFT);
        }
      } else {
        if ($yr <> 0) {
          $Return = strtoupper($Prefix) . $yr . str_pad($Number, $Len - (strlen($Prefix) + strlen($yr)), '0', STR_PAD_LEFT) . $suffix;
        } else {
          $Return = strtoupper($Prefix) . str_pad($Number, $Len - (strlen($Prefix)), '0', STR_PAD_LEFT) . $suffix;
        }
      }
    } else {
      if ($yr <> 0) {
        goto here;
      } else {
        if (strlen($PadString) > $Len) {
          $this->coreFunctions->logconsole('greater than len' . intval($Number));
          $Return = strtoupper($Prefix) . str_pad(intval($Number), $Len - (strlen($Prefix)), '0', STR_PAD_LEFT);
        } else {
          $Return = $PadString;
        }
      }
    }
    return $Return;
  }

  function SearchPosition($Search)
  {
    for ($i = 0; $i < strlen($Search); $i++) {
      if (strspn(substr($Search, $i, 1), '1234567890')) {
        return $i;
      }
    }
    return strlen($Search);
  }

  public function Getsuffix($PadString)
  {
    $Prefix = strtoupper(substr($PadString, -1));
    return $Prefix;
  }

  public function isnumber($prefix)
  {
    return (strspn($prefix, '1234567890'));
  }

  public function last_bref($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    if (isset($config['params']['fixcenter'])) {
      $center = $config['params']['fixcenter'];
    }

    $table = $config['docmodule']->tablenum;

    $filter_bref = '';
    if ($config['params']['doc'] == 'SB') {
      $prefix = $this->coreFunctions->getfieldvalue("client", "prefix", "clientid=?", [$config['params']['adminid']]);
      $filter_bref = " and bref='" . $prefix . "'";
    }

    $yr = floatval($this->coreFunctions->datareader("select yr as value FROM profile where psection ='$doc' and doc ='SED'"));
    if (floatval($yr) <> 0) {
      $last = $this->coreFunctions->opentable("select bref FROM $table where doc='$doc' and yr = " . $yr . " and center='$center'" . $filter_bref . " order by trno desc limit 1");
    } else {
      $last = $this->coreFunctions->opentable("select bref FROM $table where doc='$doc' and center='$center'" . $filter_bref . " order by trno desc limit 1");
    }

    //$last = $this->coreFunctions->opentable("select bref FROM $table where doc='$doc' and center='$center'" . $filter_bref . " order by trno desc limit 1");
    if (!empty($last)) {
      return $last[0]->bref;
    } else {
      return '';
    }
  }

  public function Prefixes($pref, $config)
  {
    $prefixes = "";

    if ($config['params']['companyid'] == 16 && $config['params']['doc'] == 'PO') { //ati
      if (isset($config['params']['head']['prefix'])) {
        if ($config['params']['head']['prefix'] == '') {
          goto defaultprefixhere;
        } else {
          $valid_pref = $this->coreFunctions->opentable("select prefix as pvalue FROM client where issupplier=1 and client='" . $config['params']['head']['client'] . "'");
        }
      } else {
        $valid_pref = $this->coreFunctions->opentable("select prefix as pvalue FROM client where issupplier=1 and prefix<>''");
      }
    } else {
      if ($pref == 'SB') {
        $valid_pref = $this->coreFunctions->opentable("select prefix as pvalue FROM client where clientid = " . $config['params']['adminid']);
      } else {
        defaultprefixhere:
        $valid_pref = $this->coreFunctions->opentable("select pvalue FROM profile where psection ='$pref' and doc ='SED'");
      }
    }

    $brprefix = '';
    if ($config['params']['companyid'] == 56) { //homeworks
      if ($config['params']['doc'] == 'PA' || $config['params']['doc'] == 'PP') {
      } else {
        if ($pref != '') {
          $brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $config['params']['center'] . "'");
        }
      }
    }

    for ($i = 0; $i < count($valid_pref); $i++) {
      $prefixes = explode(",", $valid_pref[$i]->pvalue . $brprefix);
    }
    return $prefixes;
  }

  public function gettrnodocno($docno, $config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $table = $config['docmodule']->tablenum;

    if (isset($config['params']['fixcenter'])) {
      $center = $config['params']['fixcenter'];
    }

    $qry = "select trno as value from $table where doc = ? and docno = ? and center = ?";
    return $this->coreFunctions->datareader($qry, [$doc, $docno, $center]);
  } //end fn

  public function Discount($Amt, $Discount)
  {
    if ($Discount != '') {
      $Disc = explode('/', $Discount);
    } else {
      $Disc = [];
    } //end if
    $DiscV = '';
    for ($a = 0; (count($Disc) - 1) >= $a; $a++) {
      $m = -1;
      $DiscV = $Disc[$a];
      if ($this->left($Disc[$a], 1) == '+') {
        $DiscV = substr($Disc[$a], 1);
        $m = 1;
      } //end if
      if ($DiscV !== '') {
        if ($this->right($DiscV, 1) == '%') {
          $AmountDisc = $Amt * floatval(($this->left($DiscV, strlen($DiscV) - 1)) / 100);
        } else {;
          $AmountDisc = str_replace(',', '', $DiscV);
        } //end if
      }

      $Amt = $Amt + ($AmountDisc * $m);
    } //emd each
    return $Amt;
  } //end function discount

  public function right($value, $count)
  {
    return substr($value, ($count * -1));
  } //end fn

  public function left($string, $count)
  {
    return substr($string, 0, $count);
  } //end fn

  public function validateDate($date, $format = 'Y-m-d')
  {
    $d = DateTime::createFromFormat($format, $date);
    $result = $d && $d->format($format) === $date;

    if (!$result) {
      $format = 'Y/m/d';
      $d = DateTime::createFromFormat($format, $date);
      $result = $d && $d->format($format) === $date;
    }

    return $result;
  }

  public function computecosting($itemid, $whid, $loc, $expiry, $trno, $line, $qty, $doc, $companyid)
  {
    $origqty = 0.0;
    $origqty = $qty;
    $aveqty = $costvalue = $avecost = $sumbal = $bal = $fcost = 0;
    $message = '';
    $forex = 1;
    $x = '';

    switch ($companyid) {
      case 56: //homeworks
        $avecost = $this->coreFunctions->getfieldvalue("item", "avecost", "itemid=?", [$itemid]);
        break;
    }

    $this->coreFunctions->execqry("delete from costing where trno = ? and line = ?", 'delete', [$trno, $line]);
    $strRRStatus = "select rrstatus.trno, rrstatus.line, rrstatus.cost, ifnull(rrstatus.bal,0) as bal,
      rrstatus.itemID, rrstatus.whID,item.minimum,item.maximum,client.client as whcode,
      client.clientname as whname,rrstatus.forex from rrstatus
      left join client on client.clientid=rrstatus.whid left join item on item.itemid=rrstatus.itemid
      where rrstatus.itemid = " . $itemid . "
      and rrstatus.whid = " . $whid . " and rrstatus.loc='" . $loc . "'";
    // $this->coreFunctions->LogConsole($strRRStatus);

    if ($expiry == '1900-01-01' || $expiry == '' || $expiry == null) {
      $strRRStatus =  $strRRStatus . " and (rrstatus.expiry='1900-01-01' or rrstatus.expiry='0000-0-0' or rrstatus.expiry='' or rrstatus.expiry is null) ";
    } else {
      if ($this->validateDate($expiry) || $this->validateDate($expiry, "Y/m/d")) {
        $expiry1 = date_format(date_create($expiry), "Y-m-d");
        $expiry2 = date_format(date_create($expiry), "Y/m/d");
        $strRRStatus =  $strRRStatus . " and (rrstatus.expiry = '$expiry1' or rrstatus.expiry = '$expiry2')";
      } else {
        $strRRStatus =  $strRRStatus . " and rrstatus.expiry = '$expiry'";
      }
    } //end if
    $strRRStatus =  $strRRStatus . " and rrstatus.bal<>0 order by rrstatus.encoded, rrstatus.line";

    $data = $this->coreFunctions->opentable($strRRStatus);
    if ($data != null) {
      for ($i = 0; $i < count($data); $i++) {
        $bal = $data[$i]->bal;
        $forex = $data[$i]->forex;
        if (floatval($forex) == 0) {
          $forex = 1;
        }
        if (($origqty > $bal) && $origqty <> 0) {
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, {$data[$i]->bal}, $itemid, $whid, 0, '$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = ($costvalue + ($data[$i]->cost * $data[$i]->bal));
            $aveqty = $aveqty + $data[$i]->bal;
            $origqty = round($origqty - $data[$i]->bal, 6);
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $data[$i]->bal);
            }
          } else {

            return -1;
          } //end if
        } elseif (($origqty <= $bal) && $origqty <> 0) {
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, $origqty, $itemid, $whid, 0,'$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $origqty;
            $aveqty = $aveqty + $origqty;
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $origqty);
            }
            $origqty = 0;
          } else {
            return -1;
          } //end if
        } //$origqty>$bal && $origqty<>0


      } // for ($i=0;$i<count($data);$i++){

      if ($origqty > 0) {
        $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
        return -1;
      } //end if

      $strsql = "DELETE FROM costing WHERE refx = 0 AND linex = 0 AND served = 0 AND bal = 0";
      $this->coreFunctions->execqry($strsql, 'delete');
      if (($costvalue <> 0) && ($aveqty <> 0)) {
        $fcost = $fcost / $aveqty;
        $strsql = "update lastock set fcost = " . $fcost . " where trno =? and line=?";
        $this->coreFunctions->execqry($strsql, 'update', [$trno, $line]);

        switch ($companyid) {
          case 56: //homeworks
            return $avecost;
            break;
          default:
            return $costvalue / $aveqty;
            break;
        }
      } else {
        switch ($companyid) {
          case 56: //homeworks
            return $avecost;
            break;
          default:
            return 0;
            break;
        }
      } //end if
    } else {
      $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
      return -1;
    } //end if
  } //end compute costing

  public function computecostingmi($itemid, $whid, $loc, $expiry, $trno, $line, $refx, $linex, $qty, $doc)
  {
    $origqty = $qty;
    $aveqty = $costvalue = $sumbal = $bal = $fcost = 0;
    $message = '';
    $forex = 1;
    $x = '';
    $this->coreFunctions->execqry("delete from costing where trno = ? and line = ?", 'delete', [$trno, $line]);
    $strRRStatus = "select rrstatus.trno, rrstatus.line, rrstatus.cost, ifnull(rrstatus.bal,0) as bal,
      rrstatus.itemID, rrstatus.whID,item.minimum,item.maximum,client.client as whcode,
      client.clientname as whname,rrstatus.forex from rrstatus
      left join client on client.clientid=rrstatus.whid left join item on item.itemid=rrstatus.itemid
      where rrstatus.itemid = ?
      and rrstatus.whid = ? and rrstatus.loc=? and rrstatus.trno =? and rrstatus.line =?";

    if ($expiry == '1900-01-01' || $expiry == '' || $expiry == null) {

      $strRRStatus =  $strRRStatus . " and (rrstatus.expiry='1900-01-01' or rrstatus.expiry='0000-0-0' or rrstatus.expiry='' or rrstatus.expiry is null) ";
    } else {
      $strRRStatus =  $strRRStatus . " and rrstatus.expiry = '$expiry'";
    } //end if

    $strRRStatus =  $strRRStatus . " and rrstatus.bal<>0 order by rrstatus.encoded";

    $data = $this->coreFunctions->opentable($strRRStatus, [$itemid, $whid, $loc, $refx, $linex]);
    if ($data != null) {
      for ($i = 0; $i < count($data); $i++) {
        $bal = $data[$i]->bal;
        $forex = $data[$i]->forex;
        if (floatval($forex) == 0) {
          $forex = 1;
        }
        if (($origqty > $bal) && $origqty <> 0) {

          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, {$data[$i]->bal}, $itemid, $whid, 0, '$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $data[$i]->bal;
            //$aveqty = $qty; //original by lysa
            $aveqty = $aveqty + $data[$i]->bal;
            $origqty = $origqty - $data[$i]->bal;
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $data[$i]->bal);
            }
          } else {

            return -1;
          } //end if
        } elseif (($origqty <= $bal) && $origqty <> 0) {
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, $origqty, $itemid, $whid, 0,'$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $origqty;
            $aveqty = $aveqty + $origqty;
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $origqty);
            }
            $origqty = 0;
          } else {
            return -1;
          } //end if
        } //$origqty>$bal && $origqty<>0


      } // for ($i=0;$i<count($data);$i++){

      if ($origqty > 0) {
        $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
        return -1;
      } //end if

      $strsql = "DELETE FROM costing WHERE refx = 0 AND linex = 0 AND served = 0 AND bal = 0";
      $this->coreFunctions->execqry($strsql, 'delete');
      if (($costvalue <> 0) && ($aveqty <> 0)) {
        $fcost = $fcost / $aveqty;
        $strsql = "update lastock set fcost = " . $fcost . " where trno =? and line=?";
        $this->coreFunctions->execqry($strsql, 'update', [$trno, $line]);
        return $costvalue / $aveqty;
      } else {
        return 0;
      } //end if
    } else {
      $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
      return -1;
    } //end if
  } //end compute costing

  public function computecostingpallet($itemid, $whid, $locid, $palletid, $trno, $line, $qty, $doc, $params)
  {
    $origqty = $qty;
    $aveqty = $costvalue = $sumbal = $bal = $fcost = 0;
    $message = '';
    $forex = 1;
    $x = '';

    switch ($params['companyid']) {
      case 6: //mitsukoshi
        $based_cost = 'item';
        break;

      default:
        $based_cost = 'rrstatus';
        break;
    }

    $this->coreFunctions->execqry("delete from costing where trno = ? and line = ?", 'delete', [$trno, $line]);
    $strRRStatus = "select rrstatus.trno, rrstatus.line, " . $based_cost . ".cost, ifnull(rrstatus.bal,0) as bal,
      rrstatus.itemID, rrstatus.whID,item.minimum,item.maximum,client.client as whcode,
      client.clientname as whname,rrstatus.forex from rrstatus
      left join client on client.clientid=rrstatus.whid left join item on item.itemid=rrstatus.itemid
      where rrstatus.itemid = ? and rrstatus.whid = ? and rrstatus.locid=?"; // 8.9.2021 remove - and rrstatus.palletid=?
    $strRRStatus =  $strRRStatus . " and rrstatus.bal<>0 order by rrstatus.dateid"; // 9.1.2021 - order by rrstatus.encoded

    $data = $this->coreFunctions->opentable($strRRStatus, [$itemid, $whid, $locid]); // 8.9.2021 remove - , $palletid

    if ($data != null) {
      for ($i = 0; $i < count($data); $i++) {
        $bal = $data[$i]->bal;
        $forex = $data[$i]->forex;
        if (floatval($forex) == 0) {
          $forex = 1;
        }
        if (($origqty > $bal) && $origqty <> 0) {
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, {$data[$i]->bal}, $itemid, $whid, 0, '$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $data[$i]->bal;
            //$aveqty = $qty; //original by lysa
            $aveqty = $aveqty + $data[$i]->bal;
            $origqty = $origqty - $data[$i]->bal;

            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $data[$i]->bal);
            }
          } else {
            return -1;
          } //end if
        } elseif (($origqty <= $bal) && $origqty <> 0) {
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, $origqty, $itemid, $whid, 0,'$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $origqty;
            $aveqty = $aveqty + $origqty;
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $origqty);
            }
            $origqty = 0;
          } else {
            return -1;
          } //end if
        } //$origqty>$bal && $origqty<>0

      } // for ($i=0;$i<count($data);$i++){

      if ($origqty > 0) {
        $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
        return -1;
      } //end if

      $strsql = "DELETE FROM costing WHERE refx = 0 AND linex = 0 AND served = 0 AND bal = 0";
      $this->coreFunctions->execqry($strsql, 'delete');

      if (($costvalue <> 0) && ($aveqty <> 0)) {
        $fcost = $fcost / $aveqty;
        $strsql = "update lastock set fcost = " . $fcost . " where trno =? and line=?";
        $this->coreFunctions->execqry($strsql, 'update', [$trno, $line]);
        return $costvalue / $aveqty;
      } else {
        return 0;
      } //end if
    } else {
      $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
      return -1;
    } //end if
  } //end compute costing

  public function computecostingserial($itemid, $whid, $trno, $line, $qty, $doc, $rrref, $sline, $loc)
  {
    $origqty = 0.0;
    $origqty = $qty;
    $aveqty = $costvalue = $sumbal = $bal = $fcost = 0;
    $message = '';
    $forex = 1;
    $x = '';
    //$rr = explode("~",$rrref);
    $this->coreFunctions->execqry("delete from costing where trno = ? and line = ?", 'delete', [$trno, $line]);
    $strRRStatus = "select rrstatus.trno, rrstatus.line, rrstatus.cost, ifnull(rrstatus.bal,0) as bal,
      rrstatus.itemID, rrstatus.whID,item.minimum,item.maximum,client.client as whcode,
      client.clientname as whname,rrstatus.forex from rrstatus
      left join client on client.clientid=rrstatus.whid left join item on item.itemid=rrstatus.itemid
      left join serialin as sin on sin.trno = rrstatus.trno and sin.line = rrstatus.line
      where rrstatus.itemid = " . $itemid . " and sin.sline in (" . $sline . ")
      and rrstatus.whid = " . $whid . " and rrstatus.loc = '" . $loc . "' and rrstatus.bal<>0 order by rrstatus.encoded, rrstatus.line";
    // $this->coreFunctions->LogConsole($strRRStatus);
    //and rrstatus.trno = ".$rr[0]." and rrstatus.line = ".$rr[1] ." 
    $data = $this->coreFunctions->opentable($strRRStatus);
    if ($data != null) {
      for ($i = 0; $i < count($data); $i++) {
        $origqty = 1;
        $bal = $data[$i]->bal;
        $forex = $data[$i]->forex;
        if (floatval($forex) == 0) {
          $forex = 1;
        }
        if (($origqty > $bal) && $origqty <> 0) {
          $this->coreFunctions->LogConsole('1st' . $data[$i]->trno);
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, 1, $itemid, $whid, 0, '$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = ($costvalue + ($data[$i]->cost * $data[$i]->bal));
            $aveqty = $aveqty + $data[$i]->bal;
            $origqty = round($origqty - $data[$i]->bal, 6);
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $data[$i]->bal);
            }
          } else {

            return -1;
          } //end if
        } elseif (($origqty <= $bal) && $origqty <> 0) {
          $this->coreFunctions->LogConsole('2nd' . $data[$i]->trno);
          $ins_qry = "insert INTO costing (trno, line, refx, linex, served, itemID, whID,bal, doc, IsPosted)
              select $trno, $line, {$data[$i]->trno}, {$data[$i]->line}, 1, $itemid, $whid, 0,'$doc',
              (select (case when IfNull(postdate, '') = '' then 0 else 1 end) FROM cntnum WHERE trno = $trno)";
          if ($this->coreFunctions->execqry($ins_qry, 'insert') >= 1) {
            $costvalue = $costvalue + $data[$i]->cost * $origqty;
            $aveqty = $aveqty + $origqty;
            if ($forex <> 1) {
              $fcost = $fcost + (($data[$i]->cost / $data[$i]->forex) * $origqty);
            }
            $origqty = 0;
          } else {
            return -1;
          } //end if
        } //$origqty>$bal && $origqty<>0


      } // for ($i=0;$i<count($data);$i++){

      if ($origqty > 0) {
        $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
        return -1;
      } //end if

      $strsql = "DELETE FROM costing WHERE refx = 0 AND linex = 0 AND served = 0 AND bal = 0";
      $this->coreFunctions->execqry($strsql, 'delete');
      if (($costvalue <> 0) && ($aveqty <> 0)) {
        $fcost = $fcost / $aveqty;
        $strsql = "update lastock set fcost = " . $fcost . " where trno =? and line=?";
        $this->coreFunctions->execqry($strsql, 'update', [$trno, $line]);
        return $costvalue / $aveqty;
      } else {
        return 0;
      } //end if
    } else {
      $this->coreFunctions->execqry("delete from costing where trno=$trno and line=$line", 'delete');
      return -1;
    } //end if
  } //end compute costing



  public function getlastclient($pref, $type)
  {
    $length = strlen($pref);
    $return = '';
    $field = '';

    switch ($type) {
      case 'customer':
        $field = 'iscustomer';
        break;
      case 'supplier':
        $field = 'issupplier';
        break;

      case 'warehouse':
        $field = 'iswarehouse';
        break;
      case 'agent':
        $field = 'isagent';
        break;
      case 'employee':
        $field = 'isemployee';
        break;
      case 'forwarder':
        $field = 'istrucking';
        break;
      case 'tenant':
        $field = 'istenant';
        break;
    }

    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where ' . $field . ' =1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where ' . $field . ' =1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function GetPrefix($PadString)
  {
    $Prefix = strtoupper(substr($PadString, 0, $this->SearchPosition($PadString)));
    return $Prefix;
  }

  public function getPrefixes($doc, $config)
  {
    $prefixes = $this->Prefixes($doc, $config);
    if (isset($prefixes[0]) && $prefixes[0] == "") {
      return empty($prefixes);
    } else {
      return $prefixes;
    }
  }


  public function generatebarcode($config, $folder)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $pref = '';
    $path = 'App\Http\Classes\modules\\' . $folder . '\\stockcard';

    $pref = app($path)->prefix;
    if ($config['params']['companyid'] == 16) $pref = "ITM"; //ati

    if (strlen($pref) == 0) {
      $pref = app($path)->prefix;
    }
    if (!$pref) {
      $prefixes = $this->getPrefixes('stockcard', $config);
      $pref = isset($prefixes[0]) ? $prefixes[0] : 'IT';
    }

    $barcode2 = app($path)->getlastbarcode($pref);
    getNextSeqHere:
    $seq = (substr($barcode2, $this->SearchPosition($barcode2), strlen($barcode2)));
    $seq += 1;

    if ($seq == 0 || empty($pref)) {
      if (empty($pref)) {
        $pref = strtoupper($barcode2);
      }
      $barcode2 =  app($path)->getlastbarcode($pref);
      $seq = (substr($barcode2, $this->SearchPosition($barcode2), strlen($barcode2)));
      $seq += 1;
    }
    $poseq = $pref . $seq;

    $newbarcode = $this->PadJ($poseq, $barcodelength);

    if ($config['params']['companyid'] == 16) { //ati
      $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$newbarcode], '', true);
      if ($itemid != 0) {
        $barcode2 = app($path)->getlastbarcode($newbarcode);
        goto getNextSeqHere;
      }
    }

    return $newbarcode;
  }

  public function sbcdiffInMonthsInt($date1, $date2)
  {
    $date1 = Carbon::parse($date1);
    $date2 = Carbon::parse($date2);

    return (int) $date1->diffInMonths($date2, false);
  }



}
