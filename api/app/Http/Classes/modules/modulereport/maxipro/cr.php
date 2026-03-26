<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class cr
{
  private $modulename = "Received Payment";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'prepared', 'checked', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }


  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$username' as prepared,
      '' as checked,
      '' as approved
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "select trno,dateid,docno,clientname,address,acno,acnoname,client,ref,sum(cr) as cr,
                      postdate,sum(db) as db,alias,checkno,projcode,projname
              from (select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
                           head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, 
                           coa.acnoname, coa.alias as ali,client.client, detail.ref, 
                           date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,
                           proj.code as projcode, proj.name as projname
                    from ((lahead as head 
                    left join ladetail as detail on detail.trno=head.trno) 
                    left join coa on coa.acnoid=detail.acnoid) 
                    left join client on client.client=detail.client
                    left join projectmasterfile as proj on proj.line=head.projectid
                    where head.doc='cr' and head.trno='$trno' and detail.db <> 0 
                    union all
                    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
                           head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, 
                           coa.acnoname, coa.alias as ali,client.client, detail.ref, 
                           date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,
                           proj.code as projcode, proj.name as projname
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno) 
                    left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.clientid=detail.clientid 
                    left join projectmasterfile as proj on proj.line=head.projectid
                    where head.doc='cr' and head.trno='$trno' and detail.db <> 0  ) as a
              group by trno,dateid,docno,clientname,address,acno,acnoname,client,ref,postdate,alias,checkno,
                       projcode,projname
                      
              union all
              select trno,dateid,docno,clientname,address,acno,acnoname,client,ref,sum(cr) as cr,
                      postdate,sum(db) as db,alias,checkno,projcode,projname
              from (select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
                           head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, 
                           coa.acnoname, coa.alias as ali,client.client, detail.ref, 
                           date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,
                           proj.code as projcode, proj.name as projname
                    from ((lahead as head 
                    left join ladetail as detail on detail.trno=head.trno) 
                    left join coa on coa.acnoid=detail.acnoid) 
                    left join client on client.client=detail.client
                    left join projectmasterfile as proj on proj.line=head.projectid
                    where head.doc='cr' and head.trno='$trno' and detail.cr <> 0
                    union all
                    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, 
                           head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, 
                           coa.acnoname, coa.alias as ali,client.client, detail.ref, 
                           date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,
                           proj.code as projcode, proj.name as projname
                    from ((glhead as head 
                    left join gldetail as detail on detail.trno=head.trno) 
                    left join coa on coa.acnoid=detail.acnoid)
                    left join client on client.clientid=detail.clientid 
                    left join projectmasterfile as proj on proj.line=head.projectid
                    where head.doc='cr' and head.trno='$trno' and detail.cr <> 0  order by line) as a
              group by trno,dateid,docno,clientname,address,acno,acnoname,client,ref,postdate,alias,checkno,
                       projcode,projname
              
      ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    return $this->Max_CR_PDF($params, $data);
  }


  public function Max_CR_header_PDF($params, $data)
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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);


    $this->reportheader->getheader($params);

    PDF::MultiCell(0, 0, "\n");

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '120');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DOCUMENT # : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetLineStyle(array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3));

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "CUSTOMER : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 18, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "DATE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "ADDRESS : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "REF : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "PROJECT : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(650, 0, (isset($data[0]['projname']) ? $data[0]['projname'] : ''), 'B', 'L', false);


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(300, 0, "Print Date: " . date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '', 'L', false, 0);
    PDF::MultiCell(460, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(730, 0, "", "");
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(730, 5, '', 'T');
    PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3));

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(65, 18, "ACCT #", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(190, 18, "ACCOUNT NAME", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "REFERENCE #", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 18, "DATE", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(75, 18, "DEBIT", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(75, 18, "CREDIT", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(95, 18, "CLIENT", 'B', 'C', false);
  }

  public function Max_CR_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetMargins(30, 30);
    $this->Max_CR_header_PDF($params, $data);

    $countarr = 0;
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $acno = $data[$i]['acno'];
        $acnoname = $data[$i]['acnoname'];
        $ref = $data[$i]['ref'];
        $postdate = $data[$i]['postdate'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $client = $data[$i]['client'];
        $debit = $debit < 0 ? '-' : $debit;
        $credit = $credit < 0 ? '-' : $credit;

        $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
        $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
        $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
        $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(65, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(190, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(70, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(75, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(75, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(10, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(95, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'C', 0, 1, '', '', true, 0, false, false, 'M');
        }


        PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(75, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
        if ($data[$i]['alias'] == 'CB') {
          PDF::MultiCell(50, 0, 'Check #: ', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(140, 0, $data[$i]['checkno'], '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
        } else {
          PDF::MultiCell(190, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
        }
        PDF::MultiCell(465, 0, '', '', 'C', 0, 1, '', '', true, 0, false, false, 'M');
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->Max_CR_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3));
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(730, 0, '', 'B');

    PDF::MultiCell(730, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(465, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(730, 0, "");

    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(730, 5, '', 'T');
    PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(263, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(263, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(263, 0, 'Approved By: ', '', 'L');

    PDF::MultiCell(0, 0, "");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(210, 18, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);
    PDF::MultiCell(210, 18, $params['params']['dataparams']['checked'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);
    PDF::MultiCell(210, 18, $params['params']['dataparams']['approved'], 'B', 'C');
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
