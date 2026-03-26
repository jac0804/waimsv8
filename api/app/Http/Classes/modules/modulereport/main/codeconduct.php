<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class codeconduct
{

  private $modulename = "Conduct of Conduct";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.artid as trno, head.code, head.description,
    d1a, d1b, d2a, d2b, d3a, d3b, d4a, d4b, d5a, d5b, 
    section, detail.description as section_desc
    from codehead as head
    left join codedetail as detail on head.artid = detail.artid
    where head.artid = '$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_codeconduct_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_codeconduct_PDF($params, $data);
    }
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CODE OF CONDUCT', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ARTICLE : ', '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['description']) ? $data[0]['description'] : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SECTION', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('FIRST', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF DAYS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SECOND', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF DAYS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('THIRD', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF DAYS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('FOURTH', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF DAYS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('FIFTH', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF DAYS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function default_codeconduct_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['section'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['section_desc'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d1a'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d1b'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d2a'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d2b'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d3a'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d3b'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d4a'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d4b'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d5a'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['d5b'], '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_codeconduct_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Article: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(620, 0, (isset($data[0]['description']) ? $data[0]['description'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(50, 0, "SECTION", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(65, 0, "FIRST", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "# OF\nDAYS", '', 'C', false, 0);
    PDF::MultiCell(65, 0, "SECOND", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "# OF\nDAYS", '', 'C', false, 0);
    PDF::MultiCell(65, 0, "THIRD", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "# OF\nDAYS", '', 'C', false, 0);
    PDF::MultiCell(65, 0, "FOURTH", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "# OF\nDAYS", '', 'C', false, 0);
    PDF::MultiCell(65, 0, "FIFTH", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "# OF\nDAYS", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_codeconduct_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_codeconduct_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      // $section_height = PDF::GetStringHeight(50, $data[$i]['section']);
      // $section_desc_height = PDF::GetStringHeight(75, $data[$i]['section_desc']);
      // $max_height = max($section_height, $section_desc_height);

      // if($max_height > 25) {
      //   $max_height = $max_height + 15;
      // }
      $maxrow = 1;
      $section = $data[$i]['section'];
      $section_desc = $data[$i]['section_desc'];

      $arr_section = $this->reporter->fixcolumn([$section], '16', 0);
      $arr_sectiondesc = $this->reporter->fixcolumn([$section_desc], '16', 0);
      $arr_d1a = $this->reporter->fixcolumn([$data[$i]['d1a']], '13', 0);
      $arr_d2a = $this->reporter->fixcolumn([$data[$i]['d2a']], '13', 0);
      $arr_d3a = $this->reporter->fixcolumn([$data[$i]['d3a']], '13', 0);
      $arr_d4a = $this->reporter->fixcolumn([$data[$i]['d4a']], '13', 0);
      $arr_d5a = $this->reporter->fixcolumn([$data[$i]['d5a']], '13', 0);
      $arr_d1b = $this->reporter->fixcolumn([$data[$i]['d1b']], '13', 0);
      $arr_d2b = $this->reporter->fixcolumn([$data[$i]['d2b']], '13', 0);
      $arr_d3b = $this->reporter->fixcolumn([$data[$i]['d3b']], '13', 0);
      $arr_d4b = $this->reporter->fixcolumn([$data[$i]['d4b']], '13', 0);
      $arr_d5b = $this->reporter->fixcolumn([$data[$i]['d5b']], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_section, $arr_sectiondesc, $arr_d1a, $arr_d2a, $arr_d3a, $arr_d4a, $arr_d5a, $arr_d1b, $arr_d2b, $arr_d3b, $arr_d4b, $arr_d5b]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, (isset($arr_section[$r]) ? $arr_section[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_sectiondesc[$r]) ? $arr_sectiondesc[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(65, 0, (isset($arr_d1a[$r]) ? $arr_d1a[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(50, 0, (isset($arr_d1b[$r]) ? $arr_d1b[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(65, 0, (isset($arr_d2a[$r]) ? $arr_d2a[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(50, 0, (isset($arr_d2b[$r]) ? $arr_d2b[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(65, 0, (isset($arr_d3a[$r]) ? $arr_d3a[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(50, 0, (isset($arr_d3b[$r]) ? $arr_d3b[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(65, 0, (isset($arr_d4a[$r]) ? $arr_d4a[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(50, 0, (isset($arr_d4b[$r]) ? $arr_d4b[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(65, 0, (isset($arr_d5a[$r]) ? $arr_d5a[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(50, 0, (isset($arr_d5b[$r]) ? $arr_d5b[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      }
      if (intVal($i) + 1 == $page) {
        $this->default_codeconduct_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(700, 0, "", "T");
    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
