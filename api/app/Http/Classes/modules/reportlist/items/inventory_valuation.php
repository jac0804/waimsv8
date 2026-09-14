<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class inventory_valuation
{
    public $modulename = 'Inventory Valuation with Cost';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:800px;max-width:800px;';
    public $directprint = false;

    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        // $fields = ['radioprint', 'radiooption'];
        $fields = ['radioprint', 'start'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', array(
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ));
        // data_set($col1, 'radiooption.options', array(
        //     ['label' => 'Default', 'value' => 0, 'color' => 'green'],
        //     // ['label' => 'Item Brand', 'value' => 1, 'color' => 'green'],
        //     // ['label' => 'Item Category', 'value' => 2, 'color' => 'green'],
        //     // ['label' => 'Car Brand', 'value' => 3, 'color' => 'green'],
        //     // ['label' => 'No Price', 'value' => 4, 'color' => 'green'],
        //     // ['label' => 'No Price/With Picture', 'value' => 5, 'color' => 'green'],
        //     // ['label' => 'With Price/With Picture', 'value' => 6, 'color' => 'green'],
        // ));

        $fields = ['divsion', 'categoryname'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $startdate = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select
        'default' as print,
        0 as poption,
        '" . $startdate . "' as start,
        left(now(),10) as end,
        '' as infratype,
        '' as categoryname,
        '' as divsion

     ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        // $data = $this->data_query($config);
        return $this->reportDefaultLayout($config);
    }

    // public function data_query($config)
    // {
    //     $companyid = $config['params']['companyid'];
    //     $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    //     $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    //     $poption = $config['params']['dataparams']['poption'];

    //     $filter = '';
    //     $leftjoin = '';
    //     $orderby = '';
    //     $query = '';

    //     switch ($poption) {
    //         case 1:
    //             $orderby = "order by brand";
    //             break;
    //         case 2:
    //             $orderby = "order by category";
    //             break;
    //         case 3:
    //             $orderby = "order by cb.brand";
    //             break;
    //         case 4:
    //         case 5:
    //         case 6:
    //             // picture-based price list: only show items that actually have a picture
    //             // $filter = " and i.picture is not null and i.picture <> '' ";
    //             $orderby = "order by partno";
    //             break;
    //     }


    //     $query = "select ifnull(cat.name, '') as category, ifnull(b.brand_desc, '') as brand, partno, othcode as equiv,
    //     concat('/images/product/',i.partno,'.PNG') as picture ,ifnull(m.model_name, '') as crmodel,
    //     p.positions, cb.brand as cbrand, info.fyear as yrmodel,
    //     `type` as stype, amt as price
    //     from item as i
    //     left join iteminfo as info on info.itemid = i.itemid
    //     left join model_masterfile as m on m.model_id = i.model
    //     left join itemcategory as cat on cat.line = i.category
    //     left join frontend_ebrands as b on b.brandid = i.brand
    //     left join carbrand as cb on cb.id = i.carid
    //     left join positions as p on p.id = info.positionid
    //     where 1=1 $filter
    //     $orderby";
    //     // var_dump($query);
    //     return $this->coreFunctions->opentable($query);
    // }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];
        $start = date("M-d-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("M-d-Y", strtotime($config['params']['dataparams']['end']));
        $printDate = date("m/d/y");
        $printTime = date("g:i:s A");

        $str = '';
        $layoutsize = '1000';
        $font = 'Times New Roman';
        $fontsize = "10";
        $fontsize2 = "9";
        $border = "1px solid ";
        $groupby = "";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        // $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($reporttimestamp, '800', null, false, '', '', 'L', $font, $fontsize);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        switch ($poption) {
            case 1:
                $groupby = "ITEM BRAND";
                break;
            case 2:
                $groupby = "ITEM CATEGORY";
                break;
            case 3:
                $groupby = "CAR BRAND";
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<span style="color:#8B0000;">PRICE LIST</span>', null, null, false, '10px solid', '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '400');
        // $str .= $this->reporter->pagenumber('Page', '100', null, false, $border, '', 'R', $font, $fontsize , '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GROUP BY :', '110', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($poption != 0 ? $groupby : '', '220', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '470');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '3px solid', 'B', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // 120, 320, 100, 120, 100, 120, 120
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Product Code', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Product Name', '320', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('U O M', '100', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Stocks', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Lot#', '100', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('Latest Cost', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('Value', '120', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '310', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize2, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $layoutsize = '800';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];

        // if (empty($result)) {
        //     return $this->othersClass->emptydata($config);
        // }

        // Picture rows are taller than plain text rows, so fewer fit per page.
        if ($poption == 5) {
            $limitPerPage = 6;
        } elseif ($poption == 6) {
            $limitPerPage = 7;
        } else {
            $limitPerPage = 35;
        }
        $rowCount = 0;
        $currentLabel = '';
        $grpLabel = '';

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        // $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '125px;margin-top:5px;');
        $str .= $this->displayHeader($config);

        $str .= $this->reporter->endreport();
        return $str;
    }
} // end class
