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
use App\Http\Classes\sbcscript\sbcscript;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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
    private $sbcscript;

    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
        $this->sbcscript = new sbcscript;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'radiooption'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
        ]);
        data_set($col1, 'radiooption.label', 'Group By');
        data_set($col1, 'radiooption.options', array(
            ['label' => 'Item Brand', 'value' => 0, 'color' => 'green'],
            ['label' => 'Item Category', 'value' => 1, 'color' => 'green'],
            ['label' => 'Car Brand', 'value' => 2, 'color' => 'green']
        ));

        $fields = ['radioretagging'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioretagging.label', 'Price Option');
        data_set($col2, 'radioretagging.options', array(
            ['label' => 'Default', 'value' => 0, 'color' => 'green'],
            ['label' => 'No Price', 'value' => 1, 'color' => 'green'],
            ['label' => 'No Price/With Picture', 'value' => 2, 'color' => 'green'],
            ['label' => 'With Price/With Picture', 'value' => 3, 'color' => 'green'],
        ));

        $fields = ['radioreporttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'radioreporttype.label', 'Category Option');
        data_set($col3, 'radioreporttype.options', array(
            ['label' => 'All Items', 'value' => 0, 'color' => 'green'],
            ['label' => 'New Arrival', 'value' => 1, 'color' => 'green'],
        ));

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select
        'PDFM' as print,
        0 as poption,
        0 as reporttype,
        0 as tagging,
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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $data = $this->data_query($config);
        $poption = $config['params']['dataparams']['poption'];

        switch ($poption) {
            case 0:
                return $this->itemBrandLayout($config, $data);
                break;
            case 1:
                return $this->itemCatLayout($config, $data);
                break;
            case 2:
                return $this->itemBrandLayout($config, $data);
                break;
        }
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $poption = $config['params']['dataparams']['poption'];
        $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : 0;

        $filter = '';
        $leftjoin = '';
        $groupby = '';
        $orderby = '';
        $query = '';

        switch ($poption) {
            case 0:
                $orderby = "order by brand";
                break;
            case 1:
                $orderby = "order by category";
                break;
            case 2:
                $orderby = "order by cb.brand";
                break;
        }

        if ($poption == 0) {
            $orderby = "order by brand, cbrand, partno";
            $groupby = "group by b.brand_desc, cat.name, subcat.name, i.partno, othcode, i.itemname, i.isnewarrival, p.positions, cb.brand, info.fyear, i.sizeid, `type`, amt";
        }

        if ($poption == 1) {
            $orderby = "order by brand, cbrand, partno";
            $groupby = "group by subcat.name, b.brand_desc, cat.name, i.partno, othcode, i.itemname, i.isnewarrival, p.positions, cb.brand, info.fyear, i.sizeid, `type`, amt";
            if ($reporttype == 1) {
                $filter .= " and i.isnewarrival = 1 ";
            }
        }

        if ($poption == 2) {
            $orderby = "order by (cb.brand is null or cb.brand = ''), cbrand, partno";
            $groupby = "group by b.brand_desc, cat.name, subcat.name, i.partno, othcode, i.itemname, m.model_name, i.isnewarrival, p.positions, cb.brand, info.fyear, i.sizeid, `type`, amt";
        }

        $query = "select ifnull(cat.name, '') as category, ifnull(subcat.name, '') as itemsubcategory,  ifnull(b.brand_desc, '') as brand, partno, othcode as equiv,
        concat('/images/product/',i.partno,'.JPG') as picture ,ifnull(i.itemname, '') as crmodel,i.isnewarrival,
        p.positions, cb.brand as cbrand, info.fyear as yrmodel,i.sizeid as type,
        `type` as stype, amt as price
        from item as i
        left join iteminfo as info on info.itemid = i.itemid
        left join model_masterfile as m on m.model_id = i.model
        left join itemcategory as cat on cat.line = i.category
        left join itemsubcategory as subcat on subcat.line = i.subcat
        left join frontend_ebrands as b on b.brandid = i.brand
        left join carbrand as cb on cb.id = i.carid
        left join positions as p on p.id = info.positionid
        where 1=1 $filter
        $groupby
        $orderby
        limit 50
        ;";
        // var_dump($query);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];
        $priceOption = $config['params']['dataparams']['tagging'];
        $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : 0;
        $printDate = date("m/d/y");
        $printTime = date("g:i:s A");

        $font = '';
        $fonts = '';
        $fontbold = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = $fonts = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }
        $fontsize = "10";
        $fontsize2 = "10";
        $border = "1px solid ";
        $groupby = "";
        $title = "";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', 'LETTER');
        PDF::SetMargins(20, 10, 20);

        switch ($poption) {
            case 0:
                $groupby = "ITEM BRAND";
                break;
            case 1:
                $groupby = "ITEM CATEGORY";
                break;
            case 2:
                $groupby = "CAR BRAND";
                break;
        }

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        if ($poption == 1) {
            $title = $reporttype == 1 ? '* * * NEW ARRIVAL * * *' : '';
        }

        $withPrice = ($priceOption == 0 || $priceOption == 3);
        $withPicture = ($priceOption == 2 || $priceOption == 3);

        if ($priceOption == 0) {
            $widths = [0, 100, 80, 140, 60, 60, 60, 70];  //570
        } elseif ($priceOption == 1) {
            $widths = [0, 110, 90, 160, 70, 70, 70, 0]; //570  = 100,80,180
        } elseif ($priceOption == 2) {
            $widths = [95, 90, 70, 135, 60, 60, 60, 0]; //570  = 80,70,80,100
        } elseif ($priceOption == 3) {
            $widths = [95, 85, 60, 120, 50, 50, 50, 60]; //570 = 80, 
        }

        PDF::SetFont($fonts, 'B', 19);
        PDF::MultiCell(null, 20, $title, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(0, 40, "\n\n\n");
        PDF::SetFont($fonts, 'B', $fontsize);
        PDF::MultiCell(70, 26, 'GROUP BY :', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(350, 25, $groupby, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(150, 25, 'Print Date: ' . strtoupper(date('d-F-Y')), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        PDF::SetFont($fonts, 'B', $fontsize2);
        if ($withPicture) {
            PDF::MultiCell($widths[0], 26, 'Picture', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        }

        PDF::MultiCell($widths[1], 26, 'Part No.', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell($widths[2], 26, 'Equivalent No', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell($widths[3], 26, 'Model Description', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell($widths[4], 26, 'Year Mod', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell($widths[5], 26, 'Position', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell($widths[6], 26, 'Type', 'TBLR', 'C', false, $withPrice ? 0 : 1, '', '', true, 0, false, true, 0, 'M', false);

        if ($withPrice) {
            PDF::MultiCell($widths[7], 26,  'Price', 'TBLR', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }
    }

    public function itemBrandLayout($config, $result)
    {
        $font = '';
        $fonts = '';
        $fontbold = '';

        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = $fonts = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }

        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];
        $priceOption = $config['params']['dataparams']['tagging'];

        $currentLabel = '';
        $grpLabel = '';
        $curBrand = '';
        $curBrand = '';
        $lastBrand = '';
        $borderStyle = '';
        $h = 15;

        $counted = count($result);
        $this->displayHeader($config);
        $limit = PDF::GetPageHeight() - 80;

        $withPrice = ($priceOption == 0 || $priceOption == 3);
        $withPicture = ($priceOption == 2 || $priceOption == 3);

        if ($priceOption == 0) {
            $widths = [0, 100, 80, 140, 60, 60, 60, 70];  //570
        } elseif ($priceOption == 1) {
            $widths = [0, 110, 90, 160, 70, 70, 70, 0]; //570  
        } elseif ($priceOption == 2) {
            $widths = [95, 90, 70, 135, 60, 60, 60, 0]; //570 
        } elseif ($priceOption == 3) {
            $widths = [95, 85, 60, 120, 50, 50, 50, 60]; //570 
        }

        $pick = function ($arr, $r) use ($withPicture) {
            if ($withPicture) {
                return implode("\n", $arr); // all wrapped lines go into one cell
            }
            return isset($arr[$r]) ? $arr[$r] : '';
        };

        for ($i = 0; $i < ($counted); $i++) {
            $maxrow = 1;
            $grpLabel = $result[$i]['brand'];

            $type = $result[$i]['type'];
            $positions = $result[$i]['positions'];
            $crmodel = $result[$i]['crmodel'];
            $partno = $result[$i]['partno'];
            $equiv = $result[$i]['equiv'];
            $price = $result[$i]['price'];
            $yrmodel = $result[$i]['yrmodel'];

            $arr_type = $this->reporter->fixcolumn([$type], 10, 0);
            $arr_positions = $this->reporter->fixcolumn([$positions], 12, 0);
            $arr_crmodel = $this->reporter->fixcolumn([$crmodel], 22, 0);
            $arr_partno = $this->reporter->fixcolumn([$partno], 16, 0);
            $arr_equiv = $this->reporter->fixcolumn([$equiv], 16, 0);
            $arr_price = $this->reporter->fixcolumn([$price], 16, 0);
            $arr_yrmodel = $this->reporter->fixcolumn([$yrmodel], 10, 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_type, $arr_positions, $arr_crmodel, $arr_partno, $arr_equiv, $arr_yrmodel]);

            $rowsToDraw = $withPicture ? 1 : $maxrow;
            $h = $withPicture ? max(45, $maxrow * 15) : 15; // min block height, grows when the text wraps
            $mh = $withPicture ? $h : 0;
            $brandH = ($curBrand != $result[$i]['brand'] && $result[$i]['brand'] != '') ? 30 : 0; // brand heading = 2 rows

            if (PDF::GetY() + $h + $brandH >= $limit) {
                PDF::MultiCell(570, null, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                $this->displayFooter($curBrand);
                $this->displayHeader($config, $counted);
            }
            // for ($r = 0; $r < $lines; $r++) {
            for ($r = 0; $r < $rowsToDraw; $r++) {
                // car brand line 
                if ($curBrand != $result[$i]['brand']) {
                    $curBrand = $result[$i]['brand'];
                    if ($curBrand != '') {
                        PDF::SetFont($fonts, 'B', $fontsize);
                        PDF::MultiCell(570, 15, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(570, 15, $curBrand, 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    }
                }

                // item row
                PDF::SetFont($fonts, '', $fontsize);
                if ($withPicture) {
                    $borderStyle = 'TBLR';
                } else {
                    $borderStyle = ($r == 0) ? 'TLR' : 'LR';
                }

                if ($withPicture) {
                    $hasImage = file_exists(public_path($result[$i]['picture']));
                    $rowX = PDF::GetX();
                    $rowY = PDF::GetY();
                    PDF::MultiCell($widths[0], $h, $hasImage ? '' : 'Image Unavailable', $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $h, 'M', false);
                    if ($hasImage) {
                        PDF::Image(public_path($result[$i]['picture']), $rowX + 1, $rowY + 1, 93, 43); // fixed size
                    }
                }

                $txtEquiv = $pick($arr_equiv, $r);
                PDF::MultiCell($widths[1], $h, $pick($arr_partno, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[2], $h, $txtEquiv != '' ? ' ' . $txtEquiv : '', $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[3], $h, $pick($arr_crmodel, $r), $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[4], $h, $pick($arr_yrmodel, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[5], $h, $pick($arr_positions, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[6], $h, $pick($arr_type, $r), $borderStyle, 'C', false, $withPrice ? 0 : 1, '', '', true, 0, false, true, $mh, 'M', false);

                if ($withPrice) {
                    PDF::MultiCell($widths[7], $h, ($r == 0 && $price != 0) ? number_format($price, 2) : ($r == 0 ? '-' : ''), ($priceOption == 0 || $priceOption == 3) ? $borderStyle : '', 'R', false, 1, '', '', true, 0, false, true, $mh, 'M', false);
                }
                $lastBrand = $result[$i]['brand'];
            }
        }
        PDF::MultiCell(570, 0,  '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        $this->displayFooter($curBrand);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function carBrandLayout($config, $result)
    {
        $font = '';
        $fonts = '';
        $fontbold = '';

        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = $fonts = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }

        $fontsize = "10";
        $priceOption = $config['params']['dataparams']['tagging'];

        $curBrand = '';
        $borderStyle = '';
        $newPage = false;
        $h = 15;

        $counted = count($result);

        $this->displayHeader($config, $counted);
        $limit = PDF::GetPageHeight() - 100;

        $withPrice = ($priceOption == 0 || $priceOption == 3);
        $withPicture = ($priceOption == 2 || $priceOption == 3);

        if ($priceOption == 0) {
            $widths = [0, 100, 80, 140, 60, 60, 60, 70];  //570
        } elseif ($priceOption == 1) {
            $widths = [0, 110, 90, 160, 70, 70, 70, 0]; //570
        } elseif ($priceOption == 2) {
            $widths = [95, 90, 70, 135, 60, 60, 60, 0]; //570
        } elseif ($priceOption == 3) {
            $widths = [95, 85, 60, 120, 50, 50, 50, 60]; //570
        }

        $pick = function ($arr, $r) use ($withPicture) {
            if ($withPicture) {
                return implode("\n", $arr);
            }
            return isset($arr[$r]) ? $arr[$r] : '';
        };

        for ($i = 0; $i < $counted; $i++) {
            // group label: car brand, or a fallback when the item has none
            $carBrand = (isset($result[$i]['cbrand']) && $result[$i]['cbrand'] != '') ? $result[$i]['cbrand'] : 'NO CAR BRAND';

            $type = $result[$i]['type'];
            $positions = $result[$i]['positions'];
            $crmodel = $result[$i]['crmodel'];
            $partno = $result[$i]['partno'];
            $equiv = $result[$i]['equiv'];
            $price = $result[$i]['price'];
            $yrmodel = $result[$i]['yrmodel'];

            $arr_type = $this->reporter->fixcolumn([$type], 10, 0);
            $arr_positions = $this->reporter->fixcolumn([$positions], 12, 0);
            $arr_crmodel = $this->reporter->fixcolumn([$crmodel], 22, 0);
            $arr_partno = $this->reporter->fixcolumn([$partno], 16, 0);
            $arr_equiv = $this->reporter->fixcolumn([$equiv], 16, 0);
            $arr_yrmodel = $this->reporter->fixcolumn([$yrmodel], 10, 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_type, $arr_positions, $arr_crmodel, $arr_partno, $arr_equiv, $arr_yrmodel]);


            $rowsToDraw = $withPicture ? 1 : $maxrow;
            $h = $withPicture ? max(50, $maxrow * 15) : 15;
            $mh = $withPicture ? $h : 0;

            for ($r = 0; $r < $rowsToDraw; $r++) {

                // new page: close the table, print the footer, start a new page.
                // $curBrand is kept, so the car brand label is NOT printed again on the new page
                if (PDF::GetY() + ($withPicture ? $h : 0) >= $limit) {
                    PDF::MultiCell(570, null, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    $this->displayFooter($curBrand);
                    $this->displayHeader($config, $counted);
                    $newPage = true;
                }

                if ($curBrand != $carBrand) {
                    if ($curBrand != '' && !$newPage) {
                        PDF::MultiCell(570, null, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    }

                    $curBrand = $carBrand;
                    PDF::SetFont($fonts, 'B', $fontsize);
                    PDF::MultiCell(570, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(570, 15, $curBrand, 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }

                $newPage = false;

                // item row
                PDF::SetFont($fonts, '', $fontsize);
                if ($withPicture) {
                    $borderStyle = 'TBLR';
                } else {
                    $borderStyle = ($r == 0) ? 'TLR' : 'LR';
                }

                // picture column (only when the price option includes pictures)
                if ($withPicture) {
                    $hasImage = file_exists(public_path($result[$i]['picture']));
                    $rowX = PDF::GetX();
                    $rowY = PDF::GetY();
                    PDF::MultiCell($widths[0], $h, $hasImage ? '' : 'Image Unavailable', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, $h, 'M', false);
                    if ($hasImage) {
                        PDF::Image(public_path($result[$i]['picture']), $rowX + 1, $rowY + 1, 93, 48);
                    }
                }

                $txtEquiv = $pick($arr_equiv, $r);

                PDF::MultiCell($widths[1], $h, $pick($arr_partno, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[2], $h, $txtEquiv != '' ? ' ' . $txtEquiv : '', $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[3], $h, $pick($arr_crmodel, $r), $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[4], $h, $pick($arr_yrmodel, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[5], $h, $pick($arr_positions, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[6], $h, $pick($arr_type, $r), $borderStyle, 'C', false, $withPrice ? 0 : 1, '', '', true, 0, false, true, $mh, 'M', false);

                if ($withPrice) {
                    PDF::MultiCell($widths[7], $h, ($r == 0 && $price != 0) ? number_format($price, 2) : ($r == 0 ? '-' : ''), $borderStyle, 'R', false, 1, '', '', true, 0, false, true, $mh, 'M', false);
                }
            }
        }

        // close the last car brand's table
        if ($curBrand != '') {
            PDF::MultiCell(570, null, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }

        $this->displayFooter($curBrand);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function itemCatLayout($config, $result)
    {
        $font = '';
        $fonts = '';
        $fontbold = '';

        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = $fonts = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
        }

        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];
        $poption = $config['params']['dataparams']['poption'];
        $priceOption = $config['params']['dataparams']['tagging'];

        $currentLabel = '';
        $grpLabel = '';
        $curCat = '';
        $curBrand = '';
        $lastBrand = '';
        $borderStyle = '';
        $h = 15;

        $counted = count($result);

        $this->displayHeader($config, $counted);
        $limit = PDF::GetPageHeight() - 100;

        $withPrice = ($priceOption == 0 || $priceOption == 3);
        $withPicture = ($priceOption == 2 || $priceOption == 3);

        if ($priceOption == 0) {
            $widths = [0, 100, 80, 140, 60, 60, 60, 70];  //570
        } elseif ($priceOption == 1) {
            $widths = [0, 110, 90, 160, 70, 70, 70, 0]; //570  = 100,80,180
        } elseif ($priceOption == 2) {
            $widths = [95, 90, 70, 135, 60, 60, 60, 0]; //570  = 80,70,80,100
        } elseif ($priceOption == 3) {
            $widths = [95, 85, 60, 120, 50, 50, 50, 60]; //570 = 80, 
        }

        // With a picture, an item is ONE row (wrapped lines are joined inside each cell).
        // Without a picture, each wrapped line is its own 15px row, as before.
        $pick = function ($arr, $r) use ($withPicture) {
            if ($withPicture) {
                return implode("\n", $arr);
            }
            return isset($arr[$r]) ? $arr[$r] : '';
        };

        for ($i = 0; $i < ($counted); $i++) {
            $grpLabel = $result[$i]['cbrand'];

            // sub-category label: items without one go under "NO SUB-CATEGORY" instead of an empty banner
            $subCat = ($result[$i]['itemsubcategory'] != '') ? $result[$i]['itemsubcategory'] : 'NO SUB-CATEGORY';

            $type = $result[$i]['type'];
            $positions = $result[$i]['positions'];
            $crmodel = $result[$i]['crmodel'];
            $partno = $result[$i]['partno'];
            $equiv = $result[$i]['equiv'];
            $price = $result[$i]['price'];
            $yrmodel = $result[$i]['yrmodel'];

            $arr_type = $this->reporter->fixcolumn([$type], 10, 0);
            $arr_positions = $this->reporter->fixcolumn([$positions], 12, 0);
            $arr_crmodel = $this->reporter->fixcolumn([$crmodel], 24, 0);
            $arr_partno = $this->reporter->fixcolumn([$partno], 16, 0);
            $arr_equiv = $this->reporter->fixcolumn([$equiv], 16, 0);
            $arr_yrmodel = $this->reporter->fixcolumn([$yrmodel], 10, 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_type, $arr_positions, $arr_crmodel, $arr_partno, $arr_equiv, $arr_yrmodel]);

            // row sizing: picture rows are at least 50px and grow if the text wraps
            $rowsToDraw = $withPicture ? 1 : $maxrow;
            $h = $withPicture ? max(50, $maxrow * 15) : 15;
            $mh = $withPicture ? $h : 0;

            for ($r = 0; $r < $rowsToDraw; $r++) {

                // new page (uses the real row height when there is a picture)
                if (PDF::GetY() + ($withPicture ? $h : 0) >= $limit) {
                    PDF::MultiCell(570, null, '', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    $this->displayFooter($curBrand);
                    $this->displayHeader($config, $counted);
                }

                // sub-category line
                if ($curCat != $subCat) {
                    $curCat = $subCat;
                    $curBrand = '';
                    $itemcatY = PDF::GetY() + 5;
                    PDF::SetFont($fonts, 'B', $fontsize);
                    PDF::SetY($itemcatY);
                    PDF::MultiCell(570, 15, $curCat, 'BTLR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                }

                // car brand line 
                if ($curBrand != $result[$i]['cbrand']) {
                    $curBrand = $result[$i]['cbrand'];
                    if ($curBrand != '') {
                        PDF::SetFont($fonts, 'B', $fontsize);
                        PDF::MultiCell(null, 5, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(570, 15, $curBrand, 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
                    }
                }

                // item row
                PDF::SetFont($fonts, '', $fontsize);
                if ($withPicture) {
                    $borderStyle = 'TBLR';
                } else {
                    $borderStyle = ($r == $maxrow - 1) ? 'BLR' : 'LR';
                }

                // picture column (only when the price option includes pictures)
                if ($withPicture) {
                    $hasImage = file_exists(public_path($result[$i]['picture']));
                    logger('dir exists: ' . (is_dir(public_path('images/product')) ? 'yes' : 'no') . ' | files: ' . json_encode(@scandir(public_path('images/product'))));
                    $rowX = PDF::GetX();
                    $rowY = PDF::GetY();
                    PDF::MultiCell($widths[0], $h, $hasImage ? '' : 'Image unavailable', 'TBLR', 'C', false, 0, '', '', true, 0, false, true, $h, 'M', false);
                    if ($hasImage) {
                        PDF::Image(public_path($result[$i]['picture']), $rowX + 1, $rowY + 1, 93, 48);
                    }
                }


                $txtEquiv = $pick($arr_equiv, $r);

                PDF::MultiCell($widths[1], $h, $pick($arr_partno, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[2], $h, $txtEquiv != '' ? ' ' . $txtEquiv : '', $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[3], $h, $pick($arr_crmodel, $r), $borderStyle, 'L', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[4], $h, $pick($arr_yrmodel, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[5], $h, $pick($arr_positions, $r), $borderStyle, 'C', false, 0, '', '', true, 0, false, true, $mh, 'M', false);
                PDF::MultiCell($widths[6], $h, $pick($arr_type, $r), $borderStyle, 'C', false, $withPrice ? 0 : 1, '', '', true, 0, false, true, $mh, 'M', false);

                if ($withPrice) {
                    PDF::MultiCell($widths[7], $h, ($r == 0 && $price != 0) ? number_format($price, 2) : ($r == 0 ? '-' : ''), $borderStyle, 'R', false, 1, '', '', true, 0, false, true, $mh, 'M', false);
                }

                $lastBrand = $result[$i]['cbrand'];
            }
        }
        $this->displayFooter($curBrand);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function displayFooter($brand)
    {
        $fonts = 'ARIAL';
        $fontsize = '8';

        // pushes the footer toward the bottom of the page
        $breakMargin = PDF::getBreakMargin();
        PDF::SetAutoPageBreak(false);
        PDF::SetY(-40);

        PDF::SetFont($fonts, 'B', $fontsize);
        PDF::MultiCell(360, 20, 'NOTE: WE SERVE THE RIGHT TO CHANGE PRICES W/OUT PRIOR NOTICE.', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(150, 20, $brand, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, 'Page ' . PDF::getAliasNumPage() . '/' . PDF::getAliasNbPages(), 0, 'R', false, 1);

        PDF::SetAutoPageBreak(true, $breakMargin);
    }

    public function sbcscript($config)
    {
        return $this->sbcscript->pricelist_report($config);
    }
} // end class