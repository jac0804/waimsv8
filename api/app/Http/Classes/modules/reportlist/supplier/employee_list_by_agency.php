<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class employee_list_by_agency
{
    public $modulename = 'Employee List By Agency';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = array('orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000');

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
        $fields = array('radioprint', 'start', 'end', 'agencyname', 'radiolayoutformat');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', array(
            array('label' => 'Default', 'value' => 'default', 'color' => 'red')
        ));

        data_set(
            $col1,
            'radiolayoutformat.options',
            array(
                array('label' => 'Active', 'value' => '0', 'color' => 'teal'),
                array('label' => 'Inactive', 'value' => '1', 'color' => 'teal'),
                array('label' => 'Both', 'value' => '2', 'color' => 'teal')
            )
        );

        $fields = array('print');
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", array($center));
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
    '' as client,
    '' as clientname,
    '0' as clientid,
    '' as street,
    '' as area,
      '' as dclientname,
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix,
      '' as agencyname,
      '2' as layoutformat
      ";
        return $this->coreFunctions->opentable($paramstr);
    }


    public function getloaddata($config)
    {
        return array();
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return array('status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams);
    }

    public function reportplotting($config)
    {
        return $this->reportDefault_Layout($config);
    }


    public function reportDefault_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $street = isset($config['params']['dataparams']['area']) ? $config['params']['dataparams']['area'] : '';
        $region = isset($config['params']['dataparams']['region']) ? $config['params']['dataparams']['region'] : '';
        $province = isset($config['params']['dataparams']['province']) ? $config['params']['dataparams']['province'] : '';
        $nationality = isset($config['params']['dataparams']['nationality']) ? $config['params']['dataparams']['nationality'] : '';
        $agencyname = isset($config['params']['dataparams']['agencyname']) ? $config['params']['dataparams']['agencyname'] : '';
        $layoutformat = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '2';
        $filter = "";

        if ($agencyname != "") {
            $filter .= " and emp.agencyname ='$agencyname'";
        }

        switch ($layoutformat) {
            case '0': // active
                $filter .= " and client.isinactive = 0";
                break;
            case '1': // inactive
                $filter .= " and client.isinactive = 1";
                break;
            default: // both
                break;
        }

        // filter by resignation date range; still show employees who haven't
        // resigned yet, regardless of the selected range.
        $filter .= " and (emp.resigned is null or emp.resigned between '$start' and '$end')";

        $query = "select client.client, client.clientname, client.addr, client.tel2 as phone, client.tel as tln,
        emp.tin, client.contact, emp.hired, emp.resigned,
        case when client.isinactive = 0 then 'ACTIVE' else 'INACTIVE' end as status,
        emp.agencyname, client.area
        from client
        left join employee as emp on emp.empid = client.clientid
        left join clientinfo as info on info.clientid = client.clientid
        where client.isemployee = 1
        $filter
        order by emp.agencyname asc, client.clientname asc
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $start = date("F-j-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F-j-Y", strtotime($config['params']['dataparams']['end']));
        $agencyname = isset($config['params']['dataparams']['agencyname']) ? $config['params']['dataparams']['agencyname'] : '';
        $layoutformat = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '2';

        switch ($layoutformat) {
            case '0':
                $statuslabel = 'ACTIVE';
                break;
            case '1':
                $statuslabel = 'INACTIVE';
                break;
            default:
                $statuslabel = 'BOTH';
                break;
        }

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE LIST - ACTIVE', null, null, false, '', '', 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col( '   Agency: ' . ($agencyname != '' ? strtoupper($agencyname) : 'ALL AGENCY') . '   Status: ' . $statuslabel, null, null, false, '', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO.', '30', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CODE', '70', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '150', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '250', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TELEPHONE#', '100', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('T.I.N.#', '90', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CONTACT PERSON', '120', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('START', '60', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('END', '60', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($statuslabel, '70', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "9";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $limitPerPage = 25;
        $rowCount = 0;

        $resultArray = array_values($result);
        $totalRows = count($resultArray);

        $currentAgency = null;
        $agencyRowNumber = 0;

        // pre-count employees per agency so we can print "No. of Employee : N"
        // right when the agency header prints, without a second query pass.
        $agencyCounts = array();
        foreach ($resultArray as $row) {
            $agencyKey = $row->agencyname ? $row->agencyname : '';
            if (isset($agencyCounts[$agencyKey])) {
                $agencyCounts[$agencyKey] = $agencyCounts[$agencyKey] + 1;
            } else {
                $agencyCounts[$agencyKey] = 1;
            }
        }

        for ($i = 0; $i < $totalRows; $i++) {

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->begintable($layoutsize);
            }

            $row = $resultArray[$i];
            $agency = $row->agencyname ? $row->agencyname : '';

            // agency changed -> print agency header row + employee count
            if ($agency !== $currentAgency) {
                $currentAgency = $agency;
                $agencyRowNumber = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('AGENCY : ' . strtoupper($agency), '700', null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('No. of Employee : ' . $agencyCounts[$agency], '300', null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $agencyRowNumber++;

            $start = $row->hired ? date("m-d-y", strtotime($row->hired)) : '';
            $end   = $row->resigned ? date("m-d-y", strtotime($row->resigned)) : '';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($agencyRowNumber, '30', null, false, '', '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->client, '70', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->clientname, '150', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->addr, '250', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->tln, '100', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->tin, '90', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->contact, '120', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($start, '60', null, false, '', '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($end, '60', null, false, '', '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->status, '70', null, false, '', '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $rowCount++;

            $nextAgency = ($i < $totalRows - 1) ? ($resultArray[$i + 1]->agencyname ? $resultArray[$i + 1]->agencyname : '') : null;
            $isLastOfGroup = ($i == $totalRows - 1) || ($nextAgency !== $agency);
            if ($isLastOfGroup && $i < $totalRows - 1) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '', '15', false, '', '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class