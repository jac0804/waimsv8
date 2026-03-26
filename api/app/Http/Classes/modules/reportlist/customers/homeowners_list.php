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

class homeowners_list
{
  public $modulename = 'Homeowners List';
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
    $companyid = $config['params']['companyid']; {
      $fields = ['radioprint', 'dprojectname'];
    }

    $col1 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as projectcode,
    '' as projectname,
    0 as projectid
    ");
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {

    $filter   = "";
    if ($config['params']['dataparams']['projectname'] != "") {
      $filter   .= " and i.projectid=" . $config['params']['dataparams']['projectid'];
    }

    $query = "select 
    cust.client,cust.clientname, cust.addr, cust.tin, cust.contact,i.barcode as meter
    from client as cust
    left join item as i on i.clientid = cust.clientid
    where cust.iscustomer=1 and cust.clientname<>'' 
    $filter 
    order by cust.addr";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeadertable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('C O D E', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('C U S T O M E R &nbsp N A M E', '', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('M E T E R  &nbsp N o.', '210', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('R E A D I N G', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function displayHeader($config, $recordCount)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

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
    $str .= $this->reporter->col('HOMEOWNER  LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $project = '';
    if ($config['params']['dataparams']['projectname'] == '') {
      $project = 'ALL';
    } else {
      $project = $config['params']['dataparams']['projectname'];
    }

    $str .= $this->reporter->col('PROJECT: ', 100, null, false, $border, '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($project, 600, null, false, $border, '', '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Total No. of Customers: ', 150, null, false, $border, '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($recordCount, 50, null, false, $border, '', '', $font, $fontsize, '', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 0;
    $page = 50;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $this->reporter->linecounter = 0;

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, count($result));
    $str .= $this->displayHeadertable($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->client, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->meter, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->endreport();
    return $str;
  }
}
