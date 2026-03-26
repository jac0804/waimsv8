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
use DateTime;


class separation_breakdown_report
{
    public $modulename = 'Separation Breakdown Report';
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

        $fields = ['radioprint',  'divrep',  'month', 'year', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company');
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
        $paramstr = "select 'default' as print, left(now(),4) as year, '' as bmonth,
         '' as month, '' as divid,'' as divcode,
              '' as divname,'' as divrep,
              '' as division";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }



    public function reportdata($config)
    {
        $reportHtml = '';
        $result = $this->default_query($config);

        if (empty($result)) {
            return [
                'status' => 'false',
                'msg' => 'No transaction',
                'report' => $this->othersClass->emptydata($config),
                'graph' => null
            ];
        }

        $reportHtml .= $this->pie_displayHeader($config);
        list($values, $labels, $color) = $this->chartdata($result);
        $piehere = $this->createpie($values, $labels, $color);
        $reportHtml .= "<div style='text-align:center;'><img src='{$piehere}' alt='Separation Breakdown Pie Graph'></div>";

        return [
            'status' => 'true',
            'msg' => 'Report generated with embedded static charts.',
            'report' => $reportHtml,
            'graph' => $piehere
        ];
    }


    public function totalemp($config)
    {
        $query = " select count(empid) as tlemp from  employee ";
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($filters)
    {
        $year = $filters['params']['dataparams']['year'];
        $bmonth = $filters['params']['dataparams']['bmonth'];
        $filter   = "";
        $divid     = $filters['params']['dataparams']['divid'];
        $divrep    = $filters['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and divid = $divid";
        }

        // $qry = "select m,sum(c) as c from
        // (select distinct ifnull(clr.resignedtype,'')  as m, count(clr.resignedtype) as c
        // from hclearance as clr
        // left join employee as emp on emp.empid=clr.empid where  year(clr.dateid)= '$year'  and month(clr.dateid)= '$bmonth' $filter
        // group by clr.resignedtype) as t
        // group by t.m";

        $qry = "select resignedtype as m, count(empid) as c from employee where resigned is not null and year(resigned)= '$year'  and month(resigned)= '$bmonth' $filter  group by resignedtype";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }



    private function pie_displayHeader($params)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($params['params']);
        $year = $params['params']['dataparams']['year'];
        $bmonth = $params['params']['dataparams']['bmonth'];
        $month = $params['params']['dataparams']['month'];

        $monthName = strtoupper(date('F', strtotime($bmonth)));

        $layoutsize = '1000';

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
        $str .= $this->reporter->col('SEPARATION BREAKDOWN FOR ' . $month . ' ' . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

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
            // $labels[] = $d->m . ' - ' . $d->c;  // Label format: "category (count - percent%)"

            $labels[] = $d->m . ' - ' . $d->c;
            $color[] = $this->randomColor();
        }
        return [$values, $labels, $color];
    }



    private function createpie($data, $labels, $colors)
    {
        $font = database_path() . '/images/fonts/ARIAL.ttf';

        // Sukat ng canvas (mas mataas na para may space sa baba)
        $width = 1000;
        $height = 600; // dating 600, tinaasan para sa spacing
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        $total = array_sum($data);
        $angleStart = 0;

        // Posisyon ng gitna ng pie (X, Y)
        $centerX = 360;
        $centerY = 300; // dating 330, mas ibaba na ngayon 
        //pag ibaba pa ay kailangan taasan pa yung height para hindi maputol yung pie

        $diameter = 500;
        $black = imagecolorallocate($img, 0, 0, 0);

        // Gumuhit ng bawat slice ng pie
        foreach ($data as $i => $value) {
            $angle = ($value / $total) * 360;

            $colorHex = $colors[$i % count($colors)];
            $rgb = sscanf($colorHex, "#%02x%02x%02x");
            $depth = 20; // para sa shadow effect

            // Shadow effect - gumuhit muna ng mas madilim pababa
            for ($d = $depth; $d > 0; $d--) {
                $darkColor = imagecolorallocate(
                    $img,
                    max($rgb[0] - 40, 0),
                    max($rgb[1] - 40, 0),
                    max($rgb[2] - 40, 0)
                );

                imagefilledarc(
                    $img,
                    $centerX,
                    $centerY + $d,
                    $diameter,
                    $diameter,
                    $angleStart,
                    $angleStart + $angle,
                    $darkColor,
                    IMG_ARC_PIE
                );
            }

            // Main na kulay ng slice
            $mainColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
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

            // I-compute ang position ng percentage text sa loob ng slice
            $angleMid = $angleStart + ($angle / 2);
            $angleRad = deg2rad($angleMid);
            $labelRadius = $diameter / 2.5;

            $labelX = $centerX + cos($angleRad) * $labelRadius;
            $labelY = $centerY + sin($angleRad) * $labelRadius;

            $percent = number_format(($value / $total) * 100, 2);

            imagettftext(
                $img,
                14,
                0,
                (int)$labelX - 20,
                (int)$labelY + 5,
                $black,
                $font,
                "$percent%"
            );

            $angleStart += $angle;
        }

        // ---------------- LEGEND (Labels) ----------------

        // Simula ng legend (label) – mas mataas para sabayan ang pie
        $legendX = 650;
        $legendY = 300; // dating 150, tinaasan para sabayan ang pie

        $lineHeight = 18; // taas ng bawat linya ng label

        foreach ($labels as $i => $label) {
            $colorHex = $colors[$i % count($colors)];
            $rgbLegend = sscanf($colorHex, "#%02x%02x%02x");
            $legendColor = imagecolorallocate($img, $rgbLegend[0], $rgbLegend[1], $rgbLegend[2]);

            // Kung mahaba ang label, putulin kada 45 na character
            $wrapped = wordwrap($label, 45, "\n", true);
            $lines = explode("\n", $wrapped);

            // Box ng kulay
            imagefilledrectangle($img, $legendX, $legendY, $legendX + 12, $legendY + 12, $legendColor);

            // I-drawing bawat linya ng label
            foreach ($lines as $j => $line) {
                imagettftext(
                    $img,
                    12,
                    0,
                    $legendX + 18,
                    $legendY + 12 + ($j * $lineHeight),
                    $black,
                    $font,
                    $line
                );
            }

            // I-adjust ang Y posisyon ng susunod na label
            $legendY += count($lines) * $lineHeight + 8;
        }

        // Output image as base64 (pwede i-display sa <img src="...">)
        ob_start();
        imagepng($img);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
