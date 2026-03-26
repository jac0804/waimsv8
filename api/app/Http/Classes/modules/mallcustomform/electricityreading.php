<?php

namespace App\Http\Classes\modules\mallcustomform;

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
use Symfony\Component\VarDumper\VarDumper;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class electricityreading
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ELECTRICITY READING';
  public $gridname = 'entrygrid';
  public $head = 'electricreading';
  public $hhead = 'helectricreading';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = [];
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2869,
      'saveallentry' => 2870,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $client = 0;
    $clientname = 1;
    $estart = 2;
    $eend = 3;
    $consump = 4;
    $editprev = $this->othersClass->checkAccess($config['params']['user'], 4203);

    $tab = [$this->gridname => [
      'gridcolumns' => [
        'client', 'clientname',
        'estart', 'eend', 'consump'
      ]
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'TENANTS';

    $obj[0][$this->gridname]['columns'][$client]['label'] = 'Tenant Code';
    $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';


    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Tenant Name';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$client]['align'] = 'text-left';

    $obj[0][$this->gridname]['columns'][$client]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$estart]['label'] = 'Previous Reading';
    $obj[0][$this->gridname]['columns'][$eend]['label'] = 'Current Reading';

    if ($editprev) {
      $obj[0][$this->gridname]['columns'][$estart]['type'] = 'input';
    } else {
      $obj[0][$this->gridname]['columns'][$estart]['type'] = 'label';
    }

    $obj[0][$this->gridname]['columns'][$eend]['type'] = 'input';

    $obj[0][$this->gridname]['columns'][$estart]['checkfield'] = 'isposted';
    $obj[0][$this->gridname]['columns'][$eend]['checkfield'] = 'isposted';
    $obj[0][$this->gridname]['columns'][$consump]['type'] = 'label';


    return $obj;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['month', 'year'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'amt.readonly', false);
    data_set($col1, 'amt.label', 'Rate');
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');

    $fields = ['start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);


    $fields = ['create', 'post', 'print'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'create.label', 'REFRESH');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $user = $config['params']['user'];
    $data = $this->coreFunctions->opentable("
      select 
      '0' as bmonth,
      '' as month,
      '' as year,
      '' as start,
      '' as end,
      '' as search,'' as isposted,
      '' as bgcolor
    ");
    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  private function selectqry()
  {
    $qry = "elect.line, elect.clientid, elect.estart, elect.eend, elect.erate, elect.bmonth, 
      elect.byear, elect.center, elect.readstart, elect.readend, elect.consump,
      cl.client, cl.clientname,
      case 
        when elect.isposted=0 then 'false' 
        else 'true' 
      end as isposted,
      '' as errcolor";

    return $qry;
  }

  public function loaddata($config)
  {
    $search = $config['params']['dataparams']['search'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];

    $filter = "";

    if ($search != "") {
      $filter .= " and cl.client LIKE '%" . $search . "%' or cl.clientname LIKE '%" . $search . "%'";
    }

    if ($bmonth != "") {
      $filter .= " and elect.bmonth = '" . $bmonth . "'";
    }

    if ($year != "") {
      $filter .= " and elect.byear = '" . $year . "'";
    }


    $select = $this->selectqry();
    $qry = "select '' as bgcolor, " . $select . " 
    from " . $this->head . " as elect
    left join client as cl on cl.clientid = elect.clientid
    where 1=1 " . $filter . "
    union all
    select '' as bgcolor, " . $select . " 
    from " . $this->hhead . " as elect
    left join client as cl on cl.clientid = elect.clientid
    where 1=1 " . $filter . "
    order by line";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case "create":
        $data = [];
        $center = $config['params']['center'];
        $head = $config['params']['dataparams'];

        $bmonth = $head['bmonth'];
        $year = $head['year'];
        $start = date('Y-m-d', strtotime($head['start']));
        $end = date('Y-m-d', strtotime($head['end']));
        $billdate = strtotime($year . '-' . $bmonth . '-1');
        $prevbilldate =  strtotime("-1 month", $billdate);
        $prevbilldate = $this->othersClass->datefilter(date("Y-m-d", $prevbilldate));
        $exist = $this->coreFunctions->datareader("select clientid as value from " . $this->head . " where center =? and bmonth =? and byear=?", [$center, $bmonth, $year]);

        if (empty($exist)) {
          if ($head['start'] == "") {
            return ["status" => false, "msg" => "Start Date is Required!", "data" => []];
          }

          if ($head['end'] == "") {
            return ["status" => false, "msg" => "End Date is Required!", "data" => []];
          }
        }

        $exist = $this->coreFunctions->datareader("select clientid as value from " . $this->hhead . " where center =? and bmonth =? and byear=?", [$center, $bmonth, $year]);
        if (!empty($exist)) {
          return ["status" => false, "msg" => "Already Billed!", "data" => []];
        }

        if ($head['bmonth'] != "" || $head['year'] != "") {
          $checking = $this->coreFunctions->opentable("
          select group_concat(clientid) as clientid 
          from (
            select clientid from " . $this->head . " 
            where bmonth = '" . $bmonth . "' and byear = '" . $year . "' and center = '" . $center . "'
            union all
            select clientid from " . $this->hhead . " 
            where bmonth = '" . $bmonth . "' and byear = '" . $year . "' and center = '" . $center . "'
          ) as tbl");

          $erate = $this->coreFunctions->datareader("select amt as value from electricrate order by dateid desc limit 1");

          if ($checking[0]->clientid !== NULL) {
            $qry = "
              insert into " . $this->head . "(clientid, bmonth, byear, center, readstart, readend, erate,estart,eend)
              select tinfo.clientid, '" . $bmonth . "', '" . $year . "', '" . $center . "', 
              (select readstart from " . $this->head . " where bmonth = $bmonth and byear= $year and center='" . $center . "' limit 1),
              (select readend from " . $this->head . " where bmonth = $bmonth and byear= $year and center='" . $center . "' limit 1), " . $erate . ",
              (select eend from " . $this->hhead . " where bmonth = month('" . $prevbilldate . "') and byear=year('" . $prevbilldate . "') and clientid = cl.clientid) as estart,0 as eend
              from tenantinfo as tinfo
              left join client as cl on cl.clientid = tinfo.clientid
              left join loc as loc on loc.line = cl.locid
              where cl.isinactive = 0 and cl.istenant = 1 and  loc.emeter is not null and tinfo.clientid not in (" . $checking[0]->clientid . ")
              ";

            $this->coreFunctions->execqry($qry, 'insert');
          } else {
            $qry = "
                insert into " . $this->head . "(clientid, bmonth, byear, center, readstart, readend, erate,estart,eend)
                select tinfo.clientid, '" . $bmonth . "', '" . $year . "', '" . $center . "', '" . $start . "', '" . $end . "', " . $erate . ",                
                ifnull((select eend from " . $this->hhead . " where bmonth = month('" . $prevbilldate . "') and byear=year('" . $prevbilldate . "') and clientid = cl.clientid),0) as estart,0 as eend
                from tenantinfo as tinfo
                left join client as cl on cl.clientid = tinfo.clientid
                left join loc as loc on loc.line = cl.locid
                where cl.isinactive = 0 and cl.istenant = 1 and  loc.emeter is not null";

            $this->coreFunctions->execqry($qry, 'insert');
          }
        }

        return $this->loaddata($config);
        break;
      case 'load':
        return $this->loaddata($config);
        break;
      case 'saveallentry':
        $this->savechanges($config);
        return $this->loaddata($config);
        break;
      case 'post':
        $this->posting($config);
        return $this->loaddata($config);
        break;
      case 'print':
        return $this->setupreport($config);
        break;
    }
  }

  private function savechanges($config)
  {
    $rows = $config['params']['rows'];

    foreach ($rows as $k => $val) {
      if ($val["bgcolor"] != "") {
        unset($val["bgcolor"], $val["errcolor"], $val["isposted"], $val["client"], $val["clientname"]);
        $val["consump"] = $val["eend"] - $val['estart'];

        $this->coreFunctions->sbcupdate($this->head, $val, ['line' => $val["line"]]);
      }
    }
  }

  private function posting($config)
  {
    $center = $config['params']['center'];
    $head = $config['params']['dataparams'];

    $bmonth = $head['bmonth'];
    $year = $head['year'];

    $qry = "update " . $this->head . " set isposted = 1 where center ='" . $center . "' and bmonth = " . $bmonth . " and byear=" . $year;
    $this->coreFunctions->execqry($qry, 'update');
  }

  public function stockstatus($config)
  {
    $action = $config['params']["action"];

    switch ($action) {
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    }
  }

  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Successfully loaded.', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }

  public function createreportfilter($config)
  {
    $fields = ['month', 'year', 'description', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'month.type', 'hidden');
    data_set($col1, 'year.type', 'hidden');
    data_set($col1, 'description.type', 'hidden');
    data_set($col1, 'description.name', 'search');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $month = $config['params']['dataparams']['month'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];
    $search = $config['params']['dataparams']['search'];

    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '" . $month . "' as month,
        '" . $bmonth . "' as bmonth,
        '" . $year . "' as year,
        '" . $search . "' as search,
        '' as prepared,
        '' as approved,
        '' as received
    "
    );
  }

  public function default_query($config)
  {
    $search = $config['params']['dataparams']['search'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];

    $filter = "";

    if ($search != "") {
      $filter .= " and cl.client LIKE '%" . $search . "%' or cl.clientname LIKE '%" . $search . "%'";
    }

    if ($bmonth != "") {
      $filter .= " and elect.bmonth = '" . $bmonth . "'";
    }

    if ($year != "") {
      $filter .= " and elect.byear = '" . $year . "'";
    }

    $select = "
      elect.line, elect.clientid, elect.estart, elect.eend, elect.erate, elect.bmonth, 
      elect.byear, elect.center, elect.readstart, elect.readend, elect.consump,
      cl.client, cl.clientname, loc.name as locname, loc.emeter,t.emulti";

    $qry = "select " . $select . " 
    from " . $this->head . " as elect
    left join client as cl on cl.clientid = elect.clientid
    left join loc as loc on loc.line = cl.locid
    left join tenantinfo as t on t.clientid = cl.clientid
    where 1=1 " . $filter . "
    union all
    select " . $select . " 
    from " . $this->hhead . " as elect
    left join client as cl on cl.clientid = elect.clientid
    left join loc as loc on loc.line = cl.locid
    left join tenantinfo as t on t.clientid = cl.clientid
    where 1=1 " . $filter . "
    order by line";


    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function default_waterreading_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $search = $params['params']['dataparams']['search'];
    $month = $params['params']['dataparams']['month'];
    $bmonth = $params['params']['dataparams']['bmonth'];
    $year = $params['params']['dataparams']['year'];


    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, "Electricity Consumption Proof Sheet", '', 'C');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "for the month of $month, $year", '', 'C');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(100, 0, "Tenant Code", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "Tenant Name", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Loc", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Meter #", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "Curr. Reading", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "Prev. Reading", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Consumption", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "Rate", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "Total Amount", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function reportdata($params)
  {
    $data = $this->default_query($params);

    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_waterreading_header_PDF($params, $data);
    $gamt = 0;
    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);

      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(100, 0, $data[$i]->client, '', 'L', 0, 0, '', '');
      PDF::MultiCell(150, 0, $data[$i]->clientname, '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[$i]->locname, '', 'L', 0, 0, '', '');
      PDF::MultiCell(75, 0, $data[$i]->emeter, '', 'L', 0, 0, '', '');
      PDF::MultiCell(50, 0, number_format($data[$i]->estart, $decimalqty), '', 'R', 0, 0, '', '');
      PDF::MultiCell(50, 0, number_format($data[$i]->eend, $decimalqty), '', 'R', 0, 0, '', '');
      PDF::MultiCell(75, 0, number_format($data[$i]->consump, $decimalqty), '', 'R', 0, 0, '', '');
      PDF::MultiCell(50, 0, number_format($data[$i]->erate, $decimalcurr), '', 'R', 0, 0, '', '');
      PDF::MultiCell(50, 0, number_format(($data[$i]->erate * $data[$i]->emulti) * $data[$i]->consump, $decimalcurr), '', 'R', 0, 0, '', '');
      PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_waterreading_header_PDF($params, $data);
        $page += $count;
      }
      $gamt += (($data[$i]->erate * $data[$i]->emulti) * $data[$i]->consump);
    }

    PDF::MultiCell(700, 0, "", "B");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 0, "", '', 'L', 0, 0, '', '');
    PDF::MultiCell(50, 0, number_format($gamt, $decimalcurr), '', 'R', 0, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => PDF::Output($this->modulename . '.pdf', 'S'), 'directprint' => false, 'action' => 'print'];
  }
} //end class
