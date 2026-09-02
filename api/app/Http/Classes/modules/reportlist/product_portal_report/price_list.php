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

class price_list
{
    public $modulename = 'Price List';
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
        $fields = ['radioprint', 'radiooption'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radiooption.label', 'Group By');
        data_set($col1, 'radiooption.options', array(
            ['label' => 'Default', 'value' => 0, 'color' => 'green'],
            ['label' => 'Item Brand', 'value' => 1, 'color' => 'green'],
            ['label' => 'Item Category', 'value' => 2, 'color' => 'green'],
            ['label' => 'Car Brand', 'value' => 3, 'color' => 'green'],
            ['label' => 'No Price', 'value' => 4, 'color' => 'green'],
            ['label' => 'No Price/With Picture', 'value' => 5, 'color' => 'green'],
            ['label' => 'With Price/With Picture', 'value' => 6, 'color' => 'green'],
        ));

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select
        'default' as print,
        0 as poption,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as infratype

     ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $poption = $config['params']['dataparams']['poption'];

        $filter = '';
        $leftjoin = '';
        $orderby = '';
        $query = '';

        switch ($poption) {
            case 1:
                $orderby = "order by brand";
                break;
            case 2:
                $orderby = "order by category";
                break;
            case 3:
                $orderby = "order by cb.brand";
                break;
            case 4:
            case 5:
            case 6:
                // picture-based price list: only show items that actually have a picture
                $filter = " and i.picture is not null and i.picture <> '' ";
                $orderby = "order by partno";
                break;
        }


        $query = "select ifnull(cat.name, '') as category, ifnull(b.brand_desc, '') as brand, partno, othcode as equiv,i.picture, ifnull(m.model_name, '') as crmodel,
        p.positions, cb.brand as cbrand, info.fyear as yrmodel,
        `type` as stype, amt as price
        from item as i
        left join iteminfo as info on info.itemid = i.itemid
        left join model_masterfile as m on m.model_id = i.model
        left join itemcategory as cat on cat.line = i.category
        left join frontend_ebrands as b on b.brandid = i.brand
        left join carbrand as cb on cb.id = i.carid
        left join positions as p on p.id = info.positionid
        where 1=1 $filter
        $orderby";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    // Builds the PICTURE table cell

    private function buildPictureCell($picturePath, $boxW, $boxH, $imgW, $imgH)
    {
        $box = '<div style="width:' . $boxW . 'px;height:' . $boxH . 'px;border:1px solid #dcdcdc;background:#ffffff;text-align:center;line-height:' . $boxH . 'px;font-size:11px;">PICTURE</div>';

        if (!empty($picturePath)) {
            $src = asset('/public' . ltrim($picturePath));
            $box = '<div style="width:' . $boxW . 'px;height:' . $boxH . 'px;border:1px solid #dcdcdc;background:#ffffff;text-align:center;">
                <img src="' . $src . '" style="width:' . $imgW . 'px;height:' . $imgH . 'px;object-fit:contain;margin-top:5px;">
            </div>';
        }

        return $box;
    }

    // Allows a value cell to wrap onto a second line, but caps it at 2 lines —

    private function clampCell($text, $lines = 2)
    {
        return '<div style="display:-webkit-box;-webkit-line-clamp:' . $lines . ';-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;">' . $text . '</div>';
    }

    public function displayHeader($config, $recordCount)
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
        $layoutsize = '800';
        $font = 'Tahoma';
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

        if ($poption == 4) {
            // WITH PRICE / WITH PICTURE — picture column on the far left, PRICE kept on the far right
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PART #', '130', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('EQUIVALENT #', '130', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('CAR MODEL', '90', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('YEAR MODEL', '90', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('POSITION', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('SIZE/TYPE', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } elseif ($poption == 5) {
            // NO PRICE / WITH PICTURE — picture column on the far right, no PRICE column
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PICTURE', '200', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('PART #', '125', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('EQUIVALENT #', '125', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('CAR MODEL', '110', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('YEAR MODEL', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('POSITION', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('SIZE/TYPE', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } elseif ($poption == 6) {
            // WITH PRICE / WITH PICTURE — picture column on the far left, PRICE kept on the far right
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PICTURE', '140', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('PART #', '130', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('EQUIVALENT #', '130', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('CAR MODEL', '90', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('YEAR MODEL', '90', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('POSITION', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('SIZE/TYPE', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('PRICE', '90', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            // 25, 110, 115, 125, 80, 110, 110, 115
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '25', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, '');
            $str .= $this->reporter->col('PART #', '110', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B', '', '');
            $str .= $this->reporter->col('EQUIVALENT #', '115', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('CAR MODEL', '125', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('YEAR MODEL', '80', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B');
            $str .= $this->reporter->col('POSITION', '110', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B', '', '');
            $str .= $this->reporter->col('SIZE/TYPE', '110', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B', '', '');
            $str .= $this->reporter->col('PRICE', '115', null, false, '2px solid', 'TB', 'C', $font, $fontsize2, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        return $str;
    }

    public function reportDefaultLayout($config, $result)
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
        $limitPerPage = ($poption == 4 || $poption == 5) ? 6 : 42;
        $rowCount = 0;
        $currentLabel = '';
        $grpLabel = '';

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        // $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '125px;margin-top:5px;');
        $str .= $this->displayHeader($config, count($result));

        foreach ($result as $data) {

            switch ($poption) {
                case 1:
                    $grpLabel = $data->brand;
                    break;
                case 2:
                    $grpLabel = $data->category;
                    break;
                case 3:
                    $grpLabel = $data->cbrand;
                    break;
                default:
                    $grpLabel = '';
            }

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            if ($poption != 0 && $currentLabel != $grpLabel) {
                $currentLabel = $grpLabel;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($grpLabel, '800', null, false, '2px solid', '', 'L', $font, $fontsize, 'B');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $rowCount++;
            }

            if ($poption == 4) {
                // NO PRICE 
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($this->clampCell($data->partno), '125', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($this->clampCell($data->equiv), '125', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->crmodel, '110', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->yrmodel, '80', null, false, '2px solid', '', 'CT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->positions, '80', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->stype, '80', null, false, '2px solid', '', 'CT', $font, $fontsize, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } elseif ($poption == 5) {
                // NO PRICE / WITH PICTURE
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($this->buildPictureCell($data->picture, 180, 140, 170, 130), '200', null, false, '2px solid', '', 'C', $font, $fontsize, '');
                $str .= $this->reporter->col($this->clampCell($data->partno), '125', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($this->clampCell($data->equiv), '125', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->crmodel, '110', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->yrmodel, '80', null, false, '2px solid', '', 'CC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->positions, '80', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->stype, '80', null, false, '2px solid', '', 'CC', $font, $fontsize, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } elseif ($poption == 6) {
                // WITH PRICE / WITH PICTURE
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($this->buildPictureCell($data->picture, 130, 120, 125, 115), '140', null, false, '2px solid', '', 'C', $font, $fontsize, '');
                $str .= $this->reporter->col($this->clampCell($data->partno), '130', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($this->clampCell($data->equiv), '130', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->crmodel, '90', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->yrmodel, '90', null, false, '2px solid', '', 'CC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->positions, '80', null, false, '2px solid', '', 'LC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->stype, '80', null, false, '2px solid', '', 'CC', $font, $fontsize, '');
                $str .= $this->reporter->col($data->price != 0 ? number_format($data->price, 2) : '-', '90', null, false, '2px solid', '', 'RC', $font, $fontsize, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } else {
                // 25, 110, 115, 125, 80, 110, 110, 115
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '25', null, false, '2px solid', '', 'CT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->partno, '110', null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->equiv, '115', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->crmodel, '125', null, false, '2px solid', '', 'LT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->yrmodel, '80', null, false, '2px solid', '', 'CT', $font, $fontsize, '');
                $str .= $this->reporter->col($data->positions, '110', null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->stype, '110', null, false, '2px solid', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->price != 0 ? number_format($data->price, 2) : '-', '115', null, false, '2px solid', '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $rowCount++;
        }

        $str .= $this->reporter->endreport();
        return $str;
    }
} // end class