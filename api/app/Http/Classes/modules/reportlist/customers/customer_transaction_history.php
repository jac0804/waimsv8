<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class customer_transaction_history
{
  public $modulename = 'Customers Transaction History';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'start', 'end', 'radioposttype'];
    $col1 = $this->fieldClass->create($fields);
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '0' as posttype
      ");
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $posttype = $config['params']['dataparams']['posttype'];

    if ($posttype == '0') {
      $query = "select head.trno, head.docno, date(head.dateid) as dateid, client.client, client.clientname from glhead as head left join client on client.clientid=head.clientid where head.doc='SJ' and date(head.dateid) between '" . $start . "' and '" . $end . "' order by head.docno";
    } else {
      $query = "select head.trno, head.docno, date(head.dateid) as dateid, client.client, client.clientname from lahead as head left join client on client.client=head.client where head.doc='SJ' and date(head.dateid) between '" . $start . "' and '" . $end . "' order by head.docno";
    }
    return $this->coreFunctions->opentable($query);
  }

  public function getData($trno)
  {
    $query = "select head.doc, head.trno, head.docno, date(head.dateid) as dateid, client.client, client.clientname, sum(detail.db-detail.cr) as amount from glhead as head left join gldetail as detail on detail.trno=head.trno left join client on client.clientid=head.clientid where head.doc='SJ' and head.trno='" . $trno . "'
      group by head.doc, head.trno, head.docno, dateid, client.client, client.clientname
      union all
      select head.doc, head.trno, head.docno, date(head.dateid) as dateid, client.client, client.clientname, sum(detail.db-detail.cr) as amount from lahead as head left join ladetail as detail on detail.trno=head.trno left join client on client.client=head.client where head.doc<>'SJ' and detail.refx='" . $trno . "'
      group by head.doc, head.trno, head.docno, dateid, client.client, client.clientname
      union all
      select head.doc, head.trno, head.docno, date(head.dateid) as dateid, client.client, client.clientname, sum(detail.db-detail.cr) as amount from glhead as head left join gldetail as detail on detail.trno=head.trno left join client on client.clientid=head.clientid where head.doc<>'SJ' and detail.refx='" . $trno . "'
      group by head.doc, head.trno, head.docno, dateid, client.client, client.clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER TRANSACTION HISTORY', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date from: ' . $config['params']['dataparams']['start'] . ' to: ' . $config['params']['dataparams']['end'], null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER ID/CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INVOICE NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TRANSACTION', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TRANS NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 10;
    $page = 10;
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $client = '';
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($client == $data->client ? '' : $data->client . ' ' . $data->clientname, '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $client = $data->client;
      $data = $this->getData($data->trno);
      $total = 0;
      if (!empty($data)) {
        foreach ($data as $k => $d) {
          $trans = '';
          switch ($d->doc) {
            case 'CR':
              $trans = 'Receipt';
              break;
            case 'SR':
              $trans = 'Return';
              break;
            case 'SJ':
              $trans = 'Invoice';
              break;
            default:
              $trans = 'Others';
              break;
          }
          if ($k == 0) {
            $str .= $this->reporter->col($trans, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($d->docno, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($d->dateid, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($d->amount, $decimalcurr), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($trans, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($d->docno, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($d->dateid, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($d->amount, $decimalcurr), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
          }
          $total += $d->amount;
          if (($k + 1) == count($data)) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($total, $decimalcurr), '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', '300', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', '20', false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class