<?php

namespace App\Http\Classes;

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


class adashboardClass
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

    public function loadaform()
    {
        $this->config['return'] = $this->config['docmodule']->loadaform($this->config);
        // if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
        //     $this->dashboardclienttable();
        // } else {
        //     $this->dashboardwaims();
        // }
        return $this;
    }

    public function dashboardwaims()
    {

        ini_set('max_execution_time', -1);

        $this->modulecount('RR', 'red-3', true);
        $this->modulecount('employee', 'green', 'Active Employees');
        $this->modulecount('regular', 'yellow-8', 'Regular Employees');
        $this->modulecount('employee3', 'red-3', 'Sales & Operation');
        $this->modulecount('employee4', 'pink-3', 'Support Group');

        // $this->modulecount2('regular', 'blue', '1 - 5 DAYS');
        // $this->modulecount2('regular1', 'blue', '60 DAYS');
        // $this->modulecount2('regular2', 'blue', '120 DAYS');
        // $this->modulecount2('regular3', 'blue', '150 DAYS');
        // $this->modulecount2('regular4', 'blue', '160 DAYS');
        // $this->modulecount2('regular5', 'blue', '180 DAYS');
        // $this->modulecount2('regular6', 'red', 'EXPIRED CONTRACT');

        $this->companies();

        $sorting = ['qcard', 'actionlist', 'dailynotif', 'sbcgraph', 'sbclist'];
        $this->config['return'] = [
            'status' => true,
            'msg' => 'Loaded Success',
            'obj' => $this->config,
            'sorting' => $sorting
        ];
    } //end function

    public function dashboardclienttable()
    {

        ini_set('max_execution_time', -1);

        $this->modulecount('employee', 'white', 'Active Employees');

        $sorting = ['qcard', 'actionlist', 'sbcgraph', 'sbclist'];
        $this->config['return'] = [
            'status' => true,
            'msg' => 'Loaded Success',
            'obj' => $this->config,
            'sorting' => $sorting
        ];
    } //end function


    private function modulecount($doc, $color, $caption)
    {
        $count = 0;
        switch ($doc) {
            case 'employee':
                $count = $this->coreFunctions->datareader("select count(isactive) as value from employee where isactive=1");
                break;
            case 'regular':
                $count = $this->coreFunctions->datareader("select count(e.empstatus) as value from employee as emp left join empstatentry as e on e.line=emp.empstatus where emp.isactive=1 and ucase(e.empstatus)='REGULAR'");
                break;
            case 'RR':
                $qry = "select ifnull(count(glhead.trno), 0) as counting from glhead left join cntnum on cntnum.trno=glhead.trno where glhead.doc = '" . $doc . "'  and date(glhead.dateid) = '" . $dateid . "'";
                $pap = $this->coreFunctions->opentable($qry);
                $qry1 = "select ifnull(count(lahead.trno), 0) as counting from lahead left join cntnum on cntnum.trno=lahead.trno where lahead.doc = '" . $doc . "' and date(lahead.dateid) = '" . $dateid . "'";
                $uap = $this->coreFunctions->opentable($qry1);
                break;
        }

        if ($doc == 'RR') {
            $this->config['qcard'][$doc] =
            [
                'class' => 'bg-' . $color . ' text-white',
                'headalign' => 'right',
                'title' => $doc . ' Transaction',
                'subtitle' => $dateid . ' - ' . $total,
                'object' => 'btn',
                'isvertical' => true,
                'align' => 'right',
                'detail' => [
                  'btn1' => [
                    'label' => 'Posted ' . $pap[0]->counting,
                    'type' => 'customform',
                    'action' => $doc,
                    'classid' => 'posted'
                  ],
                  'btn2' => [
                    'label' => 'Unposted ' . $uap[0]->counting,
                    'type' => 'customform',
                    'action' => $doc,
                    'classid' => 'unposted'
                  ]
                ]
            ];
        } else {
            $this->config['qcard'][$doc] =
                [
                    'class' => 'bg-' . $color . ' text-white',
                    'headalign' => 'left',
                    'title' => $count,
                    'subtitle' => $caption,
                    'object' => 'btn',
                    'isvertical' => true,
                    'align' => 'right',
                    'detail' => ['btn1' => [
                        'label' => '100%',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'posted'
                    ],]
                ];
        }
    } // end function


    private function modulecount2($doc, $color, $caption)
    {
        $count = 0;
        switch ($doc) {
            case 'employee':
                $count = $this->coreFunctions->datareader("select count(isactive) as value from employee where isactive=1");
                break;
            case 'regular':
                break;
        }

        $this->config['qcard'][$doc] =
            [
                'class' => 'bg-' . $color . ' text-white',
                'headalign' => 'left',
                'title' => $caption,
                'subtitle' => $count,
                'object' => 'btn',
                'isvertical' => true,
                'align' => 'left',
                'detail' => [
                    'btn1' => [
                        'label' => 'btn1 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'posted'
                    ],
                    'btn2' => [
                        'label' => 'btn1 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn3' => [
                        'label' => 'btn3 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn4' => [
                        'label' => 'btn5 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn5' => [
                        'label' => 'btn5 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn6' => [
                        'label' => 'btn6 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn7' => [
                        'label' => 'btn7 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn8' => [
                        'label' => 'btn8 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn9' => [
                        'label' => 'btn9 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ],
                    'btn10' => [
                        'label' => 'btn10 ',
                        'type' => 'customform',
                        'action' => $doc,
                        'classid' => 'unposted'
                    ]
                ]
            ];
    } // end function

    public function companies()
    {

        $curdate = $this->othersClass->getCurrentDate();

        $bday = $this->coreFunctions->opentable("select divname, '' as picture from division");

        $data = [];

        $row1 = [];
        $row2 = [];
        $row3 = [];

        foreach ($bday as $key => $value) {

            if ($value->picture == '') {
                $value->picture = 'images/employee/company_default.png';
            } else {
                $value->picture = ltrim($value->picture, '/');
            }

            $line = [
                'subtitle' => $value->divname,
                'subtitle2' => 'subtitle2',
                'subtitle3' => ['text' => 'subtitle3', 'icon' => 'list', 'color' => 'red'],
                'subtitle4' => 'subtitle4',
                'dateid' => 'date',
                'image' => Storage::disk('public')->url($value->picture)
            ];

            array_push($row1, $line);
        }

        array_push($data, ['title' => [
            'text' => "Total Employees: 2",
            'icon' => 'star',
            'bgcolor' => 'red-10',
            'textcolor' => 'white'
        ], 'data' => $row1]);

        $this->config['dailynotif']['companies'] = ['data' => $data, 'title' => ['text' => 'EMPLOYEE OVERVIEW 2025', 'icon' => 'rss_feed', 'bgcolor' => 'red-5', 'textcolor' => 'white']];
    }
}
