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
use DateTime;

class dr_return_report
{
  public $modulename = 'DR Return Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);


    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );
    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,
                    '' as clientname,'0' as posttype,
                    '' as dclientname,'' as reportusers,'' as agent, '' as agentname,'' as dagentname,
                    '" . $defaultcenter[0]['center'] . "' as center,
                    '" . $defaultcenter[0]['centername'] . "' as centername,
                    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,'0' as clientid, '0' as agentid";
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

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $result = $this->reportDefaultLayout_TRANSACTION($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->default_QUERY_TRANSACTION_LIST($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_TRANSACTION_LIST($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $agent     = $config['params']['dataparams']['agent'];
    $agentid     = $config['params']['dataparams']['agentid'];

    $filter = "";
    $leftj = "";
    $leftj1 = "";
    $leftj2 = "";
    if ($client != "") $filter .= " and client.clientid = '$clientid' ";

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") $filter .= " and cntnum.center = '$fcenter'";

    if ($agent != "") {
      if ($posttype == 0) { //posted
        $leftj .= " left join client as agent on agent.clientid=head.agentid ";
      } elseif ($posttype == 1) { //unposted
        $leftj .= " left join client as agent on agent.client=head.agent ";
      } else { //all
        $leftj1 .= " left join client as agent on agent.clientid=head.agentid ";
        $leftj2 .= " left join client as agent on agent.client=head.agent ";
      }
      $filter .= " and agent.clientid = '$agentid' ";
    }


    switch ($posttype) {
      case '0': // POSTED
        $query = "select head.docno,sum(stock.ext) as totalamt, client.client, client.clientname,
        case cntnum.svnum when 0 then 'Tagged' else '' end as tagged, date(head.dateid) as dateid, head.yourref, head.ourref, head.rem as hrem
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid $leftj
          where head.doc='DN' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by docno, client, clientname, dateid, yourref, ourref, hrem, svnum
          order by docno";
        break;
      case '1': // UNPOSTED
        $query = "select head.docno,sum(stock.ext) as totalamt, client.client, client.clientname,
        case cntnum.svnum when 0 then 'Tagged' else '' end as tagged, date(head.dateid) as dateid, head.yourref, head.ourref, head.rem as hrem
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client $leftj
          where head.doc='DN' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by docno, client, clientname, dateid, yourref, ourref, hrem, svnum
          order by docno";
        break;
      case '2':
        $query = "select head.docno,sum(stock.ext) as totalamt, client.client, client.clientname,
        case cntnum.svnum when 0 then 'Tagged' else '' end as tagged, date(head.dateid) as dateid, head.yourref, head.ourref, head.rem as hrem
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client $leftj2
          where head.doc='DN' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by docno, client, clientname, dateid, yourref, ourref, hrem, svnum
          union all
        select head.docno,sum(stock.ext) as totalamt, client.client, client.clientname,
        case cntnum.svnum when 0 then 'Tagged' else '' end as tagged, date(head.dateid) as dateid, head.yourref, head.ourref, head.rem as hrem
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid $leftj1
          where head.doc='DN' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
          group by docno, client, clientname, dateid, yourref, ourref, hrem, svnum
          order by docno";
        break;
    }

    return $query;
  }

  public function header_transaction_list($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $clientname = $config['params']['dataparams']['clientname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $center = $config['params']['dataparams']['center'];

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;
      case 1:
        $posttype = 'Unposted';
        break;
      default:
        $posttype = 'All';
        break;
    }

    $str = '';
    $layoutsize = '1000';
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
    $str .= $this->reporter->col('DR RETURN TRANSACTION LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . ($center == '' ? 'ALL' : $center), '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . ($clientname == '' ? 'ALL' : $clientname), '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Agent: ' . ($agentname == '' ? 'ALL' : $agentname), '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '15', false, $border, '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Doc#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Client', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Client Name', '170', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amount', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function dateformat($date)
  {
    return (new DateTime($date))->format('n/j/y');
  }
  public function reportDefaultLayout_TRANSACTION($config)
  {
    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_transaction_list($config);
    $total = 0;
  

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->tagged, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->hrem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->totalamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      $total += $data->totalamt;
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total: ', '900', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class