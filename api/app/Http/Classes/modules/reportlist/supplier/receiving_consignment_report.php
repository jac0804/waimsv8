<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class receiving_consignment_report
{
  public $modulename = 'Receiving Consignment Report';
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
    $fields = ['radioprint', 'start', 'end'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'dcentername', 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        array_push($fields, 'dcentername');
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['radioposttype', 'radiosortby'];
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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    // $companyid = $config['params']['companyid'];

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end, '0' as posttype, 'docno' as sortby,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as project, 0 as projectid, '' as projectname, 
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    // $posttype = $config['params']['dataparams']['posttype'];

    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $posttype = $config['params']['dataparams']['posttype'];
    switch ($posttype) {
      case '0':
        $query = $this->reportQuery_POSTED($config);
        break;
      case '1':
        $query = $this->reportQuery_UNPOSTED($config);
        break;
      default:
        $query = $this->default_QUERY_ALL($config);
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportQuery_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype   = $config['params']['dataparams']['posttype'];
    $sortby     = $config['params']['dataparams']['sortby'];

    $filter = "";
    $filter1 = "";

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter != '') {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select docno, dateid, client, clientname, yourref, ourref, sum(rrqty) as qty, sum(ext) as amount
    from (select head.trno, head.doc, head.docno, head.dateid, client.client, head.clientname, head.yourref, head.ourref,
    stock.line, stock.refx, stock.linex, item.barcode, item.itemname, stock.uom, wh.client as swh, stock.tstrno, stock.tsline,
    stock.rrcost, stock.cost, stock.rrqty, stock.qty, stock.isamt, stock.amt, stock.isqty, stock.iss, stock.ext
    from glhead as head left join glstock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid left join client as hwh on hwh.clientid=head.whid
    where head.doc='ca' and ifnull(item.barcode,'')<>'' and stock.qty>0
    and head.dateid between '$start' and '$end' $filter $filter1
    ) as xx
    group by docno, dateid, client, clientname, yourref, ourref
    order by '$sortby', clientname";

    return $query;
  }

  public function reportQuery_UNPOSTED($config)
  {

    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype   = $config['params']['dataparams']['posttype'];
    $sortby     = $config['params']['dataparams']['sortby'];

    $filter = "";
    $filter1 = "";

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter != '') {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select docno, dateid, client, clientname, yourref, ourref, sum(rrqty) as qty, sum(ext) as amount
    from (select head.trno, head.doc, head.docno, head.dateid, head.client, head.clientname, head.yourref, head.ourref,
    stock.line, stock.refx, stock.linex, item.barcode, item.itemname, stock.uom, wh.client as swh, stock.tstrno, stock.tsline,
    stock.rrcost, stock.cost, stock.rrqty, stock.qty, stock.isamt, stock.amt, stock.isqty, stock.iss, stock.ext
    from lahead as head left join lastock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client left join client as hwh on hwh.client=head.wh
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    where head.doc='ca' and ifnull(item.barcode,'')<>'' and stock.qty>0
    and head.dateid between '$start' and '$end' $filter $filter1
    ) as xx
    group by docno, dateid, client, clientname, yourref, ourref
    order by '$sortby', clientname";

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype   = $config['params']['dataparams']['posttype'];
    $sortby     = $config['params']['dataparams']['sortby'];

    $filter = "";
    $filter1 = "";
    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter != '') {
      $filter = " and cntnum.center='$filtercenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select docno, dateid, client, clientname, yourref, ourref, sum(rrqty) as qty, sum(ext) as amount
    from (select head.trno, head.doc, head.docno, head.dateid, client.client, head.clientname, head.yourref, head.ourref,
    stock.line, stock.refx, stock.linex, item.barcode, item.itemname, stock.uom, wh.client as swh, stock.tstrno, stock.tsline,
    stock.rrcost, stock.cost, stock.rrqty, stock.qty, stock.isamt, stock.amt, stock.isqty, stock.iss, stock.ext
    from glhead as head left join glstock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid 
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid 
    left join client as hwh on hwh.clientid=head.whid
    where head.doc='ca' and ifnull(item.barcode,'')<>'' and stock.qty>0
    and head.dateid between '$start' and '$end' $filter $filter1
    union all
    select head.trno, head.doc, head.docno, head.dateid, head.client, head.clientname, head.yourref, head.ourref,
    stock.line, stock.refx, stock.linex, item.barcode, item.itemname, stock.uom, wh.client as swh, stock.tstrno, stock.tsline,
    stock.rrcost, stock.cost, stock.rrqty, stock.qty, stock.isamt, stock.amt, stock.isqty, stock.iss, stock.ext
    from lahead as head left join lastock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client left 
    join client as hwh on hwh.client=head.wh
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    where head.doc='ca' and ifnull(item.barcode,'')<>'' and stock.qty>0
    and head.dateid between '$start' and '$end' $filter $filter1 
    ) as xx
    group by docno, dateid, client, clientname, yourref, ourref
    order by '$sortby', clientname;";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $sortby     = $config['params']['dataparams']['sortby'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
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
    $str .= $this->reporter->col('RECEIVING CONSIGNMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Sort by : ' . strtoupper($sortby), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }
    $str .= $this->reporter->col('Center:' . $filtercenter, null, null, false, $border, '', '', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DR #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('REFERENCE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype   = $config['params']['dataparams']['posttype'];
    // $sortby     = $config['params']['dataparams']['sortby'];

    $count = 41;
    $page = 40;
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
    $str .= $this->default_displayHeader($config);

    $str .= $this->reporter->begintable($layoutsize);
    $grandtotal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $grandtotal = $grandtotal + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
