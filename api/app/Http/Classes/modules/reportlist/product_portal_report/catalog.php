<?php

namespace App\Http\Classes\modules\reportlist\product_portal_report;

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

class catalog
{
  public $modulename = 'Catalog';
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
    $fields = ['radioprint', 'brand', 'brand2', 'category','carbrand'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');
    data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']]);
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
      return $this->coreFunctions->opentable("select 
    'default' as print,
     0 as category,
     '' as categoryname,
    
     '' as brand,
     0 as brandid,
     '' as brandname,
     
     '' as brand2,
     0 as brandid2,
     '' as brandname2,

     '' as carbrand,
     0 as carid");
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
  // LAYOUT OF REPORT
  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout_DETAILED($config);

    return $result;
  }
  // RESULT QUERY
  public function reportDefault($config)
  {
    $query = $this->reportQuery_DETAILED($config);


    return $this->coreFunctions->opentable($query);
  }

    public function reportQuery_DETAILED($config)
    {
        // QUERY
        $brandname     = $config['params']['dataparams']['brandname'];
        $brandname2     = $config['params']['dataparams']['brandname2'];
        $brandid     = $config['params']['dataparams']['brandid'];
        $brandid2    = $config['params']['dataparams']['brandid2'];
        $catid     = $config['params']['dataparams']['category'];
        $carid     = $config['params']['dataparams']['carid'];
        $carbrand     = $config['params']['dataparams']['carbrand'];
        $categoryname     = $config['params']['dataparams']['categoryname'];

        $filter = '';
        if ($brandname != '' && $brandname2 !='') {
            $filter .= " and ( i.brand = '$brandid' or i.brand='$brandid2') ";
        }else{
            if($brandname != ''){
            $filter .= " and i.brand = '$brandid'";
            }
            if($brandname2 !=''){
            $filter .= " and i.brand = '$brandid2'";
            }
        }

        if ($categoryname != '') {
        $filter .= " and i.category = '$catid'";
        }

        if ($carbrand != '') {
        $filter .= " and i.carid = $carid";
        }

        $query = "
        select i.itemname, cat.name as categoryname, i.partno as partname,i.picture,br1.brand_desc,car.brand as carbrand
        from item as i
        left join frontend_ebrands as br1 on br1.brandid=i.brand
        left join itemcategory as cat on cat.line=i.category
        left join carbrand as car on car.id=i.carid
        where 1=1 $filter";
        return $query;
    }

  private function default_displayHeader($config)
  {
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CATALOG', null, null, false, $border, '', 'L', $font, '20', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->pagenumber('Page ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    return $str;
  }



public function reportDefaultLayout_DETAILED($config)
{
    $result = $this->reportDefault($config);
    $count = 8;
    $page = 8;
    $rowCount = 0;   // bilang ng rows na may 2 products
    $itemCount = 0;  // bilang ng valid products na na-display

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "12";

    if (empty($result)) {
        return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {

        // SKIP KAPAG WALANG LAMAN ANG PRODUCT
        // $hasData =
        //     !empty($data->itemname) ||
        //     !empty($data->picture) ||
        //     !empty($data->brand_desc) ||
        //     !empty($data->categoryname) ||
        //     (isset($data->carbrand) && !empty($data->carbrand)) ||
        //     !empty($data->partname);

        // if (!$hasData) {
        //     continue;
        // }

        // Ito ang frame ng picture.
        // Kung gusto mong palakihin o paliitin ang image box,
        // dito mo babaguhin:
        // width:175px;
        // height:135px;
        $picture = '
        <div style="width:180px; height:135px; border:1px solid #dcdcdc;background:#ffffff;text-align:center;  line-height:135px;font-size:11px; ">PICTURE </div>';

        if (!empty($data->picture)) {
            // $src = asset(ltrim($data->picture, '/'));
            $src = asset('/public' . ltrim($data->picture));
            // ACTUAL IMAGE SETTINGS
            // width:165px  = lapad ng actual image
            // height:125px = taas ng actual image
            // margin-top:5px = konting center spacing sa taas
            $picture = '
            <div style="width:180px; height:135px; border:1px solid #dcdcdc;background:#ffffff;  text-align:center; ">
                <img src="' . $src . '" style="width:170px; height:125px; object-fit:contain; margin-top:5px;  ">
            </div>';
        }

        // Label = gray
        // Value = bold
        //
        // width:75px = lapad ng label column
        // width:8px  = lapad ng colon column
        $details  = '<table style="width:100%; border-collapse:collapse; font-size:13px; line-height:1.3;">';

        $details .= '
        <tr>
            <td style="width:100px;color:#666;">Item Brand</td>
            <td style="width:8px;">:</td>
            <td><b>' . (!empty($data->brand_desc) ? $data->brand_desc : '') . '</b></td>
        </tr>';

        $details .= '
        <tr>
            <td style="color:#666;">Item Category</td>
            <td>:</td>
            <td><b>' . (!empty($data->categoryname) ? $data->categoryname : '') . '</b></td>
        </tr>';

        $details .= '
        <tr>
            <td style="color:#666;">Car Brand</td>
            <td>:</td>
            <td><b>' . (!empty($data->carbrand) ? $data->carbrand : '') . '</b></td>
        </tr>';

        $details .= '
        <tr>
            <td style="color:#666;">Part No</td>
            <td>:</td>
            <td><b>' . (!empty($data->partname) ? $data->partname : '') . '</b></td>
        </tr>';

       
        $details .= '
        <tr>
            <td style="color:#666;">Description</td>
            <td>:</td>
            <td><b>' . (!empty($data->itemname) ? $data->itemname : '') . '</b></td>
        </tr>';  

        $details .= '</table>';

        // PRODUCT CARD
        // 2 cards per row ang layout.
        //
        // width:490px = lapad ng bawat card
        // padding:8px = space sa loob ng card
        // margin-bottom:8px = pagitan ng bawat product row
        //
        // Binawasan ang width para magkaroon ng approx 25px margin
        // sa kaliwa at kanan ng buong page.
        //
        $card = '
        <div style="width:500px;  border:1px solid #d9d9d9;  background:#fafafa; padding:8px; margin-bottom:8px; "> 
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <!-- PICTURE COLUMN -->
                    <td style="width:185px;vertical-align:top;">' . $picture . ' </td>

                    <!-- SPACE NG PICTURE AT DETAILS -->
                    <td style="width:5px;"></td>

                    <!-- DETAILS COLUMN -->
                    <td style="vertical-align:top;padding-top:12px;"> ' . $details . ' </td>
                </tr>
            </table>
        </div>';

        // DISPLAY: 2 PRODUCTS KADA ROW
        // Kapag even ang itemCount, ibig sabihin start ng bagong row.
        if ($itemCount % 2 == 0) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
        }

      
        $str .= $this->reporter->col($card, '500',null,  false,   '',  '', 'L', $font,  $fontsize,  '', '',  '' );

        $itemCount++;

        // Kapag pangalawang product na sa row, isara ang row.
        if ($itemCount % 2 == 0) {
            $str .= $this->reporter->endrow();
            $rowCount++;

            // 8 rows bawat page.
            // Dahil 2 products per row, 16 products per page.
            if ($rowCount == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->begintable($layoutsize);

                $page += $count;
            }
        }
    }

    // ODD NUMBER OF PRODUCTS
    // Kapag odd ang valid products, halimbawa 15,
    // kailangan lagyan ng blank column sa kanan
    // para hindi masira ang last row.
    if ($itemCount % 2 == 1) {
        $str .= $this->reporter->col('',  '475',  null,  false,  '', '', 'L', $font, $fontsize,  '',  '', '' );

        $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
}
}//end class