<?php

namespace App\Http\Classes\modules\reportlist\warehousing_report;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class qr_code_generator
{
  public $modulename = 'QR Code Generator';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



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
    $fields = ['radioprint', 'radioreporttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'Item Code', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Pallet', 'value' => '1', 'color' => 'teal'],
        ['label' => 'Location', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['qrgenerator'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '0' as reporttype,
    '' as qrgenerator,
    '' as id,
    '' as name
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

    $result = $this->reportDefaultLayout_DETAILED($config);

    return $result;
  }

  public function reportDefault($config)
  {

    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $id          = $config['params']['dataparams']['id'];

    $filter = "";

    switch ($reporttype) {
      case 0:
        $qry = "select itemid as id, barcode as qrcode, itemname from item where itemid = '$id'";
        break;
      case 1:
        $qry = "select line as id, name as qrcode from pallet where line = '$id'";
        break;
      case 2:
        $qry = "select line as id, name as qrcode from checkerloc where line = '$id'";
        break;
    }

    return $qry;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $qrgenerator = $config['params']['dataparams']['qrgenerator'];
    $type = $config['params']['dataparams']['reporttype'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(QrCode::size(120)->generate($result[0]->qrcode), null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='margin-top:-20px; margin-left:17px;'>" . $result[0]->qrcode . "</div>", null, null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
