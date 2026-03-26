<?php

namespace App\Http\Classes\modules\reportlist\time_and_attendance_report;

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

class timekeeping_graph_report
{
    public $modulename = 'Timekeeping Graph Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
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
        $fields = ['radioprint', 'year', 'month',  'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'blue']]);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 'default' as print,
         left(now(),4) as year, '0' as bmonth,
         '' as month, '0' as reporttype";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function default_query($filters, $type)
    {
        $year = $filters['params']['dataparams']['year'];
        $bmonth = $filters['params']['dataparams']['bmonth'];

        if ($type == 1) { // leave
            $qry = "select m, sum(c) as c, color from (
                    select distinct ifnull(lc.category, '') as m, lc.colorcode as color, count(lc.category) as c
                    from leavetrans as lt
                    left join leavecategory as lc on lc.line = lt.catid
                    left join timecard as tm on tm.empid = lt.empid and date(lt.effectivity) = tm.dateid
                    where lc.category != ''
                        and year(lt.effectivity) = '$year' 
                        and month(lt.effectivity) = '$bmonth'
                    group by category, color
                ) as t
                group by t.m, t.color";
        } elseif ($type == 2) { // undertime
            $qry = "select m, sum(c) as c, color from (
                    select distinct ifnull(category, '') as m, lc.colorcode as color, count(category) as c, tm.dateid
                    from timecard as tm
                    left join undertime as ud on ud.empid = tm.empid and date(ud.dateid) = tm.dateid
                    left join leavecategory as lc on lc.line = ud.catid
                    where category != ''
                        and year(tm.dateid) = '$year' 
                        and month(tm.dateid) = '$bmonth' 
                        and tm.ismbrkin <> 0 and tm.ismbrkout <> 0
                    group by category, color, dateid
                ) as t
                group by t.m, t.color";
        } else { //employee in and out
            $qry = "select  'Morning In' as m, '#008000' as color, count(*) as c
                    from timecard as tm where isnologin = 1 and year(tm.dateid) = '$year'  and month(tm.dateid) = '$bmonth' 
                    union all
                    select  'Afternoon Out' as m, '#0000FF' as color,count(*) as c
                    from timecard as tm where isnologout = 1 and year(tm.dateid) = '$year'  and month(tm.dateid) = '$bmonth' ";
        }

        return $this->coreFunctions->opentable($qry);
    }

    public function reportdata($config)
    {
        $reportHtml = '';
        $leaveData = $this->default_query($config, 1);
        $undertimeData = $this->default_query($config, 2);
        $timeindata = $this->default_query($config, 3);

        if (!empty($timeindata)) {
            foreach ($timeindata as $row) {

                $morningInC = 0;
                $afternoonOutC = 0;

                if (!empty($timeindata)) {
                    foreach ($timeindata as $row) {
                        if ($row->m == 'Morning In') {
                            $morningInC = $row->c;
                        } else if ($row->m == 'Afternoon Out') {
                            $afternoonOutC = $row->c;
                        }
                    }
                }

                // parehas wala 
                if ($morningInC == 0 && $afternoonOutC == 0) {
                    $filteredTimeinData = 0;
                } elseif ($morningInC == 0) {
                    // kpag afternoon out lang may laman
                    $filteredTimeinData = [];
                    foreach ($timeindata as $row) {
                        if ($row->m == 'Afternoon Out' && $row->c > 0) {
                            $filteredTimeinData[] = $row;
                        }
                    }
                } elseif ($afternoonOutC == 0) {
                    // kpag mornin in lang may laman
                    $filteredTimeinData = [];
                    foreach ($timeindata as $row) {
                        if ($row->m == 'Morning In' && $row->c > 0) {
                            $filteredTimeinData[] = $row;
                        }
                    }
                } else {
                    // parehas meron
                    $filteredTimeinData = $timeindata;
                }
            }
        }

        if (empty($leaveData) && empty($undertimeData) &&  $filteredTimeinData == 0) {
            return [
                'status' => 'false',
                'msg' => 'No transaction',
                'report' => $this->othersClass->emptydata($config),
                'graph' => null
            ];
        }

        if (!empty($leaveData)) {
            $reportHtml .= $this->generateDefaultHeader($config, 1);
            list($leaveValues, $leaveLabels, $leaveColors) = $this->getLeaveChartData($leaveData);
            $leaveImg = $this->createPieChartImage($leaveValues, $leaveLabels, $leaveColors);
            $reportHtml .= "<div style='text-align:center;'><img src='{$leaveImg}' alt='Leave Pie Chart'></div>";
        }

        if (!empty($undertimeData)) {
            $reportHtml .= $this->generateDefaultHeader($config, 2);
            list($undertimeValues, $undertimeLabels, $undertimeColors) = $this->getLeaveChartData($undertimeData);
            $undertimeImg = $this->createPieChartImage($undertimeValues, $undertimeLabels, $undertimeColors);
            $reportHtml .= "<div style='text-align:center;'><img src='{$undertimeImg}' alt='Undertime Pie Chart'></div>";
        }

        $reportHtml .= '<br><br><br><br><br><br>';

        if ($filteredTimeinData <> 0) {
            $reportHtml .= $this->generateDefaultHeader($config, 3);
            list($timeinValues, $timeinLabels, $timeinColors) = $this->getLeaveChartData($filteredTimeinData);
            $timeinImg = $this->createPieChartImage($timeinValues, $timeinLabels, $timeinColors);

            $reportHtml .= "<div style='text-align:center;'><img src='{$timeinImg}' alt='Employee\'s In and Out Pie Chart'></div>";
        }

        $reportHtml .= $this->reporter->endreport();

        return [
            'status' => 'true',
            'msg' => 'Report generated with embedded static charts.',
            'report' => $reportHtml,
            'graph' => null
        ];
    }



    private function getLeaveChartData($data)
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
            $color[] = $d->color;
            $labels[] = $d->m . ' (' . $d->c . ' - ' . $percent . '%)';  // Label format: "category (count - percent%)"
        }
        return [$values, $labels, $color];
    }

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


    private function generateDefaultHeader($params, $type)
    {
        $str = '';
        $font = $this->rptfont = 'Arial';; // yung galing dito ay century gothic 
        $month = $params['params']['dataparams']['month'];
        $year = $params['params']['dataparams']['year'];
        $layoutsize = '1000';

        if ($type == 1) {
            $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50px;margin-left:10px;');
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("EMPLOYEE'S VACATION LEAVE - " . strtoupper($month) . ' ' . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } elseif ($type == 2) {
            $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50px;margin-left:10px;');
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("EMPLOYEE'S UNDERTIME - " . strtoupper($month) . ' ' . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50px;margin-left:10px;');
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("EMPLOYEE'S TIME IN AND OUT - " . strtoupper($month) . ' ' . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}
