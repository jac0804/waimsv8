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

class mode_of_application
{
    public $modulename = 'Mode of Application';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
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
        $fields = ['radioprint','dcentername', 'area', 'dbranchname','start', 'end', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.lookupclass', 'lookupcenter');
        data_set($col1, 'dcentername.label', 'Company Name');
        data_set($col1, 'dbranchname.addedparams', ['area']);
        data_set($col1, 'dbranchname.lookupclass', 'lookupbrancharea');
        data_set($col1, 'dbranchname.action', 'lookuphbranch');
        data_set($col1, 'dbranchname.label', 'Branch');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'radioreporttype.options', array(
            ['label' => 'Detailed', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Summarized', 'value' => '1', 'color' => 'orange']
        ));
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    return $this->coreFunctions->opentable("select 
    'default' as print,
    date_format(now(), '%Y-%m-01') as start,
    left(now(),10) as end,
    0 as branchid, 
    '' as branchname,
    '' as branchcode,
    '' as dbranchname,
    '' as area,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '0' as reporttype
    ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }
    public function reportplotting($config)
    {
        $result = $this->data_query($config);
        return $this->reportDefaultLayout($config, $result);
    }
    public function data_query($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $area = $config['params']['dataparams']['area'];
        $branchname = $config['params']['dataparams']['branchname'];
        $center = $config['params']['dataparams']['center'];
        $filter = "";

        if ($center != "") {
            $filter .= " and app.center = '$center'";
        }

        if ($area != "") {
            $filter .= " and client.area = '$area'";
        }

        if ($branchname != "") {
            $filter .= " and client.clientname = '$branchname'";
        }

        switch ($reporttype) {
        case 0://Detailed
            $query = "
            select app.empcode as code, concat(emplast, ', ',empfirst, ' ',empmiddle) as empname, jobtitle, date(appdate) as appdate, mapp, jstatus, remarks
            from app
            left join client on client.clientid = app.branchid
            where app.appdate between '$start' and '$end'
            $filter
            order by mapp, appdate desc, empname;
            ";
            break;
        case 1://Summarized
            $query = "
            select mapp,
            count(*) as total,
            count(case when jstatus = 'JOB OFFER' then 1 end) as hired,
            count(case when jstatus = 'FAILED' then 1 end) as failed,
            count(case when jstatus = 'BACK OUT' then 1 end) as backout,
            count(case when jstatus = 'KIP' then 1 end) as kip
            from app
            left join client on client.clientid = app.branchid
            where app.appdate between '$start' and '$end'
            $filter
            group by mapp
            order by mapp;
            ";
            break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function reportDefaultLayout($config, $result)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        $layoutsize = '1000';
        $font = 'Century Gothic';
        $fontsize = "9";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        
        $str = '';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:95px');
        $str .= $this->reportHeader($config);

        foreach ($result as  $data) {
            switch ($reporttype) {
                case 0://Detailed
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->code, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($data->empname, '150', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '0px 0px 0px 5px');
                    $str .= $this->reporter->col($data->jobtitle, '150', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '0px 0px 0px 5px');
                    $str .= $this->reporter->col($data->appdate, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->mapp, '150', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '0px 0px 0px 5px');
                    $str .= $this->reporter->col($data->jstatus, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->remarks, '150', null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '0px 0px 0px 5px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    break;
                case 1://Summarized
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->mapp, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->total, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->hired, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->failed, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->backout, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->kip, '150', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    break;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportHeader($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $area = $config['params']['dataparams']['area'];
        $branchname = $config['params']['dataparams']['branchname'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str = '';
        $layoutsize = '1000';
        $border = '1px solid';
        $font = 'Century Gothic';
        $fontsize = '10';
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $report = '';

        $str .= '<br/><br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, null, null, 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, null, null, 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($reporttype == 0){
            $report = 'DETAILED';
        }else if ($reporttype == 1){
            $report = 'SUMMARIZED';
        }

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MODE OF APPLICATION - '. $report, null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        !empty($area) ? $area : $area = 'All';
        !empty($branchname) ? $branchname : $branchname = 'All';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Area: ' . $area, null, null, false, $border, '', '', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Branch: ' . $branchname, null, null, false, $border, '', '', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        switch ($reporttype) {
            case 0: //Detailed
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('APPLICANT NAME', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('POSITION APPLIED', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('DATE APPLIED', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('MODE OF APPLICATION', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('STATUS OF APPLICATION', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('REMARKS', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case 1://Summarized
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('MODE OF APPLICATION', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('TOTAL NUMBER OF APPLICANTS', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('NO. OF HIRED', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('NO. OF FAILED', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('NO. OF BACKOUT', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('NO. OF KEEP IN PROFILE(KIP)', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
        }
        return $str;
    }

}