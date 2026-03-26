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
use Illuminate\Support\Facades\URL;

class generation_statistics_report
{
    public $modulename = 'Generation Statistics Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $fields = [];

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
        $fields = ['radioprint', 'radioreporttype', 'divrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Generation Statistic Overview', 'value' => '0', 'color' => 'red'],
            ['label' => 'Generation Statistic Pie Graph', 'value' => '1', 'color' => 'red']
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
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
            '0' as reporttype,
              '0' as divid,'' as divcode,
              '' as divname,'' as divrep,
              '' as division");
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
                $result = $this->reportDefault($config);
                if (!empty($result)) {
                    $reportHtml .= $this->pie_displayHeader($config);
                    list($svalues, $labels, $colors, $totalemp) = $this->chartdata($result);
                    $piehere = $this->createpie($svalues, $labels, $colors, $totalemp);
                    // var_dump($svalues, $labels, $colors);
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



    public function reportDefault($config)
    {
        $gen_res = $this->genration($config);
        $gen_fields = "";
        foreach ($gen_res as $gen) {
            $g = strtolower($gen->generation);
            $start = $gen->startyear;
            $end = $gen->endyear;
            $gen_fields .= ", sum(case when extract(year from emp.bday) between '" . $start . "' and '" . $end . "' then 1 else 0 end) as '" . $g . "'";
        }

        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }
        $query = " select divi.divname,  divi.divid,
                    count(emp.empid) as total_employees
                    $gen_fields
                from division as divi
                left join employee as emp on emp.divid = divi.divid
                where 1=1 $filter
                group by divi.divname, divi.divid
                order by divname";
        return $this->coreFunctions->opentable($query);
    }


    public function genration($config)
    {
        $query = "select generation,startyear,endyear from generation order by startyear";
        return $this->coreFunctions->opentable($query);
    }

    public function totalemp($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }
        $query = " select count(emp.empid) as total_employees
                from division as divi
                left join employee as emp on emp.divid = divi.divid where 1=1 $filter";
        return $this->coreFunctions->opentable($query);
    }


    private function displayHeader($config)
    {

        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '10';
        $gen_res = $this->genration($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $division   = $config['params']['dataparams']['division'];
        $companyid  = $config['params']['companyid'];
        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $datar = $this->totalemp($config);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GENERATION STATISTICS', '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        if (!empty($datar)) {
            $str .= $this->reporter->col('Total : ' . number_format($datar[0]->total_employees, 0), '500', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        foreach ($gen_res as $gen) {
            $genname = strtoupper($gen->generation);
            $start = $gen->startyear;
            $end = $gen->endyear;
            $str .= $this->reporter->col($genname, '134', null, $bgcolors, $border, 'T', 'C', $font, $font_size, 'B', $fontcolor, '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Company Name', '250', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        foreach ($gen_res as $gen) {
            $start = $gen->startyear;
            $end = $gen->endyear;
            $str .= $this->reporter->col('( ' . $start . ' - ' . $end . ' )', '134', null, $bgcolors, $border, 'B', 'C', $font, $font_size, 'B', $fontcolor, '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $gen_res = $this->genration($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $count = 55;
        $page = 55;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);

        // $colors = ['#DCDCDC', '#C0C0C0', '#808080', '#696969'];
        //Gainsboro ,Silver, Gray, dim gray 
        $totals = [];
        $totalEmployeesSum = 0;

        foreach ($result as $key => $data) {
            $totalEmployeesSum += $data->total_employees;
            // $bg_color = $colors[$key % 4]; // rotate colors:
            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->divname, '250', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');

            foreach ($gen_res as $gen) {
                $alias = strtolower($gen->generation);
                $value = $data->$alias;
                if (!isset($totals[$alias])) {
                    $totals[$alias] = 0;
                }
                $totals[$alias] += $value;
                $str .= $this->reporter->col($value, '67', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
                $totalEmployees = $data->total_employees;
                if ($totalEmployees == 0 || $value == 0) {
                    $percentage = '';
                } else {
                    $percentage = number_format(($value / $totalEmployees) * 100, 0) . '%';
                }

                $str .= $this->reporter->col($percentage, '67', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');
            }

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->col('Total', '250', null, '', $border, 'LBR', 'LT', $font, $font_size, '', '', '');

        foreach ($gen_res as $gen) {
            $alias = strtolower($gen->generation);
            $totalValue = isset($totals[$alias]) ? $totals[$alias] : 0;

            if ($totalEmployeesSum == 0 || $totalValue == 0) {
                $totalPercentage = '';
            } else {
                $totalPercentage = number_format(($totalValue / $totalEmployeesSum) * 100, 0) . '%';
            }

            // var_dump($totalPercentage);
            $str .= $this->reporter->col($totalValue, '67', null, '', $border, 'LBR', 'LT', $font, $font_size);
            $str .= $this->reporter->col($totalPercentage, '67', null, '', $border, 'LBR', 'LT', $font, $font_size);
        }

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
    private function pie_displayHeader($config)
    {
        $border = '1px solid';
        $font = 'Century Gothic';
        $str = '';
        $layoutsize = '1000';
        $str .= '<br/><br/><br/><br/><br/><br/>';
        $datar = $this->totalemp($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Generation Statistics Pie Graph', '900', null, false, $border, '', 'C', $font, '18', 'B', '', '');
        if (!empty($datar)) {
            $str .= $this->reporter->col('Total : ' . number_format($datar[0]->total_employees, 0), '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private function chartdata($data)
    {
        $values = [];
        $labels = [];
        $colors = [];

        $gen_res = $this->genration($data);
        $totals_per_gen = [];
        $grand_total_employees = 0;

        foreach ($data as $row) {
            $grand_total_employees += $row->total_employees;
            foreach ($gen_res as $gen) {
                $alias = strtolower($gen->generation);
                $value = isset($row->$alias) ? $row->$alias : 0;

                if (!isset($totals_per_gen[$alias])) {
                    $totals_per_gen[$alias] = 0;
                }
                $totals_per_gen[$alias] += $value;
            }
        }

        foreach ($gen_res as $gen) {
            $alias = strtolower($gen->generation);
            $value = isset($totals_per_gen[$alias]) ? $totals_per_gen[$alias] : 0;

            if ($value > 0) {
                // $percent = ($grand_total_employees == 0) ? 0 : number_format(($value / $grand_total_employees) * 100, 2);
                $values[] = $value;
                $labels[] = $alias . ' = ' . $value;
                $colors[] = $this->randomColor();
            }
        }

        return [$values, $labels, $colors, $grand_total_employees];
    }

    /////center yung label
    // private function createpie($data, $labels, $colors, $totalemp)
    // {
    //     $font = database_path() . '/images/fonts/ARIAL.ttf';

    //     $width = 1000;
    //     $height = 800;  // tinaasan para may space ang legend
    //     $img = imagecreatetruecolor($width, $height);

    //     $white = imagecolorallocate($img, 255, 255, 255);
    //     imagefill($img, 0, 0, $white);

    //     $black = imagecolorallocate($img, 0, 0, 0);
    //     $centerX = 450;
    //     $centerY = 280;
    //     $diameter = 500;

    //     $total = $totalemp;
    //     $angleStart = 0;
    //     $depth = 20;

    //     // Draw pie slices
    //     foreach ($data as $i => $value) {
    //         if ($value <= 0) continue;

    //         $angle = ($value / $total) * 360;
    //         $rgb = sscanf($colors[$i % count($colors)], "#%02x%02x%02x");

    //         // 3D shadow effect
    //         for ($d = $depth; $d > 0; $d--) {
    //             $shadow = imagecolorallocate(
    //                 $img,
    //                 max($rgb[0] - 40, 0),
    //                 max($rgb[1] - 40, 0),
    //                 max($rgb[2] - 40, 0)
    //             );
    //             imagefilledarc(
    //                 $img,
    //                 $centerX,
    //                 $centerY + $d,
    //                 $diameter,
    //                 $diameter,
    //                 $angleStart,
    //                 $angleStart + $angle,
    //                 $shadow,
    //                 IMG_ARC_PIE
    //             );
    //         }

    //         // Main pie slice
    //         $sliceColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    //         imagefilledarc(
    //             $img,
    //             $centerX,
    //             $centerY,
    //             $diameter,
    //             $diameter,
    //             $angleStart,
    //             $angleStart + $angle,
    //             $sliceColor,
    //             IMG_ARC_PIE
    //         );

    //         // Percent text inside slice
    //         $midAngle = deg2rad($angleStart + $angle / 2);
    //         $labelRadius = $diameter / 2.5;
    //         $labelX = $centerX + cos($midAngle) * $labelRadius;
    //         $labelY = $centerY + sin($midAngle) * $labelRadius;

    //         $percent = number_format(($value / $total) * 100, 2) . '%';
    //         imagettftext($img, 14, 0, (int)$labelX - 20, (int)$labelY + 5, $black, $font, $percent);

    //         $angleStart += $angle;
    //     }

    //     // === Centered Horizontal Legend at Bottom ===
    //     $boxSize = 12;
    //     $spacing = 180;
    //     $legendCount = count($labels);
    //     $totalLegendWidth = ($legendCount - 1) * $spacing;
    //     $legendStartX = ($width - $totalLegendWidth) / 2;

    //     // Add more spacing between pie and legend
    //     $legendY = $centerY + $diameter / 2 + $depth + 60;

    //     foreach ($labels as $i => $label) {
    //         $rgb = sscanf($colors[$i % count($colors)], "#%02x%02x%02x");
    //         $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);

    //         $x = $legendStartX + ($i * $spacing);

    //         // Color box
    //         imagefilledrectangle($img, $x, $legendY, $x + $boxSize, $legendY + $boxSize, $color);

    //         // Label text
    //         imagettftext($img, 14, 0, $x + $boxSize + 5, $legendY + $boxSize, $black, $font, $label);
    //     }

    //     // Output
    //     ob_start();
    //     imagepng($img);
    //     $imageData = ob_get_clean();
    //     imagedestroy($img);

    //     return 'data:image/png;base64,' . base64_encode($imageData);
    // }


    private function createpie($data, $labels, $colors, $totalemp)
    {
        $font = database_path() . '/images/fonts/ARIAL.ttf';

        $width = 1000;
        $height = 750;  // dagdag taas para sa legend
        $img = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        $black = imagecolorallocate($img, 0, 0, 0);
        $centerX = 450;
        $centerY = 350;
        $diameter = 500;
        $depth = 20;

        $total = $totalemp;
        $angleStart = 0;

        // Draw each slice
        foreach ($data as $i => $value) {
            if ($value <= 0) continue;

            $angle = ($value / $total) * 360;
            $rgb = sscanf($colors[$i % count($colors)], "#%02x%02x%02x");

            // 3D shadow effect
            for ($d = $depth; $d > 0; $d--) {
                $shadow = imagecolorallocate(
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
                    $shadow,
                    IMG_ARC_PIE
                );
            }

            // Main slice
            $sliceColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledarc(
                $img,
                $centerX,
                $centerY,
                $diameter,
                $diameter,
                $angleStart,
                $angleStart + $angle,
                $sliceColor,
                IMG_ARC_PIE
            );

            // Percent inside slice
            $midAngle = deg2rad($angleStart + $angle / 2);
            $labelRadius = $diameter / 2.5;
            $labelX = $centerX + cos($midAngle) * $labelRadius;
            $labelY = $centerY + sin($midAngle) * $labelRadius;

            $percent = number_format(($value / $total) * 100, 2) . '%';
            imagettftext($img, 14, 0, (int)$labelX - 20, (int)$labelY + 5, $black, $font, $percent);

            $angleStart += $angle;
        }

        // Legend - aligned to left starting at x = 100
        $boxSize = 12;
        $spacing = 180;
        $legendStartX = 100;
        $legendY = $centerY + $diameter / 2 + $depth + 60;

        foreach ($labels as $i => $label) {
            $rgb = sscanf($colors[$i % count($colors)], "#%02x%02x%02x");
            $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);

            $x = $legendStartX + ($i * $spacing);

            // Draw color box
            imagefilledrectangle($img, $x, $legendY, $x + $boxSize, $legendY + $boxSize, $color);

            // Draw label
            imagettftext($img, 14, 0, $x + $boxSize + 5, $legendY + $boxSize, $black, $font, $label);
        }

        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}//end class