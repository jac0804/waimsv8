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

class account_receivables_register
{
  public $modulename = 'Current Customer Receivables';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'contra'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as client,
      '' as clientname,
      '' as contra,
      '' as acnoname,
      '0' as acnoid,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername
      ";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $result = $this->reportDefaultLayout_LAYOUT($config, $result); // POSTED
    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $query = $this->reportDefault_QUERY($config);
    $result = $this->coreFunctions->opentable($query);
    return $this->reportplotting($config, $result);
  }

  public function reportDefault_QUERY($config)
  {
    $filter = "";
    $filter1 = "";
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client='" . $client . "'");
    $acno = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];

    if ($client != '') {
      $filter = " and head.client='" . $client . "'";
      $filter1 = " and head.clientid='" . $clientid . "'";
    }
    if ($acno != '') {
      $filter .= " and coa.acnoid='" . $acnoid . "' ";
      $filter1 .= " and coa.acnoid='" . $acnoid . "' ";
    }

    $qry = "
      select head.trno,client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
        (case when detail.db>0 then detail.bal else detail.bal*-1 end) as balance, detail.db,coa.acnoname,head.yourref
        from (arledger as detail
        left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where date(detail.dateid) between '" . $start . "' and '" . $end . "' and left(coa.alias,2)='AR' " . $filter1 . "
        order by client.clientname, detail.dateid, detail.docno,head.yourref
    ";
    return $qry;
  }

  public function displayHeader_DEFAULT($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $filtercenter = $config['params']['dataparams']['center'];
    $client = $config['params']['dataparams']['client'];
    $contra = $config['params']['dataparams']['contra'];
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT RECEIVABLES REGISTER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($contra == '') {
      $str .= $this->reporter->col('Account : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '660px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '75', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_LAYOUT($config, $result)
  {
    $companyid = $config['params']['companyid'];
    $count = 40;
    $page = 40;
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $this->reporter->linecounter = 0;
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DEFAULT($config);
    $dbtotal = 0;
    $baltotal = 0;
    foreach ($result as $key => $data) {
      $data2 = $this->getData2($data->trno, $config);

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->acnoname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->db, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $dbtotal += $data->db;
      $baltotal += $data->balance;
      $stotal = 0;
      if (!empty($data2)) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CR #', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CR Account', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Details', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '75', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        foreach ($data2 as $key2 => $d2) {
          $stotal = $stotal + $d2->db;
          if ($d2->alias == 'CR' || $d2->alias == 'CB' || $d2->alias == 'CA') {
            $str .= $this->reporter->startrow(null, null, false, false, '', '', '', '', 'B', 'blue');
          } else {
            $str .= $this->reporter->startrow();
          }
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($d2->docno, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($d2->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($d2->acnoname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($d2->checkno, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($d2->db, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
       
        }
        $str .= $this->reporter->endtable();
      }
      if (($key + 1) == count($result)) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '625', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Balance: ', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
     
        $str .= $this->reporter->col(number_format($baltotal, $this->companysetup->getdecimal('currency', $config['params'])), '75', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }
    return $str;
  }

  public function getData2($trno, $config)
  {
    $query = "select detail.trno, detail.line, detail.refx, detail.linex 
      from lahead as head 
      left join ladetail as detail on detail.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='CR' and left(coa.alias,2)='AR' and detail.refx=" . $trno . "
      union all
      select detail.trno, detail.line, detail.refx, detail.linex 
      from glhead as head 
      left join gldetail as detail on detail.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='CR' and left(coa.alias,2)='AR' and detail.refx=" . $trno;
    $data = $this->coreFunctions->opentable($query);
    $datas = [];
    if (!empty($data)) {
      foreach ($data as $d) {
        $query2 = "select (case when left(coa.alias,2)='CB' or left(coa.alias,2)='CA' then 1 else 0 end) as grp, left(coa.alias,2) as alias,head.docno, date(head.dateid) as dateid, coa.acno, coa.acnoname, detail.db, detail.cr, detail.refx, detail.checkno
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='CR' and left(coa.alias,2) <> 'AR' and head.trno=" . $d->trno . "
          union all
          select (case when left(coa.alias,2)='CB' or left(coa.alias,2)='CA' then 1 else 0 end) as grp, left(coa.alias,2) as alias,head.docno, date(head.dateid) as dateid, coa.acno, coa.acnoname, detail.db, detail.cr, detail.refx, detail.checkno
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='CR' and left(coa.alias,2) <> 'AR' and head.trno=" . $d->trno . " order by grp";
        $data2 = $this->coreFunctions->opentable($query2);
        if (!empty($data2)) {
          foreach ($data2 as $d2) {
            array_push($datas, $d2);
          }
        }
      }
    }
    return $datas;
  }
}
