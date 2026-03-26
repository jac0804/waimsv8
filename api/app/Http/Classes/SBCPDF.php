<?php

namespace App\Http\Classes;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\companysetup;
use PDF;
use Illuminate\Support\Facades\URL;

class SBCPDF
{
    public $linecounter = 0;
    private $coreFunctions;
    private $othersClass;
    private $companysetup;
    public $line = 0;
    protected $pagenum;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->companysetup = new companysetup;
    } //end construct





    function csvbatchsave($config, $data)
    {

        // format: eto ung need ilagay sa file ng report tapos CSV ung Print Type
        //public function reportdatacsv($config)
        //  {
        //    $data = $this->coreFunctions->opentable("select * from coa");
        //    $ret = $this->reporter->csvbatchsave($config,$data);
        //    return ['status' => true, 'msg' => 'Generating CSV successfully', 'data' => $ret['data'], 'params' => $this->reportParams, 'path' => $ret['filename'],'count'=>$ret['count'],'callback'=>true,'action'=>'csvbatch','name'=>'chartOfAccount']; 
        //  }

        $filename = 'csvfile/' . $config['params']['name'] . $config['params']['user'];
        // Create directory if it doesn't exist
        if (!Storage::disk('sbcpath')->exists(dirname($filename))) {
            Storage::disk('sbcpath')->makeDirectory(dirname($filename));
        }
        $str = json_encode($data);
        $chunks = str_split($str, 1000000000);
        $count = 0;
        $returnstr = '';
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $path = [];
        foreach ($chunks as $key => $value) {
            if ($key == 0) {
                $returnstr = $value;
            } else {
                $putResult = Storage::disk('sbcpath')->put($filename . date_format(date_create($current_timestamp), 'mdYHis') . $key . '.sbc', $value);
                array_push($path, $filename . date_format(date_create($current_timestamp), 'mdYHis') . $key . '.sbc');
            }
            $count = $key;
        }
        $count = 0;
        return ['filename' => $path, 'status' => 'ok', 'count' => $count, 'data' => $returnstr];
    }


    //   width,  height,  background, border,   text-alignment, font,   fontsize, color,     fontweight,    padding,       margin
    function begintable($w = null, $h = null, $bg = false,  $b = false, $al = '',  $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '')
    {
        $style = $this->styler($w, $h, $bg, $b, $al, $f, $fs, $fw, $fc, $pad, $m);
        return  $d = '<table style="border-collapse: collapse;clear: both;' . $style . '">';
    } //end fn

    function endtable()
    {
        return '</table>';
    } //end fn

    function startrow($w = null, $h = null, $bg = false,  $b = false, $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '')
    {
        $style = $this->styler($w, $h, $bg, $b, $al, $f, $fs, $fw, $fc, $pad, $m);
        $this->line++;
        return  $d = '<tr style="' . $style . '">';
    } //end fn

    function endrow()
    {
        return '</tr>';
    } //end fn



    // Jiks - 02-10-2023 - add colspan for merge column for excel
    function col($txt = '', $w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $isamount = 0, $colspan = 0, $bc = null)
    {
        $cps = "";
        $style = $this->styler($w, $h, $bg, $b, $b_, $al, $f, $fs, $fw, $fc, $pad, $m, $bc);
        if ($isamount == 1) $addedstyle .= ';mso-number-format:#\,##0.00;';

        if ($colspan != 0) {
            $cps = 'colspan = "' . $colspan . '" ';
        }
        $len = $this->othersClass->val($len);
        if ($len == 0) {
            //@TODO multiline printing
            return  $d = '<td ' . $cps . ' style="' . $style . ';' . $addedstyle . '">' . $txt . '</td>'; //ucwords(strtolower($txt))
        } else {
            return  $d = '<td ' . $cps . ' style="' . $style . ';' . $addedstyle . '">' . substr($txt, 0, $len) . '</td>'; //ucwords(strtolower(substr($txt,0,$len)))
        }
    } //end fn

    function row($cols, $txt)
    {
        $str = '';
        $linecounter = 1;

        $str .= '<tr>';

        foreach ($cols as $key => $col) {
            $w = isset($col[0]) ? $col[0] : null;
            $h = isset($col[1]) ? $col[1] : null;
            $bg = isset($col[2]) ? $col[2] : false;
            $b = isset($col[3]) ? $col[3] : false;
            $b_ = isset($col[4]) ? $col[4] : '';
            $al = isset($col[5]) ? $col[5] : '';
            $f = isset($col[6]) ? $col[6] : '';
            $fs = isset($col[7]) ? $col[7] : '';
            $fw = isset($col[8]) ? $col[8] : '';
            $fc = isset($col[9]) ? $col[9] : '';
            $pad = isset($col[10]) ? $col[10] : '';
            $m = isset($col[11]) ? $col[11] : '';
            $len = isset($col[12]) ? $col[12] : 0;

            $style = $this->styler($w, $h, $bg, $b, $b_, $al, $f, $fs,  $fw, $fc, $pad, $m);

            if ($len != 0) {
                $this->breakword($txt, $key, $len, $col, $style, $linecounter);
            } else {
                $str .= '<td style="' . $style . '">' . ucwords(strtolower(substr($txt[$key], 0, $w / 4))) . '</td>';
            } //end if
        } //end for each

        $str .= '</tr>';
        $this->linecounter++;
        $this->line++;
        return $str;
    } //end fn

    public function letterhead($center, $username, $config = [])
    {
        $str = '';
        $str .= '<span class="header"></span>';
        $str .= $this->generateReportHeader($center, $username, $config);
        return $str;
    } //end letterhead

    public function setreporttimestamp($config, $user, $headerdata)
    {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $username = $user;
        $companyid = 0;
        $resellerid = 0;

        if (!empty($config)) {
            $username = $config['params']['user'];
            $companyid = $config['params']['companyid'];
            $resellerid = $config['params']['resellerid'];
        }
        switch ($resellerid) {
            case 2:
                return strtoupper($username) . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->code . ' RSSC');
                break;
            default:
                return $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name);
                break;
        }
    }

    public function letterfooter()
    {
        return '<span class="footer"></span>';
    } //end letterhead

    private function generateReportHeader($center, $username, $config)
    {
        // col($txt = '', $w = null, $h = null, $bg = false,  $b = false, $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $isamount = 0, $colspan = 0)
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        if (isset($config['params'])) {
            $font = $this->companysetup->getrptfont($config['params']);
        } else {
            $font = 'Century Gothic';
        }

        $str = '';

        $reporttimestamp = $this->setreporttimestamp($config, $username, $headerdata);

        $str .= $this->startrow();
        $str .=  $this->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
        $str .=  $this->endrow();
        if (isset($config['params']['companyid'])) {
            switch ($config['params']['companyid']) {
                case 21: //kinggeorge
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, '14', 'B', 'green', '', 0, '', 0, 5);
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '', 0, '', 0, 5);
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '', 0, '', 0, 5);
                    $str .= $this->endrow();
                    break;
                case 30: //rt
                    break;
                case 34: //evergreen
                    $str .= $this->startrow();
                    $str .= $this->col(' ', '600', '40', false, '1px solid ', '', 'c', $font, '14', 'B', '', '');
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    break;
                case 23: //labsol cebu
                case 41: //labsol manila
                case 52: //technolab
                    $str .= $this->startrow();
                    $str .= $this->col($headerdata[0]->name, null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
                    $str .= $this->endrow();

                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    break;
                case 63: //ericco - no header 
                    break;
                case 62: //onesky
                    $logo = "";
                    $width = "900px";
                    $height = "180px";
                    $align = "R";
                    $dislogo = '';
                    if (isset($config['params']['dataparams']['division'])) {
                        switch ($config['params']['dataparams']['division']) {
                            case 'EXT':
                                $logo = URL::to('/images/onesky/onesky_logo.png');
                                $dislogo = '<img src ="' . $logo . '" alt="mbc" width="330" height ="90px" >';
                                break;
                            case 'NDC':
                                $logo = URL::to('/images/onesky/nson_logo.png');
                                $dislogo = '<img src ="' . $logo . '" alt="mbc" width="330" height ="90px" >';
                                break;
                        }
                        if ($dislogo != "") {
                            $str .= '<div style="position: relative;">';
                            $str .= "<div style='position:absolute; margin:-100px 0 0 0'>";
                            $str .= $this->startrow();
                            $str .= $this->col($dislogo, '200', null, false, '1px solid', '', $align, $font, '14', 'B', '', '');
                            $str .= $this->endrow();
                            $str .= "</div>";
                            $str .= "</div>";
                        }
                    }
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
                    $str .= $this->endrow();

                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    break;
                case 58: //cdohris
                    $logo = "";
                    $width = "1000px";
                    $height = "180px";
                    $align = "R";
                    if (isset($config['params']['dataparams']['division'])) {
                        switch ($config['params']['dataparams']['division']) {
                            case '001':
                                $logo = URL::to('/images/cdohris/cdohris_logo.png');
                                $width = "1000px";
                                $height = "250px";
                                $align = "C";
                                break;
                            case '002':
                                $logo = URL::to('/images/cdohris/mbcpaflogo.png');
                                break;
                            case '003':
                                $logo = URL::to('/images/cdohris/ridefundpaf.png');
                                break;
                            case '004':
                                $logo = URL::to('/images/cdohris/motormate.png');
                                $width = "400px";
                                $height = "250px";
                                $align = "C";
                                break;
                        }
                    }
                    if ($logo != "") {
                        $str .= '<div style="position: relative;">';
                        switch ($config['params']['name']) {
                            case 'personnel_requisition':
                            case 'payroll_register':
                                if ($config['params']['dataparams']['divrep'] != '') {
                                    $addr = $this->coreFunctions->getfieldvalue('division', "address", "divcode=?", [$config['params']['dataparams']['division']]);

                                    $str .= $this->startrow();
                                    $str .= $this->col(strtoupper($config['params']['dataparams']['divname']), null, null, false, '1px solid ', '', 'L', $font, '14', 'B', '', '') . '<br />';
                                    $str .= $this->endrow();

                                    $str .= $this->startrow();
                                    $str .= $this->col(strtoupper($addr), null, null, false, '1px solid ', '', 'L', $font, '13', 'B', '', '') . '<br />';
                                    $str .= $this->endrow();
                                } else {
                                    goto defaultHeader;
                                }
                                break;
                            default:
                                if(isset($config['params']['dataparams']['divrep'])){
                                    if ($config['params']['dataparams']['divrep'] != '') {
                                        $str .= "<div style='position:absolute; margin:-150px 0 0 0'>";
                                        $str .= $this->startrow();
                                        $str .= $this->col('<img src ="' . $logo . '" alt="mbc" width="' . $width . '" height ="' . $height . '" >', '250', null, false, '1px solid', '', $align, $font, '14', 'B', '', '');
                                        $str .= $this->endrow();
                                        $str .= "</div>";
                                    } else {
                                        goto defaultHeader;
                                    }
                                }else {
                                    goto defaultHeader;
                                }

                                break;
                        }

                        $str .= "</div>";
                    } else {
                        defaultHeader:

                        $str .= $this->startrow();
                        $str .= $this->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', $font, '14', 'B', '', '') . '<br />';
                        $str .= $this->endrow();

                        $str .= $this->startrow();
                        $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'L', $font, '13', 'B', '', '') . '<br />';
                        $str .= $this->endrow();
                        $str .= $this->startrow();
                        $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'L', $font, '13', 'B', '', '') . '<br />';
                        $str .= $this->endrow();
                    }



                    break;

                default:
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
                    $str .= $this->endrow();

                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    $str .= $this->startrow();
                    $str .= $this->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
                    $str .= $this->endrow();
                    break;
            }
        }

        return $str;
    } //end function generate report header

    function tableheader($txt = '', $rowspan = '', $colspan = '', $w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = 'courier', $fs = '', $fw = '', $fc = '', $pad = '', $m = '')
    {
        $style = $this->styler($w, $h, $bg, $b, $b_, $al, $f, $fs, $fw, $fc, $pad, $m);

        if ($rowspan != '') {
            return  $d = '<th rowspan=' . $rowspan . '; style="' . $style . '">' . $txt . '</th>';
        } //END IF
        elseif ($colspan != '') {
            return  $d = '<th colspan=' . $colspan . '; style="' . $style . '">' . $txt . '</th>';
        } //end fn
        else {
            return  $d = '<th style="' . $style . '">' . $txt . '</th>';
        } //end 
    } //end fn

    function breakword($txt, $key, $len, $col, $style, $linecounter)
    {
        $val1 = '';
        $val2 = '';
        $val3 = '';
        $val4 = '';
        $linenum = 0;
        $sam = ucwords($txt[$key]);

        if ($key >= 0) {
            $linenum = ceil(strlen($sam) / $len);
            $line1 = substr($sam, 0, $len);
            $line2 = substr($sam, $len, $len);
            $line3 = substr($sam, $len * 2, $len);
            $line4 = substr($sam, $len * 3, $len);

            $len2 = strlen($line2);
            $len3 = strlen($line3);
            $len4 = strlen($line4);
            for ($keyfield = 0; $keyfield <= $key; $keyfield++) {
                if ($keyfield == $key) {
                    if ($col != null) {
                        $val1 .= '<td style="' . $style . '">' . $line1 . '</td>';
                        $val2 .= '<td style="' . $style . '">' . $line2 . '</td>';
                        $val3 .= '<td style="' . $style . '">' . $line3 . '</td>';
                        $val4 .= '<td style="' . $style . '">' . $line4 . '</td>';
                    }
                }
            }
        }

        for ($i = 0; $i < 1; $i++) {
            if ($len > 0) {
                $this->multiline($val1, 1, $i);
                if ($len2 > 0) {
                    $this->multiline($val2, 2, $i);
                    if ($len3 > 0) {
                        $this->multiline($val3, 3, $i);
                        if ($len4 > 0) {
                            $this->multiline($val4, 4, $i);
                        }
                    }
                }
            }
            $linecounter++;
            $this->line++;
        }
    } //end fn

    function multiline($val, $lines)
    {
        return $val;                                                                // output
    } //end fn

    function styler($w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $bc = null)
    {
        if ($w != null) {
            $w_ = ' width:' . $w . 'px; ';
        } else {
            $w_ = '';
        }
        if ($h != null) {
            $h_ = ' height:' . $h . 'px; ';
        } else {
            $h_ = '';
        }
        if ($bg) {
            $bgcolor = ' background: ' . $bg . '; ';
        } else {
            $bgcolor = '';
        }
        if ($bc != null) {
            $bc = 'border-color:' . $bc . ';';
        } else {
            $bc = 'border-color:black;';
        }
        $b_ = preg_split('//', strtoupper($b_));
        $border = '';

        foreach ($b_ as $bline) {
            switch ($bline) {
                case 'L': {
                        $border .= ' border-left: ' . $b . '; ' . $bc;
                        break;
                    }
                case 'R': {
                        $border .= ' border-right: ' . $b . '; ' . $bc;
                        break;
                    }
                case 'T': {
                        $border .= ' border-top: ' . $b . '; ' . $bc;
                        break;
                    }
                case 'B': {
                        $border .= ' border-bottom: ' . $b . '; ' . $bc;
                        break;
                    }
            }
        } //end for each

        switch (strtoupper($al)) {
            case 'R': {
                    $align = 'text-align:right';
                    break;
                }
            case 'C': {
                    $align = 'text-align:center';
                    break;
                }

            case 'RT': {
                    $align = 'text-align:right; vertical-align: text-top;';
                    break;
                }
            case 'CT': {
                    $align = 'text-align:center; vertical-align: text-top;';
                    break;
                }
            case 'LT': {
                    $align = 'text-align:left; vertical-align: text-top;';
                    break;
                }

            default: {
                    $align = 'text-align:left';
                    break;
                }
        } //end switch

        $font = $f != '' ? ' font-family: ' . $f . '; ' : '';
        $fontsize = $fs != '' ? ' font-size: ' . $fs . 'pt; ' : '';
        $fontcolor = $fc != '' ? ' color: ' . $fc . '; ' : '';
        $fw = $fw != '' ? strtoupper($fw) : '';

        switch ($fw) {
            case 'B': {
                    $fontweight = ' font-weight: bold; ';
                    break;
                }
            case 'BI':
            case 'IB': {
                    $fontweight = ' font-weight:bold; font-style: italic; ';
                    break;
                }
            case 'I': {
                    $fontweight = ' font-weight:normal; font-style: italic; ';
                    break;
                }
            default: {
                    $fontweight = ' font-weight:inherit; ';
                    break;
                }
        } //end if

        $padding = $pad != '' ? 'padding : ' . $pad . ';' : '';
        $margin = $m != '' ? 'margin : ' . $m . ';' : '';
        $style = $w_ . $h_ . $bgcolor . $border . $font . $fontsize . $fontcolor . $fontweight . $padding . $margin . $align;
        return $style;
    } //end fn

    function page_break()
    {
        return '<div style="page-break-after: always;">&nbsp;</div>'; // class="page-break"
    }
    function beginreport($w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '')
    {
        $this->pagenum = 0;
        if ($w == null) {
            $w = '800';
        }
        if ($m == '') {
            $m = '96px;margin-top:10px;';
        }
        $style = $this->styler($w, $h, $bg, $b, $b_, $al, $f, $fs, $fw, $fc, $pad, $m);
        //return '<div id="report_content" style="margin:auto;'. $style .'">';
        return '<div id="report_content" style="' . $style . '">';
    }
    function endreport()
    {
        $this->pagenum = 0; // reset
        $this->linecounter = 0; // reset
        return '</div>';
    }
    function addline()
    {
        $this->linecounter++;
    }

    function header($header = null)
    {
        $str = '';
        $headerlogo = false;
        $headertext = false;
        if ($header != null) {
            $headerheight = isset($header['h']) ? $header['h'] : 0;
            $b = isset($header['b']) ? $header['b'] : '0px';
            $b_ = isset($header['b_']) ? $header['b_'] : '';


            $b_ = preg_split('//', strtoupper($b_));

            $border = '';

            foreach ($b_ as $bline) {
                switch ($bline) {
                    case 'L': {
                            $border .= ' border-left: ' . $b . '; ';
                            break;
                        }
                    case 'R': {
                            $border .= ' border-right: ' . $b . '; ';
                            break;
                        }
                    case 'T': {
                            $border .= ' border-top: ' . $b . '; ';
                            break;
                        }
                    case 'B': {
                            $border .= ' border-bottom: ' . $b . '; ';
                            break;
                        }
                }
            } //end for each

            if (isset($header['logo']) && $header['logo']) {
                $headerlogo = isset($header['logo'][0]) ? $header['logo'][0] : false;
                $l_width = isset($header['logo'][1]) ? $header['logo'][1] : '100';
                $l_float = isset($header['logo'][2]) ? strtoupper($header['logo'][2]) : '';
                switch ($l_float) {
                    case 'R': {
                            $l_float = 'right';
                            break;
                        }
                    case 'L': {
                            $l_float = 'left';
                            break;
                        }
                    default: {
                            $l_float = 'center';
                            break;
                        }
                }
                //                $this->l_margin=isset($header['logo'][2])?$header['logo'][2] : '11' ;
                $l_padding = isset($header['logo'][4]) ? $header['logo'][4] : '0px';
            } //end if

            if (isset($header['title']) && $header['title']) {
                $headertitle = isset($header['title'][0]) ? $header['title'][0] : '';
                $t_font = isset($header['title'][1]) ? $header['title'][1] : '';
                $t_fontsize = isset($header['title'][2]) ? $header['title'][2] : '14';
                $t_weight = isset($header['title'][3]) ? strtoupper($header['title'][3]) : '';
                switch ($t_weight) {
                    case 'B': {
                            $t_fontweight = ' bold';
                            break;
                        }
                    case 'BI':
                    case 'IB': {
                            $t_fontweight = ' bold; font-style: italic ';
                            break;
                        }
                    case 'I': {
                            $t_fontweight = ' normal; font-style: italic ';
                            break;
                        }
                    default: {
                            $t_fontweight = ' normal ';
                            break;
                        }
                } //end if

                $t_float = isset($header['title'][4]) ? strtoupper($header['title'][4]) : '';

                switch ($t_float) {
                    case 'R': {
                            $t_float = 'right';
                            break;
                        }
                    case 'L': {
                            $t_float = 'left';
                            break;
                        }
                    default: {
                            $t_float = 'center';
                            break;
                        }
                }
                $t_padding = isset($header['title'][5]) ? $header['title'][5] : '0px';
                $t_color = isset($header['title'][6]) ? $header['title'][6] : 'black';
            }

            if (isset($header['string']) && $header['string']) {
                $headerstring = isset($header['string'][0]) ? $header['string'][0] : '';
                $s_font = isset($header['string'][1]) ? $header['string'][1] : '';
                $s_fontsize = isset($header['string'][2]) ? $header['string'][2] : '11';
                $s_weight = isset($header['string'][3]) ? strtoupper($header['string'][3]) : '';
                switch ($s_weight) {
                    case 'B': {
                            $s_fontweight = ' bold';
                            break;
                        }
                    case 'BI':
                    case 'IB': {
                            $s_fontweight = ' bold; font-style: italic ';
                            break;
                        }
                    case 'I': {
                            $s_fontweight = ' normal; font-style: italic ';
                            break;
                        }
                    default: {
                            $s_fontweight = ' normal ';
                            break;
                        }
                }
                $s_float = isset($header['string'][4]) ? strtoupper($header['string'][4]) : '';
                switch ($s_float) {
                    case 'R': {
                            $s_float = 'right';
                            break;
                        }
                    case 'L': {
                            $s_float = 'left';
                            break;
                        }
                    default: {
                            $s_float = 'center';
                            break;
                        }
                }
                $s_padding = isset($header['string'][5]) ? $header['string'][5] : '0px';
                $s_color = isset($header['string'][6]) ? $header['string'][6] : 'black';
            }

            if (isset($header['substring']) && $header['substring']) {
                $headersubstring = $header['substring'];
            }
        }



        $str .= '<div class="header" style="padding:5px;margin: auto;text-align:center;font-size: 14px; height:' . $headerheight . 'px; ' . $border . ' ">';

        $str .= '<div class="header_content">';

        if ($headerlogo) {
            $str .= '<div style = "max-width:380px; float:left;" class="rep_logo"
                                        >' . Html::img('images/test.jpeg', ['class' => 'logo', 'style' => '
                                                width:' . $l_width . 'px;
                                                float:' . $l_float . ';
                                                margin:' . $l_padding . ';']) . '
                                          </div>';
            $headertext = 'float:left;';
        }


        $str .= '<div class="header_holder" style="width:380px; height:' . $headerheight . 'px; ' . $headertext . ' ">';
        $str .= '<div class="rep_title"
                                                                style="
                                                                max-width:400px; 
                                                                margin: auto;
                                                                font-family:' . $t_font . ';
                                                                font-weight:' . $t_fontweight . ';
                                                                font-size:' . $t_fontsize . 'px;
                                                                text-align:' . $t_float . ';
                                                                color:' . $t_color . ';
                                                                padding:' . $t_padding . ';"
                                                >' . $headertitle . '</div>';
        $str .= '<div class="rep_string"
                                                                style="
                                                                max-width:400px; 
                                                                margin: auto;
                                                                font-family:' . $s_font . ';
                                                                font-weight:' . $s_fontweight . ';
                                                                font-size:' . $s_fontsize . 'px;
                                                                text-align:' . $s_float . ';
                                                                color:' . $s_color . ';
                                                                padding:' . $s_padding . ';">' . $headerstring . '<br />' . $headersubstring . '</div>';
        $str .= '</div>';

        $str .= '</div>';


        $str .= '</div>';
        $str .= '<div class="clear" style ="clear:both;"></div>';

        return $str;
    }

    function printline()
    {
        return '<hr>';
    }

    function pagenumber($txt = '', $w = null, $h = null, $bg = false,  $b = false, $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '')
    {
        $this->pagenum++;
        $style = $this->styler($w, $h, $bg, $b, $al, $f, $fs, $fw, $fc, $pad, $m, $addedstyle = '');
        return  '<td style="' . $style . '">' . $txt . ' : ' . $this->pagenum . ' </td>'; //ucwords(strtolower($txt))

        // not working on excel --
        // if ($len == 0) {
        //     //@TODO multiline printing
        //     return  '<style> body { counter-reset: page; border-collapse:collapse;clear: both; } 
        //         #pagenumber::before { counter-increment: page; content: counter(page); left: 0; top: 100%; white-space: nowrap; 
        //         z-index: 20; -moz-border-radius: 5px; -moz-box-shadow: 0px 0px 4px #222;  
        //         background-image: -moz-linear-gradient(top, #eeeeee, #cccccc} </style>
        //         <td style="' . $style . '">' . $txt . ' <span id="pagenumber"></span> </td>'; //ucwords(strtolower($txt))
        // } else {
        //     return  '<style> body { counter-reset: page; border-collapse:collapse;clear: both; } 
        //         #pagenumber::before { counter-increment: page; content: counter(page); left: 0; top: 100%; white-space: nowrap; 
        //         z-index: 20; -moz-border-radius: 5px; -moz-box-shadow: 0px 0px 4px #222;  
        //         background-image: -moz-linear-gradient(top, #eeeeee, #cccccc} </style>
        //         <td style="' . $style . '">' . substr($txt, 0, $len) . ' <span id="pagenumber"></span></td>'; //ucwords(strtolower(substr($txt,0,$len)))
        // }
    }

    function pdfcreate()
    {
        //$pdf_settings = \Config::get('laravel-tcpdf');
        //$pdf = new \Elibyy\TCPDF\TCPdf($pdf_settings['page_orientation'], $pdf_settings['page_units'], $pdf_settings['page_format'], true, 'UTF-8', false);
        $pdf = new \Elibyy\TCPDF\TCPdf('p', 'px');
        return $pdf;
    }


    public function fixcolumn($col = [], $len = 0, $isnewline = 0)
    {

        $itemcol = [];
        foreach ($col as $cols) {
            if (ctype_space($cols)) { // check if contains white
                $cols = trim($cols); // remove whitespaces
            }

            if (is_string($cols)) { // checking if string
                if ($cols != "") { // change to != "" instead of empty - jiks 10/-17-2022

                    $cols = str_replace("</div>", "", $cols);
                    $cols = str_replace(["<div>", "<br>", "&#13;", "<br/>", "<br />"], "\n", $cols);


                    $array = explode("\n", $cols);

                    foreach ($array as $key => $arri) {

                        $arri = strip_tags($arri, '<strike><b><i><u>');

                        if (str_contains($arri, "<strike>") || str_contains($arri, "</strike>")) {
                            $len = $len + 8;
                        }

                        if (str_contains($arri, "<b>") || str_contains($arri, "</b>")) {
                            $len = $len + 7;
                        }

                        if (str_contains($arri, "<i>") || str_contains($arri, "</i>")) {
                            $len = $len + 7;
                        }

                        if (str_contains($arri, "<u>") || str_contains($arri, "</u>")) {
                            $len = $len + 7;
                        }


                        $itemdesword = $conwords = [];
                        $itemdesword = explode(' ', $arri);
                        $itemdeswordstring = $w = '';
                        $length = 0;

                        foreach ($itemdesword as $key => $word) {

                            if (strlen($word) > $len) {
                                $itemdesword = (str_split(trim($word), $len));

                                foreach ($itemdesword as $arri) {
                                    if (strstr($arri, "\n")) {
                                        $array = preg_split("/\r\n|\n|\r/", $arri);
                                        foreach ($array as $arr) {
                                            $word = $arr;
                                            $itemdeswordstring = $itemdeswordstring . $word . " ";
                                            if (strlen($itemdeswordstring) > $len) {
                                                $itemdeswordstring = preg_replace('~(.*)' . preg_quote($word, '~') . '~', '$1' . "", $itemdeswordstring, 1); // 
                                                $itemdeswordstring = ltrim($itemdeswordstring, ' ');
                                                if ($itemdeswordstring != '') {
                                                    array_push($conwords, $itemdeswordstring);
                                                }
                                                $itemdeswordstring = '';
                                                $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                            }
                                        }
                                    } else {
                                        $word = $arri;
                                        $itemdeswordstring = $itemdeswordstring . $word . " ";
                                        if (strlen($itemdeswordstring) > $len) {
                                            $itemdeswordstring = preg_replace('~(.*)' . preg_quote($word, '~') . '~', '$1' . "", $itemdeswordstring, 1); // 
                                            $itemdeswordstring = ltrim($itemdeswordstring, " ");
                                            if ($itemdeswordstring != '') {
                                                array_push($conwords, $itemdeswordstring);
                                            }
                                            $itemdeswordstring = '';
                                            $itemdeswordstring = $itemdeswordstring . $word . " ";
                                        }
                                    }
                                }
                            } else {
                                $itemdeswordstring = $itemdeswordstring . $word . " ";
                                if (strlen($itemdeswordstring) > $len) {

                                    $itemdeswordstring = preg_replace('~(.*)' . preg_quote($word, '~') . '~', '$1' . "", $itemdeswordstring, 1); // 
                                    $itemdeswordstring = ltrim($itemdeswordstring, ' ');
                                    if ($itemdeswordstring != '') {
                                        array_push($conwords, $itemdeswordstring);
                                    }
                                    $itemdeswordstring = '';
                                    $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                }
                            }
                        }

                        if ($itemdeswordstring != '') {
                            array_push($conwords, $itemdeswordstring);
                        }

                        foreach ($conwords as $arri) {
                            if (strstr($arri, "\n")) {
                                $array = preg_split("/\r\n|\n|\r/", $arri);
                                foreach ($array as $arr) {
                                    array_push($itemcol, $arr);
                                }
                            } else {
                                array_push($itemcol, $arri);
                            }
                        }
                    }
                    if ($isnewline) {
                        array_push($itemcol, "\n");
                    }
                }
            }
        } // end foreach
        return $itemcol;
    }

    public function fixcolumntest($len, $col = [], $isnewline = 1)
    { // test 
        $itemcol = [];
        foreach ($col as $cols) {
            if (!empty($cols)) {
                $cols = str_replace("</div>", "", $cols);
                $cols = str_replace(["<div>", "<br>", "&#13;", "<br/>", "\n"], "<br />", $cols);
                $array = (explode("<br />", $cols));
                foreach ($array as $key => $arri) {
                    if (count($array) == 1) {

                        $itemdesword = $conwords = [];
                        $itemdesword = explode(' ', $arri);
                        $itemdeswordstring = $w = '';
                        $length = 0;

                        foreach ($itemdesword as $key => $word) {
                            if (strlen($word) > $len) {
                                $itemdesword = (str_split(trim($word), $len));

                                foreach ($itemdesword as $word) {
                                    $itemdeswordstring = $itemdeswordstring . $word . " ";
                                    if (strlen($itemdeswordstring) > $len) {
                                        $itemdeswordstring = str_replace($word, "", $itemdeswordstring);
                                        $itemdeswordstring = ltrim($itemdeswordstring, ' ');
                                        if ($itemdeswordstring != '') {
                                            array_push($conwords, $itemdeswordstring);
                                        }
                                        $itemdeswordstring = '';
                                        $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                    }
                                }
                            } else {
                                $itemdeswordstring = $itemdeswordstring . $word . " ";
                                if (strlen($itemdeswordstring) > $len) {
                                    $itemdeswordstring = str_replace($word, "", $itemdeswordstring);
                                    $itemdeswordstring = ltrim($itemdeswordstring, ' ');
                                    if ($itemdeswordstring != '') {
                                        array_push($conwords, $itemdeswordstring);
                                    }
                                    $itemdeswordstring = '';
                                    $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                }
                            }
                        }
                        array_push($conwords, $itemdeswordstring);

                        foreach ($conwords as $arri) {
                            if (strstr($arri, "<br />")) {
                                $array = preg_split("<br />", $arri);
                                foreach ($array as $arr) {
                                    array_push($itemcol, $arr);
                                }
                            } else {
                                array_push($itemcol, $arri);
                            }
                        }
                    } else {
                        if (strstr($arri, "<br />")) {
                            $itemdesword = $conwords = [];
                            $itemdesword = explode(' ', $arri);
                            $itemdeswordstring = '';
                            foreach ($itemdesword as $yy) {
                                $xx = (str_split($yy, $len - 5));
                                foreach ($xx as $word) {
                                    $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                    if (strlen($itemdeswordstring) > $len) {
                                        $itemdeswordstring = str_replace($word, "<br />", $itemdeswordstring);
                                        array_push($conwords, $itemdeswordstring);
                                        $itemdeswordstring = '';
                                        $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                    }
                                }
                            }
                            array_push($conwords, $itemdeswordstring);

                            $xlist = implode(' ', $conwords);
                            $x2 = (explode("<br />", $xlist));
                            foreach ($x2 as $x3) {
                                array_push($itemcol, $x3);
                            }

                            $array = preg_split("<br />", $arri);
                            foreach ($array as $arr) {
                                array_push($itemcol, $arr);
                            }
                        } else {

                            $itemdesword = $conwords = [];
                            $itemdesword = explode(' ', $arri);
                            $itemdeswordstring = $w = '';
                            $length = 0;
                            foreach ($itemdesword as $yy) {
                                $w = $w . $yy . ' ';
                                if (strlen($w) > $len) {
                                    $length = $len - strlen($w);
                                }

                                $xx = (str_split($yy, $len - $length));
                                foreach ($xx as $word) {
                                    $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                    if (strlen($itemdeswordstring) > $len) {
                                        $itemdeswordstring = str_replace($word, "<br />", $itemdeswordstring);
                                        array_push($conwords, $itemdeswordstring);
                                        $itemdeswordstring = '';
                                        $itemdeswordstring = $itemdeswordstring . $word . ' ';
                                    }
                                }
                            }
                            array_push($conwords, $itemdeswordstring);

                            $xlist = implode(' ', $conwords);
                            $x2 = (explode("<br />", $xlist));
                            foreach ($x2 as $x3) {
                                array_push($itemcol, $x3);
                            }
                        }
                    }
                }
                if ($isnewline) {
                    array_push($itemcol, "<br />");
                }
            }
        }
        return $itemcol;
    }


    public function ftNumberToWordsConverter($number, $ischeck = true, $cur = 'PHP', $isnumbercent = false)
    {
        $numberwords = $this->ftNumberToWordsBuilder(number_format($number, 2, '.', ''), $ischeck, $isnumbercent);

        switch ($cur) { // add more currency
            case 'USD':
                $cur = 'DOLLAR';
                break;

            case 'PHP':
                $cur = 'PESOS';
                break;

            default:
                $cur = "";
                break;
        }

        if (strpos($numberwords, "/") == true || strpos($numberwords, "CENTS") == true || strpos($numberwords, "CENTAVOS") == true) {
            $numberwords = str_replace(" AND ", " " . $cur . " AND ", $numberwords);
        } else {
            $this->coreFunctions->LogConsole('that' . $numberwords . 'aaa' . $cur);
            $numberwords .= " " . $cur . " ";
        } //end if

        return $numberwords;
    } //end function convert to words

    public function ftNumberToWordsBuilder($number, $ischeck = true, $isnumbercent = false)
    {
        if ($number == 0) {
            return 'Zero';
        } else {
            $hyphen      = ' ';
            $conjunction = ' ';
            $separator   = ' ';
            $negative    = 'negative ';
            $decimal     = ' and ';
            $dictionary  = array(
                0                   => '',
                1                   => 'One',
                2                   => 'Two',
                3                   => 'Three',
                4                   => 'Four',
                5                   => 'Five',
                6                   => 'Six',
                7                   => 'Seven',
                8                   => 'Eight',
                9                   => 'Nine',
                10                  => 'Ten',
                11                  => 'Eleven',
                12                  => 'Twelve',
                13                  => 'Thirteen',
                14                  => 'Fourteen',
                15                  => 'Fifteen',
                16                  => 'Sixteen',
                17                  => 'Seventeen',
                18                  => 'Eighteen',
                19                  => 'Nineteen',
                20                  => 'Twenty',
                30                  => 'Thirty',
                40                  => 'Forty',
                50                  => 'Fifty',
                60                  => 'Sixty',
                70                  => 'Seventy',
                80                  => 'Eighty',
                90                  => 'Ninety',
                100                 => 'Hundred',
                1000                => 'Thousand',
                1000000             => 'Million',
                1000000000          => 'Billion',
                1000000000000       => 'Trillion',
                1000000000000000    => 'Quadrillion',
                1000000000000000000 => 'Quintillion'
            );

            if (!is_numeric($number)) {
                return false;
            } //end if

            if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
                // overflow
                return false;
            } //end if

            if ($number < 0) {
                return $negative . $this->ftNumberToWordsBuilder(abs($number), $ischeck);
            } //end if

            $string = $fraction = null;

            if (strpos($number, '.') !== false) {
                $fractionvalues = explode('.', $number);
                if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
                    list($number, $fraction) = explode('.', $number);
                } //end if
            } //end if

            switch (true) {
                case $number < 21:
                    $string = $dictionary[(int) $number];
                    break;

                case $number < 100:
                    $tens   = ((int) ($number / 10)) * 10;
                    $units  = $number % 10;
                    $string = $dictionary[(int) $tens];
                    if ($units) {
                        $string .= $hyphen . $dictionary[(int) $units];
                    } //end if
                    break;

                case $number < 1000:
                    $hundreds  = $number / 100;
                    $remainder = $number % 100;
                    $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
                    if ($remainder) {
                        $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder, $ischeck);
                    } //end if
                    break;

                default:
                    $baseUnit = pow(1000, floor(log($number, 1000)));
                    $numBaseUnits = (int) ($number / $baseUnit);
                    $remainder = $number % $baseUnit;
                    $string = $this->ftNumberToWordsBuilder($numBaseUnits, $ischeck) . ' ' . $dictionary[(int) $baseUnit];
                    if ($remainder) {
                        $string .= $remainder < 100 ? $conjunction : $separator;
                        $string .= $this->ftNumberToWordsBuilder($remainder, $ischeck);
                    } //end if
                    break;
            } //end switch
            if (null !== $fraction && is_numeric($fraction)) {
                if ($ischeck) { // for check
                    $string .= $decimal . ' ' . $fraction .  '/100';
                    $words = array();
                    $string .= implode(' ', $words);
                } else { // for currency                    
                    if ($isnumbercent) {
                        $cent = round($fraction, 2);
                    } else {
                        $cent = $this->ftNumberToWordsBuilder(round($fraction, 2));
                    }
                    $string .= $decimal . ' ' . $cent . ' CENTAVOS';
                }
            } //end if

            return strtoupper($string);
        } //end
    } //end fn

    public function mrow($val, $val2 = [])
    {
        // $val[0] = value, $val[1] = size, $val[2] = align, $val[3] = type
        return [
            'val1' => [
                'val' => $val[0],
                'size' => isset($val[1]) ? $val[1] : '',
                'align' => isset($val[2]) ? $val[2] : 'L',
                'type' => isset($val[3]) ? $val[3] : ''
            ],
            'val2' => [
                'val' => isset($val2[0]) ? $val2[0] : '',
                'size' => isset($val2[1]) ? $val2[1] : '',
                'align' => isset($val2[2]) ? $val2[2] : ''
            ]
        ];
    }

    public function generatemreport($str, $printerLen)
    {
        $mview = [];
        $mprint = [];
        foreach ($str as $s) {
            $str1 = $s['val1']['val'];
            $str1size = $s['val1']['size'];
            $str1align = $s['val1']['align'];
            $str1type = $s['val1']['type'];
            $str2 = $s['val2']['val'];
            $str2align = $s['val2']['align'];
            switch ($str1type) {
                case 'qrcode':
                    $qrcode = base64_encode(QrCode::format('png')->size(200)->generate($str1));
                    $qrcode = 'data:image/png;base64, ' . $qrcode;
                    array_push($mprint, ['str' => $qrcode, 'ftype' => '', 'type' => 'qrcode']);
                    array_push($mview, ['str' => $qrcode, 'ftype' => '', 'type' => 'qrcode']);
                    break;
                case 'barcode':
                    $barcode = DNS1D::getBarcodePNG('1234567890', 'C39+', 1, 50, [0, 0, 0], true);
                    $barcode = 'data:image/png;base64,' . $barcode;
                    array_push($mprint, ['str' => $barcode, 'ftype' => '', 'type' => 'barcode']);
                    array_push($mview, ['str' => $barcode, 'ftype' => '', 'type' => 'barcode']);
                    break;
                default:
                    if (strlen($str1 . '' . $str2) >= $printerLen) {
                        array_push($mprint, ['str' => $str1 . '' . $str2, 'ftype' => $str1size, 'type' => 'text']);
                        array_push($mview, ['str' => $str1 . '' . $str2, 'ftype' => $str1size, 'type' => 'text']);
                    } else {
                        if ($str2 == '') {
                            switch (strtolower($str1align)) {
                                case 'c':
                                case 'r':
                                    $len = floor(($printerLen - strlen($str1)));
                                    if (strtolower($str1align) == 'c') {
                                        $len = floor(($printerLen - strlen($str1)) / 2);
                                    }
                                    $strs1 = str_repeat("&nbsp;", $len) . '' . $str1;
                                    $strs11 = str_pad($str1, ($len + strlen($str1)), " ", STR_PAD_LEFT);
                                    array_push($mview, ['str' => $strs1, 'ftype' => $str1size, 'type' => 'text']);
                                    array_push($mprint, ['str' => $strs11, 'ftype' => $str1size, 'type' => 'text']);
                                    break;
                                default:
                                    array_push($mview, ['str' => $str1, 'ftype' => $str1size, 'type' => 'text']);
                                    array_push($mprint, ['str' => $str1, 'ftype' => $str1size, 'type' => 'text']);
                                    break;
                            }
                        } else {
                            switch (strtolower($str2align)) {
                                case 'c':
                                case 'r':
                                    $len = floor(($printerLen - (strlen($str1) + strlen($str2))));
                                    if (strtolower($str2align) == 'c') {
                                        $len = floor(($printerLen - (strlen($str1) + strlen($str2))) / 2);
                                    }
                                    $strs1 = $str1 . '' . str_repeat("&nbsp;", $len) . '' . $str2;
                                    $strs11 = $str1 . '' . str_pad($str2, ($len + strlen($str2)), " ", STR_PAD_LEFT);
                                    array_push($mview, ['str' => $strs1, 'ftype' => $str1size, 'type' => 'text']);
                                    array_push($mprint, ['str' => $strs11, 'ftype' => $str1size, 'type' => 'text']);
                                    break;
                                default:
                                    array_push($mview, ['str' => $str1 . '' . $str2, 'ftype' => $str1size, 'type' => 'text']);
                                    array_push($mprint, ['str' => $str1 . '' . $str2, 'ftype' => $str1size]);
                                    break;
                            }
                        }
                    }
                    break;
            }
        }
        return ['view' => $mview, 'print' => $mprint];
    }
}//end class

// Image method signature:
// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)
