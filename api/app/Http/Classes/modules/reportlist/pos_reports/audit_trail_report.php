<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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

class audit_trail_report
{
    public $modulename = 'Audit Trail Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => '1', 'format' => 'legal', 'layoutSize' => '1200'];


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
        $fields = ['start', 'end', 'prepared', 'received', 'approved', 'radioreporttype', 'usernamee'];

        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dcentername.label', 'Center');
        // data_set($col1, 'dcentername.action', 'lookupcenter');
        data_set($col1, 'usernamee.lookupclass', 'lookupuserss');
        array_set($col1, 'start.type', 'date');
        array_set($col1, 'end.type', 'date');
        array_set($col1, 'radioreporttype.label', 'Format');
        array_set($col1, 'radioreporttype.options', [
            ['label' => 'BIR', 'value' => '0', 'color' => 'teal'],
        ]);


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
    
        '' as usernamee,
        '' as userid,
        '' as prepared,
        '' as received, 
        '' as approved,
        '0' as reporttype


     ");
    }
    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        // $center = $config['params']['center'];
        $user = $config['params']['dataparams']['usernamee'];
        $userid = $config['params']['dataparams']['usernamee'];

        $filter = '';
        $query = '';

        // if ($user !== '' && $userid !== '0') {
        //   $filter .= " and tl.userid = '" . $userid . "'";
        // }

        if (!empty($userid)) {
            $filter .= " and tl.userid = '" . $userid . "'";
        }


        $query = "select head.docno, tl.oldversion as task, tl.userid, tl.dateid
                    from table_log tl
                    left join cntnum on cntnum.trno = tl.trno
                    left join head on head.trno = cntnum.trno
                    and head.bref in ('SI','V','RT')
                    where date(tl.dateid) between '$start' and '$end' $filter
                    union all
                    select head.docno, tl.oldversion as task, tl.userid, tl.dateid
                    from htable_log tl
                    left join cntnum on cntnum.trno = tl.trno
                    left join head on head.trno = cntnum.trno
                    and head.bref in ('SI','V','RT')
                    where date(tl.dateid) between '$start' and '$end' $filter
                    union all
                    select head.docno, tl.docno as task, tl.userid, tl.dateid
                    from del_table_log tl
                    left join cntnum on cntnum.trno = tl.trno
                    left join head on head.trno = cntnum.trno
                    and head.bref in ('SI','V','RT')
                    where date(tl.dateid) between '$start' and '$end' $filter ";
        // var_dump($query);

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['usernamee'];
        // $type     = $config['params']['dataparams']['posttype'];

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Sub total per Sales', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('Audit Trail ', '1000', null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Date : '  .  $start  . '  to  ' .  $end, '200', null, false, '7px solid ', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('Date : ', '70', null, false, '7px solid ', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '7px solid ', '', '', $font, '', 'B', '', '');
        $str .= $this->reporter->col($start . ' to ' . $end, '250', null, false, '7px solid ', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '270', null, false, '7px solid ', '', '', $font, '', '', '', '');
        if ($user == '') {
            $str .= $this->reporter->col('Username : ALL USERS', '200', null, false, '1px solid ', '', 'L', $font, '12', 'B', '', '');
        } else {
            $str .= $this->reporter->col('Username : ' . $user, '200', null, false, '1px solid ', '', 'L', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATEE', '200', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('USER', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DOCUMENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('TASK', '730', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "11";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $limitPerPage = 45;
        $rowCount = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        foreach ($result as $data) {

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // Repeat header every time na nag next page
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->userid, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->task, '730', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();

            $rowCount++;
        }
        $str .= $this->reporter->endtable();

        // For footer but not needed for now
        $str .= '<br/><br/>';
        $config['params']['doc'] = $this->modulename;
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'posted', $dataparams['approved']);
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by: ', '300', null, false, $border, '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received by: ', '300', null, false, $border, '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Posted By: ', '300', null, false, $border, '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $config['params']['dataparams']['prepared'], '300', null, false, $border, 'B', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('' . $config['params']['dataparams']['received'], '300', null, false, $border, 'B', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('' . $config['params']['dataparams']['approved'], '300', null, false, $border, 'B', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class