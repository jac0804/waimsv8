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

class sales_report_per_person
{
  public $modulename = 'Sales Report Per Person';
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
    $fields = ['radioprint', 'start', 'end', 'repitemgroup', 'agentname', 'cur', 'radioposttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'agentname.label', 'Sales Person');
    data_set($col1, 'cur.type', 'input');
    data_set($col1, 'cur.class', 'cscur');
    data_set($col1, 'cur.readonly', false);
    data_set($col1, 'cur.required', true);
    data_set($col1, 'cur.label', 'PHP to SGD Rate');

    data_set($col1, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);

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
      '' as cur,
      '0' as posttype,
      '0' as agentid,
      '' as agent,
      '' as agentname,
      '' as repitemgroup,
      0 as projectid
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
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype  = $config['params']['dataparams']['posttype'];
    $itemgroupname  = $config['params']['dataparams']['repitemgroup'];
    $itemgroupid  = $config['params']['dataparams']['projectid'];
    $agentname  = $config['params']['dataparams']['agentname'];
    $salespersonid  = $config['params']['dataparams']['agentid'];
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;


    $filter = "";

    if ($itemgroupname != "") {
      $filter .= " and prj.line = '" . $itemgroupid . "'";
    }

    if ($agentname != "") {
      $filter .= " and ag.clientid = '" . $salespersonid . "'";
    }

    switch ($posttype) {
      case '0'; // posted
        $query = "select hqshead.docno as sodocno, sohead.docno as sodocno, 
        date(sohead.dateid) as dateid,
        hqshead.client, hqshead.clientname, prj.name as projectname,
        item.barcode, item.itemname, isqty as qty, isamt as itemrate,
        ifnull(hqshead.agent, '') as agcode, ifnull(ag.clientname, 'NO SALES PERSON') as agname,
        hqshead.yourref as ponum, date(hqshead.dateid) as podate
        from hsqhead as sohead
        left join hqshead as hqshead on hqshead.sotrno = sohead.trno
        left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
        left join item as item on item.itemid = hqsstock.itemid
        left join projectmasterfile as prj on prj.line = hqsstock.projectid
        left join client as ag on ag.client = hqshead.agent
        where date(sohead.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        order by ag.clientname, sohead.dateid";
        break;
      case '1': // unposted
        $query = "select hqshead.docno as sodocno, sohead.docno as sodocno, 
        date(sohead.dateid) as dateid,
        hqshead.client, hqshead.clientname, prj.name as projectname,
        item.barcode, item.itemname, isqty as qty, isamt as itemrate,
        ifnull(hqshead.agent, '') as agcode, ifnull(ag.clientname, 'NO SALES PERSON') as agname,
        hqshead.yourref as ponum, date(hqshead.dateid) as podate
        from sqhead as sohead
        left join hqshead as hqshead on hqshead.sotrno = sohead.trno
        left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
        left join item as item on item.itemid = hqsstock.itemid
        left join projectmasterfile as prj on prj.line = hqsstock.projectid
        left join client as ag on ag.client = hqshead.agent
        where date(sohead.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        order by ag.clientname, sohead.dateid";
        break;
      case '2': // all 
        $query = "
          select hqshead.docno as sodocno, sohead.docno as sodocno, 
          date(sohead.dateid) as dateid,
          hqshead.client, hqshead.clientname, prj.name as projectname,
          item.barcode, item.itemname, isqty as qty, isamt as itemrate,
          ifnull(hqshead.agent, '') as agcode, ifnull(ag.clientname, 'NO SALES PERSON') as agname,
          hqshead.yourref as ponum, date(hqshead.dateid) as podate
          from hsqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          left join client as ag on ag.client = hqshead.agent
          where date(sohead.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
          union all 
          select hqshead.docno as sodocno, sohead.docno as sodocno, 
          date(sohead.dateid) as dateid,
          hqshead.client, hqshead.clientname, prj.name as projectname,
          item.barcode, item.itemname, isqty as qty, isamt as itemrate,
          ifnull(hqshead.agent, '') as agcode, ifnull(ag.clientname, 'NO SALES PERSON') as agname,
          hqshead.yourref as ponum, date(hqshead.dateid) as podate
          from sqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          left join client as ag on ag.client = hqshead.agent
          where date(sohead.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
          order by agname, dateid
        ";
        break;
    }

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype  = $config['params']['dataparams']['posttype'];
    $itemgroupname  = $config['params']['dataparams']['repitemgroup'];
    $sgdrate        = $config['params']['dataparams']['cur'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    switch ($posttype) {
      case '0':
        $posttype = "POSTED";
        break;
      case '1':
        $posttype = "UNPOSTED";
        break;
      case '2':
        $posttype = "ALL";
        break;
    }

    if ($itemgroupname == "") {
      $itemgroupname = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PHP TO SGD RATE: ' . $sgdrate, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Group: ' . $itemgroupname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES PERSON', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SO NUMBER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO NUMBER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO DATE', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM NAME', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM RATE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VALUE IN SGD', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');


    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $sgdrate        = $config['params']['dataparams']['cur'];
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $agentname = "";
    $amount = 0;
    $subtotal = 0;
    $subtotalSGD = 0;
    $grandtotalSGD = 0;

    $datacount = count($result);
    $counter = 0;
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $counter++;
      $str .= $this->reporter->addline();
      $agname = "";
      if ($agentname != $data->agname) {
        if ($agentname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('SUB TOTAL SGD: ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotalSGD, $decimalprice), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $subtotalSGD = 0;
        }

        if ($data->agname === "") {
          $agname = "NO SALES PERSON";
        } else {
          $agname = $data->agname;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($agname, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sodocno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ponum, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->podate, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->projectname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $decimalprice), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->itemrate, $decimalprice), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $amount = $data->qty * $data->itemrate;
      $sgd = $amount * $sgdrate;
      $str .= $this->reporter->col(number_format($amount, $decimalprice), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($sgd, $decimalprice), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->page_break();
        $str .= $this->header_DEFAULT($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $page = $page + $count;
      } //end if


      $agentname = $data->agname;
      $subtotal += $amount;
      $subtotalSGD += $sgd;
      $grandtotalSGD += $sgd;

      if ($datacount == $counter) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL SGD: ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotalSGD, $decimalprice), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $subtotalSGD = 0;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL SGD: ', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotalSGD, $decimalprice), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class