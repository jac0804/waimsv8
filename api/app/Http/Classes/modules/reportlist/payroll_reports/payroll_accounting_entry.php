<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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

class payroll_accounting_entry
{
    public $modulename = 'Payroll Accounting Entry';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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
        $fields = ['radioprint', 'batchrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');
        data_set($col1, 'batchrep.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }
    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
    '' as batchid,
    '' as batcrep,
    0 as line,
    'default' as print
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
        return $this->reportDefaultLayout($config);
    }
    public function reportDefault($config)
    {
        $batchid = $config['params']['dataparams']['line'];
        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 62: //onesky
                $query = "
            select * from (
            select (case when acc.type = 'D' then sum(p.db-p.cr) else 0 end ) as db,acc.codename,
            (case when acc.type = 'C' then (case when (sum(p.cr-p.db)) > 0 then sum(p.cr-p.db) else -(sum(p.cr-p.db)) end) else 0 end ) as cr,acc.type,
            date(batch.startdate) as startdate,date(batch.enddate) as enddate from paytrancurrent as p 
            left join paccount as pacc ON pacc.line = p.acnoid
            left join aaccount as acc ON acc.line = pacc.aaid
            left join batch on batch.line=p.batchid
            where p.batchid = " . $batchid . "
            group by acc.codename,acc.type,batch.startdate,batch.enddate
            union all
            select  p.db,acc.codename,p.cr,acc.type,
            date(batch.startdate) as startdate,date(batch.enddate) as enddate from paytranhistory as p 
            left join paccount as pacc ON pacc.line = p.acnoid
            left join aaccount as acc ON acc.line = pacc.aaid
            left join batch on batch.line=p.batchid
            where p.batchid = " . $batchid . "
            group by acc.codename,acc.type,batch.startdate,batch.enddate,p.db,p.cr
            ) as v
              order by type desc";
                break;
            default:
                $query = "   
             select pa.codename, sum(p.db) as db, sum(p.cr) as cr,p.acnoid,
             date(batch.startdate) as startdate,date(batch.enddate) as enddate
             from paytrancurrent as p
             left join paccount as pa on pa.line = p.acnoid
             left join batch on batch.line=p.batchid
             where batch.line =  $batchid  and pa.alias not in ('PPBLE')
             group by pa.codename,p.acnoid,batch.startdate,batch.enddate
             union all
             select pa.codename,sum(p.db) as db, sum(p.cr) as cr,p.acnoid,
             date(batch.startdate) as startdate,date(batch.enddate) as enddate
             from paytranhistory as p
             left join paccount as pa on pa.line = p.acnoid
             left join batch on batch.line=p.batchid
             where batch.line =  $batchid  and pa.alias not in ('PPBLE')
             group by pa.codename,p.acnoid,batch.startdate,batch.enddate";

                break;
        }

        return $this->coreFunctions->opentable($query);
    }
    private function displayHeader($config, $data)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $batch  = $config['params']['dataparams']['batch'];

        $str = '';
        $layoutsize = '1000';
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Accounting Entry', null, null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From : ', '50', null, false, $border, '', '', $font, $font_size, 'B', 'L', '');
        $str .= $this->reporter->col('' . $data[0]->startdate, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('To : ', '40', null, false, $border, '', '', $font, $font_size, 'B', 'L', '');
        $str .= $this->reporter->col('' . $data[0]->enddate, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '750', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Batch : ', '60', null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('' . $batch, '140', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '800', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Account', '334', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(strtoupper('Debit'), '333', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(strtoupper('Credit'), '333', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function reportDefaultLayout($config)
    {

        $result =  $this->reportDefault($config);
        $border = '1px dotted';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = '1000';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $result);

        $totaldb = 0;
        $totalcr = 0;

        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("" . $data->codename, '334', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("" . $data->db == 0 ? '-' : number_format($data->db, 2), '333', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("" . $data->cr == 0 ? '-' : number_format($data->cr, 2), '333', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $result);
                $page = $page + $count;
            }

            $totaldb += $data->db;
            $totalcr += $data->cr;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("GRAND TOTAL", '334', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("" . number_format($totaldb, 2), '333', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalcr, 2), '333', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}
