<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class encashment_report
{
  public $modulename = 'Encashment Report';
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
    $fields = ['radioprint', 'checked', 'prepared', 'approved', 'noted'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'checked.label', 'Check #');
    data_set($col1, 'checked.required', true);



    $fields = [''];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $loggeduser = $config['params']['user'];
    $adminid = $config['params']['adminid'];
    $user = $this->coreFunctions->opentable("
    select cl.clientname as prepared, emp.clientname as approved
    from client as cl
    left join client as emp on emp.clientid = cl.empid
    where cl.clientid = $adminid");

    $prepared = !empty($user) ? $user[0]->prepared : '';
    $approved = !empty($user) ? $user[0]->approved : '';
    if (empty($prepared)) {
      $prepared = $loggeduser;
    }


    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    date_format(date(now()),'%m/%d/%Y') as today,
    '' as checked,
    '$prepared' as prepared,'$approved' as approved,'MAA' as noted
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

    $result = $this->reportDefaultLayout_ENCASHMENT($config);


    return $result;
  }

  public function reportDefaultLayout_ENCASHMENT($config)
  {
    $result = $this->reportDefault($config);
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];
    $noted     = $config['params']['dataparams']['noted'];

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT_ENCASHMENT($config, $layoutsize);

    $i = 0;

    $totalamt = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $i++;

        $totalamt += $data->amt;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($i, '20', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '380', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');

        $notes = "";
        if ($data->rem != "") {
          $notes .= $data->rem . '<br>';
        }

        $datax = $this->getparticulars($data->trno);
        if (!empty($datax)) {
          foreach ($datax as $key => $value) {
            if ($value->rem != '') {
              $notes .= $value->rem . ' ' . $value->amount . "<br>";
            }
          }
        }

        $str .= $this->reporter->col($notes, '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT_ENCASHMENT($config, $layoutsize);
          $page = $page + $count;
        } //end if

      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By:', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved By:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Noted By:', '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($noted, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT_ENCASHMENT($config, $layoutsize)
  {
    $checkno     = $config['params']['dataparams']['checked'];
    $center     = $config['params']['center'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);


    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Encashment ', null, null, false, $border, '', '', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($checkno == '') {
      $checkno = 'ALL';
    }
    $str .= $this->reporter->col($checkno, '150', null, false, $border, '', '', $font, '13', '', '', '');
    $str .= $this->reporter->col('', '850', null, false, $border, '', '', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV#', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Posting Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier', '380', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User Remark', '270', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefault($config)
  {
    // QUERY

    $checkno = $config['params']['dataparams']['checked'];
    $filter = '';
    if ($checkno != '') {
      $filter .= "and detail.checkno = '" . $checkno . "'";
    }
    // $query = "select head.isencashment,head.docno,date_format(head.dateid,'%m/%d/%Y') as dateid,supp.clientname,detail.cr as amt,detail.checkno,head.rem,head.trno
    // from lahead as head
    // left join ladetail as detail on detail.trno=head.trno
    // left join client as supp on supp.client=head.client
    // where head.doc='CV' and head.isencashment = 1 and detail.checkno<>'' $filter
    // union all
    // select head.isencashment,head.docno,date_format(head.dateid,'%m/%d/%Y') as dateid,supp.clientname,detail.cr as amt,detail.checkno,head.rem,head.trno
    // from glhead as head
    // left join gldetail as detail on detail.trno=head.trno
    // left join client as supp on supp.clientid=head.clientid
    // where head.doc='CV' and head.isencashment = 1 and detail.checkno<>'' $filter";
    $query = "select head.isencashment,head.docno,date_format(head.dateid,'%m/%d/%Y') as dateid,supp.clientname,detail.cr as amt,detail.checkno,head.rem,head.trno
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join client as supp on supp.clientid=head.clientid
    where head.doc='CV' and head.isencashment = 1 and detail.checkno<>'' $filter";


    return $this->coreFunctions->opentable($query);
  }

  private function getparticulars($trno)
  {

    $qry = "select line, rem, amount from hparticulars where trno = ?
          order by line
  ";
    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }
}//end class