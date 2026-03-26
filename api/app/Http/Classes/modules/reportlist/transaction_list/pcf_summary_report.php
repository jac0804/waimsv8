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

class pcf_summary_report
{
  public $modulename = 'PCF Summary Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];

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
    $user = $config['params']['user'];
    $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '$userid' as userid,
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

    return $this->reportDefaultLayout($config);
  }

  private function getExpensesList($date1,$date2)
  {
    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $query = "select distinct exp from (select  req.category as exp,req.sortline
    from pxchecking as chk  left join pxhead as head on head.trno=chk.trno
    left join reqcategory as req on req.line=chk.expenseid where req.line <> 94 and date(head.dateid) between '$date1' and '$date2'
    
    union all
    select  req.category,req.sortline as exp
    from hpxchecking as chk left join hpxhead as head on head.trno=chk.trno
    left join reqcategory as req on req.line=chk.expenseid where req.line <> 94  and date(head.dateid) between '$date1' and '$date2') as a order by sortline";
    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }

  private function checkProdHeadAccess($userid)
  {
    $qry = "select count(line) as value from projectmasterfile where agentid = $userid";
    
    return $this->coreFunctions->datareader($qry);
  }

  public function reportDefault($config)
  {
    // QUERY
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    
    $admin = $config['params']['adminid'];

    if($admin==0){
      $user = $config['params']['user'];
      $userid = $config['params']['dataparams']['userid'];
    }else{
      $user = $config['params']['user'];
      $userid = $admin;
    }

    $pcfAdminAccess = $this->othersClass->checkAccess($user, 5389);

    // var_dump($pcfAdminAccess);
    //if 0, no access
    // $prodHeadAccess = $this->checkProdHeadAccess($userid);
    $prodHeadAccess = $this->othersClass->checkAccess($user, 5413);
    // var_dump($prodHeadAccess);
    $salesHeadAccess = $this->othersClass->checkAccess($user, 5554);
    // var_dump($salesHeadAccess);
    
    $addedJoins = "";
    $haddedJoins = "";
    $accessFilter = "";
    $extcol="";
    $pxcjoin="";
    $hpxcjoin="";
    // $pxcheckinjoin="left join pxchecking as chk on chk.trno=head.trno";
    // $pxcheckinhjoin="left join hpxchecking as chk on chk.trno=head.trno";
    if($pcfAdminAccess){//if admin, access all
      // $str = $this->pcf_admin_pdf($config, $data);
      $addedJoins = " left join (select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from pxchecking chk
           left join reqcategory req on req.line = chk.expenseid
           left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno

           left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from pxstock
           group by trno) s on s.trno = head.trno";
      $haddedJoins = "left join ( select req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from hpxchecking chk
           left join reqcategory req on req.line = chk.expenseid
           left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
           left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from hpxstock
           group by trno) s on s.trno = head.trno";
    //  $extcol=",sum(s.ext) as ext,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(d.budget) as budget,sum(d.sjamount) as sjamount";
     $extcol=", ifnull(s.ext, 0) as ext, ifnull(s.totaltp, 0) as totaltp, ifnull(s.totalsrp, 0) as totalsrp, sum(d.budget) as budget, sum(d.sjamount) as sjamount";

    }else{
      if($prodHeadAccess>0)//if may access, all PCF with items under item group of user
      {

        $groupid = $this->coreFunctions->datareader('select line as value from projectmasterfile where agentid=?', [$userid]); //kinuha ang itemgroup group ng user

        $addedJoins = "
         left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from pxchecking chk
           left join reqcategory req on req.line = chk.expenseid
           left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
        
        left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from pxstock
           group by trno) s on s.trno = head.trno

        left join item as i on i.itemid=stock.itemid
        left join projectmasterfile as p on p.line=i.projectid";

        $haddedJoins = "
        left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from hpxchecking chk
        left join reqcategory req on req.line = chk.expenseid
        left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
        left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from hpxstock
           group by trno) s on s.trno = head.trno

        left join item as i on i.itemid=stock.itemid
        left join projectmasterfile as p on p.line=i.projectid";

        $accessFilter = " and p.line = '$groupid'";
        // $extcol=",sum(s.ext) as ext,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(d.budget) as budget,sum(d.sjamount) as sjamount";
         $extcol=", ifnull(s.ext, 0) as ext, ifnull(s.totaltp, 0) as totaltp, ifnull(s.totalsrp, 0) as totalsrp, sum(d.budget) as budget, sum(d.sjamount) as sjamount";
      }elseif($salesHeadAccess>0){ //sales head access, lalabas lahat ng transactions ng mga user under ng Sales Group nya
         
        $salesgid = $this->coreFunctions->datareader('select salesgroupid as value from client where clientid=?', [$userid]); //kinuha ang sales group ng user

        $addedJoins = "
         left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from pxchecking chk
           left join reqcategory req on req.line = chk.expenseid
           left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
        
           left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from pxstock
           group by trno) s on s.trno = head.trno

          left join client as cl on cl.clientid=head.clientid
          left join client as agn on agn.clientid=head.agentid";

        $haddedJoins = "
          left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
          from hpxchecking chk
          left join reqcategory req on req.line = chk.expenseid
          left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
          group by chk.trno,req.category) d on d.trno = head.trno
          left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
          from hpxstock
          group by trno) s on s.trno = head.trno

          left join client as cl on cl.clientid=head.clientid
          left join client as agn on agn.clientid=head.agentid";

        $accessFilter = " and (cl.salesgroupid='$salesgid' or agn.salesgroupid='$salesgid')";
         $extcol=", ifnull(s.ext, 0) as ext, ifnull(s.totaltp, 0) as totaltp, ifnull(s.totalsrp, 0) as totalsrp, sum(d.budget) as budget, sum(d.sjamount) as sjamount";

      }else{//no access to product head, can only view transactions made by user
        $addedJoins = "
         left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from pxchecking chk 
           left join reqcategory req on req.line = chk.expenseid
           left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
        
        left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext
           from pxstock
           group by trno) s on s.trno = head.trno

        left join item as i on i.itemid=stock.itemid
        left join projectmasterfile as p on p.line=i.projectid";

        $haddedJoins = "
        left join ( select  req.category,chk.trno,sum(chk.actual) as actual,sum(chk.budget) as budget,sum(sjexp.amount) as sjamount
           from hpxchecking chk
        left join reqcategory req on req.line = chk.expenseid
        left join sjexp on sjexp.pxtrno=chk.trno and sjexp.pxline=chk.line
           group by chk.trno,req.category) d on d.trno = head.trno
        left join ( select trno,sum(totaltp) as totaltp, sum(totalsrp) as totalsrp,sum(ext) as ext 
           from hpxstock
           group by trno) s on s.trno = head.trno

        left join item as i on i.itemid=stock.itemid
        left join projectmasterfile as p on p.line=i.projectid";
        $accessFilter = " and head.createby = '$user'";
      }
    }

    // 5413
    $expenses = $this->getExpensesList($start,$end);
    $expensesField = '';
    
    if(!empty($expenses)){
      foreach ($expenses as $key => $uniq) {
        $expensesField .= "sum(case when d.category = '".$uniq->exp."' then (case ifnull(d.sjamount,0) when 0 then d.actual end) else 0 end) as '$uniq->exp',";
          if($prodHeadAccess<=0 && $salesHeadAccess<=0  && $pcfAdminAccess <=0){
          $expensesField .= "sum(case when req.category = '".$uniq->exp."' then (case ifnull(d.sjamount,0) when 0 then d.actual end) else 0 end) as '$uniq->exp',";
          }
      }
    }

   
    
    $selectFields = "
      select ag.clientname as salesperson,head.clientname as company,head.poref as pono,head.potrno,head.trno,
      $expensesField
      head.dtcno as refno,head.oandausdphp,so.docno  as sodocno";
      $grp="";
      if($prodHeadAccess<=0 && $salesHeadAccess<=0 && $pcfAdminAccess <=0){
      $leftJoins = "left join client as ag on ag.clientid=head.agentid
       left join reqcategory as req on req.line=chk.expenseid ";
      $pxcjoin="left join pxchecking as chk on chk.trno=head.trno 
                left join sjexp as sj on sj.pxtrno=chk.trno and sj.pxline=chk.line";
      $hpxcjoin="left join hpxchecking as chk on chk.trno=head.trno 
                 left join sjexp as sj on sj.pxtrno=chk.trno and sj.pxline=chk.expenseid";
      $grp="";
     }else{
      $leftJoins = "left join client as ag on ag.clientid=head.agentid";
      $grp=", s.ext, s.totaltp,s.totalsrp";
     }
     
    $grouping = "group by ag.clientname,head.clientname,head.poref,head.dtcno,head.potrno,head.oandausdphp,head.trno,so.docno ";
    $main_qry = "
      $selectFields $extcol ,'U' as stat
      from pxhead as head
      left join pxstock as stock on stock.trno = head.trno
      left join hqshead as po on po.trno=head.potrno
      left join hsqhead as so on so.trno=po.sotrno
      $pxcjoin
      $leftJoins
      $addedJoins
      where date(head.dateid) between '" . $start . "' and '" . $end . "' $accessFilter
      $grouping $grp

      union all

      $selectFields $extcol ,'P' as stat
      from hpxhead as head
      left join hpxstock as stock on stock.trno = head.trno
      left join hqshead as po on po.trno=head.potrno
      left join hsqhead as so on so.trno=po.sotrno
      $hpxcjoin
      $leftJoins
      $haddedJoins
      where date(head.dateid) between '" . $start . "' and '" . $end . "' $accessFilter
      $grouping  $grp

    ";
    // var_dump($main_qry);
    $this->coreFunctions->LogConsole($main_qry);
    return $this->coreFunctions->opentable($main_qry);
  }

  private function displayHeader($config,$validExpenses)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    // $layoutsize = '1200';
    //  $expenses = $this->getExpensesList();
    // $exp= $this->count_exp($config, $validExpenses);
    // $layoutsize = 1500 + ($exp * 100);
     $layoutsize = 1500 + (count($validExpenses) * 100);


    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $header = $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PCF Summary Report', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';


    $user = $config['params']['user'];
    $userid = $config['params']['dataparams']['userid'];

    $pcfAdminAccess = $this->othersClass->checkAccess($user, 5389);
    
     $salesHeadAccess = $this->othersClass->checkAccess($user, 5554);
    
    $prodHeadAccess = $this->othersClass->checkAccess($user, 5413);
    
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();


    // $str .= $this->reporter->col('DTC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('SO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('PO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('PO Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('Sales Person', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('Company', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    
    
    // foreach ($expenses as $key => $uniq) {
    //   $str .= $this->reporter->col(strtoupper($uniq->exp), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    // }
    // $str .= $this->reporter->col('Duty 2%', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    if($pcfAdminAccess){
       $str .= $this->reporter->col('DTC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('SO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('PO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');       
       $str .= $this->reporter->col('Sales Person', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('Company', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('PO Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('TP', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
       $str .= $this->reporter->col('Duty 2%', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    
      foreach ($validExpenses as $uniq){
        $str .= $this->reporter->col(strtoupper($uniq->exp), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('Total Actual Expense', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    
      $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Initial Margin', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Actual Margin', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Delta', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SI#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DR#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }elseif($prodHeadAccess>0 && $salesHeadAccess >0 ){


      $str .= $this->reporter->col('DTC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PO Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sales Person', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Company', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      
       $str .= $this->reporter->col('Duty 2%', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
     foreach ($validExpenses as $uniq){
        $str .= $this->reporter->col(strtoupper($uniq->exp), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      }
    
      $str .= $this->reporter->col('Total Actual Expense', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SI#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DR#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }else{


      $str .= $this->reporter->col('DTC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PO#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PO Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sales Person', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Company', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      
       $str .= $this->reporter->col('Duty 2%', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
     foreach ($validExpenses as $uniq){
        $str .= $this->reporter->col(strtoupper($uniq->exp), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('Total Actual Expense', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DR#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }







    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_org($config)
  {
    $result = $this->reportDefault($config);

    $count = 48;
    $page = 50;
    // $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    $totaltps=0;
    $totalsrps =0;
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    

    $expenses = $this->getExpensesList($start,$end);
    $exp= $this->count_exp($config, $expenses);
    $layoutsize = 1500 + ($exp * 100);
    $str .= $this->reporter->beginreport($layoutsize);
    

    $str .= $this->displayHeader($config,$expenses);

    $user = $config['params']['user'];
    $userid = $config['params']['dataparams']['userid'];
    
    $pcfAdminAccess = $this->othersClass->checkAccess($user, 5389);
    
    $salesHeadAccess = $this->othersClass->checkAccess($user, 5554);
    
    $prodHeadAccess = $this->othersClass->checkAccess($user, 5413);

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if(isset($data->potrno)){
          
          $poamt = $this->coreFunctions->datareader("select sum(g.amt) as value from (
            select qss.trno,sum(qss.ext) as amt 
            from qsstock as qss 
            group by qss.trno
            union all
            select qss.trno,sum(qss.ext) as amt 
            from hqsstock as qss
            group by qss.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from qtstock as qts 
            group by qts.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from hqtstock as qts 
            group by qts.trno
          ) as g where g.trno = $data->potrno");
        }else{
          $poamt = 0;
        }

        $str .= $this->reporter->addline();
          
        $totalexp = 0;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(isset($data->refno) ? $data->refno : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->sodocno) ? $data->sodocno : '', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->pono) ? $data->pono : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($poamt) ? number_format($poamt,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->salesperson) ? $data->salesperson : '', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->company) ? $data->company : '', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        

        if(!empty($expenses)){
          foreach ($expenses as $key => $uniq) {
            $expstring = $uniq->exp;
            $str .= $this->reporter->col(isset($data->$expstring) ? number_format($data->$expstring,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $totalexp += $data->$expstring;
          }
        }

        $duty = $this->coreFunctions->datareader("select sum(actual) as value from (select actual from pxchecking where trno = ? and expenseid = 94
        union all
        select actual from hpxchecking where trno = ? and expenseid = 94) as a",[$data->trno,$data->trno],'',true);
        $totalexp += $duty;
        $str .= $this->reporter->col(number_format($duty,2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $drdocno="";
          if($data->potrno !=0){
            $drdocno = $this->coreFunctions->datareader("select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }

        $sidocno="";
          if($data->potrno !=0){
            $sidocno = $this->coreFunctions->datareader("select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }


        if($pcfAdminAccess){
          $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          // $totaltps +=$data->totaltp;
          //  $totalsrps +=$data->totalsrp;

          if(isset($totalexp) && isset($poamt)){
            // $actualMargin = $this->othersClass->calculatePercentage($totalexp, $poamt);
            $tltp= $data->totaltp * $data->oandausdphp;
            $tlbudget= $tltp+$data->budget;
            $aftimargin = $data->totalsrp - $tlbudget; 
            $actualMargin = $this->othersClass->calculatePercentage($aftimargin, $data->totalsrp);
          }else{
            $actualMargin = 0;
          }
          
          //if($data->stat =='P'){
          $str .= $this->reporter->col(number_format($actualMargin,2).'%', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          //}else{
          //$str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', ''); 
          //}

          //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          //actual margin = balance/ po amount
          $margin =0;
          if($poamt !=0){
            $margin=($balance/$poamt)*100;
          }
         
          $str .= $this->reporter->col((isset($margin) ? number_format($margin,2) : 0).'%', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          
          //sales invoice ready col    
          $str .= $this->reporter->col($sidocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

          //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


         }elseif($prodHeadAccess>0 && $salesHeadAccess > 0){
           
           $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
         
           //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        
          //sales invoice ready col    
          $str .= $this->reporter->col($sidocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

            //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

          //EMPTY
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');



        }else{
          $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        
            //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

           //EMPTY
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        }



        $str .= $this->reporter->endrow();


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config,$expenses);

          $page = $page + $count;
        }
      }
    }

    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    
    foreach ($expenses as $key => $uniq) {
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }
     if($pcfAdminAccess){
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
     }elseif($prodHeadAccess>0 && $salesHeadAccess > 0){
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');

     }else{
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
     }
    

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }


   public function reportDefaultLayout($config){
   
    $user = $config['params']['user'];
    $str = '';
    $pcfAdminAccess = $this->othersClass->checkAccess($user, 5389);

    if($pcfAdminAccess){
     return $this->pcf_admin($config);
    }else{
     return $this->others($config);
    }

   }


   public function count_exp($config, $expenses)
    {
        $count = 0;
        foreach ($expenses as $i => $value) {
            $count++;
        }
        return $count;
    }


    ///////2.11.2026
    public function pcf_admin($config)
  {
    $result = $this->reportDefault($config);
    // var_dump($result);
    $count = 48;
    $page = 50;
    // $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    $totaltps=0;
    $totalsrps =0;
    $date1      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $date2        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    

    // $expenses = $this->getExpensesList($start,$end);
    // $exp= $this->count_exp($config, $expenses);
    // $layoutsize = 1500 + ($exp * 100);

     $expenses = $this->getExpensesList($date1,$date2);

    $validExpenses = [];

    foreach ($expenses as $uniq) {
        $field = $uniq->exp;

        foreach ($result as $row) {
            if (isset($row->$field) && (float)$row->$field != 0) {
                $validExpenses[] = $uniq;
                break;
            }
        }
    }

    $layoutsize = 1500 + (count($validExpenses) * 100);

    $str .= $this->reporter->beginreport($layoutsize);
  
    $str .= $this->displayHeader($config, $validExpenses);
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if(isset($data->potrno)){          
          $poamt = $this->coreFunctions->datareader("select sum(g.amt) as value from (
            select qss.trno,sum(qss.ext) as amt 
            from qsstock as qss 
            group by qss.trno
            union all
            select qss.trno,sum(qss.ext) as amt 
            from hqsstock as qss
            group by qss.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from qtstock as qts 
            group by qts.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from hqtstock as qts 
            group by qts.trno
          ) as g where g.trno = $data->potrno");
        }else{
          $poamt = 0;
        }

        $str .= $this->reporter->addline();
          
        $totalexp = 0;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(isset($data->refno) ? $data->refno : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->sodocno) ? $data->sodocno : '', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->pono) ? $data->pono : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        
        // if(!empty($expenses)){
        //   foreach ($expenses as $key => $uniq) {
        //     $expstring = $uniq->exp;
        //     // $str .= $this->reporter->col(isset($data->$expstring) ? number_format($data->$expstring,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        //     $totalexp += $data->$expstring;
        //   }
        // }
        //margin
        $tltp= $data->totaltp * $data->oandausdphp;
        if(isset($totalexp) && isset($poamt)){
            // $actualMargin = $this->othersClass->calculatePercentage($totalexp, $poamt);
            //$tltp= $data->totaltp * $data->oandausdphp;
            $tlbudget= $tltp+$data->budget;
            $aftimargin = $data->totalsrp - $tlbudget; 
            $actualMargin = $this->othersClass->calculatePercentage($aftimargin, $data->totalsrp);
          }else{
            $actualMargin = 0;
          }
          
          //if($data->stat =='P'){
          
          //}else{
          //$str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', ''); 
          //}

        $str .= $this->reporter->col(isset($data->salesperson) ? $data->salesperson : '', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->company) ? $data->company : '', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($poamt) ? number_format($poamt,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($tltp) ? number_format($tltp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');//tp
        //duty
        $duty = $this->coreFunctions->datareader("select sum(actual) as value from (select actual from pxchecking where trno = ? and expenseid = 94
        union all
        select actual from hpxchecking where trno = ? and expenseid = 94) as a",[$data->trno,$data->trno],'',true);
        $totalexp += (float)$duty;
        $str .= $this->reporter->col(number_format($duty,2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
       
        // if(!empty($expenses)){
        //   foreach ($expenses as $key => $uniq) {
        //     $expstring = $uniq->exp;
        //     $str .= $this->reporter->col(isset($data->$expstring) ? number_format($data->$expstring,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        //     $totalexp += $data->$expstring;
        //   }
        // }

        foreach ($validExpenses as $uniq) {
            $field = $uniq->exp;
            $amount = isset($data->$field) ? (float)$data->$field : 0;

            $str .= $this->reporter->col(number_format($amount,2), '100', null, false, $border, '', 'R', $font, $fontsize);

            $totalexp += $amount;
        }

      
        $drdocno="";
          if($data->potrno !=0){
            $drdocno = $this->coreFunctions->datareader("select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }

        $sidocno="";
          if($data->potrno !=0){
            $sidocno = $this->coreFunctions->datareader("select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }

          $totalexp = $totalexp + $tltp;
          $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
         

          //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          //actual margin = balance/ po amount
          $margin =0;
          if($poamt != 0){
            $margin=($balance/$poamt)*100;
          }

          $delta = $margin-$actualMargin;

          $str .= $this->reporter->col(number_format($actualMargin,2).'%', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col((isset($margin) ? number_format($margin,2) : 0).'%', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          if ($delta <=0){
            $str .= $this->reporter->col(number_format($delta,2).'%', '100', null, false, $border, '', 'C', $font, $fontsize, '', 'red', '');
          }else{
            $str .= $this->reporter->col(number_format($delta,2).'%', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          }
          
          //sales invoice ready col    
          $str .= $this->reporter->col($sidocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

          //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

          $str .= $this->reporter->endrow();


         if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config, $validExpenses);

          $page = $page + $count;
        }
      }
    }

    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    
     foreach ($validExpenses as $uniq){
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    
    

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
   
  }


   public function others($config)
  {
    $result = $this->reportDefault($config);

    $count = 48;
    $page = 50;
    // $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    $totaltps=0;
    $totalsrps =0;
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    

    // $expenses = $this->getExpensesList($start,$end);
    // $exp= $this->count_exp($config, $expenses);
    // $layoutsize = 1500 + ($exp * 100);
    $expenses = $this->getExpensesList($start,$end);

    $validExpenses = [];

    foreach ($expenses as $uniq) {
        $field = $uniq->exp;

        foreach ($result as $row) {
            if (isset($row->$field) && (float)$row->$field != 0) {
                $validExpenses[] = $uniq;
                break;
            }
        }
    }

    $layoutsize = 1500 + (count($validExpenses) * 100);


    
    $str .= $this->reporter->beginreport($layoutsize);
    

    $str .= $this->displayHeader($config, $validExpenses);

    $user = $config['params']['user'];
    $userid = $config['params']['dataparams']['userid'];
    
    
    $salesHeadAccess = $this->othersClass->checkAccess($user, 5554);
    
    $prodHeadAccess = $this->othersClass->checkAccess($user, 5413);

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if(isset($data->potrno)){
          
          $poamt = $this->coreFunctions->datareader("select sum(g.amt) as value from (
            select qss.trno,sum(qss.ext) as amt 
            from qsstock as qss 
            group by qss.trno
            union all
            select qss.trno,sum(qss.ext) as amt 
            from hqsstock as qss
            group by qss.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from qtstock as qts 
            group by qts.trno
            union all
            select qts.trno,sum(qts.ext) as amt 
            from hqtstock as qts 
            group by qts.trno
          ) as g where g.trno = $data->potrno");
        }else{
          $poamt = 0;
        }

        $str .= $this->reporter->addline();
          
        $totalexp = 0;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(isset($data->refno) ? $data->refno : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->sodocno) ? $data->sodocno : '', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->pono) ? $data->pono : '', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($poamt) ? number_format($poamt,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->salesperson) ? $data->salesperson : '', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($data->company) ? $data->company : '', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        
        $duty = $this->coreFunctions->datareader("select sum(actual) as value from (select actual from pxchecking where trno = ? and expenseid = 94
        union all
        select actual from hpxchecking where trno = ? and expenseid = 94) as a",[$data->trno,$data->trno],'',true);
        $totalexp += $duty;
        $str .= $this->reporter->col(number_format($duty,2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        
        // if(!empty($expenses)){
        //   foreach ($expenses as $key => $uniq) {
        //     $expstring = $uniq->exp;
        //     $str .= $this->reporter->col(isset($data->$expstring) ? number_format($data->$expstring,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        //     $totalexp += $data->$expstring;
        //   }
        // }

        
        foreach ($validExpenses as $uniq) {
            $field = $uniq->exp;
            $amount = isset($data->$field) ? (float)$data->$field : 0;

            $str .= $this->reporter->col(number_format($amount,2), '100', null, false, $border, '', 'R', $font, $fontsize);

            $totalexp += $amount;
        }



        $drdocno="";
          if($data->potrno !=0){
            $drdocno = $this->coreFunctions->datareader("select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('DR',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }

        $sidocno="";
          if($data->potrno !=0){
            $sidocno = $this->coreFunctions->datareader("select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join lastock on lastock.trno = c.trno where lastock.refx = ?
            union all
           select ifnull(concat('SI',c.seq),'') as value from cntnum as c left join glstock on glstock.trno = c.trno where glstock.refx = ?
            limit 1", [$data->potrno,$data->potrno]);
          }


       if($prodHeadAccess>0 && $salesHeadAccess > 0){
           
           $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
         
           //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        
          //sales invoice ready col    
          $str .= $this->reporter->col($sidocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

            //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

          //EMPTY
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        }else{
          $str .= $this->reporter->col(isset($totalexp) ? number_format($totalexp,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            //Balance PO amount - total expenses  
          $balance=$poamt-$totalexp;
          $str .= $this->reporter->col(isset($balance) ? number_format($balance,2) : 0, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        
            //DR DOCNO
          $str .= $this->reporter->col($drdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

           //EMPTY
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        }

        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config,$validExpenses);

          $page = $page + $count;
        }
      }
    }

    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    
     foreach ($validExpenses as $uniq){
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }
   if($prodHeadAccess>0 && $salesHeadAccess > 0){
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');

     }else{
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
     }
    

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

   




}//end class