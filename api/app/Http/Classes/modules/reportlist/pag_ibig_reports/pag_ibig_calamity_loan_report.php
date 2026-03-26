<?php

namespace App\Http\Classes\modules\reportlist\pag_ibig_reports;

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

class pag_ibig_calamity_loan_report
{
  public $modulename = 'Pag Ibig Calamity Loan Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['month', 'year'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    month(now()) as month,
    left(now(),4) as year,
    '' as deptrep
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $filter   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter .= " and emp.deptid = $deptid";
    }

    if ($divid != 0) {
      if ($config['params']['companyid'] == 58) { //cdo
        $filter .= " and emp.contricompid = $divid";
      } else {
        $filter .= " and emp.divid = $divid";
      }
    }


    $emplvl = $this->othersClass->checksecuritylevel($config);

    // $query = "
    //   select g.empid,g.acnoid,g.clientname,g.client,g.hdmf,g.ee,g.er,g.ec,g.cr,ss.amt,sum(st.db-st.cr),ss.amt+(sum(st.db-st.cr)) as bal
    //   from (
    //     select emp.empid,p.acnoid,e.clientname,e.client,emp.hdmf, sum(p.ee) as ee,sum(p.er) as er,sum(p.ec) as ec,sum(p.cr) as cr
    //     from
    //     (
    //       select pt.empid,pt.acnoid,pt.cr as ee,0 as er,0 as ec,0 as cr
    //       from paytrancurrent as pt 
    //       left join paccount as pa on pa.line=pt.acnoid
    //       where pa.code ='PT93' 
    //       and (year(pt.dateid) < '" . $year . "' OR (year(pt.dateid) = '" . $year . "' AND month(pt.dateid) <= '" . $month . "'))
        
    //       union all
    //       select pt.empid,pt.acnoid,pt.cr as ee,0 as er,0 as ec,0 as cr
    //       from paytranhistory as pt 
    //       left join paccount as pa on pa.line=pt.acnoid
    //       where pa.code = 'PT93' 
    //       and (year(pt.dateid) < '" . $year . "' OR (year(pt.dateid) = '" . $year . "' AND month(pt.dateid) <= '" . $month . "'))
        
          
    //     ) as p
    //     left join client as e on e.clientid = p.empid
    //     left join employee as emp on emp.empid=p.empid
    //     where e.client <>'' and p.ee<>0 and emp.level in $emplvl $filter
    //     group by emp.empid,p.acnoid,e.clientname,e.client,emp.hdmf
    //   ) as g
      
    //   left join standardsetup as ss on ss.empid=g.empid and ss.acnoid=g.acnoid
    //   left join standardtrans as st on st.trno=ss.trno
    //   where (year(st.dateid) < '" . $year . "' OR (year(st.dateid) = '" . $year . "' AND month(st.dateid) <= '" . $month . "'))
    //   group by g.empid,g.acnoid,g.clientname,g.client,g.hdmf,g.ee,g.er,g.ec,g.cr,ss.amt
    // ";
    $query = "select ss.docno, 
    ss.empid,
    e.client,e.clientname,emp.hdmf,
    ss.acnoid,
    sum(st.cr) as amount,ss.balance as bal
    from standardsetup as ss
    left join standardtrans as st on st.trno=ss.trno
    left join paccount as pa on pa.line=ss.acnoid
    left join batch as b on b.line=st.batchid
    left join client as e on e.clientid=ss.empid
    left join employee as emp on emp.empid=ss.empid
    where pa.code = 'PT93' and e.client <> '' and st.cr <> 0 
    and year(b.dateid) = '" . $year . "' AND month(b.dateid) = '" . $month . "' and emp.level in $emplvl $filter
    group by 
    ss.docno, ss.empid,e.client,e.clientname,emp.hdmf,ss.acnoid,ss.balance
    ";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAG-IBIG CALAMITY LOAN REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    if ($divid == 0) {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $monthNum = $month;
    $monthname = date("F", mktime(0, 0, 0, $monthNum, 10));
    $str .= $this->reporter->col('For the Month of ' . strtoupper($monthname) . ' and Year ' . strtoupper($year), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('H D M F #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('A M O U N T', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('B A L A N C E', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hdmf, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $Tot = $Tot + $data->amount;


      $str .= $this->reporter->endrow();
      $Grandtot = $Grandtot + $Tot;
      $Tot = 0;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }



    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class