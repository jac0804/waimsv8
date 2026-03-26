<?php

namespace App\Http\Classes\modules\reportlist\hris_reports;

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



class gender_statistic
{
    public $modulename = 'Gender Statistics Report';
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

        $fields = ['radioprint', 'radioreporttype', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Gender Statistic Overview', 'value' => '0', 'color' => 'red'],
            ['label' => 'Gender Statistic Pie Graph', 'value' => '1', 'color' => 'red']
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'blue']]);
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 'default' as print,   '0' as reporttype";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

  
    public function reportdata($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0':
                $str = $this->reportDefaultLayout($config);
                return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
                break;
            case '1':
                $reportHtml = '';
                $result = $this->default_query($config);
                if (!empty($result)) {
                    $reportHtml .= $this->pie_displayHeader($config);
                    list($svalues, $labels, $color) = $this->chartdata($result);
                    $piehere = $this->createpie($svalues, $labels, $color);
                    $reportHtml .= "<div style='text-align:center;'><img src='{$piehere}' alt='Generation Statistics Pie Graph'></div>";
                }
                return [
                    'status' => 'true',
                    'msg' => 'Report generated with embedded static charts.',
                    'report' => $reportHtml,
                    'graph' => $piehere
                ];
                break;
        }
        // $str = $this->reportplotting($config);
        // return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function totalemp($config)
    {
        $query = " select count(empid) as tlemp from  employee ";
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($filters)
    {
        $qry = "select m,sum(c) as c from
        (select distinct ifnull(gender,'')  as m, count(gender) as c
        from employee  where gender != '' 
        group by gender) as t
        group by t.m 
        ORDER BY (m = 'Male') desc, m";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    private function generateDefaultHeader($params)
    {
        $str = '';
        $fontsize = '12';
        $font = $this->companysetup->getrptfont($params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $layoutsize = '1000';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Gender Statistics ', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Gender ', '200', '15', $bgcolors, '1px solid ', '', 'C', $font, $fontsize, 'B',  $fontcolor, '10px');
        $str .= $this->reporter->col('Gender Distribution', '300', '15', $bgcolors, '1px solid ', '', 'L', $font, $fontsize, 'B',  $fontcolor, '10px');
        $str .= $this->reporter->col('Percentage', '200',  '15', $bgcolors, '1px solid ', '', 'L', $font, $fontsize, 'B',  $fontcolor, '10px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }



    private function pie_displayHeader($params)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($params['params']);

        $layoutsize = '1000';
        $str .= '<br><br><br><br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Gender Statistics Pie Graph ', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private function default_gender_comparison_graph($config, $data)
    {
        $datahere = [];
        $cat = [];
        $total = 0;
        foreach ($data as $key => $value) {
            $total += $value->c;
        }
        foreach ($data as $key => $value) {
            $datahere[$key] = round(($value->c /  $total) * 100);
            $cat[$key] = $value->m . ' (' . $value->c . ')';
        }


        //bar
        $series = [['name' => 'Gender', 'data' => $datahere]];
        $chartoption = [
            'chart' => ['type' => 'bar', 'height' => 500],
            'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
            'title' => ['text' => 'Gender ', 'align' => 'left', 'style' => ['color' => 'black']],
            'dataLabels' => ['enabled' => false],

            'xaxis' => ['title' => ['text' => 'Gender'], 'categories' => $cat],
            'yaxis' => ['title' => ['text' => ''], 'max' => 50],
            'fill' => ['opacity' => 1]
        ];


        $config['sbcgraph']['gender'] = ['series' => $series, 'option' => $chartoption];
        return array('series' => $series, 'chartoption' => $chartoption);
    }




    public function reportDefaultLayout($config)
    {
        $result = $this->default_query($config);
        $totalemp = $this->totalemp($config);
        $totalem = $totalemp[0]->tlemp;
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->generateDefaultHeader($config);

        // $colors = ['#DCDCDC', '#C0C0C0', '#808080', '#696969'];
        //Gainsboro ,Silver, Gray, dim gray 

        $totaldist = 0;

        foreach ($result as $key => $data) {

            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('' . $data->m, '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->c, '300', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
            if ($totalem == 0) {
                $percentage = '';
            } else {
                $percentage = number_format(($data->c / $totalem) * 100, 0) . '%';
            }
            $str .= $this->reporter->col($percentage, '200', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
            $totaldist += $data->c;
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $total = $totaldist != 0 ? number_format($totaldist, 0) : 0;

        if ($totalem == 0) {
            $percentage = '';
        } else {
            $percentage = number_format(($total / $totalem) * 100, 0) . '%';
        }

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->col('Total', '200', null, '', $border, 'LBR', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($total, '300', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($percentage, '200', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        // $str .= $this->reporter->printline();
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }


    private function randomColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    private function chartdata($data)
    {
        $values = [];
        $labels = [];
        $color = [];
        $total = 0;

        // calculte ang total ng lahat ng count (c)
        foreach ($data as $d) {
            $total += $d->c;
        }
        // var_dump($total); 
        // Para sa bawat category, kunin ang count, percentage, at buuin ang label
        foreach ($data as $d) {
            $values[] = $d->c;  // Raw count para sa pie chart
            $percent = number_format(($d->c / $total) * 100, 2);  // Percentage ng bawat bahagi
            // $color[] = $d->color;
            $labels[] = $d->m . ' (' . $d->c . ' - ' . $percent . '%)';  // Label format: "category (count - percent%)"
            $color[] = $this->randomColor();
        }
        return [$values, $labels, $color];
    }

    private function createpie($data, $labels, $colors)
    {
        $font = database_path() . '/images/fonts/ARIAL.ttf';  //path ng font

        // var_dump($colors); //#5FFB17  hex colors 
        $width = 1000;   // Lapad ng canvas 
        $height = 600;   // Taas ng canvas
        $img = imagecreatetruecolor($width, $height); // Gumawa ng bagong blangkong canvas 

        $white = imagecolorallocate($img, 255, 255, 255); // Mag-assign ng kulay puti (255,255,255 = white)
        imagefill($img, 0, 0, $white); // Kulayan ng puti ang buong canvas

        $total = array_sum($data); // Kunin ang total ng lahat ng values sa data (gagamitin para sa percentage)
        $angleStart = 0; // Simula ng unang slice (0 degrees)

        $centerX = 400; // X-position ng gitna ng pie chart
        $centerY = 330; // Y-position ng gitna ng pie chart
        $diameter = 500; // Gaano kalaki ang pie chart (diameter = buong bilog)

        $black = imagecolorallocate($img, 0, 0, 0);  // Gumawa ng itim na kulay para sa text

        // Para ma-center ng patayo ang mga legend text sa gilid ng chart
        $yStart = $centerY - (count($labels) * 12 / 2);
        // 12 =  height ng bawat label. Divide by 2 para magstart mula sa taas ng gitna

        foreach ($data as $i => $value) {
            $angle = ($value / $total) * 360;
            // Halimbawa: Kung 50 ang value at total ay 100, magiging (50/100)*360 = 180 degrees
            // Ibig sabihin, kalahati ng bilog ang slice

            $colorHex = $colors[$i % count($colors)]; //kukunin ko yung kulay na nasa parameter 

            //kukunin ang rgb ng slice color-yung color na galing sa parameter para gamitin sa shadow effect mala 3d na pie

            // Gamit ang darker version ng mismong slice color para natural tingnan 
            //shadow shadow effect dito

            // Tama na ang pagkuha ng rgb dito, colorHex ay string "#5FFB17"
            $rgb = sscanf($colorHex, "#%02x%02x%02x"); // Kunin ang RGB ng slice color 
            // yung rgb na kinukuha dito ay gagamitin sa shadow effect
            $depth = 20; // Gaano kakapal ang "3D" shadow (mas mataas = mas makapal)

            for ($d = $depth; $d > 0; $d--) {
                // Gumawa ng darker version ng slice color
                $darkColor = imagecolorallocate(
                    $img,
                    max($rgb[0] - 40, 0),  // Bawasan ng 40 ang bawat RGB value
                    max($rgb[1] - 40, 0),
                    max($rgb[2] - 40, 0)
                );

                imagefilledarc(
                    $img,
                    $centerX,
                    $centerY + $d, // I-offset pa-baba ang shadow
                    $diameter,
                    $diameter,
                    $angleStart,
                    $angleStart + $angle,
                    $darkColor,
                    IMG_ARC_PIE
                );
            }

            //Main na Slice 
            $mainColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]); // Original bright color

            imagefilledarc(
                $img,
                $centerX,
                $centerY,
                $diameter,
                $diameter,
                $angleStart,
                $angleStart + $angle,
                $mainColor,
                IMG_ARC_PIE
            );

            $angleMid = $angleStart + ($angle / 2);
            // Kinuha ang gitna ng slice. Gagamitin para mailagay sa gitna ang percentage text

            $angleRad = deg2rad($angleMid);
            // Kinonvert ang degrees papuntang radians dahil ang cos() at sin() ay gumagamit ng radians
            // Radian ay unit ng angle tulad ng degrees. Kailangan ito para gumana ang cos() at sin().
            //  deg2rad() ay function para i-convert ang degrees papuntang radians.
            // Ginagamit ito kasi ang cos() at sin() ay hindi tumatanggap ng degrees, radians ang kailangan.

            $labelRadius = $diameter / 2.5;
            // Gaano kalayo ang text mula gitna. Di masyadong sa dulo para di sumobra sa bilog
            // Ang diameter ay kabuuang laki ng bilog. Dinivide sa 2.5 para yung text ay lumabas malapit sa gitna ng slice, hindi sa dulo.

            $labelX = $centerX + cos($angleRad) * $labelRadius;
            $labelY = $centerY + sin($angleRad) * $labelRadius;
            // Gamit ang cos() at sin() para kunin ang exact X,Y position sa bilog kung saan ilalagay ang percentage

            $percent = number_format(($value / $total) * 100, 2);
            // Kinukuha ang percentage ng slice (hal. 50/100 = 50%)

            // imagestring(
            //     $img,                       // $img ay ang canvas image na ginagawa natin gamit ang imagecreatetruecolor.
            //     10,                         // Font size
            //     (int)$labelX - 10,         // X-position ng text, -10 para medyo nasa gitna
            //     (int)$labelY - 7,          // Y-position ng text, -7 para ayusin vertical alignment
            //     "$percent%",               // Text na ilalagay (halimbawa: "50%")
            //     $black                     // Kulay ng text
            // );

            imagettftext(
                $img,
                14, // font size
                0, // angle
                (int)$labelX - 20,
                (int)$labelY + 5,
                $black,
                $font,
                "$percent%"
            );


            $angleStart += $angle;
            // Para sa next slice, simula sa dulo ng previous. Halimbawa: 0 → 90 → 180 → 270...
        }

        // Para sa Legend (maliit na box at pangalan sa kanan ng chart)
        $legendX = 720;
        // X-position kung saan magsisimula ang mga legend sa kanan

        $yStart = $centerY - (count($labels) * 15 / 2);
        // Para i-center din nang patayo ang legend, gamit ang 15 spacing per item
        // dinivide sa 2  Para mailagay ang mga legend sa gitna ng pie chart sa vertical na direksyon
        // labels galing sa qry 

        foreach ($labels as $i => $label) {
            $colorHex = $colors[$i % count($colors)]; // Kulay para sa kasamang slice
            $rgbLegend = sscanf($colorHex, "#%02x%02x%02x");
            $legendColor = imagecolorallocate($img, $rgbLegend[0], $rgbLegend[1], $rgbLegend[2]);

            $y = $yStart + ($i * 20); // Gamit ang spacing na 20 para sa bawat label pataas
            //  $yStart ay panimulang Y position ng mga legend.
            // Ginagamit ito para hindi masyadong dikit-dikit ang mga label sa legend.

            imagefilledrectangle(
                $img,              //  Ang image canvas kung saan idodrawing ang rectangle (box).
                $legendX,           //  $legendX ay X-position ng box sa kanan ng pie chart.
                $y,                  // Simula ng rectangle (X,Y)
                $legendX + 12,         //  nag plus  Para magkaroon ng lapad ang box, 12 pixels mula sa simula.
                $y + 12,        // Sukat ng box: 12x12 pixels
                $legendColor                         // Kulay ng box
            );

            // imagestring(
            //     $img,
            //     10,                             // Font size
            //     $legendX + 18,
            //     $y,             // X position (18px to the right of the box), Y same as box
            //     $label,                        // Pangalan ng label galing sa qry -ito yung categoiry
            //     $black                         // Kulay ng text
            // );

            imagettftext(
                $img,
                14,
                0,
                $legendX + 18,
                $y + 12, // para umangkop sa taas ng rectangle box
                $black,
                $font,
                $label
            );
        }

        ob_start();               // Simulan ang output buffering
        imagepng($img);           // Gawing PNG ang image
        $imageData = ob_get_contents(); // Kuhanin ang image content na nasa memorya
        ob_end_clean();           // Linisin ang buffer
        imagedestroy($img);       // I-delete sa memorya ang image (para di mag-leak)

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
