<?php

namespace App\Http\Classes\modules\sales;

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
use App\Http\Classes\SBCPDF;

use Exception;

class comm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Commission';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $logger;
  public $tablelogs = 'table_log';
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $tablenum = 'cntnum';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs_del = 'del_table_log';
  private $commission = [];
  public $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array('view' => 2991, 'save' => 2991, 'saveallentry' => 2991);
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $isselected = 0;
    $isreleased = 1;
    $siref = 2;
    $sidate = 3;
    $docno = 4;
    $dateid = 5;
    $clientname = 6;
    $podate = 7;
    $poref = 8;
    $amt = 9;
    $stock_projectname = 10;
    $agentname = 11;
    $markup = 12;
    $agentcom = 13;

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'isselected', 'isreleased', 'siref', 'sidate', 'docno', 'dateid', 'clientname', 'podate', 'poref', 'amt', 'stock_itemgroup', 'agentname', 'markup', 'agentcomamt', 'overrideagent', 'agent2comamt'
        ]
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['showtotal'] = true;
    $obj[0][$this->gridname]['totalfield'] = 'agentcomamt';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'COLLECTION LIST';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$isreleased]['label'] = "Status";
    $obj[0][$this->gridname]['columns'][$docno]['label'] = "Payment Ref.";
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Payment Date";
    $obj[0][$this->gridname]['columns'][$sidate]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Customer";
    $obj[0][$this->gridname]['columns'][$podate]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$poref]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$poref]['style'] = "text-align:left;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "SI Amount";
    $obj[0][$this->gridname]['columns'][$amt]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "text-align:right;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$siref]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$stock_projectname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$agentname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$markup]['label'] = "Profit(%)";
    $obj[0][$this->gridname]['columns'][$markup]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$markup]['style'] = "text-align:left;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$agentcom]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$agentcom]['label'] = "Commission";

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $return = [];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry',];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "RELEASE";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['start', 'end', 'agentname', 'agentid', 'salesgroup','sgid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'agentname.label', 'Sales Person');
    data_set($col1, 'agentname.readonly', false);
    data_set($col1, 'agentname.class', '');


    $fields = ['refresh', 'print', 'release', 'uploadexcel'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.label', 'Compute');
    data_set($col2, 'refresh.action', 'refresh');
    data_set($col2, 'refresh.style', 'width:100px;whiteSpace: normal;min-width:100px;');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $start = "curdate()";
    $end = "curdate()";
    $agentid = 0;
    $agent = '';
    $agentname = '';
    $salesgroup = '';
    $salesgroupid = 0;
    $sgid =0;

    if (isset($config['params']['dataparams'])) {
      if ($config['params']['dataparams']['start'] == null) {
        $start = "null";
      } else {
        $start = "date('" . $config['params']['dataparams']['start'] . "')";
      }

      if ($config['params']['dataparams']['end'] == null) {
        $end = "null";
      } else {
        $end = "date('" . $config['params']['dataparams']['end'] . "')";
      }

      $agentid = $config['params']['dataparams']['agentid'];
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      $salesgroup = $config['params']['dataparams']['salesgroup'];
      $salesgroupid = $config['params']['dataparams']['salesgroupid'];
      $sgid =  $config['params']['dataparams']['salesgroupid'];
    }

    $qry = "select " . $start . " as `start`, " . $end . " as `end`, '" . $agent . "' as agent, " . $agentid . " as agentid, '" . $agentname . "' as agentname, '" . $salesgroup . "' as salesgroup, " . $salesgroupid . " as salesgroupid,".$sgid." as sgid";
    $data = $this->coreFunctions->opentable($qry);
    return $data[0];
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];
    switch ($action) {
      case 'refresh':
        return $this->loaddata($config);
        break;
      case 'print':
        return $this->setupreport($config);
        break;
      case 'saveallentry':
        $result = $this->tagrelease($config);
        if ($result["status"]) {
          return $this->loaddetails($config);
        } else {
          return $result;
        }
        break;
      case 'release':
        $result = $this->tagrelease($config, 1);
        if ($result["status"]) {
          return $this->loaddetails($config);
        } else {
          return $result;
        }
        break;
      default:
        return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function tagrelease($config, $all = 0)
  {
    if ($all == 0) {
      $rows = $config['params']['rows'];
      $release = '';

      if (empty($rows)) {
        return ['status' => false, 'msg' => 'Please select accounts that you want to release.'];
      } else {
        foreach ($rows as $key => $val) {
          $data = [];
          if ($val["isselected"] == "true") {
            $release = $this->othersClass->getCurrentTimeStamp();
            $data['releaseddate'] = $release;
            $data['releaseby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate("commdetails", $data, ['trno' => $val["trno"], 'line' => $val["line"]]);
            $this->coreFunctions->sbcupdate("incentives", ['agrelease' => $release], ['trno' => $val["trno"], 'line' => $val["line"]]);
            $this->logger->sbcwritelog($val["trno"], $config, 'RELEASE COMM', $val['siref'] . ', Agent: ' . $val['agentname'] . ', Release Date: ' . $release);
          }
        }
      }
    } else {
      $rows = $this->coreFunctions->opentable($this->selectqry($config));
      foreach ($rows as $key => $val) {
        $data = [];
        $release = $this->othersClass->getCurrentTimeStamp();
        $data['releaseddate'] = $release;
        $data['releaseby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate("commdetails", $data, ['trno' => $rows[$key]->trno, 'line' => $rows[$key]->line]);
        $this->coreFunctions->sbcupdate("incentives", ['agrelease' => $release], ['trno' => $rows[$key]->trno]);
        $this->logger->sbcwritelog($rows[$key]->trno, $config, 'RELEASE COMM', $rows[$key]->siref . ', Agent: ' . $rows[$key]->agentname . ', Release Date: ' . $release);
      }
    }


    return ['status' => true, 'msg' => ''];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']["action"];
    switch ($action) {
      case 'refresh':
        return $this->loaddata($config);
        break;
      case 'uploadexcel':
        return $this->uploadcomm($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $action . ')'];
        break;
    } // end switch
  }


  private function selectqry($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $sgid = $config['params']['dataparams']['sgid'];

    $filter = '';
    $filter1 = '';

    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and (ag.salesgroupid = " . $salesgroupid. " or ph.clientid = ".$sgid.")";
    }

    //and cd.line = i.line
    $qry = "select 'false' as isselected,head.doc,cd.ptrno,i.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,head.docno,ar.dateid as sidate,cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          case ar.doc when 'AR' then detail.rem else replace(ar.docno,'DR','SI') end as siref,
          date_format(detail.podate,'%m/%d/%Y') as podate,detail.poref,case ar.doc when 'AR' then format(sum(detail.db-detail.cr),2) else format(sum(stock.ext),2) end as amt,
          p.name as stock_itemgroup,sum(stock.ext) as ext,ar.tax,sum(stock.cost*stock.iss) as tp,sum(cd.gp) as gp,cd.comrate,concat(round((((sum(stock.ext-cd.delcharge-cd.insurance))-sum(stock.cost*stock.iss))/sum(stock.ext-cd.delcharge-cd.insurance)*100),2),'%') as markup,
          head.dateid,sum(cd.comamt) as agentcomamt,sum(cd.overridecomm) as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,sum(cd.pheadcomm) as pheadcomm,ar.doc as ardoc,sum(cd.delcharge) as delcharge,sum(cd.insurance) as insurance
          from commdetails as cd left join incentives as i on cd.ptrno = i.ptrno and cd.trno = i.trno 
          left join gldetail as detail on detail.trno=cd.trno and detail.line = i.line
          left join glhead as head on head.trno = i.ptrno
          left join glhead as ar on ar.trno = i.trno
          left join glstock as stock on stock.trno = i.trno and stock.line = cd.line
          left join client as cl on cl.clientid = head.clientid
          left join projectmasterfile as p on p.line = case ar.doc when 'AR' then detail.projectid else stock.projectid end
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = case ar.doc when 'AR' then detail.agentid else ar.agentid end
          left join client as sgh on sgh.clientid = cd.overrideid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          where i.isusd = 0 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,i.line,head.doc,head.docno,ar.dateid,cl.client,cl.clientname,ag.client,ag.clientname,
          ar.doc, ar.docno,date_format(detail.podate,'%m/%d/%Y'),detail.poref,
          p.name,ar.tax,cd.comrate,head.dateid,
          sgh.clientname,ph.clientname,ph.client,detail.rem,cd.releaseddate,cd.ptrno
          union all
          select 'false' as isselected,'USD' as doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,i.ref as docno,i.podate as sidate,
          cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,i.invno as siref,
          date_format(i.podate,'%m/%d/%Y') as podate,i.poref,i.amt,
          p.name as stock_itemgroup,0 as ext,0 as tax,0 as tp,cd.gp,cd.comrate,'' as markup,
          i.depodate as dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,'USD' as ardoc,cd.delcharge,cd.insurance
          from commdetails as cd left join incentives as i on cd.usdline = i.usdline
          left join client as cl on cl.clientid = i.clientid
          left join projectmasterfile as p on p.line = i.projectid
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = i.agentid
          left join client as sgh on sgh.clientid = cd.overrideid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          where i.isusd =1 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,cl.client,cl.clientname,ag.client,ag.clientname,
          i.ref,date_format(i.podate,'%m/%d/%Y'),i.poref,i.podate,i.invno,i.amt,
          p.name,cd.gp,cd.comrate,i.depodate,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm
          order by agentname,dateid,siref,stock_itemgroup";
    return $qry;
  }

  private function loaddata($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $filter = '';
    $filter1 = '';
    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    $qry = "select distinct ptrno,trno, line,doc,agentid2,agentid,amt,clientid from incentives
        where isusd =0 and date(depodate) between '" . $start . "' and '" . $end . "' and agrelease is null " . $filter1;

    $forcomm = $this->coreFunctions->opentable($qry);
    
    //local
    foreach ($forcomm as $k => $val) {
      $comm = 0;
      $comrate = 0;
      $invamt = 0;
      $commoverride = 0;
      $del = 0;
      $tdel = 0;
      $tins = 0;
      $gross = 0;
      $vattype  = '';
      $commdet = [];
      $pheadid = 0;

      if ($forcomm[$k]->doc == 'AR') {
        $projectid = $this->coreFunctions->getfieldvalue("gldetail", "ifnull(projectid,0)", "trno=? and line =?", [$forcomm[$k]->trno, $forcomm[$k]->line]);
        $pagentid = $this->coreFunctions->getfieldvalue("projectmasterfile", "ifnull(agentid,0)", "line=?", [$projectid]);
        $vattype = $this->coreFunctions->getfieldvalue("client", "vattype", "clientid=?", [$forcomm[$k]->clientid]);
        if ($vattype == 'VATABLE') {
          $comm = ($forcomm[$k]->amt / 1.12) * (1 / 100);
        } else {
          $comm = $forcomm[$k]->amt * (1 / 100);
        }

        if ($projectid == '') {
          $projectid = 0;
          $pagentid = 0;
        }

        if ($forcomm[$k]->agentid2 != 0 && $forcomm[$k]->agentid2 != $forcomm[$k]->agentid) {
          if ($vattype == 'VATABLE') {
            $commoverride = ($forcomm[$k]->amt / 1.12) * (0.5 / 100);
          } else {
            $commoverride = $forcomm[$k]->amt * (0.5 / 100);
          }
        }

        $commdet["ptrno"] = $forcomm[$k]->ptrno;
        $commdet["trno"] = $forcomm[$k]->trno;
        $commdet["line"] = $forcomm[$k]->line;
        $commdet["projectid"] = $projectid;
        $commdet["agentid"] = $forcomm[$k]->agentid;
        $commdet["overrideid"] = $forcomm[$k]->agentid2;
        $commdet["pheadid"] = $pagentid;
        $commdet["gp"] = 0;
        $commdet["comrate"] = 1;
        $commdet["comamt"] = $comm;
        $commdet["delcharge"] = 0;
        $commdet["insurance"] = 0;
        $commdet["overridecomm"] = $commoverride;

        $pheadid = $this->coreFunctions->getfieldvalue("projectmasterfile", "agentid", "line=?", [$projectid]);
        if ($pheadid != 0) {
          $commdet["pheadcomm"] = $comm / 2;
        } else {
          $commdet["pheadcomm"] = 0;
        }

        array_push($this->commission, $commdet);

        $this->coreFunctions->execqry("update incentives set netamt =" . round($forcomm[$k]->amt, 2) . " ,agentcomamt = " . round($comm, 2) . ",delcharge = " . round($tdel, 2) . ",insurance =" . round($tins, 2) . ",agent2comamt =" . round($commoverride, 2) . " where trno = " . $forcomm[$k]->trno . " and line =" . $forcomm[$k]->line);
      } else {
        $qry = "select s.trno,s.line,s.projectid,sum(s.cost*s.iss) as tp,sum(s.ext) as ext,h.dateid,p.comrate,
        (select ic.depodate from incentives as ic where ic.trno = s.trno order by ic.depodate desc limit 1) as pdate,d.delcharge,s.insurance,ifnull(p.agentid,0) as agentid,h.yourref as poref,h.doc
        from glstock as s left join glhead as h on h.trno = s.trno
        left join projectmasterfile as p on p.line = s.projectid
        left join item on item.itemid = s.itemid
        left join delstatus as d on d.trno = h.trno where item.noncomm <> 1 and p.comrate <>0 and s.trno =" . $forcomm[$k]->trno . " group by s.trno,s.line,s.projectid,h.dateid,p.comrate,d.delcharge,s.insurance,ifnull(p.agentid,0),h.yourref,h.doc";

        $tamt = $this->coreFunctions->datareader("select sum(ext) as value from (" . $qry . ") as a");
        $data = $this->coreFunctions->opentable($qry);
        $del = 0;
        $tdel = 0;
        $tins = 0;
        $netamt = 0;
        $gross = 0;
        $override = 0;
        $comma = 0;

        foreach ($data as $key => $value) {
          $ardate = date_create($data[$key]->dateid);
          $crdate = date_create($data[$key]->pdate);
          $interval = date_diff($ardate, $crdate);
          $pmonth = $interval->format('%m');
          $insurance = $data[$key]->insurance;

          if (intval($pmonth) <= 6) {
            if ($data[$key]->ext == 0) {
              $profit = 0;
            } else {
              $p = $tamt / $data[$key]->ext;
              $del = $data[$key]->delcharge / $p;

              $invamt = $data[$key]->ext - $del - $insurance;

              $profit = (($invamt - $data[$key]->tp) / $invamt) * 100;
            }

            //if (floatval($profit) >= 10) {
              if (floatval($profit) >= 10 && floatval($profit) < 15) {
                $comrate = $data[$key]->comrate / 2;
              } elseif (floatval($profit) >= 15) {
                $comrate = $data[$key]->comrate;
              }else{
                $comrate =0;
              }

              if($comrate != 0){
                $comm = $comm + ($invamt * ($comrate / 100));
                $tdel = $tdel + $del;
                $tins = $tins + $insurance;
                $netamt = $netamt + $invamt;
                $comma = ($invamt * ($comrate / 100));
              }else{
                $comma =0;                
              }
              

              if ($forcomm[$k]->agentid2 != 0 && $forcomm[$k]->agentid2 != $forcomm[$k]->agentid) {
                $commoverride = $commoverride + ($comma / 2); //$invamt*(0.5/100);
                $override = ($comma / 2); //$invamt*(0.5/100);
              }

              $commdet["ptrno"] = $forcomm[$k]->ptrno;
              $commdet["trno"] = $forcomm[$k]->trno;
              $commdet["line"] = $data[$key]->line;
              $commdet["projectid"] = $data[$key]->projectid;
              $commdet["agentid"] = $forcomm[$k]->agentid;
              $commdet["overrideid"] = $forcomm[$k]->agentid2;
              $commdet["pheadid"] = $data[$key]->agentid;
              $commdet["gp"] = $profit;
              $commdet["comrate"] = $comrate;
              $commdet["comamt"] = number_format($comma, 2, '.', '');
              $commdet["delcharge"] = number_format($del, 2, '.', '');
              $commdet["insurance"] = number_format($insurance, 2, '.', '');
              $commdet["overridecomm"] = number_format($override, 2, '.', '');
              $commdet["usdline"] = 0;
              if ($data[$key]->agentid != 0) {
                if ($forcomm[$k]->agentid2 != $data[$key]->agentid) {
                  $commdet["pheadcomm"] = number_format($comma / 2, 2, '.', '');
                } else {
                  $commdet["pheadcomm"] = 0;
                }
              } else {
                $commdet["pheadcomm"] = 0;
              }

              array_push($this->commission, $commdet);
            //}
          }
          $gross = $gross + $data[$key]->ext;
        }
        $this->coreFunctions->execqry("update incentives set netamt =" . round($netamt, 2) . ",agentcomamt = " . round($comm, 2) . ",delcharge = " . round($tdel, 2) . ",insurance =" . round($tins, 2) . ",agent2comamt =" . round($commoverride, 2) . " where trno = " . $forcomm[$k]->trno);
      }
    }

    //aftech accounts
    //usd
    $qry = "select distinct usdline,ref,poref,podate,invno,agentid2,agentid,amt,clientid,projectid,gp from incentives
        where isusd != 0 and date(depodate) between '" . $start . "' and '" . $end . "' and agrelease is null " . $filter1;

    $forcomm = $this->coreFunctions->opentable($qry);
    foreach ($forcomm as $k => $val) {
      $comm = 0;
      $comrate = $this->coreFunctions->getfieldvalue("projectmasterfile", "comrate", "line=?", [$forcomm[$k]->projectid]);

      if ($comrate == '') {
        $comrate = 0;
      }

      $invamt = 0;
      $commoverride = 0;
      $del = 0;
      $tdel = 0;
      $tins = 0;
      $gross = 0;
      $vattype  = '';
      $commdet = [];
      $pheadid = $this->coreFunctions->getfieldvalue("projectmasterfile", "ifnull(agentid,0)", "line=?", [$forcomm[$k]->projectid]);

      if ($pheadid == '') {
        $pheadid = 0;
      }

      $del = 0;
      $tdel = 0;
      $tins = 0;
      $netamt = 0;
      $gross = 0;
      $override = 0;
      $comma =0;


      //if (floatval($forcomm[$k]->gp) >= 10) {
        if (floatval($forcomm[$k]->gp) >= 10) {
          if(floatval($forcomm[$k]->gp) < 15){
            if ($comrate != 0) {
              $comrate = $comrate / 2;
            }
          }   
        }else{
          $comrate =0;
        }

        if($comrate !=0){
          $comma = ($forcomm[$k]->amt * ($comrate / 100));
        }        

        if ($forcomm[$k]->agentid2 != 0 && $comma !=0) {
          if ($forcomm[$k]->agentid2 != $forcomm[$k]->agentid) {
            $override = $comma / 2;
          }
        }

        $commdet["ptrno"] = 0;
        $commdet["trno"] = 0;
        $commdet["line"] = 0;
        $commdet["usdline"] = $forcomm[$k]->usdline;
        $commdet["projectid"] = $forcomm[$k]->projectid;
        $commdet["agentid"] = $forcomm[$k]->agentid;
        $commdet["overrideid"] = $forcomm[$k]->agentid2;
        $commdet["pheadid"] = $pheadid;
        $commdet["gp"] = $forcomm[$k]->gp;
        $commdet["comrate"] = $comrate;
        $commdet["comamt"] = number_format($comma, 2, '.', '');
        $commdet["delcharge"] = 0;
        $commdet["insurance"] = 0;
        $commdet["overridecomm"] = number_format($override, 2, '.', '');
        if ($pheadid != 0 && $comma !=0) {
          if ($forcomm[$k]->agentid2 != $pheadid) {
            $commdet["pheadcomm"] = number_format($comma / 2, 2, '.', '');
          } else {
            $commdet["pheadcomm"] = 0;
          }
        } else {
          $commdet["pheadcomm"] = 0;
        }
        array_push($this->commission, $commdet);
        $this->coreFunctions->execqry("update incentives set agentcomamt = " . round($comma, 2) . ",agent2comamt =" . round($override, 2) . " where usdline = " . $forcomm[$k]->usdline);
      //}
    }//end usd


    $delqry = "";
    foreach ($this->commission as $key => $value) {
      foreach ($value as $key2 => $value2) {
        $this->commission[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
      }

      if ($this->commission[$key]['ptrno'] != 0) {
        $isexist = $this->coreFunctions->getfieldvalue("commdetails", "ptrno", "usdline = 0 and ptrno=? and trno=? and line=? and projectid =?", [$this->commission[$key]['ptrno'], $this->commission[$key]['trno'], $this->commission[$key]['line'], $this->commission[$key]['projectid']]);
        $delqry = "delete from commdetails where usdline =0 and ptrno =" . $this->commission[$key]['ptrno'] . " and trno =" . $this->commission[$key]['trno'] . " and line =" . $this->commission[$key]['line'] . " and projectid =" . $this->commission[$key]['projectid'];
      } else {
        $isexist = $this->coreFunctions->getfieldvalue("commdetails", "usdline", "usdline = ?", [$this->commission[$key]['usdline']]);
        $delqry = "delete from commdetails where usdline =" . $this->commission[$key]['usdline'];
      }


      if (strlen($isexist) != 0) {
        $this->coreFunctions->execqry($delqry, "delete");
        $this->coreFunctions->sbcinsert("commdetails", $this->commission[$key]);
      } else {
        $this->coreFunctions->sbcinsert("commdetails", $this->commission[$key]);
      }
    }

    return $this->loaddetails($config);
  }

  private function loaddetails($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];

    $filter = '';
    $filter1 = '';
    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and sg.line = " . $salesgroupid;
    }
    //and cd.line = i.line remove on left join incentive 03072024
    $qry = "select 'false' as isselected,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,head.docno,ar.dateid as sidate,cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          case ar.doc when 'AR' then detail.rem else ar.docno end as siref,
          date_format(detail.podate,'%m/%d/%Y') as podate,detail.poref,case ar.doc when 'AR' then format(sum(detail.db-detail.cr),2) else format(sum(stock.ext),2) end as amt,
          p.name as stock_itemgroup,sum(stock.ext) as ext,ar.tax,sum(stock.cost*stock.iss) as tp,cd.gp,cd.comrate,concat(round((((sum(stock.ext-cd.delcharge-cd.insurance))-sum(stock.cost*stock.iss))/sum(stock.ext-cd.delcharge-cd.insurance)*100),2),'%') as markup,
          head.dateid,format(cd.comamt,2) as agentcomamt,format(cd.overridecomm,2) as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm
          from commdetails as cd  left join incentives as i on cd.ptrno = i.ptrno and cd.trno = i.trno 
          left join glhead as head on head.trno = i.ptrno
          left join glhead as ar on ar.trno = i.trno
          left join glstock as stock on stock.trno = i.trno and stock.projectid = cd.projectid
          left join gldetail as detail on detail.trno=cd.trno and detail.line = cd.line
          left join client as cl on cl.clientid = head.clientid
          left join projectmasterfile as p on p.line = case ar.doc when 'AR' then detail.projectid else stock.projectid end
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = case ar.doc when 'AR' then detail.agentid else ar.agentid end
          left join client as sgh on sgh.clientid = cd.overrideid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          where isusd =0 and ag.nocomm=0 and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,head.docno,ar.dateid,cl.client,cl.clientname,ag.client,ag.clientname,
          ar.doc, ar.docno,date_format(detail.podate,'%m/%d/%Y'),detail.poref,
          p.name,ar.tax,cd.gp,cd.comrate,head.dateid,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,detail.rem,cd.releaseddate
          union all
          select 'false' as isselected,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,i.ref as docno,i.podate as sidate,
          cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          i.invno as siref, date_format(i.podate,'%m/%d/%Y') as podate,i.poref,i.amt,
          p.name as stock_itemgroup,0 as ext,0 as tax,0 as tp,cd.gp,cd.comrate,'' as markup,
          i.depodate as dateid,format(cd.comamt,2) as agentcomamt,format(cd.overridecomm,2) as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm
          from commdetails as cd  left join incentives as i on i.usdline = cd.usdline
          left join client as cl on cl.clientid = i.clientid
          left join projectmasterfile as p on p.line = i.projectid
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = i.agentid
          left join client as sgh on sgh.clientid = cd.overrideid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          where isusd =1 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,i.ref,i.podate,cl.client,cl.clientname,ag.client,ag.clientname,
          date_format(i.podate,'%m/%d/%Y'),i.poref,i.invno,i.amt,
          p.name,cd.gp,cd.comrate,i.depodate,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,cd.releaseddate
          order by siref,stock_itemgroup,dateid";

    $d = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $d]];
  }

  private function uploadcomm($config)
  {
    $data = $config['params']['data'];
    $inc = [];
    $commdet = [];
    $pmsg = '';

    $validheader = ["paymentdate", "refno", "customer", "po", "podate", "items", "invoice", "usdamt", "currency", "rate", "amount", "agent", "gp"];

    $rawdata = $config['params']['data'][0];
    $rawdata = array_change_key_case($rawdata, CASE_LOWER);
    $rawheader = array_keys($rawdata);

    foreach ($validheader as $key) {
      if (!in_array(strtolower($key), $rawheader)) {
        return ['status' => false, 'msg' => 'Invalid template, missing field `' . $key . '`', 'valid' => $validheader, 'upload' => $rawheader];
      }
    }
    //checking of itemgroup
    foreach ($data as $i => $j) {
      $pid = $this->coreFunctions->getfieldvalue("projectmasterfile", "ifnull(line,0)", "code=?", [$data[$i]['items']]);
      if ($pid == 0) {
        if ($pmsg != '') {
          $pmsg = $pmsg . ", " . $data[$i]['items'];
        } else {
          $pmsg = $data[$i]['items'];
        }
      }
    }

    if ($pmsg != '') {
      return ['status' => false, 'msg' => 'Item group does not exist: ' . $pmsg];
    }

    foreach ($data as $k => $v) {
      $inc['isusd'] = 1;
      $inc['depodate'] = date('Y-m-d', strtotime($data[$k]['paymentdate']));
      $inc['ref'] = $data[$k]['refno'];
      $inc['poref'] = $data[$k]['po'];
      $inc['podate'] = date('Y-m-d', strtotime($data[$k]['podate']));
      $inc['invno'] = $data[$k]['invoice'];
      $inc['usdamt'] = $data[$k]['usdamt'];
      $inc['amt'] = $data[$k]['amount'];
      $inc['netamt'] = $data[$k]['amount'];
      $inc['rate'] = $data[$k]['rate'];
      $inc['gp'] = floatval($data[$k]['gp']);
      $pid = $this->coreFunctions->getfieldvalue("projectmasterfile", "ifnull(line,0)", "code=?", [$data[$k]['items']]);
      if ($pid != '') {
        $inc['projectid'] = $pid;
      } else {
        $inc['projectid'] = 0;
      }
      $inc['clientid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[$k]['customer']]);

      $agentinfo = $this->coreFunctions->opentable("select ag.clientid as agentid,case sg.isoverride when 1 then sg.clientid else 0 end as agentid2 
      from client as ag  left join salesgroup as ag2 on ag2.line=ag.salesgroupid
      left join client as sg on sg.clientid = ag2.agentid where ag.client = '" . $data[$k]['agent'] . "'");

      $inc['agentid'] = $agentinfo[0]->agentid;
      $inc['agentid2'] = $agentinfo[0]->agentid2;


      array_push($commdet, $inc);
    }

    $usdline = 0;
    try {
      foreach ($commdet as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $commdet[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }

        $qry = "select usdline as value from incentives where usdline <>0 order by usdline desc limit 1";
        $usdline = $this->coreFunctions->datareader($qry);
        if ($usdline == '') {
          $usdline = 0;
        }
        $usdline = $usdline + 1;
        $commdet[$key]['usdline'] = $usdline;

        $isexist = $this->coreFunctions->getfieldvalue("incentives", "ref", "agrelease is null and ref=? and poref=? and projectid=? and invno =?", [$commdet[$key]['ref'], $commdet[$key]['poref'], $commdet[$key]['projectid'], $commdet[$key]['invno']]);
        if (strlen($isexist) != 0) {
          $this->coreFunctions->execqry("delete from incentives where ref=? and poref=? and projectid=? and invno =?", "delete", [$commdet[$key]['ref'], $commdet[$key]['poref'], $commdet[$key]['projectid'], $commdet[$key]['invno']]);
          $this->coreFunctions->sbcinsert("incentives", $commdet[$key]);
        } else {
          $this->coreFunctions->sbcinsert("incentives", $commdet[$key]);
        }
      }
      return ['status' => true, 'msg' => 'Uploaded!'];
    } catch (Exception $ex) {
      return ['status' => false, 'msg' => ' ' . substr($ex, 0, 1000)];
    }
  }

  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }


  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'start', 'end', 'agentid', 'prepared', 'approved', 'received', 'radioreporttype', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.type', 'hidden');
    data_set($col1, 'end.type', 'hidden');
    data_set($col1, 'agentid.type', 'hidden');
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Commission Per Agent', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Commission Per Sales Group', 'value' => '1', 'color' => 'orange']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $agentid = $config['params']['dataparams']['agentid'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $agentname = $config['params']['dataparams']['agentname'];
    $sgid = $config['params']['dataparams']['sgid'];

    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '" . $start . "' as start,
        '" . $end . "' as end,
        '" . $agentid . "' as agentid,
        '" . $salesgroupid . "' as salesgroupid,
        '" . $agentname . "' as agentname,
        '" . $salesgroup . "' as salesgroup,
        '" . $sgid . "' as sgid,
        '' as prepared,
        '' as approved,
        '' as received,
        '0' as reporttype
    "
    );
  }

  private function sales_group_query($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $sgid = $config['params']['dataparams']['sgid'];
    $filter = '';
    $filter1 = '';

    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and (ag.salesgroupid = " . $salesgroupid. " or ph.clientid = ".$sgid.")";
    }

    //and cd.line = i.line
    $qry = "select 'false' as isselected,head.doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,head.docno,ar.dateid as sidate,cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          case ar.doc when 'AR' then detail.rem else replace(ar.docno,'DR','SI') end as siref,
          date_format(detail.podate,'%m/%d/%Y') as podate,detail.poref,case ar.doc when 'AR' then format(sum(detail.db-detail.cr),2) else format(sum(stock.ext),2) end as amt,
          p.name as stock_itemgroup,sum(stock.ext) as ext,ar.tax,sum(stock.cost*stock.iss) as tp,cd.gp,cd.comrate,concat(round((((sum(stock.ext-cd.delcharge-cd.insurance))-sum(stock.cost*stock.iss))/sum(stock.ext-cd.delcharge-cd.insurance)*100),2),'%') as markup,
          head.dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,ar.doc as ardoc,cd.delcharge,cd.insurance, sg.leader, sg.groupname
          from commdetails as cd left join incentives as i on cd.ptrno = i.ptrno and cd.trno = i.trno 
          left join gldetail as detail on detail.trno=cd.trno and detail.line = cd.line
          left join glhead as head on head.trno = i.ptrno
          left join glhead as ar on ar.trno = i.trno
          left join glstock as stock on stock.trno = i.trno and stock.projectid = cd.projectid       
          left join client as cl on cl.clientid = head.clientid
          left join projectmasterfile as p on p.line = case ar.doc when 'AR' then detail.projectid else stock.projectid end
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = case ar.doc when 'AR' then detail.agentid else ar.agentid end
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd = 0 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,head.doc,head.docno,ar.dateid,cl.client,cl.clientname,ag.client,ag.clientname,
          ar.doc, ar.docno,date_format(detail.podate,'%m/%d/%Y'),detail.poref,
          p.name,ar.tax,cd.gp,cd.comrate,head.dateid,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,detail.rem,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm, sg.leader, sg.groupname
          union all
          select 'false' as isselected,'USD' as doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,i.ref as docno,i.podate as sidate,
          cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,i.invno as siref,
          date_format(i.podate,'%m/%d/%Y') as podate,i.poref,i.amt,
          p.name as stock_itemgroup,0 as ext,0 as tax,0 as tp,cd.gp,cd.comrate,'' as markup,
          i.depodate as dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,'USD' as ardoc,cd.delcharge,cd.insurance, sg.leader, sg.groupname
          from commdetails as cd left join incentives as i on cd.usdline = i.usdline
          left join client as cl on cl.clientid = i.clientid
          left join projectmasterfile as p on p.line = i.projectid
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = i.agentid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd =1 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,cl.client,cl.clientname,ag.client,ag.clientname,
          i.ref,date_format(i.podate,'%m/%d/%Y'),i.poref,i.podate,i.invno,i.amt,
          p.name,cd.gp,cd.comrate,i.depodate,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm, sg.leader, sg.groupname
          order by groupname,leader,overrideagent,agentname,dateid,siref,stock_itemgroup";

    return $qry;
  }

  private function sales_group_summary_query($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $sgid = $config['params']['dataparams']['sgid'];
    $filter = '';
    $filter1 = '';

    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and (ag.salesgroupid = " . $salesgroupid. " or ph.clientid = ".$sgid.")";
    }
    // and cd.line = i.line
    $qry = "select overrideagent, agentname, sum(amt) as amt, SUM(agent2comamt) as agent2comamt from (select 'false' as isselected,head.doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,head.docno,ar.dateid as sidate,cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          case ar.doc when 'AR' then detail.rem else replace(ar.docno,'DR','SI') end as siref,
          date_format(detail.podate,'%m/%d/%Y') as podate,detail.poref,case ar.doc when 'AR' then format(sum(detail.db-detail.cr),2) else format(sum(stock.ext),2) end as amt,
          p.name as stock_itemgroup,sum(stock.ext) as ext,ar.tax,sum(stock.cost*stock.iss) as tp,cd.gp,cd.comrate,concat(round((((sum(stock.ext-cd.delcharge-cd.insurance))-sum(stock.cost*stock.iss))/sum(stock.ext-cd.delcharge-cd.insurance)*100),2),'%') as markup,
          head.dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,ar.doc as ardoc,cd.delcharge,cd.insurance
          from commdetails as cd left join incentives as i on cd.ptrno = i.ptrno and cd.trno = i.trno
          left join gldetail as detail on detail.trno=cd.trno and detail.line = cd.line
          left join glhead as head on head.trno = i.ptrno
          left join glhead as ar on ar.trno = i.trno
          left join glstock as stock on stock.trno = i.trno and stock.projectid = cd.projectid       
          left join client as cl on cl.clientid = head.clientid
          left join projectmasterfile as p on p.line = case ar.doc when 'AR' then detail.projectid else stock.projectid end
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = case ar.doc when 'AR' then detail.agentid else ar.agentid end
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd = 0 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,head.doc,head.docno,ar.dateid,cl.client,cl.clientname,ag.client,ag.clientname,
          ar.doc, ar.docno,date_format(detail.podate,'%m/%d/%Y'),detail.poref,
          p.name,ar.tax,cd.gp,cd.comrate,head.dateid,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,detail.rem,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm
          union all
          select 'false' as isselected,'USD' as doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,i.ref as docno,i.podate as sidate,
          cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,i.invno as siref,
          date_format(i.podate,'%m/%d/%Y') as podate,i.poref,i.amt,
          p.name as stock_itemgroup,0 as ext,0 as tax,0 as tp,cd.gp,cd.comrate,'' as markup,
          i.depodate as dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,'USD' as ardoc,cd.delcharge,cd.insurance
          from commdetails as cd left join incentives as i on cd.usdline = i.usdline
          left join client as cl on cl.clientid = i.clientid
          left join projectmasterfile as p on p.line = i.projectid
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = i.agentid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd =1 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,cl.client,cl.clientname,ag.client,ag.clientname,
          i.ref,date_format(i.podate,'%m/%d/%Y'),i.poref,i.podate,i.invno,i.amt,
          p.name,cd.gp,cd.comrate,i.depodate,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm
          order by overrideagent,agentname,dateid,siref,stock_itemgroup) as tbl
          group by overrideagent, agentname
          order by overrideagent, agentname";

    return $qry;
  }

  private function sales_summary_perteam_query($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $sgid = $config['params']['dataparams']['sgid'];
    $filter = '';
    $filter1 = '';

    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
      $filter1 = " and agentid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and (ag.salesgroupid = " . $salesgroupid. " or ph.clientid = ".$sgid.")";
    }
    //and cd.line = i.line
    $qry = "select groupname, sum(agentcomamt + agent2comamt + pheadcomm) as agentcomamt from 
    (select 'false' as isselected,head.doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,head.docno,ar.dateid as sidate,cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,
          case ar.doc when 'AR' then detail.rem else replace(ar.docno,'DR','SI') end as siref,
          date_format(detail.podate,'%m/%d/%Y') as podate,detail.poref,case ar.doc when 'AR' then format(sum(detail.db-detail.cr),2) else format(sum(stock.ext),2) end as amt,
          p.name as stock_itemgroup,sum(stock.ext) as ext,ar.tax,sum(stock.cost*stock.iss) as tp,cd.gp,cd.comrate,concat(round((((sum(stock.ext-cd.delcharge-cd.insurance))-sum(stock.cost*stock.iss))/sum(stock.ext-cd.delcharge-cd.insurance)*100),2),'%') as markup,
          head.dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,ar.doc as ardoc,cd.delcharge,cd.insurance, sg.groupname
          from commdetails as cd left join incentives as i on cd.ptrno = i.ptrno and cd.trno = i.trno 
          left join gldetail as detail on detail.trno=cd.trno and detail.line = cd.line
          left join glhead as head on head.trno = i.ptrno
          left join glhead as ar on ar.trno = i.trno
          left join glstock as stock on stock.trno = i.trno and stock.projectid = cd.projectid       
          left join client as cl on cl.clientid = head.clientid
          left join projectmasterfile as p on p.line = case ar.doc when 'AR' then detail.projectid else stock.projectid end
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = case ar.doc when 'AR' then detail.agentid else ar.agentid end
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd = 0 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,head.doc,head.docno,ar.dateid,cl.client,cl.clientname,ag.client,ag.clientname,
          ar.doc, ar.docno,date_format(detail.podate,'%m/%d/%Y'),detail.poref,
          p.name,ar.tax,cd.gp,cd.comrate,head.dateid,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,detail.rem,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm, sg.groupname
          union all
          select 'false' as isselected,'USD' as doc,cd.ptrno,cd.line,case ifnull(cd.releaseddate,'') when '' then '' else 'RELEASED' end as isreleased, cd.trno,i.ref as docno,i.podate as sidate,
          cl.client,cl.clientname,ag.client as agentcode,ag.clientname as agentname,i.invno as siref,
          date_format(i.podate,'%m/%d/%Y') as podate,i.poref,i.amt,
          p.name as stock_itemgroup,0 as ext,0 as tax,0 as tp,cd.gp,cd.comrate,'' as markup,
          i.depodate as dateid,cd.comamt as agentcomamt,cd.overridecomm as agent2comamt,
          sgh.clientname as overrideagent,ph.clientname as pheadagentname,ph.client as phead,cd.pheadcomm,'USD' as ardoc,cd.delcharge,cd.insurance, sg.groupname
          from commdetails as cd left join incentives as i on cd.usdline = i.usdline
          left join client as cl on cl.clientid = i.clientid
          left join projectmasterfile as p on p.line = i.projectid
          left join client as ph on ph.clientid = p.agentid
          left join client as ag on ag.clientid = i.agentid
          left join salesgroup as sg on sg.line = ag.salesgroupid
          left join client as sgh on sgh.clientid = cd.overrideid
          where i.isusd =1 and ag.nocomm=0 and date(i.depodate) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by cd.trno,cd.line,cl.client,cl.clientname,ag.client,ag.clientname,
          i.ref,date_format(i.podate,'%m/%d/%Y'),i.poref,i.podate,i.invno,i.amt,
          p.name,cd.gp,cd.comrate,i.depodate,format(cd.comamt,2),format(cd.overridecomm,2),
          sgh.clientname,ph.clientname,ph.client,cd.pheadcomm,cd.releaseddate,cd.delcharge,cd.insurance,cd.ptrno,cd.comamt,cd.overridecomm, sg.groupname
          order by overrideagent,agentname,dateid,siref,stock_itemgroup) as tbl
          group by groupname
          order by groupname";
    return $qry;
  }

  public function reportdata($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0':
        $str = $this->report_per_agent_layout($config);
        break;
      case '1':
        $str = $this->report_per_sales_group_layout($config);
        break;
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'directprint' => false, 'action' => 'print'];
  }

  public function report_per_agent_layout($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start = date('F d, Y', strtotime($config['params']['dataparams']['start']));
    $end = date('F d, Y', strtotime($config['params']['dataparams']['end']));
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $data = $this->coreFunctions->opentable($this->selectqry($config));

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 2000;
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMMISSION PER AGENT', null, null, false, $border, '', 'L', $font, 18, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($start, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TO:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($end, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Group :', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($salesgroup, '730', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DEDUCTION', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '700', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent Name', '150', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DR/SI#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('CR#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Payment Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Customer', '150', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PO#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PO Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Item Group', '100', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Delivery Actual Amount', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Insurance', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Amt Due For Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Gross Profit', '50', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Comm. Rate', '50', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Override Agent', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $price_deci = $this->companysetup->getdecimal('price', $config['params']);

    $agentname = "";
    $subtotal = 0;
    $total = 0;
    $totalover = 0;
    $totalprodcomm = 0;
    $overridesubtotal = 0;

    foreach ($data as $key => $val) {
      if ($agentname != $val->agentname) {
        if ($agentname != "") {
          $str .= $this->subTotal_per_agent($subtotal, $overridesubtotal, $price_deci);
          $subtotal = 0;
          $overridesubtotal = 0;
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($val->agentname, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($val->doc != 'CR') {
        $crref = $this->coreFunctions->datareader("select group_concat(distinct h.crref SEPARATOR ', ') as value from glhead as head left join gldetail as detail on detail.trno = head.trno 
          left join coa on coa.acnoid = detail.acnoid left join glhead as h on h.trno = detail.refx
          where head.trno = " . $val->ptrno . " and coa.alias ='AR5'");

        if ($crref != '') {
          $val->docno = $crref;
        }
      }

      $comrate = '';
      $vattype = '';
      $gp = '';
      $val->amt = $this->othersClass->sanitizekeyfield('amt', $val->amt);
      if ($val->ardoc == 'AR') {
        $comrate = number_format($val->comrate, 2);
        $vattype = $this->coreFunctions->getfieldvalue("client", "vattype", "client=?", [$val->client]);
        if ($vattype == 'VATABLE') {
          $val->netamt = $val->amt / 1.12;
        } else {
          $val->netamt = $val->amt;
        }
      } else {
        $val->netamt = ($val->amt - $val->delcharge - $val->insurance);
        $comrate = number_format($val->comrate, 2);
      }

      if ($val->gp <= 15) {
        $gp = number_format($val->gp, 2);
      } else {
        $gp = "";
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->siref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->sidate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->poref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->podate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->stock_itemgroup, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->delcharge, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->insurance, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->netamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($gp, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($comrate, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->agentcomamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->overrideagent, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->agent2comamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->pheadagentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->pheadcomm, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $agentname = $val->agentname;
      $subtotal += $val->agentcomamt;
      $total += $val->agentcomamt;
      $totalover += $val->agent2comamt;
      $overridesubtotal += $val->agent2comamt;
      $totalprodcomm += $val->pheadcomm;
    }

    $str .= $this->subTotal_per_agent($subtotal, $overridesubtotal, $price_deci);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('GRAND TOTAL:', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($totalover, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($totalprodcomm, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->report_per_agent_summary($config, $data, $layoutsize, $border, $font);
    $str .= "<br>";

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_per_agent_summary($config, $data, $layoutsize, $border, $font)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];
    $sgid = $config['params']['dataparams']['sgid'];

    $filter = '';
    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and sg.agentid = " . $sgid;
    }

    $str = "";
    $agentname = "";
    $totalcommision = 0;
    $totalover = 0;
    $totalphead = 0;
    $totalucommision = 0;
    $totaluover = 0;
    $totaluphead = 0;
    $gtotal = 0;
    $comm = 0;
    $ocomm = 0;
    $pcomm = 0;
    $ucomm = 0;
    $uocomm = 0;
    $upcomm = 0;

    $agents = $this->coreFunctions->opentable("select clientid,client,clientname from client as ag 
      left join salesgroup as sg on sg.line = ag.salesgroupid
      where isagent =1 " . $filter . "  order by clientname");

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Summary', null, null, false, $border, '', 'L', $font, 14, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // per agent
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Per Agent', null, null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Peso Account', '270', null, false, $border, 'LTRB', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Dollar Account', '270', null, false, $border, 'LTRB', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Total', '90', null, false, $border, 'LTR', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Agent', '150', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Sales Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Sales Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, 'BR', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    foreach ($agents as $a => $v) {
      $comm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
      and i.trno = cd.trno  where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ucomm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
      where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
      and i.trno = cd.trno  where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.overrideid = " . $agents[$a]->clientid);
      $uocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
      where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.overrideid = " . $agents[$a]->clientid);
      $pcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
      and i.trno = cd.trno  where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.pheadid = " . $agents[$a]->clientid);
      $upcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
      where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.pheadid = " . $agents[$a]->clientid);
      $total = $comm + $ocomm + $pcomm + $ucomm + $uocomm + $upcomm;

      if ($total != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($agents[$a]->clientname, '150', null, false, $border, '', 'L', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($comm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($ocomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($pcomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col(number_format($ucomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($uocomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($upcomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col(number_format($total, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->endrow();
      }

      $totalcommision += $comm;
      $totalucommision += $ucomm;
      $totalover += $ocomm;
      $totaluover += $uocomm;
      $totalphead += $pcomm;
      $totaluphead += $upcomm;
      $gtotal += $total;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '150', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totalcommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totalover, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totalphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totalucommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totaluover, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totaluphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($gtotal, 2), '90', null, false, $border, 'T', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $agentname = "";
    // $commission = 0;
    // $totalcommision = 0;
    // $str .= "<br><br>";
    // // per overriding agent
    // $qry = $this->qry_per_agent_summary($config);
    // $data = $this->coreFunctions->opentable($qry);
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Per Overriding Agent', null, null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Sales Agent', '120', null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->col('Sales Commission', '300', null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->endrow();
    // foreach ($data as $key => $val) {
    //   if($agentname != $val->overrideagent){
    //     if($agentname != "") {
    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col($agentname,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //         $str .= $this->reporter->col(number_format($commission,2),'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //       $str .= $this->reporter->endrow();
    //       $commission = 0;
    //     }
    //   }

    //   if($val->ardoc!='AR'){
    //     if($val->amt!=0){
    //       $commission += $val->agent2comamt;
    //       $totalcommision += $val->agent2comamt;
    //       $agentname = $val->overrideagent;
    //     }        
    //   }else{
    //     $commission += $val->agent2comamt;
    //     $totalcommision += $val->agent2comamt;
    //     $agentname = $val->overrideagent;
    //   }


    // }

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col($agentname,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //   $str .= $this->reporter->col(number_format($commission,2),'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    // $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Total','120', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    //   $str .= $this->reporter->col(number_format($totalcommision,2),'120', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    // $agentname = "";
    // $itemgroup = "";
    // $commission = 0;
    // $totalcommision = 0;
    // $str .= "<br><br>";

    // // per Product Head
    // $qry = $this->qry_per_agent_perproducthead($config);
    // $data = $this->coreFunctions->opentable($qry);

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Per Product Head', null, null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Product Head', '120', null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->col('Item Group', '120', null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->col('Commission', '300', null, false, $border, '', 'L', $font, 12, 'B', '', '3px');
    // $str .= $this->reporter->endrow();
    // foreach ($data as $key => $val) {
    //   if($commission>0){
    //     if($agentname != $val->leader){
    //       //if($itemgroup !=$val->stock_itemgroup){
    //         if($agentname != "") {
    //           $str .= $this->reporter->startrow();
    //             $str .= $this->reporter->col($agentname,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //             $str .= $this->reporter->col($itemgroup,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //             $str .= $this->reporter->col(number_format($commission,2),'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //           $str .= $this->reporter->endrow();
    //           $commission = 0;
    //         }
    //       //}          
    //     }else{
    //       if($itemgroup !=$val->stock_itemgroup){
    //         if($agentname != "") {
    //           $str .= $this->reporter->startrow();
    //             $str .= $this->reporter->col($agentname,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //             $str .= $this->reporter->col($itemgroup,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //             $str .= $this->reporter->col(number_format($commission,2),'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //           $str .= $this->reporter->endrow();
    //           $commission = 0;
    //         }
    //       } 
    //     }
    //   }

    //   if($val->ardoc!='AR'){
    //     if($val->amt!=0){
    //       $commission += ($val->agentcomamt / 2);    
    //       $agentname = $val->leader;
    //       $itemgroup = $val->stock_itemgroup;    
    //       $totalcommision += ($val->agentcomamt / 2);
    //     }        
    //   }else{
    //     $commission += ($val->agentcomamt / 2);    
    //     $agentname = $val->leader;
    //     $itemgroup = $val->stock_itemgroup;    
    //     $totalcommision += ($val->agentcomamt / 2);
    //   }
    // }

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col($agentname,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //  $str .= $this->reporter->col($itemgroup,'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    //   $str .= $this->reporter->col(number_format($commission,2),'120', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    // $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Total','120', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    //   $str .= $this->reporter->col('','120', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    //   $str .= $this->reporter->col(number_format($totalcommision,2),'120', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    // $str .= $this->reporter->endrow();
    //$str .= $this->reporter->endtable();

    return $str;
  }

  public function subTotal_per_agent($subtotal = 0, $overridesubtotal  = 0, $decimal)
  {
    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 2000;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('SUB TOTAL:', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($overridesubtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function report_per_sales_group_layout($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start = date('F d, Y', strtotime($config['params']['dataparams']['start']));
    $end = date('F d, Y', strtotime($config['params']['dataparams']['end']));
    $salesgroup = $config['params']['dataparams']['salesgroup'];

    $data = $this->coreFunctions->opentable($this->sales_group_query($config));
    // foreach ($data as $key => $value) {
    //   foreach($data[$key] as $s => $v){
    //     $data[$key]->$s = $this->othersClass->sanitizekeyfield($s, $data[$key]->$s);
    //   }

    // }

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 2000;
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMMISSION PER SALES GROUP', null, null, false, $border, '', 'L', $font, 18, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($start, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TO:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($end, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Group :', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($salesgroup, '730', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DEDUCTION', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '700', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Override Agent Name', '150', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DR/SI#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('CR#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Payment Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Customer', '150', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PO#', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PO Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Item Group', '100', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Delivery Actual Amount', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Insurance', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Amt Due For Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Gross Profit', '50', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Comm. Rate', '50', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Agent', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Commission', '100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $price_deci = $this->companysetup->getdecimal('price', $config['params']);

    $agentname = "";
    $overridesubtotal = 0;
    $subtotal = 0;
    $total = 0;
    $totalover = 0;
    $totalprodcomm = 0;

    foreach ($data as $key => $val) {
      if ($agentname != $val->overrideagent) {
        if ($agentname != "") {
          $str .= $this->subTotal_per_agent($subtotal, $overridesubtotal, $price_deci);
          $subtotal = 0;
          $overridesubtotal  = 0;
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($val->overrideagent, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($val->doc != 'CR') {
        $crref = $this->coreFunctions->datareader("select group_concat(distinct h.crref SEPARATOR ', ') as value from glhead as head left join gldetail as detail on detail.trno = head.trno 
          left join coa on coa.acnoid = detail.acnoid left join glhead as h on h.trno = detail.refx
          where head.trno = " . $val->ptrno . " and coa.alias ='AR5'");

        if ($crref != '') {
          $val->docno = $crref;
        }
      }

      $comrate = '';
      $vattype = '';
      $gp = '';
      $val->amt = $this->othersClass->sanitizekeyfield('amt', $val->amt);
      if ($val->ardoc == 'AR') {
        $comrate = number_format($val->comrate, 2);
        $vattype = $this->coreFunctions->getfieldvalue("client", "vattype", "client=?", [$val->client]);
        if ($vattype == 'VATABLE') {
          $val->netamt = $val->amt / 1.12;
        } else {
          $val->netamt = $val->amt;
        }
      } else {
        $val->netamt = ($val->amt - $val->delcharge - $val->insurance);
        $comrate = number_format($val->comrate, 2);
      }

      // $gp = number_format($val->gp,2);
      if ($val->gp <= 15) {
        $gp = number_format($val->gp, 2);
      } else {
        $gp = "";
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->siref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->sidate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->poref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->podate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->stock_itemgroup, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->delcharge, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->insurance, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->netamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($gp, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($comrate, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->agentcomamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->agentname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->agent2comamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->pheadagentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col(number_format($val->pheadcomm, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      // $agentname = $val->agentname;
      $agentname = $val->overrideagent;
      $subtotal += $val->agentcomamt;
      $overridesubtotal += $val->agent2comamt;
      $total += $val->agentcomamt;
      $totalover += $val->agent2comamt;
      $totalprodcomm += $val->pheadcomm;
    }

    $str .= $this->subTotal_per_agent($subtotal, $overridesubtotal, $price_deci);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('GRAND TOTAL:', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($totalover, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($totalprodcomm, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str.= $this->report_per_sales_group_summary_layout($config);
    $str .= "<br/><br/>";
    $str .= $this->report_per_salesgroup_summary($config, $data, $layoutsize, $border, $font);
    $str .= "<br/><br/>";
    $str .= $this->report_sales_group_perteam_layout($config, $data);

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_per_salesgroup_summary($config, $data, $layoutsize, $border, $font)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];

    $filter = '';
    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and sg.line = " . $salesgroupid;
    }

    $str = "";
    $agentname = "";
    $totalcommision = 0;
    $totalover = 0;
    $totalphead = 0;
    $totalucommision = 0;
    $totaluover = 0;
    $totaluphead = 0;
    $gtotal = 0;
    $comm = 0;
    $ocomm = 0;
    $pcomm = 0;
    $ucomm = 0;
    $uocomm = 0;
    $upcomm = 0;

    $stotalcommision = 0;
    $stotalover = 0;
    $stotalphead = 0;
    $stotalucommision = 0;
    $stotaluover = 0;
    $stotaluphead = 0;
    $sgtotal = 0;

    $agents = $this->coreFunctions->opentable("select sg.groupname, clientid,client,clientname,ag.salesgroupid from client as ag 
      left join salesgroup as sg on sg.line = ag.salesgroupid and sg.agentid = ag.clientid
      where isagent =1 " . $filter . "  order by ag.salesgroupid");

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Summary Sales Group', null, null, false, $border, '', 'L', $font, 14, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LTB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Peso Account', '270', null, false, $border, 'LTRB', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Dollar Account', '270', null, false, $border, 'LTRB', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Total', '90', null, false, $border, 'LTR', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Group', '75', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Sales Agent', '75', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Sales Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Sales Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Override Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('Product Head Commission', '90', null, false, $border, 'LTRB', 'L', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, 'BR', 'C', $font, 12, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $gxt = "";
    foreach ($agents as $a => $v) {

      if ($gxt != $v->salesgroupid) {

        if ($gxt != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Sub Total', '75', null, false, $border, 'T', 'L', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotalcommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotalover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotalphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotalucommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotaluover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($stotaluphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->col(number_format($sgtotal, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
          $str .= $this->reporter->endrow();

          $stotalcommision = 0;
          $stotalover = 0;
          $stotalphead = 0;
          $stotalucommision = 0;
          $stotaluover = 0;
          $stotaluphead = 0;
          $sgtotal = 0;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->coreFunctions->datareader("select groupname as value from salesgroup where line = " . $agents[$a]->salesgroupid), '75', null, false, $border, '', 'L', $font, '12', 'B', '', '3px');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->endrow();
      }

      $comm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ucomm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.overrideid = " . $agents[$a]->clientid);
      $uocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.overrideid = " . $agents[$a]->clientid);
      $pcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.pheadid = " . $agents[$a]->clientid);
      $upcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.pheadid = " . $agents[$a]->clientid);
      $total = $comm + $ocomm + $pcomm + $ucomm + $uocomm + $upcomm;

      if ($total != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
        $str .= $this->reporter->col($agents[$a]->clientname, '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($comm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($ocomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($pcomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col(number_format($ucomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($uocomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->col(number_format($upcomm, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');

        $str .= $this->reporter->col(number_format($total, 2), '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
        $str .= $this->reporter->endrow();
      }

      $stotalcommision += $comm;
      $stotalucommision += $ucomm;
      $stotalover += $ocomm;
      $stotaluover += $uocomm;
      $stotalphead += $pcomm;
      $stotaluphead += $upcomm;
      $sgtotal += $total;

      $totalcommision += $comm;
      $totalucommision += $ucomm;
      $totalover += $ocomm;
      $totaluover += $uocomm;
      $totalphead += $pcomm;
      $totaluphead += $upcomm;
      $gtotal += $total;
      $gxt = $v->salesgroupid;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sub Total', '75', null, false, $border, 'T', 'L', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotalcommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotalover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotalphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotalucommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotaluover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($stotaluphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($sgtotal, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '12', '', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '75', null, false, $border, 'T', 'L', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, '12', '', '', '3px');
    $str .= $this->reporter->col(number_format($totalcommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalucommision, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totaluover, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totaluphead, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->col(number_format($gtotal, 2), '90', null, false, $border, 'T', 'R', $font, '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  // public function report_per_sales_group_summary_layout($config)
  // {
  //   $center = $config['params']['center'];
  //   $username = $config['params']['user'];
  //   $start = date('F d, Y', strtotime($config['params']['dataparams']['start']));
  //   $end = date('F d, Y', strtotime($config['params']['dataparams']['end']));
  //   $salesgroup = $config['params']['dataparams']['salesgroup'];
  //   $data = $this->coreFunctions->opentable($this->sales_group_summary_query($config));

  //   $str = "";
  //   $font =  "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $layoutsize = 800;
  //   $str .= $this->reporter->begintable($layoutsize);
  //   // $str .= $this->reporter->letterhead($center, $username);
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('SUMMARY', null, null, false, $border, '', 'L', $font, 18, 'B', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('','50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col('','750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('FROM:','50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col($start,'750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('TO:','50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col($end,'750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Sales Group :','100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col($salesgroup,'630', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= "<br>";

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Overriding Agent','150', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col('Agent Name','100', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col('Amount','100', null, false, $border, 'BTRL', 'R', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col('Commission','100', null, false, $border, 'BTRL', 'Rs', $font, $fontsize, 'B', '', '3px');

  //   $price_deci = $this->companysetup->getdecimal('ext', $config['params']);

  //   $agentname = "";
  //   $agentigop = "";
  //   $subtotal = 0;
  //   $total = 0;
  //   $amt = 0;
  //   $commamt = 0;
  //   foreach($data as $key => $val) {

  //     $val->amt = $this->othersClass->sanitizekeyfield('amt',$val->amt);
  //     $amt = $val->amt;
  //     $commamt = $val->agent2comamt;

  //     if($agentname != $val->overrideagent ) {

  //       if($agentname != "") {
  //           $str .= $this->subTotal_per_sales_group($subtotal, $price_deci);
  //           $subtotal = 0;
  //         }
  //         $str .= $this->reporter->startrow();
  //         $str .= $this->reporter->col($val->overrideagent, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //         $str .= $this->reporter->endrow();
  //       }

  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('','100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //       $str .= $this->reporter->col($val->agentname,'100', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //       $str .= $this->reporter->col(number_format($amt, $price_deci),'100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
  //       $str .= $this->reporter->col(number_format($commamt, $price_deci),'100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
  //       $str .= $this->reporter->endrow();

  //     $agentname = $val->overrideagent;
  //     $subtotal += $val->agent2comamt;
  //     $total += $val->agent2comamt;
  //   }
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->subTotal_per_sales_group($subtotal, $price_deci);
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('','100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->col('','100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->col('GRAND TOTAL:','100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col(number_format($total, $price_deci), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();


  //   return $str;
  // }

  public function report_sales_group_perteam_layout($config, $data)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $salesgroup = $config['params']['dataparams']['salesgroup'];
    $salesgroupid = $config['params']['dataparams']['salesgroupid'];

    $filter = '';
    if ($agentname != '') {
      $filter = " and ag.clientid = " . $agentid;
    }

    if ($salesgroup != '') {
      $filter = " and sg.line = " . $salesgroupid;
    }

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 250;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Summary of Commission Per Team', '250', null, false, $border, 'TBRL', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $agents = $this->coreFunctions->opentable("select sg.groupname, clientid,client,clientname,ag.salesgroupid from client as ag 
      left join salesgroup as sg on sg.line = ag.salesgroupid and sg.agentid = ag.clientid
      where isagent =1 " . $filter . "  order by ag.salesgroupid");

    $price_deci = $this->companysetup->getdecimal('ext', $config['params']);

    $agentname = "";
    $agentigop = "";
    $subtotal = 0;
    $total = 0;
    $commamt = 0;

    $totals = 0;
    $comm = 0;
    $ocomm = 0;
    $pcomm = 0;
    $ucomm = 0;
    $uocomm = 0;
    $upcomm = 0;
    $stotals = 0;
    $grp = "";
    foreach ($agents as $a => $val) {

      $comm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ucomm = $this->coreFunctions->datareader("select sum(ifnull(cd.comamt,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.agentid = " . $agents[$a]->clientid);
      $ocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.overrideid = " . $agents[$a]->clientid);
      $uocomm = $this->coreFunctions->datareader("select sum(ifnull(cd.overridecomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.overrideid = " . $agents[$a]->clientid);
      $pcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.ptrno = cd.ptrno
        and i.trno = cd.trno and i.line = cd.line where i.isusd = 0 and date(i.depodate) between '" . $start . "' and '" . $end . "' and  cd.pheadid = " . $agents[$a]->clientid);
      $upcomm = $this->coreFunctions->datareader("select sum(ifnull(cd.pheadcomm,0)) as value from commdetails as cd left join incentives as i on i.usdline = cd.usdline 
        where i.isusd = 1 and date(i.depodate) between '" . $start . "' and '" . $end . "' and cd.pheadid = " . $agents[$a]->clientid);
      $totals = $comm + $ocomm + $pcomm + $ucomm + $uocomm + $upcomm;


      if ($grp != $val->salesgroupid) {
        if ($grp != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($this->coreFunctions->datareader("select groupname as value from salesgroup where line = " . $grp), '125', null, false, $border, 'TBRL', 'L', $font, $fontsize, '', '', '3px');
          $str .= $this->reporter->col(number_format($stotals, $price_deci), '125', null, false, $border, 'TBRL', 'R', $font, $fontsize, '', '', '3px');
          $str .= $this->reporter->endrow();
          $stotals = 0;
        }
      }

      $stotals += $totals;
      $total += $totals;
      $grp = $val->salesgroupid;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->coreFunctions->datareader("select groupname as value from salesgroup where line = " . $grp), '125', null, false, $border, 'TBRL', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($stotals, $price_deci), '125', null, false, $border, 'TBRL', 'R', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL:', '125', null, false, $border, 'TBRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($total, $price_deci), '125', null, false, $border, 'TBRL', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  // public function subTotal_per_sales_group($subtotal, $decimal) {
  //   $str = "";
  //   $font =  "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";

  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('','100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->col('','100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //   $str .= $this->reporter->col('SUB TOTAL:','100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->col(number_format($subtotal, $decimal), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
  //   $str .= $this->reporter->endrow();

  //   return $str;
  // }

} //end class
