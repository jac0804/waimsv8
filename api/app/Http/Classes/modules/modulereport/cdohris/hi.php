<?php

namespace App\Http\Classes\modules\modulereport\cdohris;

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

class hi
{

    private $modulename;
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
        $user = $config['params']['user'];
        $qry = "select clientname from client where email='$user'";
        $this->coreFunctions->LogConsole($qry);
        $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

        if ((isset($name[0]['clientname']) ? $name[0]['clientname'] : '') != '') {
            $user = $name[0]['clientname'];
        }

        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            '$user' as prepared,
            '' as approved,
            '' as received
            "
        );
    }

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select num.trno,num.docno,head.tempid,c.client as tempcode, c.clientname as tempname,
                    head.fempjobid,jf.jobtitle as fjobtitle,head.fempid,cf.client as fempcode,
                    cf.clientname as fempname,head.tempjobid,jt.jobtitle as tjobtitle,
                    date(head.dateid) as dateid,head.idescription,head.iplace,head.idetails,
                    head.icomments,date(head.idate) as idate,time(head.idate) as itime,
                    demp.client as dempcode,demp.clientname as dempname,djt.jobtitle as djobtitle,
                    head.artid,head.sectid,chead.description as article,cdetail.section as sectioncode,cdetail.description as sectionname
                from incidenthead as head
                left join jobthead as djt on djt.line = head.tempjobid
                left join client as demp on demp.clientid = head.tempid
                left join client as c on c.clientid=head.tempid
                left join client as cf on cf.clientid=head.fempid
                left join jobthead as jf on jf.line=head.fempjobid
                left join jobthead as jt on jt.line=head.tempjobid
                left join hrisnum as num on num.trno = head.trno
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on cdetail.line =head.sectid and cdetail.artid=chead.artid
                where num.trno = '$trno' and num.doc='HI'
                union all
                select num.trno,num.docno,head.tempid,c.client as tempcode, c.clientname as tempname,
                    head.fempjobid,jf.jobtitle as fjobtitle,head.fempid,cf.client as fempcode,
                    cf.clientname as fempname,head.tempjobid,jt.jobtitle as tjobtitle,
                    date(head.dateid) as dateid,head.idescription,head.iplace,head.idetails,
                    head.icomments,date(head.idate) as idate,time(head.idate) as itime,
                    demp.client as dempcode,demp.clientname as dempname,djt.jobtitle as djobtitle,
                    head.artid,head.sectid,chead.description as article,cdetail.section as sectioncode,cdetail.description as sectionname
                from hincidenthead as head
                left join jobthead as djt on djt.line = head.tempjobid
                left join client as demp on demp.clientid = head.tempid
                left join client as c on c.clientid=head.tempid
                left join client as cf on cf.clientid=head.fempid
                left join jobthead as jf on jf.line=head.fempjobid
                left join jobthead as jt on jt.line=head.tempjobid
                left join hrisnum as num on num.trno = head.trno
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on cdetail.line =head.sectid and cdetail.artid=chead.artid
                where num.trno = '$trno' and num.doc='HI'
                union all
                select num.trno,num.docno,head.tempid,c.client as tempcode, c.clientname as tempname, 
                         head.fempjobid,jf.jobtitle as fjobtitle,head.fempid,cf.client as fempcode, 
                         cf.clientname as fempname,head.tempjobid,jt.jobtitle as tjobtitle,
                         date(head.dateid) as dateid,head.idescription,head.iplace,head.idetails,
                         head.icomments,date(head.idate) as idate,time(head.idate) as itime,
                         demp.client as dempcode,demp.clientname as dempname,djt.jobtitle as djobtitle,
                         head.artid,head.sectid,chead.description as article,cdetail.section as sectioncode,cdetail.description as sectionname
                from incidenthead as head  
                left join incidentdtail as detail on detail.trno = head.trno
                left join jobthead as djt on djt.line = detail.jobid
                left join client as demp on demp.clientid = detail.empid
                left join client as c on c.clientid=head.tempid
                left join client as cf on cf.clientid=head.fempid
                left join jobthead as jf on jf.line=head.fempjobid
                left join jobthead as jt on jt.line=head.tempjobid
                left join hrisnum as num on num.trno = head.trno   
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on cdetail.line =head.sectid and cdetail.artid=chead.artid
                where num.trno = '$trno' and num.doc='HI' 
                union all 
                select num.trno,num.docno,head.tempid,c.client as tempcode,c.clientname as tempname, 
                       head.fempjobid,jf.jobtitle as fjobtitle,head.fempid,cf.client as fempcode, 
                       cf.clientname as fempname,head.tempjobid,jt.jobtitle as tjobtitle,
                       date(head.dateid) as dateid,head.idescription,head.iplace,head.idetails,
                       head.icomments,date(head.idate) as idate,time(head.idate) as itime,
                       demp.client as dempcode,demp.clientname as dempname,djt.jobtitle as djobtitle,
                       head.artid,head.sectid,chead.description as article,cdetail.section as sectioncode,cdetail.description as sectionname
                from hincidenthead as head
                left join hincidentdtail as detail on detail.trno = head.trno
                left join jobthead as djt on djt.line = detail.jobid
                left join client as demp on demp.clientid = detail.empid
                left join client as c on c.clientid=head.tempid
                left join client as cf on cf.clientid=head.fempid
                left join jobthead as jf on jf.line=head.fempjobid
                left join jobthead as jt on jt.line=head.tempjobid
                left join hrisnum as num on num.trno = head.trno   
                left join codehead as chead on chead.artid=head.artid
                left join codedetail as cdetail on cdetail.line =head.sectid and cdetail.artid=chead.artid
                where num.trno = '$trno' and num.doc='HI'
            ";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($config, $data)
    {
        $data = $this->report_default_query($config);
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_HI_layout($config, $data);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            $str = $this->rpt_HI_PDF($config, $data);
        }
        return $str;
    }


    public function default_header_PDF($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);


        $query = "select d.divcode as division,d.divname,d.address from division as d
                      left join employee as e on e.divid=d.divid where empid = " . $data[0]['tempid'] . "";
        $empcomp = $this->coreFunctions->opentable($query);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($empcomp[0]->divname), '', 'L');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, strtoupper($empcomp[0]->address) . "\n\n\n", '', 'L');


        if (!empty($empcomp)) {
            if ($empcomp[0]->division == '001') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '10', 110, 110);
            }
            if ($empcomp[0]->division == '002') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '10', 110, 110);
            }
            if ($empcomp[0]->division == '003') {
                PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '10', 110, 110);
            }
        }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 18, "INCIDENT REPORT", '', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        PDF::MultiCell(160, 0, "", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 18, "DOCUMENT # : ", '', 'L', false, 0);
        PDF::MultiCell(250, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(100, 18, "DATE : ", '', 'R', false, 0);
        PDF::MultiCell(200, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        PDF::MultiCell(160, 0, "", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 18, "PERSON COMPLETING THIS FORM : ", '', 'L', false, 0);
        PDF::MultiCell(250, 18, (isset($data[0]['fempname']) ? $data[0]['fempname'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(100, 18, "JOB TITLE : ", '', 'R', false, 0);
        PDF::MultiCell(200, 18, (isset($data[0]['fjobtitle']) ? $data[0]['fjobtitle'] : ''), 'B', 'L', false);

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        // PDF::MultiCell(160, 0, "", '', 'L', false);

        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(100, 18, "To Employee : ", '', 'L', false, 0);
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(300, 18, (isset($data[0]['tempname']) ? $data[0]['tempname'] : ''), 'B', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(150, 18, "To Job Title : ", '', 'R', false, 0);
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(200, 18, (isset($data[0]['tjobtitle']) ? $data[0]['tjobtitle'] : ''), 'B', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", '', 'L', false, 0);
        PDF::MultiCell(160, 0, "", '', 'L', false);

        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(80, 0, "Incident ", '', 'L', false, 0);
        // PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        // PDF::MultiCell(60, 0, "", '', 'R', false, 0);
        // PDF::MultiCell(120, 0, '', '', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(55, 0, "Date of", '', 'L', false, 0);
        // PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(56, 0, "Time of", '', 'L', false, 0);
        // PDF::MultiCell(100, 0, '', '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(120, 0, "DATE OF INCIDENT : ", '', 'L', false, 0);
        PDF::MultiCell(100, 0, (isset($data[0]['idate']) ? $data[0]['idate'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(120, 0, "TIME OF INCIDENT : ", '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($data[0]['itime']) ? $data[0]['itime'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(150, 0, "PLACE OF INCIDENT : ", '', 'R', false, 0);
        PDF::MultiCell(200, 0, (isset($data[0]['iplace']) ? $data[0]['iplace'] : ''), 'B', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 13);
        PDF::MultiCell(500, 10, "PERSON INVOLVED", '', 'L', false, 1);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", '', 'L', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(153, 0, "EMPLOYEE CODE", '', 'L', false, 0);
        PDF::MultiCell(353, 0, "EMPLOYEE NAME", '', 'L', false, 0);
        PDF::MultiCell(254, 0, "JOBTITLE", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
    }

    public function rpt_HI_PDF($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $fontsize = "11";
        $count = 5;
        $page = 5;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_header_PDF($config, $data);

        for ($i = 0; $i < count($data); $i++) {
            $maxrow = 1;
            $arr_dempcode = $this->reporter->fixcolumn([$data[$i]['dempcode']], '25', 0);
            $arr_dempname = $this->reporter->fixcolumn([$data[$i]['dempname']], '60', 0);
            $arr_djobtitle = $this->reporter->fixcolumn([$data[$i]['djobtitle']], '40', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_dempcode, $arr_dempname, $arr_djobtitle]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(153, 20, (isset($arr_dempcode[$r]) ? $arr_dempcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(353, 20, (isset($arr_dempname[$r]) ? $arr_dempname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(254, 20, (isset($arr_djobtitle[$r]) ? $arr_djobtitle[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            if (intVal($i) + 1 == $page) {
                $this->default_header_PDF($config, $data);
                $page += $count;
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(210, 0, "INCIDENT CODE OF CONDUCT : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, $data[0]['article'], '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(210, 0, "SECTION : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, $data[0]['sectioncode'], '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(210, 0, "DESCRIPTION : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, $data[0]['sectionname'], '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(210, 0, "INCIDENT DETAILS / SUMMARY : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, $data[0]['idetails'], '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(210, 0, "ACTION TAKEN / RECOMMENDATION : ", '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, $data[0]['icomments'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(253, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $config['params']['dataparams']['received'], '', 'L', false, 0);
        PDF::MultiCell(254, 0, $config['params']['dataparams']['approved'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn

    public function default_header($config, $data)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $font = "Century Gothic ";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INCIDENT REPORT', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT # :', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From Employee : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['fempname']) ? $data[0]['fempname'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('Job Title : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['fjobtitle']) ? $data[0]['fjobtitle'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('To Employee : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['tempname']) ? $data[0]['tempname'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('To Job Title : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['tjobtitle']) ? $data[0]['tjobtitle'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Incident Description : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['idescription']) ? $data[0]['idescription'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('Place : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['iplace']) ? $data[0]['iplace'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('Date of Incident : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['idate']) ? $data[0]['idate'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->col('Time of Incident : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['itime']) ? $data[0]['itime'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('EMPLOYEE NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('JOBTITLE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function rpt_HI_layout($config, $data)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $font = "Century Gothic ";
        $fontsize = "11";
        $border = "1px solid ";
        $count = 5;
        $page = 5;
        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($config, $data);

        $str .= $this->reporter->begintable('800');
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['dempcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['dempname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['djobtitle'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($config, $data);
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

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INCIDENT DETAILS : ', '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['idetails'], '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INCIDENT COMMENTS : ', '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[0]['icomments'], '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
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
        $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    } //end fn
}
