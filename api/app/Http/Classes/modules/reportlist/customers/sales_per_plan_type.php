<?php

namespace App\Http\Classes\modules\reportlist\customers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class sales_per_plan_type
{
  public $modulename = 'Sales Per Plan Type';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.label', 'Payor');
    data_set($col1, 'dclientname.lookupclass', 'payors');
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as dclientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
    return $this->coreFunctions->opentable($paramstr);
  }

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
    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $center = $config['params']['dataparams']['center'];
    $filter = "";

    if ($client != "") $filter .= " and ehead.client='$client'";
    if ($center != "") $filter .= " and cntnum.center='$center'";

    $query = "
    select docno, concat(plantype,' (',vattype,')') as plantype, sum(amount) as amount, client, payor,planholder,vattype,bref,seq from(
      select head.docno, plan.name as plantype, plan.amount, ehead.client, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor,
      concat(info.lname, ' ', info.fname, ' ', info.mname) as planholder,
      head.vattype,cntnum.bref,cntnum.seq
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ehead on ehead.trno=head.aftrno
        left join heainfo as info on info.trno=ehead.trno
        left join plantype as plan on plan.line=ehead.planid
        where head.doc='CP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      union all
      select head.docno, plan.name as plantype, plan.amount, ehead.client, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor,
      concat(info.lname, ' ', info.fname, ' ', info.mname) as planholder,
      head.vattype,cntnum.bref,cntnum.seq
        from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ehead on ehead.trno=head.aftrno
        left join heainfo as info on info.trno=ehead.trno
        left join plantype as plan on plan.line=ehead.planid
        where head.doc='CP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
    ) as t
    group by docno,bref,seq, plantype, client, payor,planholder,vattype
    order by plantype,vattype, docno,payor,planholder";
    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES PER PLAN TYPE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $payor = "ALL";
    if ($client != '') {
      $payor = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$client]);
    }
    $str .= $this->reporter->col('Payor : ' . $payor, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLAN TYPE', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PAYOR NAME', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PLANHOLDER', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) return $this->othersClass->emptydata($config);

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $remtotal = 0;

    $payor = "";
    $plantype = "";
    $i = 0;
    $plantypeCount = 0;
    foreach ($result as $key => $data) {
      if ($plantype != '' && $plantype != ($data->plantype)) {
        SubtotalHere:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Client: ' . $plantypeCount, '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('SUBTOTAL:', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $subtotal = 0;
        $plantypeCount = 0;
        if ($i == (count((array)$result) - 1)) {
          break;
        }
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }

      $strdocno = $data->bref . $data->seq;
      $strdocno = $this->othersClass->PadJ($strdocno, 10);

      if ($plantype == '' || $plantype != ($data->plantype)) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->plantype, $layoutsize, null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($strdocno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->payor, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->planholder, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotal += $data->amount;
      $plantypeCount += 1;
      $remtotal += $data->amount;
      $plantype = $data->plantype;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }

      if ($i == (count((array)$result) - 1)) {
        goto SubtotalHere;
      }
      $i++;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRANDTOTAL:', '200', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($remtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class