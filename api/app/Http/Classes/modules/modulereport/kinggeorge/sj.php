<?php

namespace App\Http\Classes\modules\modulereport\kinggeorge;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sj
{
  private $modulename = "Sales Journal";
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

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radiostatus', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    data_set($col1, 'radiostatus.label', 'Report Type');
    data_set($col1, 'radiostatus.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'blue'],
      ['label' => 'DR (12 rows)', 'value' => '4', 'color' => 'blue'],
      ['label' => 'DR Continuous', 'value' => '6', 'color' => 'blue']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $status = 6;
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '" . $status . "' as status,
      '0' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    where head.doc='sj' and head.trno='$trno'
    UNION ALL
    select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['status'];
    $print = $params['params']['dataparams']['print'];

    if ($params['params']['dataparams']['print'] == "default") {
      if ($reporttype == 6) {
        return $this->dr_New($params, $data);
      }
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      switch ($reporttype) {
        case 1: // SI
          // code ...
          break;

        case 2: // DR
        case 3:
          return $this->dr_PDF($params, $data);
          break;

        case 4:
          return $this->dr_PDF_test($params, $data);
          break;

        case 5:
          return $this->dr_PDF_New($params, $data);
          break;

        case 6:
          return $this->dr_PDF_jad($params, $data);
          break;

        default: // default 0
          return $this->default_sj_PDF($params, $data);
          break;
      }
    }
  }



  /////////////////

  private function dr_header_New($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fsize9 = 9;
    $fsize10 = 10;
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('KINGGEORGE', '800', null, false, $border, '', 'C', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRECISION MARKETING INC.', '800', null, false, $border, '', 'C', $font, $fsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '206', null, false, $border, '', 'C', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col('DELIVERY RECEIPT', '388', null, false, $border, '', 'C', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col('No. ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '206', null, false, $border, '', 'L', $font, $fsize9, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Delivered to : ', '100', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), '270', null, false, $border, 'B', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col('Date : ', '70', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '90', null, false, $border, 'B', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '260', null, false, $border, '', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address : ', '100', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), '270', null, false, $border, '', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col('PO.No. : ', '70', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '90', null, false, $border, '', 'L', $font, $fsize10, '', '', '');

    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col('Terms : ', '50', null, false, $border, '', 'L', $font, $fsize10, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '90', null, false, $border, '', 'L', $font, $fsize10, '', '', '');

    $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('QTY', '82', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->col('UNIT', '82', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->col('DESCRIPTION', '375', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->col('UNIT PRICE', '92', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->col('DISC', '57', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->col('TOTAL AMOUNT', '112', null, false, $border, 'TLRB', 'C', $font, $fsize9, 'B', '', '4px');
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function dr_New($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 1;
    $page = 28;
    $font =  "Century Gothic";
    $fsize5 = 5;
    $fsize10 = 10;
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->dr_header_New($params, $data);


    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from lastock where trno = $trno
              union select sum(ext) as ext from glstock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $totalext = 0;
    $rcount = 1;
    $totalperpage = 0;
    $ctr = count($data);
    $total = 0;
    $ctrp = 14;
    $counttest = 1;
    $gg = 0;

    $newpageadd = 1;




    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 0);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['amt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '5', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '82', null, false, $border, 'TLR', 'C', $font, $fsize5, '', '', '');
      $str .= $this->reporter->col('', '82', null, false, $border, 'TLR', 'C', $font, $fsize5, '', '', '');
      $str .= $this->reporter->col('', '375', null, false, $border, 'TLR', 'L', $font, $fsize5, '', '', '');
      $str .= $this->reporter->col('', '92', null, false, $border, 'TLR', 'R', $font, $fsize5, '', '', '');
      $str .= $this->reporter->col('', '57', null, false, $border, 'TLR', 'R', $font, $fsize5, '', '', '');
      $str .= $this->reporter->col('', '112', null, false, $border, 'TLR', 'R', $font, $fsize5, '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->endtable();

      $totalperpage += $data[$i]['ext'];


      for ($r = 0; $r < $maxrow; $r++) {
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '82', null, false, $border, 'LR', 'C', $font, $fsize10, '', '', ' ');
        $str .= $this->reporter->col($data[$i]['uom'], '82', null, false, $border, 'LR', 'C', $font, $fsize10, '', '', '');
        $str .= $this->reporter->col($data[$i]['itemname'], '375', null, false, $border, 'LR', 'L', $font, $fsize10, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '92', null, false, $border, 'LR', 'R', $font, $fsize10, '', '', '');
        $str .= $this->reporter->col($data[$i]['disc'], '57', null, false, $border, 'LR', 'R', $font, $fsize10, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '112', null, false, $border, 'LR', 'R', $font, $fsize10, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $rcount++;
        // var_dump($page);
        if ($page > 40) {
          if ($newpageadd == 1) {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '375', null, false, $border, 'TLB', 'L', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('Sub Total', '92', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '57', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col(' ' . number_format($totalperpage, 2) . ' ', '112', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $totalperpage = 0;
            $gg = 1;

            $str .= '</br>';

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Page ' . $newpageadd, '800', null, false, $border, '', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= '</br></br></br>';

            if ($ctr >= $ctrp) {
              $newpageadd++;
              $str .= $this->dr_header_New($params, $data);
            }
            $ctrp += 14;
          } elseif ($page  < 80 && $rcount == $ctrp) {

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '375', null, false, $border, 'TLB', 'L', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('Sub Total', '92', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col('', '57', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->col(' ' . number_format($totalperpage, 2) . ' ', '112', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= '</br>';

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Page ' . $newpageadd, '800', null, false, $border, '', 'R', $font, $fsize10, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalperpage = 0;

            if ($ctr > ($ctrp - $gg) && $page > 40) {
              $str .= $this->reporter->page_break();
              $str .= '</br>';
            } elseif ($ctr == ($rcount - $gg)) {
              $str .= $this->reporter->page_break();
              $str .= '</br></br>';
            } else {
              $str .= '</br></br></br>';
            }

            $newpageadd++;
            $str .= $this->dr_header_New($params, $data);
            $ctrp += 14;
          }
        } else {
          if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
            if ($newpageadd == 1) {
              if ($page <= 40) {
                $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
                $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
                $str .= $this->reporter->col('', '375', null, false, $border, 'TLB', 'L', $font, $fsize10, '', '', '');
                $str .= $this->reporter->col('Sub Total', '92', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
                $str .= $this->reporter->col('', '57', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
                $str .= $this->reporter->col(' ' . number_format($totalperpage, 2) . ' ', '112', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
              }
            }
          }
        }

        $page = $page + $count;
      }






      // $totalext = $totalext + $data[$i]['ext'];



    }


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function dr_footer_New($params, $data, $grandtotal)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fsize9 = 9;
    $fsize10 = 10;
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '82', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '375', null, false, $border, 'TLB', 'L', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('Total', '92', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col('', '57', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col(' ' . number_format($grandtotal, 2) . ' ', '112', null, false, $border, 'TLRB', 'R', $font, $fsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '</br>';


    return $str;
  }

  private function dr_signatory_New($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fsize9 = 9;
    $fsize10 = 10;
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE: ', '80', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '660', null, false, $border, 'TLRB', 'C', $font, $fsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '</br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Checked By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Delive By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }


  //////////////////


  public function default_sj_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      } //end if
    } //end for

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R P T I O N', '500px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_sj_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function dr_header_PDF_test($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
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
    PDF::AddPage('L', [850, 570]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(500, 15, 'KING GEORGE', '', 'C');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(210, 15, '', '', 'C', false, 0);
    PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
    // PDF::SetTextColor(240,0,0);
    PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');
    // PDF::SetTextColor(0,0,0);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 15, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 15, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 18, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(10, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 18, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 18, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(50, 18, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(40, 18, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(290, 18, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(60, 18, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(35, 18, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 18, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function dr_PDF_test($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 12;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->dr_header_PDF_test($params, $data);

    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], $decimalcurr);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '5', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        if (($rcount + $maxrow) > $page) {
          $this->dr_footer_pdf($rcount, $page, $params, $data, $font, $fontbold, $fontsize);
          $this->dr_header_PDF_test($params, $data);
          $page += $count;
        }
        $border = 'LRB';
        for ($r = 0; $r < $maxrow; $r++) {
          $rcount++;
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 2, '', 'LR', 'C', false, 0, '', '', true, 0, false, true, 2, 'M', true);
          PDF::MultiCell(40, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
          PDF::MultiCell(290, 2, '', 'LR', 'L', false, 0, '', '', true, 0, false, true, 2, 'M', true);
          PDF::MultiCell(60, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
          PDF::MultiCell(35, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
          PDF::MultiCell(70, 2, '', 'LR', 'R', false, 1, '', '', true, 0, false, true, 2, 'M', true);
          if ($maxrow > 1) {
            if ($r == 0) {
              $border = 'LR';
            } else {
              $border = 'LRB';
            }
          }
          PDF::MultiCell(50, 12, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), $border, 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(40, 12, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), $border, 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(290, 12, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), $border, 'L', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(60, 12, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), $border, 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(35, 12, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), $border, 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(70, 12, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), $border, 'R', false, 1, '',  '', true, 0, false, 0, 'M', true);
        }

        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 800) {
          $this->dr_header_PDF_test($params, $data);
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 15, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(40, 15, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(290, 15, ' ', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(60, 15, 'Total Amt. ', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(35, 15, ' ', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 15, ' ' . number_format($totalext, $decimalcurr) . ' ', 'LRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0, '20', 310);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(150, 10, 'Checked By: ', '', 'L', false, 0, '20', 350);
    PDF::MultiCell(150, 10, 'Delivered By: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(220, 10, 'Received the above merchandise in good order and condition: ', '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 10, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    PDF::MultiCell(130, 10, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    PDF::MultiCell(220, 10, $params['params']['dataparams']['received'], 'B', 'L');
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function dr_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::SetMargins(20, 40);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 25, 'KING GEORGE', '', 'C');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 20, 'PRECISION MARKETING INC.', '', 'C');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(160, 25, '', '', 'C', false, 0);
    PDF::MultiCell(400, 25, 'DELIVERY RECEIPT', '', 'C', false, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(30, 25, 'No.', '', 'R', false, 0);
    PDF::MultiCell(130, 25, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 20, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(70, 20, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 20, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(375, 20, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 20, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 20, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(85, 20, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function dr_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 10;
    if ($params['params']['dataparams']['status'] == 3) {
      $count = $page = 12;
    }
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->dr_header_PDF($params, $data);

    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], $decimalcurr);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '5', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        if (($rcount + $maxrow) > $page) {
          $this->dr_footer_pdf($rcount, $page, $params, $data, $font, $fontbold, $fontsize);
          $this->dr_header_PDF($params, $data);
          $page += $count;
        }
        $border = 'LRB';
        for ($r = 0; $r < $maxrow; $r++) {
          $rcount++;
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 2, '', 'LR', 'C', false, 0, '', '', true, 0, false, true, 2, 'M', false);
          PDF::MultiCell(70, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', false);
          PDF::MultiCell(375, 2, '', 'LR', 'L', false, 0, '', '', true, 0, false, true, 2, 'M', false);
          PDF::MultiCell(70, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', false);
          PDF::MultiCell(50, 2, '', 'LR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', false);
          PDF::MultiCell(85, 2, '', 'LR', 'R', false, 1, '', '', true, 0, false, true, 2, 'M', false);
          if ($maxrow > 1) {
            if ($r == 0) {
              $border = 'LR';
            } else {
              $border = 'LRB';
            }
          }
          PDF::MultiCell(70, 18, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), $border, 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(70, 18, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), $border, 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(375, 18, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), $border, 'L', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(70, 18, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), $border, 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(50, 18, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), $border, 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(85, 18, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), $border, 'R', false, 1, '',  '', true, 0, false, 0, 'M', true);
        }

        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 800) {
          $this->dr_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 30, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 30, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(375, 30, ' ', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 30, 'Total Amt. ', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 30, ' ', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(85, 30, number_format($totalext, $decimalcurr), 'LRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    $this->dr_footer_pdf($rcount, $page, $params, $data, $font, $fontbold, $fontsize, 'last');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function dr_footer_pdf($rcount, $maxpage, $params, $data, $font, $fontbold, $fontsize, $type = '')
  {
    if ($type == '') {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(720, 30, ' ', '', 'C', false);
    }
    if ($maxpage == 12) {
      $maxpage = 10;
    }
    for ($a = $rcount; $a < $maxpage; $a++) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(720, 18, '', '', 'L', false, 1);
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(200, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Delivered By: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(320, 0, 'Received the above merchandise in good order and condition: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(320, 0, $params['params']['dataparams']['received'], 'B', 'L');
  }

  private function addrow($border)
  {
    PDF::MultiCell(100, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(100, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(250, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'R', false, 1, '', '', false, 0);
  }

  public function notallowtoprint($config, $msg)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 20;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(0, 0, $msg, '', 'L', false, 1, 280, 460);


    PDF::Image('/images/reports/warningsign.jpg', '275', '250', 300, 200);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  // jad
  public function dr_PDF_jad($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = 0;
    $page = 17;
    $totalext = 0;

    $font = $fontbold = '';
    $border = '1px solid';
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->dr_header_PDF_jad($params, $data);

    $trno = $data[0]['trno'];
    $totresult = json_decode(json_encode($this->coreFunctions->opentable("select sum(ext) as ext from (select sum(ext) as ext from lastock where trno=" . $trno . " union all select sum(ext) as ext from glstock where trno=" . $trno . ") as a")), true);
    $grandtotal = $totresult[0]['ext'];
    $totalperpage = 0;
    $newpageadd = 1;
    $rowcount = 0;
    if (!empty($data)) {
      foreach ($data as $key => $d) {
        $maxrow = 1;
        $arr_itemname = $this->reporter->fixcolumn([$d['itemname']], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($d['qty'], 0)], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$d['uom']], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([number_format($d['amt'], $decimalcurr)], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$d['disc']], '5', 0);
        $arr_ext = $this->reporter->fixcolumn([number_format($d['ext'], $decimalcurr)], '15', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        $totalperpage += $d['ext'];
        for ($r = 0; $r < $maxrow; $r++) {
          $rowcount += ($r + 1);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 12, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(40, 12, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(290, 12, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LR', 'L', false, 0, '', '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(60, 12, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'LR', 'R', false, 0, '', '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(35, 12, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), 'LR', 'R', false, 0, '', '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(70, 12, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LR', 'R', false, 1, '', '', true, 0, false, 0, 'M', true);
          if (($key + 1) == count($data)) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(40, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(290, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(60, 5, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(35, 5, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(70, 5, '', 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            // PDF::MultiCell(50, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(40, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(290, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(35, 15, '', 'TLRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
          } else if ($rowcount == $page) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(40, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(290, 5, '', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(60, 5, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(35, 5, '', 'LRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            PDF::MultiCell(70, 5, '', 'LRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(50, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(40, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(290, 15, '', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(35, 15, '', 'TLRB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            $totalperpage = 0;
            PDF::MultiCell(0, 0, "\n");
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(550, 0, 'Page ' . $newpageadd . ' ', '', 'R', false);
            $newpageadd++;
            PDF::endPage();
            $this->dr_header_PDF_jad($params, $data);
            $rowcount = 0;
          }
        }
      }
      if ($rowcount > 14) {
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(550, 0, 'Page ' . $newpageadd . ' ', '', 'R', false);
        PDF::endPage();
        $newpageadd++;
        $this->dr_header_PDF_jad($params, $data);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(40, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(290, 15, ' ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(60, 15, 'Total', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(35, 15, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(70, 15, ' ' . number_format($grandtotal, 2) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(550, 0, 'Page ' . $newpageadd . ' ', '', 'R', false);
      $this->sj_signatory_PDF($params, $data);
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function dr_header_PDF_jad($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel from center where code='" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = $fontbold = '';
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    pdf::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('L', [850, 550]); //850,550
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(500, 15, 'KING GEORGE', '', 'C');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(210, 15, '', '', 'C', false, 0);
    PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
    PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 15, 'Delivered to : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 15, 'Date : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 18, 'Address : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(10, 18, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 18, 'PO.No. : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 18, 'Terms : ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 18, 'QTY', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(40, 18, 'UNIT', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(290, 18, 'DESCRIPTION', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(60, 18, 'UNIT PRICE', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(35, 18, 'DISC', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 18, 'TOTAL AMOUNT', 'TLRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
  }

  //new start
  public function dr_header_PDF_New($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
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
    PDF::AddPage('P', [600, 850]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(500, 15, 'KING GEORGE', '', 'C');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(210, 15, '', '', 'C', false, 0);
    PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
    // PDF::SetTextColor(240,0,0);
    PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');
    // PDF::SetTextColor(0,0,0);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 15, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 15, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 18, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(10, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 18, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 18, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(50, 18, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(40, 18, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(290, 18, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(60, 18, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(35, 18, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 18, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function dr_PDF_New($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 12;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $newpageadd = 1;

    $this->dr_header_PDF_New($params, $data);

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from lastock where trno = $trno
                union select sum(ext) as ext from glstock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $arrTotal = [];

    $total = 0;
    $ctrp = 14;
    $counttest = 1;
    $gg = 0;

    $countarr = 0;
    $rcount = 1;
    if (!empty($data)) {
      $totalperpage = 0;
      $ctr = count($data);
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], $decimalcurr);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '5', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(50, 2, '', 'TLR', 'C', false, 0, '', '', true, 0, false, true, 2, 'M', true);
        PDF::MultiCell(40, 2, '', 'TLR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
        PDF::MultiCell(290, 2, '', 'TLR', 'L', false, 0, '', '', true, 0, false, true, 2, 'M', true);
        PDF::MultiCell(60, 2, '', 'TLR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
        PDF::MultiCell(35, 2, '', 'TLR', 'R', false, 0, '', '', true, 0, false, true, 2, 'M', true);
        PDF::MultiCell(70, 2, '', 'TLR', 'R', false, 1, '', '', true, 0, false, true, 2, 'M', true);

        $totalperpage += $data[$i]['ext'];

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 12, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(40, 12, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(290, 12, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LR', 'L', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(60, 12, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(35, 12, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, 0, 'M', true);
          PDF::MultiCell(70, 12, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, 0, 'M', true);

          $rcount++;
          if (PDF::getY() > 325) { //385
            if ($newpageadd == 1) {
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(50, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(40, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(290, 15, '', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(35, 15, '', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
              $totalperpage = 0;
              $gg = 1;

              PDF::MultiCell(0, 0, "\n");
              PDF::SetFont($font, '', 9);
              PDF::MultiCell(550, 0, "Page " . $newpageadd . "  ", '', 'R', false);

              PDF::MultiCell(0, 0, "\n\n\n");

              if ($ctr >= $ctrp) {
                $newpageadd++;
                PDF::MultiCell(0, 0, "\n");
                PDF::SetFont($fontbold, '', 10);
                PDF::MultiCell(500, 15, 'KING GEORGE', '', 'C');
                PDF::SetFont($font, '', 10);
                PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
                PDF::SetFont($fontbold, '', 10);
                PDF::MultiCell(210, 15, '', '', 'C', false, 0);
                PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
                PDF::SetFont($fontbold, '', 9);
                PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
                PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(60, 15, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(40, 15, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(60, 18, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(10, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(40, 18, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(50, 18, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

                PDF::SetFont($font, 'B', 9);
                PDF::MultiCell(50, 18, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(40, 18, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(290, 18, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(60, 18, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(35, 18, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                PDF::MultiCell(70, 18, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
              }
              $ctrp += 14;
            } elseif (PDF::getY()  < 800 && $rcount == ($ctrp)) {
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(50, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(40, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(290, 15, ' ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(35, 15, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

              PDF::MultiCell(0, 0, "\n");
              PDF::SetFont($font, '', 9);
              PDF::MultiCell(550, 0, "Page " .  $newpageadd . "  ", '', 'R', false);


              $totalperpage = 0;
              // var_dump('CTR: ' . $ctr . ' - ' . 'CTRP: ' . $ctrp . ' - ' . 'RCOUNT: ' . $rcount . ' - ' . PDF::getY());
              if ($ctr > ($ctrp - $gg) && PDF::getY() > 400) { //380
                // PDF::MultiCell(500, 15, 'KING GEORGEbb', '', 'C');

                PDF::Addpage();
                PDF::MultiCell(0, 0, "\n");
              } elseif ($ctr == ($rcount - $gg)) {
                PDF::Addpage();
                PDF::MultiCell(0, 0, "\n\n");
              } else {
                // PDF::MultiCell(500, 15, 'KING GEORGEaa', '', 'C');
                PDF::MultiCell(0, 0, "\n\n\n");
              }

              $newpageadd++;
              PDF::MultiCell(0, 0, "\n");
              PDF::SetFont($fontbold, '', 10);
              PDF::MultiCell(500, 15, 'KING GEORGE', '', 'C');
              PDF::SetFont($font, '', 10);
              PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
              PDF::SetFont($fontbold, '', 10);
              PDF::MultiCell(210, 15, '', '', 'C', false, 0);
              PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
              PDF::SetFont($fontbold, '', 9);
              PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
              PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(60, 15, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(40, 15, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(60, 18, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(10, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(40, 18, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(50, 18, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

              PDF::SetFont($font, 'B', 9);
              PDF::MultiCell(50, 18, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(40, 18, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(290, 18, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(60, 18, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(35, 18, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
              PDF::MultiCell(70, 18, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

              $ctrp += 14;
            }
          } else {

            if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
              if ($newpageadd == 1) {
                if (PDF::getY() <= 315) { //385
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(50, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                  PDF::MultiCell(40, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                  PDF::MultiCell(290, 15, ' ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                  PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                  PDF::MultiCell(35, 15, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
                  PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

                  $this->NEWfooter_layout($params, $data, $grandtotal);

                  PDF::MultiCell(0, 0, "\n");
                  PDF::SetFont($font, '', 9);
                  PDF::MultiCell(550, 0, "Page " . $newpageadd . "  ", '', 'R', false);
                  $newpageadd++;
                  $this->sj_signatory_PDF($params, $data);
                } else {

                  $this->dr_header_PDF_New($params, $data);

                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(740, 15, "", 'B', 'R', false, 1, 30);
                  goto lastpage;
                }
              }
            }
          }
        }
        $counttest++;

        $totalext += $data[$i]['ext'];
      }
    }

    if ($rcount > 14) {
      if ($ctrp > $rcount) {
        goto lastpage;
      } else {
        if (PDF::getY() <= 315) { //385
          $this->NEWfooter_layout($params, $data, $grandtotal);

          PDF::MultiCell(0, 0, "\n");
          PDF::SetFont($font, '', 9);
          PDF::MultiCell(250, 0, "Page " . $newpageadd . "  ", '', 'R', false);

          $this->sj_signatory_PDF($params, $data);
        } else {
          $newpageadd++;
          PDF::MultiCell(0, 0, "\n\n\n");
          PDF::SetFont($fontbold, '', 10);
          PDF::MultiCell(500, 15, 'KING GEORGEcc', '', 'C');
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(500, 15, 'PRECISION MARKETING INC.', '', 'C');
          PDF::SetFont($fontbold, '', 10);
          PDF::MultiCell(210, 15, '', '', 'C', false, 0);
          PDF::MultiCell(187, 15, 'DELIVERY RECEIPT', '', 'L', false, 0);
          PDF::SetFont($fontbold, '', 9);
          PDF::MultiCell(30, 15, 'No.', '', 'R', false, 0);
          PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L');

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(60, 15, "Delivered to : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(220, 15, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(40, 15, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(60, 18, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(220, 18, (isset($data[0]['address']) ? strtoupper($data[0]['address']) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(10, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(40, 18, "PO.No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 18, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(60, 18, (isset($data[0]['terms']) ? strtoupper($data[0]['terms']) : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

          PDF::SetFont($font, 'B', 9);
          PDF::MultiCell(50, 18, "QTY", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(40, 18, "UNIT", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(290, 18, "DESCRIPTION", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(60, 18, "UNIT PRICE", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(35, 18, "Disc", 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(70, 18, "Total Amount", 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(740, 15, "", 'B', 'L', false, 1, 30);
          goto lastpage;
        }
      }

      lastpage:
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(40, 15, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(290, 15, ' ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(60, 15, 'Sub Total', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(35, 15, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(70, 15, ' ' . number_format($totalperpage, 2) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


      $this->NEWfooter_layout($params, $data, $grandtotal);

      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(550, 0, "Page " . $newpageadd . "  ", '', 'R', false);

      $this->sj_signatory_PDF($params, $data);
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function NEWfooter_layout($params, $data, $grandtotal)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize10 = 10;
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 15, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(40, 15, ' ', 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(290, 15, ' ', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(60, 15, 'Total', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(35, 15, ' ', 'LRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 15, ' ' . number_format($grandtotal, 2) . ' ', 'LRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0, '20', 310);
    // PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n");

    // PDF::MultiCell(150, 10, 'Checked By: ', '', 'L', false, 0, '20', 350);
    // PDF::MultiCell(150, 10, 'Delivered By: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', 8);
    // PDF::MultiCell(220, 10, 'Received the above merchandise in good order and condition: ', '', 'L');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(130, 10, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    // PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    // PDF::MultiCell(130, 10, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    // PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    // PDF::MultiCell(220, 10, $params['params']['dataparams']['received'], 'B', 'L');
  }


  public function sj_signatory_PDF($params, $data)
  {
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0); //, '20', 310
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(150, 10, 'Checked By: ', '', 'L', false, 0); //, '20', 350
    PDF::MultiCell(150, 10, 'Delivered By: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(220, 10, 'Received the above merchandise in good order and condition: ', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 10, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    PDF::MultiCell(130, 10, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'L', false, 0);
    PDF::MultiCell(220, 10, $params['params']['dataparams']['received'], 'B', 'L');
  }


  //end
}
