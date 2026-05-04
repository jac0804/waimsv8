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

class annual_turn_over
{
    public $modulename = 'Annual Turn-Over';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1640px;max-width:1640px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
    public $rptfont = 'Arial';

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
        $fields = ['radioprint', 'divrep', 'year'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            '' as divid,'' as divcode,
                        '' as divname,'' as divrep,
                        '' as division,  left(now(),4) as year");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }


    public function default_query($filters)
    {



        $year = $filters['params']['dataparams']['year'];
        $filter   = "";
        $divid     = $filters['params']['dataparams']['divid'];
        $divrep    = $filters['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }

        $query = "select 
                count(*) as c,
                case when ifnull(emp.resignedtype,'') = '' then 'No Status' else ifnull(emp.resignedtype,'') end as m,
                CONCAT('#', LPAD(HEX(FLOOR(RAND() * 16777215)), 6, '0')) AS color
                from employee as emp 
                left join division as divs on divs.divid=emp.divid 
                left join jobthead as job on job.line=emp.jobid
                left join client as dept on dept.clientid=emp.deptid
                where emp.resignedtype<>'' and year(date(emp.resigned)) = '$year' $filter
                group by emp.resignedtype";


        return $this->coreFunctions->opentable($query);
    }

    public function reportdata($config)
    {
        $reportHtml = '';
        $data = $this->default_query($config);

        if (empty($data)) {
            return [
                'status' => 'false',
                'msg' => 'No transaction',
                'report' => $this->othersClass->emptydata($config),
                'graph' => null
            ];
        }


        if (!empty($data)) {
            $reportHtml .= $this->generateDefaultHeader($config, 1);
            list($dataValues, $dataLabels, $dataColors) = $this->getChartData($data);
            $dataImg = $this->createPieChartImage($dataValues, $dataLabels, $dataColors);
            $reportHtml .= "<div style='text-align:center;'><img src='{$dataImg}' alt='Leave Pie Chart'  style='margin-top: 15px;margin-left: -50px;'></div>";
        }


        $reportHtml .= '<br><br><br><br><br><br>';

        $reportHtml .= $this->reporter->endreport();

        return [
            'status' => 'true',
            'msg' => 'Report generated with embedded static charts.',
            'report' => $reportHtml,
            'graph' => null
        ];
    }


    private function getChartData($data)
    {
        $values = [];
        $labels = [];
        $color = [];
        $total = 0;

        // var_dump($data);

        // calculte ang total ng lahat ng count (c)
        foreach ($data as $d) {
            $total += $d->c;
        }
        // var_dump($total); 
        // Para sa bawat category, kunin ang count, percentage, at buuin ang label
        foreach ($data as $d) {
            $values[] = $d->c;  // Raw count para sa pie chart
            $percent = number_format(($d->c / $total) * 100, 2);  // Percentage ng bawat bahagi
            $color[] = $d->color;
            $labels[] = $d->m . ' (' . $d->c . ' - ' . $percent . '%)';  // Label format: "category (count - percent%)"
        }
        return [$values, $labels, $color];
    }

    #3D
    private function createPieChartImage($data, $labels, $colors)
    {
        $font = database_path() . '/images/fonts/ARIAL.ttf';  //path ng font

        // var_dump($colors); //#5FFB17  hex colors 
        $width = 1200;   // Lapad ng canvas 
        $height = 600;   // Taas ng canvas
        $img = imagecreatetruecolor($width, $height); // Gumawa ng bagong blangkong canvas 

        $white = imagecolorallocate($img, 255, 255, 255); // Mag-assign ng kulay puti (255,255,255 = white)
        imagefill($img, 0, 0, $white); // Kulayan ng puti ang buong canvas

        $total = array_sum($data); // Kunin ang total ng lahat ng values sa data (gagamitin para sa percentage)
        $angleStart = 0; // Simula ng unang slice (0 degrees)

        $centerX = 450; // X-position ng gitna ng pie chart
        $centerY = 280; // Y-position ng gitna ng pie chart
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

    #2D
    // private function createPieChartImage($data, $labels, $colors)
    // {
    //     $font = database_path() . '/images/fonts/ARIAL.ttf';  //path ng font

    //     // CHANGED: narrower width for better proportion (was 1200)
    //     $width = 1000;   // Lapad ng canvas 
    //     $height = 600;   // Taas ng canvas
    //     $img = imagecreatetruecolor($width, $height); // Gumawa ng bagong blangkong canvas 

    //     $white = imagecolorallocate($img, 255, 255, 255); // Mag-assign ng kulay puti (255,255,255 = white)
    //     imagefill($img, 0, 0, $white); // Kulayan ng puti ang buong canvas

    //     $total = array_sum($data); // Kunin ang total ng lahat ng values sa data (gagamitin para sa percentage)
    //     $angleStart = 0; // Simula ng unang slice (0 degrees)

    //     // CHANGED: moved pie chart a bit left to make room for legend (was 450)
    //     $centerX = 350; // X-position ng gitna ng pie chart
    //     $centerY = 280; // Y-position ng gitna ng pie chart
    //     // CHANGED: slightly smaller diameter for cleaner spacing (was 500)
    //     $diameter = 420; // Gaano kalaki ang pie chart (diameter = buong bilog)

    //     $black = imagecolorallocate($img, 0, 0, 0);  // Gumawa ng itim na kulay para sa text

    //     // REMOVED: embedded title inside the image per user request

    //     // Para ma-center ng patayo ang mga legend text sa gilid ng chart
    //     $yStart = $centerY - (count($labels) * 12 / 2);
    //     // 12 =  height ng bawat label. Divide by 2 para magstart mula sa taas ng gitna

    //     foreach ($data as $i => $value) {
    //         $angle = ($value / $total) * 360;
    //         // Halimbawa: Kung 50 ang value at total ay 100, magiging (50/100)*360 = 180 degrees
    //         // Ibig sabihin, kalahati ng bilog ang slice

    //         $colorHex = $colors[$i % count($colors)]; //kukunin ko yung kulay na nasa parameter 

    //         //kukunin ang rgb ng slice color-yung color na galing sa parameter para gamitin sa shadow effect mala 3d na pie
    //         $rgb = sscanf($colorHex, "#%02x%02x%02x"); // Kunin ang RGB ng slice color 

    //         // CHANGED: removed the 3D shadow loop entirely for a clean flat design.
    //         // Original shadow loop was:
    //         // for ($d = $depth; $d > 0; $d--) {
    //         //     $darkColor = imagecolorallocate($img, max($rgb[0]-40,0), max($rgb[1]-40,0), max($rgb[2]-40,0));
    //         //     imagefilledarc($img, $centerX, $centerY+$d, $diameter, $diameter, $angleStart, $angleStart+$angle, $darkColor, IMG_ARC_PIE);
    //         // }

    //         //Main na Slice 
    //         $mainColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]); // Original bright color

    //         imagefilledarc(
    //             $img,
    //             $centerX,
    //             $centerY,
    //             $diameter,
    //             $diameter,
    //             $angleStart,
    //             $angleStart + $angle,
    //             $mainColor,
    //             IMG_ARC_PIE
    //         );

    //         $angleMid = $angleStart + ($angle / 2);
    //         // Kinuha ang gitna ng slice. Gagamitin para mailagay sa gitna ang percentage text

    //         $angleRad = deg2rad($angleMid);
    //         // Kinonvert ang degrees papuntang radians dahil ang cos() at sin() ay gumagamit ng radians
    //         // Radian ay unit ng angle tulad ng degrees. Kailangan ito para gumana ang cos() at sin().
    //         //  deg2rad() ay function para i-convert ang degrees papuntang radians.
    //         // Ginagamit ito kasi ang cos() at sin() ay hindi tumatanggap ng degrees, radians ang kailangan.

    //         $labelRadius = $diameter / 2.5;
    //         // Gaano kalayo ang text mula gitna. Di masyadong sa dulo para di sumobra sa bilog
    //         // Ang diameter ay kabuuang laki ng bilog. Dinivide sa 2.5 para yung text ay lumabas malapit sa gitna ng slice, hindi sa dulo.

    //         $labelX = $centerX + cos($angleRad) * $labelRadius;
    //         $labelY = $centerY + sin($angleRad) * $labelRadius;
    //         // Gamit ang cos() at sin() para kunin ang exact X,Y position sa bilog kung saan ilalagay ang percentage

    //         $percent = number_format(($value / $total) * 100, 2);
    //         // Kinukuha ang percentage ng slice (hal. 50/100 = 50%)

    //         // CHANGED: use imagettftext for better font rendering, and dynamic text color for contrast
    //         $brightness = ($rgb[0] + $rgb[1] + $rgb[2]) / 3;
    //         $textColor = ($brightness > 130) ? $black : imagecolorallocate($img, 255, 255, 255);

    //         imagettftext(
    //             $img,
    //             14, // font size
    //             0, // angle
    //             (int)$labelX - 15,
    //             (int)$labelY + 6,
    //             $textColor,
    //             $font,
    //             round($percent) . '%'   // CHANGED: round to integer for cleaner look
    //         );

    //         $angleStart += $angle;
    //         // Para sa next slice, simula sa dulo ng previous. Halimbawa: 0 → 90 → 180 → 270...
    //     }

    //     // Para sa Legend (maliit na box at pangalan sa kanan ng chart)
    //     // CHANGED: reposition legend to the right of the resized pie (was 720)
    //     $legendX = $centerX + $diameter/2 + 40;
    //     // X-position kung saan magsisimula ang mga legend sa kanan

    //     // CHANGED: adjust vertical centering with larger line height (was 15, now 22)
    //     $legendYStart = $centerY - ((count($labels) * 22) / 2);

    //     foreach ($labels as $i => $fullLabel) {
    //         // CHANGED BACK: use original $fullLabel (contains "Category (count - percent%)") instead of cleaned version
    //         // No extraction, keep the raw value and percent as originally intended.

    //         $colorHex = $colors[$i % count($colors)]; // Kulay para sa kasamang slice
    //         $rgbLegend = sscanf($colorHex, "#%02x%02x%02x");
    //         $legendColor = imagecolorallocate($img, $rgbLegend[0], $rgbLegend[1], $rgbLegend[2]);

    //         $y = $legendYStart + ($i * 22); // CHANGED: spacing 22 (was 20)
    //         //  $yStart ay panimulang Y position ng mga legend.
    //         // Ginagamit ito para hindi masyadong dikit-dikit ang mga label sa legend.

    //         imagefilledrectangle(
    //             $img,              //  Ang image canvas kung saan idodrawing ang rectangle (box).
    //             $legendX,           //  $legendX ay X-position ng box sa kanan ng pie chart.
    //             $y,                  // Simula ng rectangle (X,Y)
    //             $legendX + 14,         //  nag plus  Para magkaroon ng lapad ang box, 14 pixels (was 12)
    //             $y + 14,        // Sukat ng box: 14x14 pixels (was 12)
    //             $legendColor                         // Kulay ng box
    //         );

    //         imagettftext(
    //             $img,
    //             12,             // CHANGED: slightly smaller font for legend (was 14)
    //             0,
    //             $legendX + 22,  // CHANGED: more space after box (was 18)
    //             $y + 12,        // para umangkop sa taas ng rectangle box (adjusted)
    //             $black,
    //             $font,
    //             $fullLabel      // CHANGED BACK: use the original label with count and percent
    //         );
    //     }

    //     ob_start();               // Simulan ang output buffering
    //     imagepng($img);           // Gawing PNG ang image
    //     $imageData = ob_get_contents(); // Kuhanin ang image content na nasa memorya
    //     ob_end_clean();           // Linisin ang buffer
    //     imagedestroy($img);       // I-delete sa memorya ang image (para di mag-leak)

    //     return 'data:image/png;base64,' . base64_encode($imageData);
    // }

    private function generateDefaultHeader($params, $type)
    {
        $str = '';
        $font = $this->rptfont = 'Arial';; // yung galing dito ay century gothic 
        $year = $params['params']['dataparams']['year'];
        $layoutsize = '1000';

        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50px;margin-left:120px;margin-top:-15px;');


        $center     = $params['params']['center'];
        $username   = $params['params']['user'];
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Annual Turn Over - " . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= $this->reporter->endreport();
        return $str;
    }
}//end class