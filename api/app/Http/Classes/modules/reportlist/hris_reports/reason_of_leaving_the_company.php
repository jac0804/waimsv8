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

class reason_of_leaving_the_company
{
    public $modulename = 'Reason of Leaving the Company';
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
        $fields = ['radioprint', 'divrep', 'year', 'month'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.label', 'Company Name');
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
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
              '' as division,
               year(now()) as year,
               '' as bmonth,
               monthname(now()) as month,
              '' as division");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportLayout($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function reportDefault($config)
    {
        $filter   = "";
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        $year      = $config['params']['dataparams']['year'];
        $bmonth = $config['params']['dataparams']['bmonth'];
        if ($divrep != '') {
            $filter = " and emp.divid = $divid";
        }

        $query = "select resignedtype,count(empid) as totalemp 
                  from (select emp.divid,d.divname as company,emp.resignedtype,emp.empid
                        from employee as emp
                        left join division as d on d.divid=emp.divid
                        where emp.resignedtype <> '' and year(emp.resigned) = '$year' and month(emp.resigned) = '$bmonth' $filter) as a
                  group by resignedtype 
                  order by resignedtype";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    private function pie_displayHeader($params)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($params['params']);
        $division = $params['params']['dataparams']['division'];
        $year     = $params['params']['dataparams']['year'];
        $bmonth    = $params['params']['dataparams']['month'];
        $divname  = $params['params']['dataparams']['divname'];

        $layoutsize = '1000';
        $center   = $params['params']['center'];
        $username = $params['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REASON OF LEAVING THE COMPANY ' . $year, null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if (empty($divname)) {
            $divname = 'ALL';
        }
        $str .= $this->reporter->col('Company: ' . $divname, null, null, '', '1px solid ', '', 'C', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Month: ' . $bmonth, null, null, '', '1px solid ', '', 'C', $font, '13', '', '', '');
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
        $color  = [];
        $total  = 0;

        foreach ($data as $d) {
            $total += $d->totalemp;
        }

        foreach ($data as $d) {
            $values[] = $d->totalemp;
            $percent  = number_format(($d->totalemp / $total) * 100, 2);
            $labels[] = $d->resignedtype . ' - ' . $d->totalemp;
            $color[]  = $this->randomColor();
        }

        return [$values, $labels, $color];
    }

    private function createpie($data, $labels, $colors)
    {
        $font = database_path() . '/images/fonts/ARIAL.ttf';

        $width  = 1000;
        $height = 600;
        $img    = imagecreatetruecolor($width, $height);
        $white  = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        $total      = array_sum($data);
        $angleStart = 0;

        $centerX  = 360;
        $centerY  = 300;
        $diameter = 500;
        $black    = imagecolorallocate($img, 0, 0, 0);

        foreach ($data as $i => $value) {
            $angle    = ($value / $total) * 360;
            $colorHex = $colors[$i % count($colors)];
            $rgb      = sscanf($colorHex, "#%02x%02x%02x");
            $depth    = 20;

            for ($d = $depth; $d > 0; $d--) {
                $darkColor = imagecolorallocate(
                    $img,
                    max($rgb[0] - 40, 0),
                    max($rgb[1] - 40, 0),
                    max($rgb[2] - 40, 0)
                );
                imagefilledarc($img, $centerX, $centerY + $d, $diameter, $diameter, $angleStart, $angleStart + $angle, $darkColor, IMG_ARC_PIE);
            }

            $mainColor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledarc($img, $centerX, $centerY, $diameter, $diameter, $angleStart, $angleStart + $angle, $mainColor, IMG_ARC_PIE);

            $angleMid    = $angleStart + ($angle / 2);
            $angleRad    = deg2rad($angleMid);
            $labelRadius = $diameter / 2.5;

            $labelX  = $centerX + cos($angleRad) * $labelRadius;
            $labelY  = $centerY + sin($angleRad) * $labelRadius;
            $percent = number_format(($value / $total) * 100, 2);

            $labelText = number_format($value, 0) . " (" . $percent . "%)";
            imagettftext($img, 12, 0, (int)$labelX - 20, (int)$labelY, $black, $font, number_format($value, 0));
            imagettftext($img, 10, 0, (int)$labelX - 20, (int)$labelY + 14, $black, $font, $percent . "%");
            // Calculate text width to center it better
            $bbox = imagettfbbox(14, 0, $font, $labelText);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];

            // imagettftext($img, 14, 0, (int)$labelX - 20, (int)$labelY + 5, $black, $font, "$percent%");

            $angleStart += $angle;
        }

        $legendX    = 650;
        $legendY    = 300;
        $lineHeight = 18;

        foreach ($labels as $i => $label) {
            $colorHex    = $colors[$i % count($colors)];
            $rgbLegend   = sscanf($colorHex, "#%02x%02x%02x");
            $legendColor = imagecolorallocate($img, $rgbLegend[0], $rgbLegend[1], $rgbLegend[2]);

            $value = $data[$i];
            $percent = number_format(($value / $total) * 100, 2);
            $labelWithData = $label . " - " . number_format($value, 0) . " (" . $percent . "%)";

            $wrapped = wordwrap($labelWithData, 35, "\n", true); // Slightly smaller width to accommodate extra text
            $lines   = explode("\n", $wrapped);

            $wrapped = wordwrap($label, 45, "\n", true);
            $lines   = explode("\n", $wrapped);

            imagefilledrectangle($img, $legendX, $legendY, $legendX + 12, $legendY + 12, $legendColor);

            foreach ($lines as $j => $line) {
                imagettftext($img, 12, 0, $legendX + 18, $legendY + 12 + ($j * $lineHeight), $black, $font, $line);
            }

            $legendY += count($lines) * $lineHeight + 8;
        }

        ob_start();
        imagepng($img);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    public function reportLayout($config)
    {
        $result = $this->reportDefault($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        list($values, $labels, $colors) = $this->chartdata($result);
        $pieImage = $this->createpie($values, $labels, $colors);

        $layoutsize = '1000';
        $str = '';

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->pie_displayHeader($config);

        $str .= '<br/>';
        $str .= '<img src="' . $pieImage . '" style="width:1000px;"/>';

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class