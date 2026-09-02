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

class forecast_report
{
  public $modulename = 'Forecast Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1600'];



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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'agentname', 'probability'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'agentname.label', 'Sales Person');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dclientname,
      '' as clientname,
      '' as client,
      0 as clientid,
      '' as agentname,
      '' as agent,
      0 as agentid,
      '' as probability
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

    $result = $this->reportDefaultLayout_SUMMARIZED($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    ini_set('memory_limit', '-1');

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $customerid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];
    $probability    = $config['params']['dataparams']['probability'];

    $filter = "";

    if ($client != '') {
      $filter .= " and client.clientid = '" . $customerid . "'";
    }
    if ($agent != '') {
      $filter .= " and agent.clientid = '" . $agentid . "'";
    }
    // if ($probability != "") {
    //   $filter .= " and head.probability = '" . $probability . "'";
    // }

    $query = "select head.trno, agent.agentcode as sales,client.clientname as companyname,
        head.industry,proj.name as itemgroup,item.itemname,(stock.amt*stock.iss*stock.sgdrate) as rate,
        head.probability,date(head.dateid) as quodate,date(trans.postdate) as postdate,
        info.rem,client.activity,head.docno,client.groupid,date(head.due) as due,client.client,
        stock.isqty,stock.isamt,stock.ext,client.region,req.category as indusstry, req.reqtype as subindustry,req.description as intype,req.isindustry
        from qshead as head
        left join qsstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join transnum as trans on trans.trno=head.trno
        left join projectmasterfile as proj on proj.line=item.projectid
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join client as agent on agent.client=head.agent
        left join reqcategory as req on req.line=head.industryid
        where  req.isindustry=1 and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
        union all
        select head.trno, agent.agentcode as sales,client.clientname as companyname,
        head.industry,proj.name as itemgroup,item.itemname,(stock.amt*stock.iss*stock.sgdrate) as rate,
        head.probability,date(head.dateid) as quodate,date(trans.postdate) as postdate,
        info.rem,client.activity,head.docno,client.groupid,date(head.due) as due,client.client,
        stock.isqty,stock.isamt,stock.ext,client.region,req.category as indusstry, req.reqtype as subindustry,req.description as intype,req.isindustry
        from qshead as head
        left join qtstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join transnum as trans on trans.trno=head.trno
        left join projectmasterfile as proj on proj.line=item.projectid
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join client as agent on agent.client=head.agent
        left join reqcategory as req on req.line=head.industryid
        where  req.isindustry=1 and item.islabor =1 and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
        union all
        select head.trno, agent.agentcode as sales,client.clientname as companyname,
        head.industry,proj.name as itemgroup,item.itemname,(stock.amt*stock.iss*stock.sgdrate) as rate,
        head.probability,date(head.dateid) as quodate,date(trans.postdate) as postdate,
        info.rem,client.activity,head.docno,client.groupid,date(head.due) as due,client.client,
        stock.isqty,stock.isamt,stock.ext,client.region,req.category as indusstry, req.reqtype as subindustry,req.description as intype,req.isindustry
        from hqshead as head
        left join hqsstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join transnum as trans on trans.trno=head.trno
        left join projectmasterfile as proj on proj.line=item.projectid
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join client as agent on agent.client=head.agent
        left join reqcategory as req on req.line=head.industryid
        where  req.isindustry=1 and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
        union all
        select head.trno, agent.agentcode as sales,client.clientname as companyname,
        head.industry,proj.name as itemgroup,item.itemname,(stock.amt*stock.iss*stock.sgdrate) as rate,
        head.probability,date(head.dateid) as quodate,date(trans.postdate) as postdate,
        info.rem,client.activity,head.docno,client.groupid,date(head.due) as due,client.client,
        stock.isqty,stock.isamt,stock.ext,client.region,req.category as indusstry, req.reqtype as subindustry,req.description as intype,req.isindustry
        from hqshead as head
        left join hqtstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join transnum as trans on trans.trno=head.trno
        left join projectmasterfile as proj on proj.line=item.projectid
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join client as agent on agent.client=head.agent
        left join reqcategory as req on req.line=head.industryid
        where  req.isindustry=1 and item.islabor =1 and date(head.dateid) between '" . $start . "' and '" . $end . "'" . $filter . " 
        order by docno ";
    //  var_dump($query); 
    //  break;
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1600'; //1600
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $fontsize2 = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Forecast Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales', '80', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Quotation Date', '70', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Quotation No', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Company Name', '150', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');

    //new add colums 8.12.2026
    $str .= $this->reporter->col('Industry', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Sub-Industry', '85', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Type', '80', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    
    //existing colums
    $str .= $this->reporter->col("Item Group", '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Model No.', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');

    // new add colums 8.12.2026
    $str .= $this->reporter->col('Qty', '50', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Price', '75', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Total', '75', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');

    //existing colums
    $str .= $this->reporter->col('Selling Price(SGD)', '90', null, false, $border, 'BT', 'R', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Probability', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Closing Month', '80', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Remarks/Status', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Closing Year', '50', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('Account Type', '90', null, false, $border, 'BT', 'C', $font, $fontsize2, 'B', '', '');

    //new add colum 8.12.2026
    $str .= $this->reporter->col('Region', '95', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $filterprobability = $config['params']['dataparams']['probability'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1600';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:30px');
    $str .= $this->header_DEFAULT($config);

    $totalext = 0;
    $totalbal = 0;
    $clientname = "";
    $closing = "";
    $acctstat = "";
    $qdate = "";
    $closingdate= "";
   
    foreach ($result as $key => $data) {
      $calllogdata = $this->getcalllastlog($data->trno);
      $rem = $probability = '';
      if ($calllogdata) {
        $probability = $calllogdata[0]['probability'];
        $rem = $calllogdata[0]['rem'];
      }

      if ($config['params']['companyid'] == 10) { //afti
        $probability = $data->probability;
      }

      if ($filterprobability != "") {
        if ($probability != $filterprobability) {
          goto nextrow;
        }
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $clientname = $data->companyname;
      if ($data->groupid != "") {
        $clientname = $data->companyname . ' - ' . $data->groupid;
      }

      switch ($probability) {
        case '25%': //1yr
          $qdate = strtotime($data->quodate);
          $closingdate =  strtotime("+1 year", $qdate);
          break;
        case '50%': //9months
          $qdate = strtotime($data->quodate);
          if ($config['params']['companyid'] == 10){ //afti
            $closingdate = strtotime("+9 month", $qdate);
          }else{
            $closingdate =  strtotime("+6 month", $qdate);
          }
          break;
        case '75%': //3mos
          $qdate = strtotime($data->quodate);
          $closingdate =strtotime("+3 month", $qdate);
          break;
        case '90%': //within the month
          $closingdate = strtotime($data->quodate);
          break;
        case '100%': //same on the date
          $closingdate = strtotime($data->quodate);
          break;
        default:
          $closingdate =strtotime($data->quodate);
        break;
      }

      $closing = date("F", $closingdate);
      $closingyr =date("Y", $closingdate);

      $so = $this->coreFunctions->datareader("select ifnull(dateid,'') as value from (select sq.dateid from sqhead as sq left join hqshead as qs on qs.sotrno = sq.trno where qs.client = '" . $data->client . "'
      union all select sq.dateid from hsqhead as sq left join hqshead as qs on qs.sotrno = sq.trno where qs.client = '" . $data->client . "') as a order by dateid desc limit 1");

      if ($so != '') {
        $so =  date("Y-m-d", strtotime($so));
        $d1 = date_create($so);
        $d2 = date_create($this->othersClass->getCurrentDate());
        $diff = date_diff($d1, $d2);
        $y = $diff->y;
        if ($y <= 1) {
          $acctstat = "Buying";
        } else {
          $acctstat = "Lapsed Account";
        }
      } else {
        $acctstat = "Lapsed Account";
      }

      $str .= $this->reporter->col($data->sales, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->quodate, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($clientname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
     
      //new add colums 8.12.2026
       $str .= $this->reporter->col($data->indusstry, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
       $str .= $this->reporter->col($data->subindustry, '85', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
       $str .= $this->reporter->col($data->intype, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

     
      //existing colums
      $str .= $this->reporter->col($data->itemgroup, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
     
      //new add colums 8.12.2026
      $str .= $this->reporter->col(number_format($data->isqty, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->isamt, 2), '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, 2), '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');


      //existing colums
      $str .= $this->reporter->col(number_format($data->rate, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      if ($config['params']['companyid'] == 10){ //afti
        $str .= $this->reporter->col($data->probability, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      }else{
        $str .= $this->reporter->col($probability, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col($closing, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($rem, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      if ($config['params']['companyid'] == 10){ //afti
        $str .= $this->reporter->col($closingyr, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      }else{
        $str .= $this->reporter->col(date("Y", strtotime($this->othersClass->getCurrentDate())), '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col($acctstat, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

      //new add colum 8.12.2026
      $str .= $this->reporter->col($data->region, '95', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      nextrow:
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function getcalllastlog($trno)
  {
    $query = "
        select * from(
        select line, ifnull(probability,'') as probability, ifnull(rem,'') as rem from qscalllogs where trno = $trno
        union all
        select line, ifnull(probability,'') as probability, ifnull(rem,'') as rem from hqscalllogs  where trno = $trno
        ) as a
        order by a.line desc limit 1
    ";
    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }
}//end class