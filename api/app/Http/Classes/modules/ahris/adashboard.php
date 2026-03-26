<?php

namespace App\Http\Classes\modules\ahris;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\mobile\modules\inventoryapp\inventory;
use Exception;
use Throwable;
use Session;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class adashboard
{
    private $othersClass;
    private $coreFunctions;
    private $headClass;
    private $logger;
    private $lookupClass;
    private $companysetup;
    private $config = [];
    private $sqlquery;
    private $tabClass;
    private $fieldClass;

    private $totalEmployees = 0;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->headClass = new headClass;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
        $this->companysetup = new companysetup;
        $this->sqlquery = new sqlquery;
        $this->tabClass = new tabClass;
        $this->fieldClass = new txtfieldClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5223,
        );
        return $attrib;
    }

    public function sbc($params)
    {
        $doc = strtolower($params['doc']);
        $type = strtolower($params['moduletype']);
        $classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
        try {
            $this->config['classname'] = $classname;
            $this->config['docmodule'] = new $classname();
        } catch (Exception $e) {
            echo $e;
            return $this;
        }
        $this->config['params'] = $params;
        if (isset($this->config['params']['logintype'])) {
            if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
                $access = $this->othersClass->getportalaccess($params['user']);
            } else {
                $access = $this->othersClass->getAccess($params['user']);
            }
        } else {
            $access = $this->othersClass->getAccess($params['user']);
        }
        $this->config['access'] = json_decode(json_encode($access), true);
        $this->config['mattrib'] = $this->config['docmodule']->getAttrib();
        if ($this->companysetup->getrestrictip($params)) {
            $ipaccess = $this->config['access'][0]['attributes'][3722]; //restrict ip access
            if ($ipaccess == 1) {
                $this->config['allowlogin'] = $this->othersClass->checkip($params);
                if (!$this->config['allowlogin']) {
                    $this->config['msg'] = 'RESTRICTED IP, pls inform admin';
                }
                $this->coreFunctions->LogConsole("Your IP - '" . $params['ip'] . "'");
            } else {
                $this->config['allowlogin'] = true;
            }
        }

        $istimechecking = $this->othersClass->istimechecking($params);
        if ($istimechecking['status']) {
            $this->config['loginexpired'] = $istimechecking['loginexpired'];
        }
        return $this;
    }

    public function checksecurity($accessid)
    {
        if (isset($this->config['mattrib'][$accessid])) {
            $id = $this->config['mattrib'][$accessid];

            $companyid = $this->config['params']['companyid'];
            if ($companyid == 49) { //hotmix

                if ($this->config['params']['doc'] == 'RR') {
                    if (isset($this->config['params']['action'])) {
                        if ($this->config['params']['action'] == 'getposummary') $id = $this->config['mattrib']['save'];
                        if ($this->config['params']['action'] == 'getpodetails') $id = $this->config['mattrib']['save'];
                    }
                }
            }

            $this->config['verifyaccess'] = $this->config['access'][0]['attributes'][$id - 1];
            if ($this->config['verifyaccess'] == 0) {
                $this->config['return'] = ['status' => 'denied', 'msg' => 'Invalid Access'];
            }
        } else {
            $this->coreFunctions->sbclogger('Undefined ' . $accessid . ' ' . $this->config['params']['doc'] . ' id: ' . $this->config['params']['id']);
            $this->config['return'] = ['status' => 'denied', 'msg' => 'Undefined ' . $accessid . ' ' . $this->config['params']['doc']];
        }

        return $this;
    }

    public function execute()
    {
        if (isset($this->config['allowlogin'])) {
            if (!$this->config['allowlogin']) {
                return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator', 'xx' => $this->config], 200);
            }
        }

        return response()->json($this->config['return'], 200);
    } // end function

    public function loadaform($config)
    {
        $this->config = $config;
        // if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
        //     $this->dashboardclienttable();
        // } else {
        //     $this->dashboardwaims();
        // }
        $this->dashboardwaims();
        return $this->config['return'];
    }

    public function dashboardwaims()
    {
        ini_set('max_execution_time', -1);

        $this->totalEmployees = $this->coreFunctions->datareader("select count(empid) as value from employee where isactive=1", [], '', true);
        $this->modulecountdetail('active_employee', 'green', 'Active Employees', $this->totalEmployees);

        $this->companies();

        $this->sectioncount();

        $sorting = ['qcard', 'actionlist', 'dailynotif', 'overview', 'sbcgraph', 'sbclist'];
        $this->config['return'] = [
            'status' => true,
            'msg' => 'Loaded Success',
            'obj' => $this->config,
            'sorting' => $sorting
        ];
    } //end function

    public function dashboardclienttable() {} //end function

    private function sectioncount()
    {
        $companyid =  $this->config['params']['companyid'];
        switch ($companyid) {
            case 58:
                $section = $this->coreFunctions->opentable("select sectname, count(sectname) as ctr from (
                        select if(sect.sectname='SUPPORT',sect.sectname,'SALES & OPERATIONS') as sectname
                        from employee as emp left join section as sect on sect.sectid=emp.sectid
                        where emp.isactive=1 and sect.sectid is not null ) as sec group by sectname");
                break;
            default:
                $section = $this->coreFunctions->opentable("select sect.sectname, count(sect.sectname) as ctr
                from employee as emp left join section as sect on sect.sectid=emp.sectid
                where emp.isactive=1 and sect.sectid is not null group by sect.sectname");
                break;
        }

        $index = 0;
        $colors = ['red-3', 'pink-3'];

        foreach ($section as $key => $value) {

            if (isset($colors[$index])) {
                $color = $colors[$index];
            } else {
                $color = $colors[0];
            }

            if ($value->ctr == 0) {
                $perc = 0;
            } else {
                $perc = (int)(($value->ctr / $this->totalEmployees) * 100);
            }

            $this->config['qcard'][$value->sectname] =
                [
                    'class' => 'bg-' . $color . ' text-white',
                    'headalign' => 'left',
                    'title' => $value->ctr,
                    'subtitle2' =>  $perc . '%',
                    'subtitle2size' => '25px',
                    'titlesize' => '20px',
                    'subtitle' => $value->sectname,
                    'object' => 'btn',
                    'isvertical' => true,
                    'align' => 'right',
                    'detail' => []
                ];

            $index += 1;
        }
    }

    private function modulecountdetail($doc, $color, $caption, $count, $perc = '')
    {
        $this->config['qcard'][$doc] =
            [
                'class' => 'bg-' . $color . ' text-white',
                'headalign' => 'left',
                'title' => $count,
                'titlesize' => '20px',
                'subtitle2' => $perc != '' ? $perc . '%' : '',
                'subtitle2size' => '25px',
                'subtitle' => $caption,
                'object' => 'btn',
                'isvertical' => true,
                'align' => 'right',
                'detail' => []
            ];
    }


    public function companies()
    {
        $division = $this->coreFunctions->opentable("select divid, divname, picture from division");

        $data = [];
        $row1 = [];
        $row2 = [];
        $row3 = [];

        $totalReg = $totalProb = 0;

        $statSummary = [];

        foreach ($division as $key => $value) {

            if ($value->picture == '') {
                $value->picture = 'images/employee/company_default.png';
            } else {
                $value->picture = ltrim($value->picture, '/');
            }

            $status =  $this->coreFunctions->opentable("select '1' as grp, 'Active' as empstatus, sum(emp.isactive) as ctr, 0 as line, 0 as sortline
            from employee as emp left join division as divs on divs.divid=emp.divid left join empstatentry as empstat on empstat.line=emp.empstatus 
                    where emp.isactive=1 and emp.divid=" . $value->divid . "
                    union all
                    select '2' as grp, ifnull(empstat.empstatus,'') as empstatus, count(ifnull(empstat.empstatus,'')) as ctr, empstat.line, empstat.sortline
                    from employee as emp left join empstatentry as empstat on empstat.line=emp.empstatus
                    where emp.isactive=1 and emp.divid=" . $value->divid . " and empstat.line is not null 
                    group by emp.empstatus, empstat.empstatus, empstat.line, empstat.sortline
                    order by grp, sortline, line");

            $btns = [];
            $index = 1;
            $colors = ['green-4', 'blue-4', 'red-4', 'orange-4', 'lime-4', 'teal-4'];
            foreach ($status as $keys => $vals) {

                if (isset($colors[$index - 1])) {
                    $color = $colors[$index - 1];
                } else {
                    $color = $colors[0];
                }

                if (strtoupper($vals->empstatus) != 'ACTIVE') {
                    $statLine = ['id' => strtoupper($vals->empstatus), 'count' => $vals->ctr, 'color' => $color];
                    array_push($statSummary, $statLine);
                }

                array_push($btns, [
                    'color' => $color,
                    'text' => $vals->ctr,
                    'action' => 'hrisoverview',
                    'lookupclass' => 'loadnotiflisting',
                    'type' => 'listing',
                    'addedparams' => ['divid' => $value->divid, 'statline' => $vals->line, 'statname' => $vals->empstatus],
                    'tooltip' => $vals->empstatus
                ]);
                $index += 1;
            }

            $line = [
                'subtitle' => $value->divname,
                'subtitle2' => '',
                'image' => Storage::disk('public')->url($value->picture)
            ];

            if (!empty($btns)) {
                $line['subtitle3'] = ['btns' => $btns];
            }

            array_push($row1, $line);
        }

        array_push($data, ['title' => [
            'text' => "",
            'icon' => 'star',
            'bgcolor' => 'red-10',
            'textcolor' => 'white'
        ], 'data' => $row1]);

        $text2 = ['text' => $this->totalEmployees, 'color' => 'white', 'size' => '25px', 'tooltip' => 'Employee Count'];

        $year = date('Y', strtotime($this->othersClass->getCurrentDate()));

        $this->config['overview']['companies'] = ['data' => $data, 'title' => ['text' => 'EMPLOYEE OVERVIEW ' . $year, 'icon' => 'rss_feed', 'text2' => $text2, 'bgcolor' => 'red', 'textcolor' => 'white']];

        $this->createStatCount($statSummary);
    }

    private function createStatCount($duplicates)
    {
        $result = array_reduce($duplicates, function ($carry, $item) {
            $id = $item['id'];
            if (isset($carry[$id])) {
                $carry[$id]['count'] = (int)$carry[$id]['count'] + (int)$item['count'];
            } else {
                $carry[$id] = $item;
            }
            return $carry;
        }, []);

        $result = array_values($result);

        foreach ($result as $key => $value) {
            $totalPerc = number_format(($value['count'] / $this->totalEmployees) * 100, 1);
            $this->modulecountdetail($value['id'] . '_employee', $value['color'], $value['id'], $value['count'], $totalPerc);
        }
    }
}
