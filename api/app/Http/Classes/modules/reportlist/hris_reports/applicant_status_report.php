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

class applicant_status_report
{
    public $modulename = 'Applicant Status Report';
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

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];


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
        $fields = ['radioprint','start', 'end'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end
        ");
    }

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
        $data = $this->default_query($config);
        return $this->report_default_detailed($config, $data);
    }

    public function default_query($config)
    {
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select date(req.dateid) as dateid, req.docno, jh.jobtitle, req.headcount,
            concat_ws(' ', a.empfirst, nullif(trim(a.empmiddle), ''), a.emplast) as list, t.status
            from hpersonreq as req
            left join jobthead as jh on jh.docno = req.job
            left join app as a on a.hqtrno = req.trno
            left join trxstatus as t on t.line = a.statid
            left join client as c on c.clientid = req.empid
            where date(req.dateid) between '$start' and '$end'
            union all
            select date(req.dateid) as dateid, req.docno, jh.jobtitle, req.headcount,
            concat_ws(' ', a.empfirst, nullif(trim(a.empmiddle), ''), a.emplast) as list, t.status
            from personreq as req
            left join jobthead as jh on jh.docno = req.job
            left join app as a on a.hqtrno = req.trno
            left join trxstatus as t on t.line = a.statid
            left join client as c on c.clientid = req.empid
            where date(req.dateid) between '$start' and '$end'
            and req.docno not like 'HQ%'
            order by dateid desc";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $start      = $config['params']['dataparams']['start'];
        $end        = $config['params']['dataparams']['end'];
      
        $str      = '';
        $font     = 'Tahoma';
        $fontsize = "11";
        $border   = "1px solid ";
        $layoutsize = 1000;
        $font     = $this->companysetup->getrptfont($config['params']);

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);   
        $str .= $this->reporter->col( $this->coreFunctions->opentable($qry)[0]->name, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col($this->coreFunctions->opentable($qry)[0]->address, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('APPLICANT STATUS REPORT', null, null, false, '10px solid ', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Period Cover :  ' . $start . ' to ' . $end, null, null, false, '', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '980', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT NO.',              '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TRANSACTION DATE',          '120', null, false, $border, 'TBR',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('JOB TITLE',                 '140', null, false, $border, 'TBR',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('NO. OF PERSONNEL NEEDED',   '145', null, false, $border, 'TBR',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('LIST OF APPLICANT PROCESS', '200', null, false, $border, 'TBR',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APPLICATIONS STATUS',       '245', null, false, $border, 'TBR',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function report_default_detailed($config, $data)
    {
        $str        = '';
        $layoutsize = '1000';
        $font       = 'Tahoma';
        $fontsize   = '10';
        $border     = '1px solid';

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        // Group by docno
        $grouped = [];
        foreach ($data as $row) {
            $grouped[$row->docno][] = $row;
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($grouped));

        foreach ($grouped as $docno => $rows) {

            $first     = $rows[0];
            $dateid    = date("Y-m-d", strtotime($first->dateid));
            $jobtitle  = isset($first->jobtitle)  ? $first->jobtitle  : '';
            $headcount = isset($first->headcount) ? $first->headcount : '';

            // Build numbered list of applicants
            // $applicantList = '';
            // foreach ($rows as $i => $row) {
            //     if (!empty($row->list)) {
            //         $applicantList .= ($i + 1) . '. ' . strtoupper($row->list) . '<br/>';
            //     }
            // }

            // // Build numbered list of statuses aligned with applicants
            // $statusList = '';
            // foreach ($rows as $i => $row) {
            //     if (!empty($row->list)) {
            //         $status = !empty($row->status) ? strtoupper($row->status) : '';
            //         $statusList .= ($i + 1) . '. ' . $status . '<br/>';
            //     }
            // }

            $applicantList = '';
            $statusList    = '';

            $applicants = [];
            $statuses   = [];
            
            foreach ($rows as $row) {
                if (!empty($row->list)) {
                    $applicants[] = strtoupper($row->list);
                    $statuses[]   = !empty($row->status) ? strtoupper($row->status) : '';
                }
            }

            $totalApplicants = count($applicants);

            for ($i = 0; $i < $totalApplicants; $i++) {

                $applicantList .= ($i + 1) . '. ' . $applicants[$i];
                $statusList    .= ($i + 1) . '. ' . $statuses[$i];

                // add line separator except last row
                if ($i < ($totalApplicants - 1)) {
                    $applicantList .= '<br/><hr style="margin:0;padding:0;border-top:1px solid #000;"/>';
                    $statusList    .= '<br/><hr style="margin:0;padding:0;border-top:1px solid #000;"/>';
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($docno,         '150', null, false, $border, 'BLR', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col($dateid,        '120', null, false, $border, 'BLR', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col($jobtitle,      '140', null, false, $border, 'BLR', 'LT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col($headcount,     '145', null, false, $border, 'BLR', 'CT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col($applicantList, '200', null, false, $border, 'BLR', 'LT', $font, $fontsize, '',  '', '');
            $str .= $this->reporter->col($statusList,    '245', null, false, $border, 'BLR', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

} // end of class