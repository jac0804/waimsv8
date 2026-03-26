<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class lead_report
{
  public $modulename = 'Lead Status Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-365) as start,
    left(now(),10) as end
  ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid == 12) {
      return $this->reportDefaultLayout_SUMMARIZEDnew($config);
    } else {
      return $this->reportDefaultLayout($config);
    }
  }

  public function reportDefault($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];

    if ($companyid == 10 || $companyid == 12) {

      //   $query = "
      // select
      // att.line,
      // att.clientid,
      // case
      //   when att.clientid = 0 then att.companyname
      //   else cl.clientname
      // end as companyname,
      // att.contactid,
      // case
      //   when att.contactid = 0 then att.contactname 
      //   else concat(cp.lname,', ',cp.fname,' ',cp.mname)
      // end as contactname,
      // att.contactno,
      // att.department,
      // att.designation,
      // att.email,
      // left(att.dateid, 10) as leaddate,
      // (case 
      //   when att.isexhibit = 1 then ex.title
      //   when att.isseminar = 1 then sem.title
      //   else src.title
      //  end) as srctitle,
      // att.clientstatus as clstat,
      // ag.clientname as salesperson,
      // ophead.trno,att.mrktremarks,att.saleremarks,att.status
      // from attendee as att
      // left join ophead as ophead on ophead.participantid = att.line
      // left join source as src on att.exhibitid = src.line and att.issource =1
      // left join exhibit as ex on att.exhibitid = ex.line  and att.isexhibit =1
      // left join seminar as sem on att.exhibitid = sem.line  and att.isseminar =1
      // left join client as cl on cl.clientid = att.clientid
      // left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
      // left join client as ag on ag.clientid = att.salesid
      // where date(att.dateid) between '" . $start . "' and '" . $end . "' ";

      $query = " select  att.optrno,ifnull(ophead.trno,0) as trno,ophead.docno,left(ifnull(cl.createdate,''),10) as createdate,cl.client as customerid,
        cl.clientname as customername,
        case when att.contactid = 0 then att.contactname else concat(cp.lname,', ',cp.fname,' ',cp.mname) end as contactname,
        att.contactno, left(att.dateid,10) as dateid,att.department,att.designation,att.email,
        att.mrktremarks,att.status as actstat,att.isinactive,ag.clientname as salesperson,
         (case 
         when att.isexhibit = 1 then ex.title
         when att.isseminar = 1 then sem.title
         else src.title end) as source,att.saleremarks,
        0 as ext, 'Unposted' as status
        from attendee as att
        left join (select trno,participantid,docno,createdate,source,sourceid from ophead where participantid <> 0 union all select trno, participantid,docno,createdate,source,sourceid from hophead where participantid<>0)  as ophead on ophead.participantid = att.line
        left join client as cl on cl.clientid = att.clientid
        left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
        left join client as ag on ag.clientid = att.salesid
        left join source as src on att.exhibitid = src.line and att.issource =1
        left join exhibit as ex on att.exhibitid = ex.line  and att.isexhibit =1
        left join seminar as sem on att.exhibitid = sem.line  and att.isseminar =1
        where date(att.dateid) between '" . $start . "' and '" . $end . "'  ";
        //and  ophead.docno is not null
    } else {
      $query = "
    select
    att.line,
    att.clientid,
    case
      when att.clientid = 0 then att.companyname
      else cl.clientname
    end as companyname,
    att.contactid,
    case
      when att.contactid = 0 then att.contactname 
      else concat(cp.lname,', ',cp.fname,' ',cp.mname)
    end as contactname,
    att.contactno,
    att.department,
    att.designation,
    att.email,
    left(att.dateid, 10) as leaddate,
    (case 
      when att.isexhibit = 1 then ex.title
      when att.isseminar = 1 then sem.title
      else src.title
     end) as srctitle,
    att.clientstatus as clstat,
    ag.clientname as salesperson,
    ophead.trno,att.mrktremarks,att.saleremarks,att.status
    from attendee as att
    left join (select trno,participantid from ophead where participantid <> 0 union all select trno, participantid from hophead where participantid<>0)  as ophead on ophead.participantid = att.line
    left join source as src on att.exhibitid = src.line and att.issource =1
    left join exhibit as ex on att.exhibitid = ex.line  and att.isexhibit =1
    left join seminar as sem on att.exhibitid = sem.line  and att.isseminar =1
    left join client as cl on cl.clientid = att.clientid
    left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
    left join client as ag on ag.clientid = att.salesid
    where date(att.dateid) between '" . $start . "' and '" . $end . "' ";
    }

    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $header = $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '65', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LBT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LEADS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'LBT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'RBT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Client Status', '65', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Company Name', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact Name', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Designation', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Email', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '120', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Source', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '100', null, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '120', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Mrkt Rem.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sale Rem.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 48;
    $page = 50;
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $qry = "select trno, line, left(dateid, 10) as dateid, starttime, endtime, rem, calltype, contact
      from calllogs
      where trno = '" . $data->trno . "'
      order by dateid DESC LIMIT 1";
        $calllogs = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clstat, '65', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->companyname, '100', null, false, $border, '', '', $font, $fontsize,  '', '', '', '', 0, 'max-width:100px;overflow-wrap: break-word;');
        // $str .= $this->reporter->col($data->customername, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '', 0, 'max-width:110px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
        $str .= $this->reporter->col($data->companyname, '100', null, false, $border, '', '', $font, $fontsize,  '', '', '', '', 0, 'max-width:100px;overflow-wrap: break-word;');
        $str .= $this->reporter->col($data->contactname, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->contactno, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->department, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->designation, '80', null, false, $border, '', '', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->email, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->leaddate, '120', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->srctitle, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->salesperson, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($calllogs[0]->dateid) ? $calllogs[0]->dateid : "", '120', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($calllogs[0]->rem) ? $calllogs[0]->rem : "", '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->mrktremarks, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->saleremarks == "" ? $calllogs[0]->rem : $data->saleremarks, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);

          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZEDnew($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;
    $str = '';
    $layoutsize = '1500';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "8";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheadernew($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;
    $amount = 0;
    $docno='';

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $docno='';
        $salesremarks = $data->saleremarks;
        $inqstat = "";
       
        if ($data->optrno  != 0) {
          $amount = $this->coreFunctions->datareader("select sum(ext) as value  from (select ifnull(ext,0) as ext from opstock where trno = ? union all select ifnull(ext,0) as ext from hopstock where trno = ?) as a", [$data->optrno,$data->optrno],'',true);

          $qry1 = "select docno,doc,trno from transnum where trno = " . $data->optrno;

          $xdata1 = $this->coreFunctions->opentable($qry1);
          $docno = $xdata1[0]->docno;
          switch ($xdata1[0]->doc) {
            case 'QS':
              $salesremarks = $this->coreFunctions->datareader("select ifnull(rem,'') as value from 
              (select rem,line from qscalllogs where trno = ? union all select rem,line from hqscalllogs where trno = ?) as a 
              order by line desc limit 1", [$data->optrno, $data->optrno]);

              $inqstatus = $this->coreFunctions->datareader("select ifnull(status,'') as value from 
              (select status,line from qscalllogs where trno = ? union all select status,line from hqscalllogs where trno = ?) as a 
              order by line desc limit 1", [$data->optrno, $data->optrno]);
              break;
            case 'AO':
            case 'SQ':
              $isposted = $this->othersClass->isposted2($data->optrno, 'transnum');
              if ($isposted) {
                $salesremarks = 'PROCESSED';
              } 
              break;
            case 'OP':
              $salesremarks= $this->coreFunctions->datareader("select ifnull(rem,'') as value  from calllogs where trno = ?  order by line desc limit 1", [$data->optrno]);
              $inqstatus = $this->coreFunctions->datareader("select ifnull(status,'') as value  from calllogs where trno = ?  order by line desc limit 1", [$data->optrno]);
              break;
            default:
              $salesremarks= '';
              $inqstatus = '';
              break;
          }
        }

      //   $qry = "select trno, line, left(dateid, 10) as dateid, starttime, endtime, rem, calltype, contact
      // from calllogs
      // where trno = '" . $data->trno . "'
      // order by dateid DESC LIMIT 1";
      //   $calllogs = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->addline();
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createdate, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customerid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customername, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '', 0, 'max-width:110px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
        $str .= $this->reporter->col($data->contactname, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->contactno, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->department, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->designation, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->email, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->mrktremarks, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->actstat, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->isinactive == 0 ? "Active" : "Inactive", '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->salesperson, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->source, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($salesremarks, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($amount, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        //$str .= $this->reporter->col($data->status, '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $totalext = $totalext + $amount;
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheadernew($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '', '', 0, 'max-width:70px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheadernew($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Create Date', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer ID', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact Name', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact#', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Designation', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Email', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Marketing Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Activity Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Lead Status', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Source', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    //$str .= $this->reporter->col('Status', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $layoutsize = '1500';

    $str = '';


    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Lead Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class