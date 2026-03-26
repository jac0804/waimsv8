<?php

namespace App\Http\Classes\modules\operation;

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
use App\Http\Classes\modules\crm\ld;
use App\Http\Classes\sqlquery;
use Symfony\Component\VarDumper\VarDumper;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class othercharges
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER CHARGES';
  public $gridname = 'entrygrid';
  public $head = 'chargesbilling';
  // public $hhead = 'hwaterreading';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = [];
  // line, clientid, wstart, wend, wrate, bmonth, byear, center, readstart, readend, isposted, consump
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {

    $attrib = array(
      'view' => 2556,
      'edit' => 773,
      'delete' => 2752,
      'post' => 2491,
      'additem' => 2045,
      'saveallentry' => 1847
    );

    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $description = 1;
    $amt = 2;


    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'description', 'amt'
      ]
    ]];

    // sir ung new charge po dpat n btn nd ung nilagay ntin dati, tangalin ko n po un, lagyan din po ng condition un like dun sa old
    // di pala to mag plot kasi nag redirect yan sa module.
    // module? bawal ung gnitong klase ng lookup sa entrygrid sir? eto po kse ung nirecommend ni sir fred nung busy po kyo kahapon
    // may nakita ka sample ganto ?
    // wla sir, lahat ng head table kse ay puro refresh, load, save lng mga button, wla silang grid na ktulad ng gnito sakin n need ng lookup tpos plot
    // sabi ni sir fred ay itulad ko daw po sa PO or ung may mga pick up ng item sge try 
    $stockbuttons = ['delete', 'save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'TENANTS';

    $obj[0][$this->gridname]['columns'][$description]['label'] = 'Billable Item';
    $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$description]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$description]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    // $obj[0][$this->gridname]['columns'][$wstart]['checkfield'] = 'isposted';
    // $obj[0][$this->gridname]['columns'][$wend]['checkfield'] = 'isposted';
    // $obj[0][$this->gridname]['columns'][$consump]['checkfield'] = 'isposted';


    return $obj;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {

    $tbuttons = ['addrow', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "NEW CHARGE";
    $obj[0]['action'] = "addcharge";
    $obj[0]['addedparams'] = ["clientid", "year", "bmonth"]; //san gling to sir, d po b dpat from config? uu what magic is this hahaha
    // eto ang pinaka question ko sir pano ngyari to hahaha // hahaha all parameters sa head pag nilagay mo pasasama sya dun sa addparams // sample yan
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['month', 'year'];
    $col1 = $this->fieldClass->create($fields);
    // data_set($col1, 'amt.readonly', false);
    // data_set($col1, 'amt.label', 'Rate');
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');

    data_set($col1, 'create.label', 'REFRESH');

    $fields = ['client', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'client.label', 'Tenant');
    data_set($col2, 'client.lookupclass', 'tenant');
    data_set($col2, 'client.name', 'clientname');
    data_set($col2, 'refresh.action', 'load');

    // data_set($col2, 'create.action', 'addnew');
    // data_set($col2, 'create.label', 'Add Row');
    // data_set($col2, 'start.required', true); 'start', 'end', 
    // data_set($col2, 'end.required', true);


    // $fields = ['description', 'refresh'];
    // $col3 = $this->fieldClass->create($fields);
    // data_set($col3, 'description.label', 'Seach');
    // data_set($col3, 'description.name', 'search');

    // data_set($col3, 'refresh.label', 'SEARCH');, 'col3' => $col3


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $user = $config['params']['user'];
    $data = $this->coreFunctions->opentable("
      select 
      '0' as bmonth,
      '' as month,
      '' as year,
      '' as client,
      '' as clientname,
      '' as clientid,
      '' as refresh,
      '' as addnew  
    "); // yung mga nanjan sa paramsdata pwede mo sya lagay sa addedparams, bkit sa added params lng sir pwede? d pwede sya icall sa buong file nto? 
    // bibigay siguro // saka di ko rin alam ke boss haha sya nag gawa nyan para dun sa area loc pinagawa samen noon // anyway kaw na tuloy plotting dun sa getcharges
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



  public function loaddata($config)
  {

    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];
    $clientid = $config['params']['dataparams']['clientid'];

    $filter = "";



    if ($bmonth != "") {
      $filter .= " and cb.bmonth = '" . $bmonth . "'";
    }

    if ($year != "") {
      $filter .= " and cb.byear = '" . $year . "'";
    }

    if ($clientid != "") {
      $filter .= " and t.clientid = '" . $clientid . "'";
    }

    $qry = "select oc.line,oc.description,cb.amt,t.clientname from " . $this->head . " as cb
    left join ocharges as oc on cb.cline=oc.line
    left join client as t on t.clientid=cb.clientid
    where 1=1 " . $filter . "
    order by oc.line";



    $data = $this->coreFunctions->opentable($qry);


    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];


    switch ($action) {
      case "addnew":

        $data = $config['params']['dataparams'];

        if ($data['client'] == '') {
          return ['status' => false, 'msg' => 'Please Select Tenant First', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        } else {
          $data = [];
          $data[0]['line'] = 0;
          $data[0]['description'] = '';
          $data[0]['amt'] = '';


          $data[0]['bgcolor'] = 'bg-blue-2';



          return ['status' => true, 'msg' => 'Row Added', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
        }


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

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['description'] = '';
    $data['amt'] = '';


    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function savechanges($config)
  {
    $rows = $config['params']['rows'];

    foreach ($rows as $k => $val) {
      if ($val["bgcolor"] != "") {
        unset($val["bgcolor"], $val["errcolor"], $val["isposted"], $val["client"], $val["clientname"]);

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
    // $start = date('Y-m-d', strtotime($head['start']));
    // $end = date('Y-m-d', strtotime($head['end']));

    $qry = "
      insert into " . $this->hhead . " (line, clientid, wstart, wend, wrate, bmonth, byear, center, readstart, readend, isposted, consump)
      select line, clientid, wstart, wend, wrate, bmonth, byear, center, readstart, readend, 1, consump
      from " . $this->head . "
      where bmonth = '" . $bmonth . "' and byear = '" . $year . "' and center = '" . $center . "'";
    $this->coreFunctions->execqry($qry, 'insert');

    $this->coreFunctions->execqry("delete from " . $this->head . " 
    where bmonth = '" . $bmonth . "' and byear = '" . $year . "' and center = '" . $center . "'", 'delete');
  }

  public function stockstatus($config)
  {
    $action = $config['params']["action"];


    switch ($action) {
      case 'getcharges':
        return $this->getcharges($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    }
  }

  public function getcharges($config)
  {

    $data = [];
    $data[0]['line'] = $config['params']['row']['line'];
    $data[0]['description'] = $config['params']['row']['description'];
    $data[0]['amt'] = $config['params']['row']['amt'];

    $data['bgcolor'] = 'bg-blue-2';



    return ['status' => true, 'msg' => 'Row Added', 'griddata' => ['entrygrid' => $data]];



    // yan oks na
    // $trno = $config['params']['trno'];
    // $wh = $config['params']['wh'];
    // $center = $config['params']['center'];
    // $rows = [];
    // foreach ($config['params']['rows'] as $key => $value) {
    //   $qry = "
    //     select head.docno, item.itemid,stock.trno,
    //     stock.line, item.barcode,stock.uom, stock.cost,
    //     (stock.qty-(stock.qa+stock.cdqa)) as qty,stock.rrcost,
    //     round((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
    //     stock.disc,st.line as stageid
    //     FROM hprhead as head left join hprstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno left join item on item.itemid=
    //     stock.itemid left join uom on uom.itemid=item.itemid and
    //     uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and transnum.center=? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    // ";
    //   $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
    //   if (!empty($data)) {
    //     foreach ($data as $key2 => $value) {
    //       $config['params']['data']['uom'] = $data[$key2]->uom;
    //       $config['params']['data']['itemid'] = $data[$key2]->itemid;
    //       $config['params']['trno'] = $trno;
    //       $config['params']['data']['disc'] = $data[$key2]->disc;
    //       $config['params']['data']['qty'] = $data[$key2]->rrqty;
    //       $config['params']['data']['wh'] = $wh;
    //       $config['params']['data']['loc'] = '';
    //       $config['params']['data']['expiry'] = '';
    //       $config['params']['data']['rem'] = '';
    //       $config['params']['data']['refx'] = $data[$key2]->trno;
    //       $config['params']['data']['linex'] = $data[$key2]->line;
    //       $config['params']['data']['cdrefx'] = 0;
    //       $config['params']['data']['cdlinex'] = 0;
    //       $config['params']['data']['stageid'] =  $data[$key2]->stageid;
    //       $config['params']['data']['ref'] = $data[$key2]->docno;
    //       $config['params']['data']['amt'] = $data[$key2]->rrcost;
    //       $return = $this->additem('insert', $config);
    //       if ($return['status']) {
    //         if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
    //           $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
    //           $line = $return['row'][0]->line;
    //           $config['params']['trno'] = $trno;
    //           $config['params']['line'] = $line;
    //           $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
    //           $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
    //           $row = $this->openstockline($config);
    //           $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
    //         }
    //         array_push($rows, $return['row'][0]);
    //       }
    //     } // end foreach
    //   } //end if
    // } //end foreach

  } //end function

  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
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
    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];
    $search = $config['params']['dataparams']['search'];

    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
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
      $filter .= " and water.bmonth = '" . $bmonth . "'";
    }

    if ($year != "") {
      $filter .= " and water.byear = '" . $year . "'";
    }

    $select = "
      water.line, water.clientid, water.wstart, water.wend, water.wrate, water.bmonth, 
      water.byear, water.center, water.readstart, water.readend, water.consump,
      cl.client, cl.clientname";

    $qry = "select " . $select . " 
    from " . $this->head . " as water
    left join client as cl on cl.clientid = water.clientid
    where 1=1 " . $filter . "
    union all
    select " . $select . " 
    from " . $this->hhead . " as water
    left join client as cl on cl.clientid = water.clientid
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
    $bmonth = $params['params']['dataparams']['bmonth'];
    $year = $params['params']['dataparams']['year'];

    //$width = 800; $height = 1000;

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

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Month: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $bmonth, 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Year: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $year, 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Search: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $search, 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(100, 0, "Tenant Code", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "Tenant Name", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "Previous Reading", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "Current Reading", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "Consumption", '', 'L', false);

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

    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);

      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(100, 0, $data[$i]->client, '', 'L', 0, 0, '', '');
      PDF::MultiCell(200, 0, $data[$i]->clientname, '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[$i]->wstart, '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[$i]->wend, '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[$i]->consump, '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_waterreading_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(700, 0, "", "");
    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

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
