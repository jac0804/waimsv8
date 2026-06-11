<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class monthly_summary_of_output_tax
{
  public $modulename = 'Monthly Summary of Output Tax';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];



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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 56) {  //homeworks
      data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }

    $fields = ['dateid', 'due', 'dcentername'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'project.required', false);
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'project.label', 'Item Group');
        break;
      case 56: //homeworks
        array_push($fields, 'dclientname');
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 'default' as print,
        adddate(left(now(),10),-360) as dateid,
        adddate(left(now(),10),1) as due,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname ";
    }

    switch ($companyid) {
      case 10:
      case 12: //afti, afti usd
        $paramstr .= ", '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname ";
        break;
      case 56: //homeworks
        $paramstr .= ", '0' as clientid, '' as client, '' as clientname, '' as dclientname ";
        break;
    }


    return $this->coreFunctions->opentable($paramstr);
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

  /// --- > TO BE FOLLOW

  public function default_query($filters)
  {
    $companyid = $filters['params']['companyid'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $company = $filters['params']['companyid'];
    $ispos =  $this->companysetup->getispos($filters['params']);
    $printtype = $filters['params']['dataparams']['print'];
    $filter = "";
    $filter1 = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti
      $prjid = $filters['params']['dataparams']['project'];
      $deptid = $filters['params']['dataparams']['ddeptname'];
      $project = $filters['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $filters['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and stock.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } elseif ($companyid == 56) { //homeworks
      $client    = $filters['params']['dataparams']['client'];
      $clientid = $filters['params']['dataparams']['clientid'];
      if ($client != "") {
        $filter1 .= " and client.clientid = '$clientid'";
      }
    } else {
      $filter1 .= "";
    }

    $addfield1 = '';
    $addfield2 = '';
    if ($companyid == 32) { //3m
      $addfield1 = ',client.brgy, client.area';
      $addfield2 = ',brgy,area';
    }

    $headtax = " and head.tax <> 0 ";
    if ($ispos) {
      $headtax = "";
    }


    $isvatexsales = $this->companysetup->getvatexsales($filters['params']);
    $crnet = ", ((sum(stock.ext)/1.12) * 0.12) as 'cr', (sum(stock.ext)/1.12) as 'net'";
    $crnet2 = ", (((sum(stock.ext)/1.12) * 0.12)*-1) as 'cr', ((sum(stock.ext)/1.12)*-1) as 'net'";
    if ($isvatexsales) {
      $crnet = ", (sum(stock.ext) * 0.12) as 'cr', sum(stock.ext) as 'net'";
      $crnet2 = ", ((sum(stock.ext) * 0.12)*-1) as 'cr', (sum(stock.ext)*-1) as 'net'";
    }

    switch ($printtype) {
      case 'default':
      case 'excel':
        $query = "select dateid,clientname,tin,addr,docno,db,cr,net,billingaddress  " . $addfield2 . "
              from (select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, 
                           client.tin, client.addr,head.docno ,sum(stock.ext) as 'db' " . $crnet . ", 
                           concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',
                           bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress 
                           " . $addfield1 . "
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    left join billingaddr as bill on bill.line = head.billid and bill.clientid = client.clientid
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc in ('SJ','MJ','SD','SE','SF','AI')  and
                          date(head.dateid) between '" . $start . "' and '" . $end . "' $headtax $filter $filter1
                    group by head.dateid, clientname, tin, addr, docno, billingaddress " . $addfield2 . " 
                    union all
                    select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, 
                           client.addr,head.docno ,(sum(stock.ext)*-1) as 'db' " . $crnet2 . ", 
                           concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',
                           bill.country,' ',bill.zipcode) as billingaddress " . $addfield1 . "
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    left join billingaddr as bill on bill.line = head.billid and bill.clientid = client.clientid
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc = 'CM'  and date(head.dateid) 
                          between '" . $start . "' and '" . $end . "' $headtax $filter $filter1
                    group by head.dateid, clientname, tin, addr, docno, billingaddress " . $addfield2 . ") as a
              order by dateid,docno";
        break;
      case 'CSV': //HOMEWORKS 
        $query = "select branchname as `BRANCHNAME`,  dateid as `DATE`, client as `CODE`,   clientname as `CUSTOMER`,tin as `TIN`,addr as `ADDRESS`,docno as `DOCNO`,db as `SALES`,cr as `VATAMT`,net as `NETSALES`  
              from (select if(center.name !='',center.name, br.clientname) as branchname,  date_format(dateid,'%m-%d-%Y') as dateid, client.client, client.clientname, 
                           client.tin, client.addr,head.docno ,sum(stock.ext) as 'db' " . $crnet . "
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    left join billingaddr as bill on bill.line = head.billid and bill.clientid = client.clientid
                    left join cntnum on cntnum.trno=head.trno
                    left join center on center.code=cntnum.center
                    left join client as br on br.clientid=head.branch and center.branchid
                    where head.doc in ('SJ','MJ','SD','SE','SF','AI')  and
                          date(head.dateid) between '" . $start . "' and '" . $end . "' $headtax $filter $filter1
                    group by head.dateid, client.clientname, tin, addr, docno,center.name, client.client,br.clientname
                    union all
                    select if(center.name !='',center.name, br.clientname) as branchname, date_format(dateid,'%m-%d-%Y') as dateid, client.client, client.clientname, client.tin, 
                           client.addr,head.docno ,(sum(stock.ext)*-1) as 'db' " . $crnet2 . "
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join client on head.clientid = client.clientid
                    left join billingaddr as bill on bill.line = head.billid and bill.clientid = client.clientid
                    left join cntnum on cntnum.trno=head.trno
                    left join center on center.code=cntnum.center
                    left join client as br on br.clientid=head.branch and center.branchid
                    where head.doc = 'CM'  and date(head.dateid) 
                          between '" . $start . "' and '" . $end . "' $headtax $filter $filter1
                    group by head.dateid, client.clientname, tin, addr, docno,center.name, client.client,br.clientname ) as a
              order by dateid,docno";


        break;
    }


    // ALTERNATIVE WAY
    // $query=  "select DATE_FORMAT(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr, head.docno,
    //           sum(db) as 'db' ,sum(cr) as 'cr', (sum(db)-sum(cr)) as 'net' FROM glhead AS head
    //           LEFT JOIN gldetail AS detail ON head.trno = detail.trno
    //           LEFT JOIN CLIENT ON head.clientid = client.clientid
    //           LEFT JOIN coa ON coa.acnoid = detail.acnoid
    //           LEFT JOIN cntnum on head.trno = cntnum.trno
    //           WHERE head.doc = 'SJ' and head.vattype = 'VATABLE' and coa.alias IN ('AR1','TX2') 
    //           ".$filter."  and head.dateid between '".$start."' and '".$end."'
    //           group by head.dateid, client, docno";

    $data = $this->coreFunctions->opentable($query);

    return $data;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $reportdata =  $this->DEFAULT_OUTPUT_TAX_LAYOUT($result, $config);
    return $reportdata;
  }

  private function DEFAULT_OUTPUT_TAX_HEADER($params)
  {

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $center = $params['params']['dataparams']['center'];

    $companyid = $params['params']['companyid'];
    $ccenter = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid";

    if ($companyid == 10 || $companyid == 12) { //afti
      $dept   = $params['params']['dataparams']['ddeptname'];
      $proj   = $params['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $params['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }


    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Output Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '900', null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('Customer', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
    switch ($companyid) {
      case 10:
      case 12: //afti
        $str .= $this->reporter->col('Address', '200', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Doc #', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Tax Base', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('VAT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('Barangay', '80', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Area', '80', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Address', '150', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Doc #', '80', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Total', '80', '', false, $border, 'TB', 'C', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Output Tax', '80', '', false, $border, 'TB', 'C', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Net of VAT', '80', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Address', '200', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Doc #', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Total', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Output Tax', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('Net of VAT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'B', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_OUTPUT_TAX_LAYOUT($data, $params)
  {

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = $page = 40;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }
    $group = $str = '';

    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array) $data);
    $cnt1 = 0;


    $str .= $this->reporter->beginreport('1000');

    #header here
    $str .= $this->DEFAULT_OUTPUT_TAX_HEADER($params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;



      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->clientname, '125', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12: //afti
          $str .= $this->reporter->col($data_->billingaddress, '200', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');

          $str .= $this->reporter->col(str_replace("DR", "SI", $data_->docno), '100', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '100', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '100', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          break;
        case 32: //3m
          $str .= $this->reporter->col($data_->brgy, '80', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->area, '80', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->addr, '150', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '80', '', false, $border, '', 'L', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->db, $decimal_currency), '80', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '80', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '80', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', '', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->addr, '200', '', false, $border, '', '', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '100', '', false, $border, '', '', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->db, $decimal_currency), '100', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '100', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '100', '', false, $border, '', 'R', $font, $fontsize,  '', '', '', '');
          break;
      }
      $str .= $this->reporter->endrow();
      $dateid = $data_->dateid;
      $a += $data_->db;
      $b += $data_->cr;
      $c += $data_->net;
      $totala = $totala + $data_->db;
      $totalb = $totalb + $data_->cr;
      $totalc = $totalc + $data_->net;


      if ($cnt == $cnt1) {
        if ($data_->dateid == '') {
          $group = 'NO DATE';
        } else {
          $str .= $this->reporter->startrow();
          #subtotal here
          $str .= $this->DEFAULT_OUTPUT_TAX_SUBTOTAL($a, $b, $c, $companyid, $params);
          #subtotal end

          $str .= $this->reporter->addline();

          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->dateid;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->endrow();
      } # end if

    } # end for loop


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
    switch ($companyid) {
      case 10:
      case 12: //afti
        $str .= $this->reporter->col('', '200', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('', '80', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '80', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '80', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '80', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalb, 2), '80', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '80', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '200', '', false, $border, 'T', 'C', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'R', $font, $fontsize,  'B', '', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_OUTPUT_TAX_SUBTOTAL($a, $b, $c, $companyid, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
      switch ($companyid) {
        case 10:
        case 12: //afti
          $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'R', $font, $fontsize,  'i', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          break;
        case 32: //3m
          $str .= $this->reporter->col('', '80', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', false, '1px dashed', 'T', 'R', $font, $fontsize,  'i', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'R', $font, $fontsize,  'i', '', '', '');
          break;
      }
    } else {

      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
      switch ($companyid) {
        case 10:
        case 12: //afti
          $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '100', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          break;
        case 32: //3m
          $str .= $this->reporter->col('', '80', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '80', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '80', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '80', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '80', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '100', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, $fontsize,  'B', '', '', '');
          break;
      }
    } #end if

    $str .= $this->reporter->endrow();
    return $str;
  }


  public function reportdatacsv($config)
  {
    $data = $this->default_query($config);

    $total_total = 0;
    $total_tax = 0;
    $total_net = 0;

    foreach ($data as $row => $value) {

      $total_total += $value->SALES;
      $total_tax += $value->VATAMT;
      $total_net += $value->NETSALES;

      $value->SALES = number_format($value->SALES, 2);
      $value->VATAMT = number_format($value->VATAMT, 2);
      $value->NETSALES = number_format($value->NETSALES, 2);
    }

    // gumawa ng total row
    $data[] = [
      'BRANCHNAME' => '',
      'DATE' => '',
      'CODE' => '',
      'CUSTOMER' => '',
      'TIN' => '',
      'ADDRESS' => '',
      'DOCNO' => 'TOTAL',
      'SALES' => number_format($total_total, 2),
      'VATAMT' => number_format($total_tax, 2),
      'NETSALES' => number_format($total_net, 2)
    ];

    $status = true;
    $msg = 'Generating CSV successfully';

    if (empty($data)) {
      $status = false;
      $msg = 'No data Found';
    }

    return [
      'status' => $status,
      'msg' => $msg,
      'data' => $data,
      'params' => $this->reportParams,
      'name' => 'Monthly_Summary_of_Output_Tax'
    ];
  }
}//end class
