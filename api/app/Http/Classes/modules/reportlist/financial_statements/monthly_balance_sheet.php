<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class monthly_balance_sheet
{
    public $modulename = 'Monthly Balance Sheet';
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
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['dcentername', 'costcenter'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['year', 'print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,
                    left(now(),10) as due,left(now(),4) as year,'' as code,'' as name,
                    '" . $defaultcenter[0]['center'] . "' as center,
                    '" . $defaultcenter[0]['centername'] . "' as centername,
                    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                    '' as costcenter ";

        return $this->coreFunctions->opentable($paramstr);
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

    public function reportplotting($config)
    {
        $result = $this->maxipro_query($config);
        $reportdata =  $this->MAXIPRO_MONTHLY_INCOME_STATEMENT_LAYOUT($config, $result);

        return $reportdata;
    }

    public function maxipro_query($filters)
    {
        $year = intval($filters['params']['dataparams']['year']);
        $companyid = $filters['params']['companyid'];
        $filter = '';
        $filter1 = '';
        $filter2 = '';

        $year1 = $year;
        $year2 = $year;
        $view = 'MONTHLY';

        $center = $filters['params']['dataparams']['center'];
        $costcenter = $filters['params']['dataparams']['code'];
        if ($center != '') {
            $filter .= " and cntnum.center = '" . $center . "'";
        }
        if ($costcenter != "") {
            $costcenterid = $filters['params']['dataparams']['costcenterid'];
            $filter1 .= " and detail.projectid = '" . $costcenterid . "' ";
        }

        $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,0 as monjan,0 as monfeb,0 as monmar,0 as monapr,0 as monmay,0 as monjun,0 as monjul,0 as monaug,0 as monsep,0 as monoct,0 as monnov,0 as mondec,'$year1' as year";
        $result = $this->coreFunctions->opentable($query2);
        $coa = json_decode(json_encode($result), true); // for convert to array

        $month = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
        $month2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
        $monthL = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
        $monthL2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
        $monthC = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
        $monthC2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);

        $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'A', $year1, $year2, $view, $month, $month2, $filter, $filter1, $filter2, $companyid);
        $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'L', $year1, $year2, $view, $monthL, $monthL2, $filter, $filter1, $filter2, $companyid);
        $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'C', $year1, $year2, $view, $monthC, $monthC2, $filter, $filter1, $filter2, $companyid);


        $coa[] = array(
            'acno' => '//4999', 'acnoname' => 'TOTAL LIABILITIES AND STOCKHOLDERS EQUITY',
            'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'detail' => 2,
            'monjan' => $monthL2['mjan'] + $monthC2['mjan'],
            'monfeb' => $monthL2['mfeb'] + $monthC2['mfeb'],
            'monmar' => $monthL2['mmar'] + $monthC2['mmar'],
            'monapr' => $monthL2['mapr'] + $monthC2['mapr'],
            'monmay' => $monthL2['mmay'] + $monthC2['mmay'],
            'monjun' => $monthL2['mjun'] + $monthC2['mjun'],
            'monjul' => $monthL2['mjul'] + $monthC2['mjul'],
            'monaug' => $monthL2['maug'] + $monthC2['maug'],
            'monsep' => $monthL2['msep'] + $monthC2['msep'],
            'monoct' => $monthL2['moct'] + $monthC2['moct'],
            'monnov' => $monthL2['mnov'] + $monthC2['mnov'],
            'mondec' => $monthL2['mdec'] + $monthC2['mdec'],
            'yr' => $year1
        );

        $array = json_decode(json_encode($coa), true);
        return $array;
    }

    private function MAXIPRO_PLANTTREE(&$a, $acno, $cat, $year1, $year2, $view, &$month, &$month2, $filters, $filter1, $filter2, $companyid)
    {
        $query2 = $this->MAXIPRO_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid);
        $data = $this->coreFunctions->opentable($query2);
        $result2 = json_decode(json_encode($data), true);
        $oldacno = '';
        $key = '';
        for ($b = 0; $b < count($result2); $b++) {
            switch ($view) {
                case 'MONTHLY':
                    if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
                        $a[] = array(
                            'acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'],
                            'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'],
                            'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail'],
                            'monjan' => number_format((float)$result2[$b]['monjan'], 2, '.', ''),
                            'monfeb' => number_format((float)$result2[$b]['monfeb'], 2, '.', ''),
                            'monmar' => number_format((float)$result2[$b]['monmar'], 2, '.', ''),
                            'monapr' => number_format((float)$result2[$b]['monapr'], 2, '.', ''),
                            'monmay' => number_format((float)$result2[$b]['monmay'], 2, '.', ''),
                            'monjun' => number_format((float)$result2[$b]['monjun'], 2, '.', ''),
                            'monjul' => number_format((float)$result2[$b]['monjul'], 2, '.', ''),
                            'monaug' => number_format((float)$result2[$b]['monaug'], 2, '.', ''),
                            'monsep' => number_format((float)$result2[$b]['monsep'], 2, '.', ''),
                            'monoct' => number_format((float)$result2[$b]['monoct'], 2, '.', ''),
                            'monnov' => number_format((float)$result2[$b]['monnov'], 2, '.', ''),
                            'mondec' => number_format((float)$result2[$b]['mondec'], 2, '.', ''),
                            'yr' => $result2[$b]['yr']
                        );
                        $oldacno = $result2[$b]['acno'];
                    } else {
                        $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
                        $a[$key]['monjan'] = $a[$key]['monjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
                        $a[$key]['monfeb'] = $a[$key]['monfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
                        $a[$key]['monmar'] = $a[$key]['monmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
                        $a[$key]['monapr'] = $a[$key]['monapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
                        $a[$key]['monmay'] = $a[$key]['monmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
                        $a[$key]['monjun'] = $a[$key]['monjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
                        $a[$key]['monjul'] = $a[$key]['monjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
                        $a[$key]['monaug'] = $a[$key]['monaug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
                        $a[$key]['monsep'] = $a[$key]['monsep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
                        $a[$key]['monoct'] = $a[$key]['monoct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
                        $a[$key]['monnov'] = $a[$key]['monnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
                        $a[$key]['mondec'] = $a[$key]['mondec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
                    }

                    $month['mjan'] = $month['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
                    $month['mfeb'] = $month['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
                    $month['mmar'] = $month['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
                    $month['mapr'] = $month['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
                    $month['mmay'] = $month['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
                    $month['mjun'] = $month['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
                    $month['mjul'] = $month['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
                    $month['maug'] = $month['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
                    $month['msep'] = $month['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
                    $month['moct'] = $month['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
                    $month['mnov'] = $month['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
                    $month['mdec'] = $month['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');

                    $month2['mjan'] = $month2['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
                    $month2['mfeb'] = $month2['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
                    $month2['mmar'] = $month2['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
                    $month2['mapr'] = $month2['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
                    $month2['mmay'] = $month2['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
                    $month2['mjun'] = $month2['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
                    $month2['mjul'] = $month2['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
                    $month2['maug'] = $month2['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
                    $month2['msep'] = $month2['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
                    $month2['moct'] = $month2['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
                    $month2['mnov'] = $month2['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
                    $month2['mdec'] = $month2['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
                    break;

                case '3YEARS':
                    if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
                        $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail'], 'year1' => number_format((float)$result2[$b]['year1'], 2, '.', ''), 'year2' => number_format((float)$result2[$b]['year2'], 2, '.', ''), 'year3' => number_format((float)$result2[$b]['year3'], 2, '.', ''));
                        $oldacno = $result2[$b]['acno'];
                    } else {
                        $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
                        $a[$key]['year1'] = $a[$key]['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
                        $a[$key]['year2'] = $a[$key]['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
                        $a[$key]['year3'] = $a[$key]['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
                    }
                    $month['year1'] = $month['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
                    $month['year2'] = $month['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
                    $month['year3'] = $month['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');

                    $month2['year1'] = $month2['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
                    $month2['year2'] = $month2['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
                    $month2['year3'] = $month2['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
                    break;
            }

            if ($result2[$b]['detail'] == 0) {
                if ($this->MAXIPRO_PLANTTREE($a, '\\' . $result2[$b]['acno'], $result2[$b]['cat'], $year1, $year2, $view, $month, $month2, $filters, $filter1, $filter2, $companyid)) {
                    if ($result2[$b]['levelid'] > 1) {
                        switch ($view) {
                            case 'MONTHLY':
                                $a[] = array(
                                    'acno' => $result2[$b]['acno'], 'acnoname' =>
                                    'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'],
                                    'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2,
                                    'monjan' => $month['mjan'], 'monfeb' => $month['mfeb'], 'monmar' => $month['mmar'],
                                    'monapr' => $month['mapr'], 'monmay' => $month['mmay'], 'monjun' => $month['mjun'],
                                    'monjul' => $month['mjul'], 'monaug' => $month['maug'], 'monsep' => $month['msep'],
                                    'monoct' => $month['moct'], 'monnov' => $month['mnov'], 'mondec' => $month['mdec'],
                                    'yr' => $year1
                                );
                                $month['mjan'] = 0;
                                $month['mfeb'] = 0;
                                $month['mmar'] = 0;
                                $month['mapr'] = 0;
                                $month['mmay'] = 0;
                                $month['mjun'] = 0;
                                $month['mjul'] = 0;
                                $month['maug'] = 0;
                                $month['msep'] = 0;
                                $month['moct'] = 0;
                                $month['mnov'] = 0;
                                $month['mdec'] = 0;
                                break;

                            case '3YEARS':
                                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month['year1'], 'year2' => $month['year2'], 'year3' => $month['year3']);
                                $month['year1'] = 0;
                                $month['year2'] = 0;
                                $month['year3'] = 0;
                                break;
                        }
                    } else {
                        if ($cat == 'C') {
                            $C = "('R','G')";
                            $loss = $this->DEFAULT_BALANCE_SHEETDUE('CREDIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);
                            $C = "('E','O')";
                            $loss2 = $this->DEFAULT_BALANCE_SHEETDUE('DEBIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);

                            $lmonjan = $lmonfeb = $lmonmar = $lmonapr = $lmonmay = $lmonjun = 0;
                            $lmonjul = $lmonaug = $lmonsep = $lmonoct = $lmonnov = $lmondec = 0;

                            $l2monjan = $l2monfeb = $l2monmar = $l2monapr = $l2monmay = $l2monjun = 0;
                            $l2monjul = $l2monaug = $l2monsep = $l2monoct = $l2monnov = $l2mondec = 0;

                            for ($i = 0; $i < count($loss); $i++) {
                                $lmonjan += $loss[$i]['monjan'];
                                $lmonfeb += $loss[$i]['monfeb'];
                                $lmonmar += $loss[$i]['monmar'];
                                $lmonapr += $loss[$i]['monapr'];
                                $lmonmay += $loss[$i]['monmay'];
                                $lmonjun += $loss[$i]['monjun'];
                                $lmonjul += $loss[$i]['monjul'];
                                $lmonaug += $loss[$i]['monaug'];
                                $lmonsep += $loss[$i]['monsep'];
                                $lmonoct += $loss[$i]['monoct'];
                                $lmonnov += $loss[$i]['monnov'];
                                $lmondec += $loss[$i]['mondec'];
                            }

                            for ($m = 0; $m < count($loss2); $m++) {
                                $l2monjan += $loss2[$m]['monjan'];
                                $l2monfeb += $loss2[$m]['monfeb'];
                                $l2monmar += $loss2[$m]['monmar'];
                                $l2monapr += $loss2[$m]['monapr'];
                                $l2monmay += $loss2[$m]['monmay'];
                                $l2monjun += $loss2[$m]['monjun'];
                                $l2monjul += $loss2[$m]['monjul'];
                                $l2monaug += $loss2[$m]['monaug'];
                                $l2monsep += $loss2[$m]['monsep'];
                                $l2monoct += $loss2[$m]['monoct'];
                                $l2monnov += $loss2[$m]['monnov'];
                                $l2mondec += $loss2[$m]['mondec'];
                            }

                            $jan = $lmonjan - $l2monjan;
                            $feb = $lmonfeb - $l2monfeb;
                            $mar = $lmonmar - $l2monmar;
                            $apr = $lmonapr - $l2monapr;
                            $may = $lmonmay - $l2monmay;
                            $jun = $lmonjun - $l2monjun;
                            $jul = $lmonjul - $l2monjul;
                            $aug = $lmonaug - $l2monaug;
                            $sep = $lmonsep - $l2monsep;
                            $oct = $lmonoct - $l2monoct;
                            $nov = $lmonnov - $l2monnov;
                            $dec = $lmondec - $l2mondec;

                            $month2['mjan'] = $month2['mjan'] + number_format((float)$jan, 2, '.', '');
                            $month2['mfeb'] = $month2['mfeb'] + number_format((float)$feb, 2, '.', '');
                            $month2['mmar'] = $month2['mmar'] + number_format((float)$mar, 2, '.', '');
                            $month2['mapr'] = $month2['mapr'] + number_format((float)$apr, 2, '.', '');
                            $month2['mmay'] = $month2['mmay'] + number_format((float)$may, 2, '.', '');
                            $month2['mjun'] = $month2['mjun'] + number_format((float)$jun, 2, '.', '');
                            $month2['mjul'] = $month2['mjul'] + number_format((float)$jul, 2, '.', '');
                            $month2['maug'] = $month2['maug'] + number_format((float)$aug, 2, '.', '');
                            $month2['msep'] = $month2['msep'] + number_format((float)$sep, 2, '.', '');
                            $month2['moct'] = $month2['moct'] + number_format((float)$oct, 2, '.', '');
                            $month2['mnov'] = $month2['mnov'] + number_format((float)$nov, 2, '.', '');
                            $month2['mdec'] = $month2['mdec'] + number_format((float)$dec, 2, '.', '');


                            $a[] = array(
                                'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                                'levelid' => $result2[$b]['levelid'] + 1, 'cat' => $result2[$b]['cat'],
                                'parent' => $result2[$b]['parent'], 'detail' => 1,
                                'monjan' => $jan, 'monfeb' => $feb, 'monmar' => $mar, 'monapr' => $apr,
                                'monmay' => $may, 'monjun' => $jun, 'monjul' => $jul, 'monaug' => $aug,
                                'monsep' => $sep, 'monoct' => $oct, 'monnov' => $nov, 'mondec' => $dec, 'yr' => $year1
                            );
                        }

                        switch ($view) {
                            case 'MONTHLY':
                                $a[] = array(
                                    'acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'],
                                    'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'],
                                    'parent' => $result2[$b]['parent'], 'detail' => 2,
                                    'monjan' => $month2['mjan'], 'monfeb' => $month2['mfeb'],
                                    'monmar' => $month2['mmar'], 'monapr' => $month2['mapr'],
                                    'monmay' => $month2['mmay'], 'monjun' => $month2['mjun'],
                                    'monjul' => $month2['mjul'], 'monaug' => $month2['maug'],
                                    'monsep' => $month2['msep'], 'monoct' => $month2['moct'],
                                    'monnov' => $month2['mnov'], 'mondec' => $month2['mdec']
                                );
                                break;

                            case '3YEARS':
                                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month2['year1'], 'year2' => $month2['year2'], 'year3' => $month2['year3']);
                                break;
                        }
                    }
                }
            }
        }

        if (count($result2) > 0) {
            return true;
        } else {
            return false;
        }
    } // end fn

    private function MAXIPRO_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid)
    {
        $field = '';
        switch ($cat) {
            case 'L':
            case 'R':
            case 'G':
            case 'C':
                $field = ' sum(detail.cr-detail.db) ';
                break;
            default:
                $field = 'sum(detail.db-detail.cr) ';
                break;
        }

        $years = '';
        $months = '';
        $years = "year(head.dateid)";
        $months = "month(head.dateid)";

        switch ($view) {
            case 'MONTHLY':
                $query1 = "select acno, acnoname, levelid, cat, detail,ifnull(sum(case when mon=1 then amt else 0 end),0) as monjan,
                                ifnull(sum(case when mon=2 then amt else 0 end),0) as monfeb,ifnull(sum(case when mon=3 then amt else 0 end),0) as monmar,
                                ifnull(sum(case when mon=4 then amt else 0 end),0) as monapr,ifnull(sum(case when mon=5 then amt else 0 end),0) as monmay,
                                ifnull(sum(case when mon=6 then amt else 0 end),0) as monjun,ifnull(sum(case when mon=7 then amt else 0 end),0) as monjul,
                                ifnull(sum(case when mon=8 then amt else 0 end),0) as monaug,ifnull(sum(case when mon=9 then amt else 0 end),0) as monsep,
                                ifnull(sum(case when mon=10 then amt else 0 end),0) as monoct,ifnull(sum(case when mon=11 then amt else 0 end),0) as monnov,
                                ifnull(sum(case when mon=12 then amt else 0 end),0) as mondec,yr, ifnull(sum(amt),0) as amt,
                                incomegrp, (case when incomegrp = '' then parent else inc.parent2 end) as parent
                        from (select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, 
                                    ifnull(sum(tb.amt),0) as amt, coa.incomegrp,
                                    (select acno from coa as c where coa.incomegrp=c.acnoname) as parent2
                            from coa 
                            left join (select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                        from glhead as head 
                                        left join gldetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        left join projectmasterfile as proj on proj.line=detail.projectid
                                        where  $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                        group by detail.acnoid, $months,  $years 
                                        union all 
                                        select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                        from hjchead as head 
                                        left join gldetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        left join projectmasterfile as proj on proj.line=detail.projectid
                                        where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                        group by detail.acnoid, $months,$years
                                        union all
                                        select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                        from jchead as head 
                                        left join ladetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        left join projectmasterfile as proj on proj.line=detail.projectid
                                        where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                        group by detail.acnoid, $months,$years
                                        union all
                                        select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                        from lahead as head 
                                        left join ladetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        left join projectmasterfile as proj on proj.line=detail.projectid
                                        where  $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                        group by detail.acnoid, $months,  $years ) as tb on tb.acnoid=coa.acnoid
                            group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, 
                                    tb.mon, tb.yr,coa.incomegrp,parent2) as inc 
                            where (case when incomegrp = '' then parent else inc.parent2 end) = '$acno' and cat='$cat'
                        group by acno, acnoname, levelid, cat, detail, yr,incomegrp,inc.parent,parent2";
                break;

            case "3YEARS":
                $query1 = "select acno, acnoname, levelid, cat, parent, detail,
                                ifnull(sum(case when yr=$year2-2 then amt else 0 end),0) year1,
                                ifnull(sum(case when yr=$year2-1 then amt else 0 end),0) year2,
                                ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, yr, 
                                ifnull(sum(amt),0) as amt
                            from (select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, 
                                        tb.mon, tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
                                from coa 
                                left join (select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                            from glhead as head 
                                            left join gldetail as detail on detail.trno=head.trno 
                                            left join cntnum on cntnum.trno=head.trno 
                                            where $years between '" . $year1 . "' and '" . $year2 . "' 
                                                    " . $filters . " " . $filter1 . " " . $filter2 . "
                                            group by detail.acnoid, $months,  $years) as tb on tb.acnoid=coa.acnoid
                                where coa.parent='$acno' and coa.cat='$cat'
                                group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
                                        coa.detail, tb.mon, tb.yr) as inc 
                            group by acno, acnoname, levelid, cat, parent, detail, yr";
                break;
        }
        return $query1;
    } // end fn

    private function DEFAULT_BALANCE_SHEETDUE($entry, $cat, $year1, $year2, $view, $filters, $filter1, $filter2)
    {
        $field = '';
        switch ($entry) {
            case 'CREDIT':
                $field = ' round(ifnull(sum(detail.cr-detail.db),0),2) ';
                break;
            default:
                $field = ' round(ifnull(sum(detail.db-detail.cr),0),2) ';
                break;
        }
        $years = "year(head.dateid)";
        $months = "month(head.dateid)";
        $selecthjc = '';
        $selecthjc = " union all 
                    select $field as cr, $years as yr, $months as mon
                    from hjchead as head 
                    left join gldetail as detail on detail.trno=head.trno
                    left join coa on coa.acnoid=detail.acnoid 
                    left join cntnum on cntnum.trno=head.trno
                    where $years between  '" . $year1 . "' and '" . $year2 . "' 
                    and coa.cat in " . $cat . " " . $filters . " " . $filter1 . " " . $filter2 . "
                    group by $years, $months";




        switch ($view) {
            case 'MONTHLY':
                $query1 = "select yr,ifnull(sum(case when mon=1 then cr else 0 end),0) as monjan,
                                ifnull(sum(case when mon=2 then cr else 0 end),0) as monfeb,
                                ifnull(sum(case when mon=3 then cr else 0 end),0) as monmar,
                                ifnull(sum(case when mon=4 then cr else 0 end),0) as monapr,
                                ifnull(sum(case when mon=5 then cr else 0 end),0) as monmay,
                                ifnull(sum(case when mon=6 then cr else 0 end),0) as monjun,
                                ifnull(sum(case when mon=7 then cr else 0 end),0) as monjul,
                                ifnull(sum(case when mon=8 then cr else 0 end),0) as monaug,
                                ifnull(sum(case when mon=9 then cr else 0 end),0) as monsep,
                                ifnull(sum(case when mon=10 then cr else 0 end),0) as monoct,
                                ifnull(sum(case when mon=11 then cr else 0 end),0) as monnov,
                                ifnull(sum(case when mon=12 then cr else 0 end),0) as mondec
                                from (
                                select $field as cr, $years as yr, $months as mon
                                from glhead as head left join gldetail as detail on detail.trno=head.trno
                                left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
                                where $years between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . " " . $filter1 . " " . $filter2 . "
                                group by $years, $months $selecthjc
                                ) as tb group by yr";

                break;

            case '3YEARS':
                $query1 = "select yr,ifnull(sum(case when yr=$year2-2 then cr else 0 end),0) as year1,
                            ifnull(sum(case when yr=$year2-1 then cr else 0 end),0) as year2,
                            ifnull(sum(case when yr=$year2 then cr else 0 end),0) as year3
                            from (
                            select $field as cr, $years as yr, $months as mon
                            from glhead as head left join gldetail as detail on detail.trno=head.trno
                            left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
                            where $years between '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . " " . $filter1 . " " . $filter2 . "
                            group by $years, $months
                            ) as tb group by yr";

                break;
        } // end switch

        $data = $this->coreFunctions->opentable($query1);
        $result = json_decode(json_encode($data), true);
        return $result;
    } // end fn

    private function DEFAULT_HEADER($params, $data)
    {
        $font = $this->companysetup->getrptfont($params['params']);
        $fontsize = '10';
        $fontsize12 = '12';

        $year = $params['params']['dataparams']['year'];
        $center1 = $params['params']['center'];
        $username = $params['params']['user'];
        $companyid = $params['params']['companyid'];

        $center = $params['params']['dataparams']['center'];
        $costcenter = $params['params']['dataparams']['code'];
        if ($center == '') {
            $center = "ALL";
        }

        switch ($companyid) {
            case 12: //afti usd
            case 10: //afti
                $layoutsize = 800;
                break;

            default:
                $layoutsize = 1480;
                break;
        }
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $dept   = $params['params']['dataparams']['ddeptname'];
            if ($dept != "") {
                $deptname = $params['params']['dataparams']['deptname'];
            } else {
                $deptname = "ALL";
            }

            if ($costcenter != "") {
                $costcenter = $params['params']['dataparams']['name'];
            } else {
                $costcenter = "ALL";
            }
        }

        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTHLY BALANCE SHEET', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Center :  ' . $center, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Project :  ' . $costcenter, '800', null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Department :  ' . $deptname, '800', null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Year :  ' . $year, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        return $str;
    } // end fn

    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 12: //afti usd
            case 10: //afti
                $layoutsize = 1000;
                break;

            default:
                $layoutsize = 1480;
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCOUNTS', '280', null, false, '1px solid ', 'TB', '', $font, $fontsize, 'B', '', '4px');
        $str .= $this->reporter->col('JAN', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FEB', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAR', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APR', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAY', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUN', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUL', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AUG', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SEP', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OCT', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOV', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEC', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '120', null, false, '1px solid ', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    private function MAXIPRO_MONTHLY_INCOME_STATEMENT_LAYOUT($params, $data)
    {;
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($params['params']);

        // $font_size = '10';
        $fontsize11 = 11;
        $fontsize12 = '12';

        $count = 67;
        $page = 66;
        $this->reporter->linecounter = 0;
        $str = '';
        if (empty($data)) {
            return $this->othersClass->emptydata($params);
        }

        $str .= $this->reporter->beginreport('1000');
        $str .= $this->DEFAULT_HEADER($params, $data);

        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

        for ($i = 0; $i < count($data); $i++) {

            $lineTotal = 0;
            $bold = '';

            if ($data[$i]['detail'] == 1 and ($data[$i]['monjan'] == 0 and $data[$i]['monfeb'] == 0
                and $data[$i]['monmar'] == 0 and $data[$i]['monapr'] == 0 and $data[$i]['monmay'] == 0
                and $data[$i]['monjun'] == 0 and $data[$i]['monjul'] == 0 and $data[$i]['monaug'] == 0
                and $data[$i]['monsep'] == 0 and $data[$i]['monoct'] == 0 and $data[$i]['monnov'] == 0
                and $data[$i]['mondec'] == 0)) {
            } else {

                if ($data[$i]['acnoname'] != '') {

                    $indent = '5' * ($data[$i]['levelid'] * 3);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();

                    if ($data[$i]['detail'] == 2) {
                        $bold = 'B';
                    }


                    $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, $fontsize12, $bold, '', '0px 0px 0px ' . $indent . 'px');


                    if ($data[$i]['detail'] != 0) {
                        if ($data[$i]['monjan'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font,  $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monjan'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monfeb'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monfeb'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monmar'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monmar'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monapr'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monapr'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monmay'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monmay'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monjun'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monjun'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monjul'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monjul'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monaug'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monaug'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monsep'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monsep'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monoct'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monoct'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['monnov'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['monnov'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                        if ($data[$i]['mondec'] == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($data[$i]['mondec'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }

                        $lineTotal = $data[$i]['monjan'] + $data[$i]['monfeb'] + $data[$i]['monmar'] + $data[$i]['monapr'] + $data[$i]['monmay'] + $data[$i]['monjun'] + $data[$i]['monjul'] + $data[$i]['monaug'] + $data[$i]['monsep'] + $data[$i]['monoct'] + $data[$i]['monnov'] + $data[$i]['mondec'];
                        if ($lineTotal == 0) {
                            $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        } else {
                            $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
                        }
                    }

                    $str .= $this->reporter->endrow();
                }
            }

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();


                $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
                if (!$allowfirstpage) {
                    $str .= $this->DEFAULT_HEADER($params, $data);
                }
                $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    } //end fn
}//end class