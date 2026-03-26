<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class moduleapproval
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'MODULE APPROVAL';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $lookupClass;
    private $table = 'moduleapproval';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['modulename', 'labelname', 'countsupervisor', 'countapprover', 'approverseq', 'sbcpendingapp'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5363,
            'additem' => 5363
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];
        $columns = ['action', 'modulename', 'labelname', 'countsupervisor', 'countapprover', 'approverseq'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = ['save', 'delete', 'supervisors', 'approvers'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$modulename]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$labelname]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        $obj[0][$this->gridname]['columns'][$countsupervisor]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$countapprover]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$approverseq]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";

        if ($companyid == 53) { //camera
            $obj[0][$this->gridname]['columns'][$countapprover]['label'] = "HR/Payroll Approver";
            $obj[0][$this->gridname]['columns'][$countsupervisor]['label'] = "Head Dept. Approver";
            $obj[0][$this->gridname]['columns'][0]['btns']['approvers']['label'] = 'HR/Payroll Approver';
            $obj[0][$this->gridname]['columns'][0]['btns']['supervisors']['label'] = 'Head Dept. Approver';
        }

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[2]['action'] = "lookupsetup";
        $obj[2]['lookupclass'] = "lookuplogs";
        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['modulename'] = '';
        $data['labelname'] = '';
        $data['countsupervisor'] = '';
        $data['countapprover'] = '';
        $data['approverseq'] = '';
        $data['sbcpendingapp'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            $qry = "select modulename from " . $this->table . " where modulename='" . $data['modulename'] . "'";
            $checking = $this->coreFunctions->opentable($qry);
            if (!empty($checking)) return ['status' => false, 'msg' => 'Module Name already exists. - ' . $data['modulename']];
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, 'CREATE' . ' - ' . $data['modulename'] . ' - ' . $data['labelname']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $qry = "select modulename from " . $this->table . " where modulename='" . $data['modulename'] . "' and line<>'" . $row['line'] . "'";
            $checking = $this->coreFunctions->opentable($qry);
            if (!empty($checking)) return ['status' => false, 'msg' => 'Module Name already exists. - ' . $data['modulename']];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }

            $this->logger->sbcmasterlog($line, $config, 'UPDATE' . ' - ' . $data['modulename'] . ' - ' . $data['labelname']);
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if ($data[$key]['line'] == 0) {
                    $checking = $this->coreFunctions->opentable("select modulename from " . $this->table . " where modulename='" . $data[$key]['modulename'] . "'");
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Module Name already exists. - ' . $data[$key]['modulename']];
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, 'CREATE' . ' - ' . $data[$key]['modulename'] . ' - ' . $data[$key]['labelname']);
                } else {
                    $checking = $this->coreFunctions->opentable("select modulename from " . $this->table . " where modulename='" . $data[$key]['modulename'] . "' and line<>" . $data[$key]['line']);
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Module Name already exists. - ' . $data[$key]['modulename']];

                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, 'UPDATE' . ' - ' . $data[$key]['modulename'] . ' - ' . $data[$key]['labelname']);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }



    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            case 'lookupmodulelist2':
                return $this->lookupClass->lookupmodulelist2($config);
                break;
            case 'lookupapproverseq':
                return $this->lookupapproverseq($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookupapproverseq($config)
    {
        $lookupsetup = [
            'type' => 'single',
            'title' => 'List of Approver Seq',
            'style' => 'width:500px;max-width:500px;height:400px'
        ];
        $plotsetup = [
            'plottype' => 'plotgrid',
            'plotting' => ['approverseq' => 'approverseq']
        ];
        $index = $config['params']['index'];

        $cols = [
            ['name' => 'approverseq', 'label' => 'Seq', 'align' => 'left', 'field' => 'approverseq', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $data = $this->coreFunctions->opentable("
            select 'Supervisor,Approver' as approverseq
            union all
            select 'Approver,Supervisor' as approverseq
            union all
            select 'Supervisor' as approverseq
            union all
            select 'Approver' as approverseq
            union all
            select 'Supervisor or Approver' as approverseq
        ");
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];

        $cols = [
            ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']

        ];

        $qry = "select trno, doc, task, dateid, user
                from " . $this->tablelogs . "
                where doc = ?
                union all 
                select trno, doc, task, dateid, user
                from " . $this->tablelogs_del . "
                where doc = ?
                order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    public function reportsetup($config)
    {
        $txtfield = $this->createreportfilter();
        $txtdata = $this->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function createreportfilter()
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
        );
    }

    private function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select line, category, position from reqcategory order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportdata($params)
    {
        $data = $this->report_default_query($params);
        $str = $this->default_layout($params, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }


    private function default_layout_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
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
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(360, 0, "POSITION", 'B', 'L', false, 0);
        PDF::MultiCell(360, 0, "NAME OF OFFICIAL", 'B', 'L', false, 1);
        PDF::MultiCell(0, 0, "\n");
    }

    private function default_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_layout_header($params, $data);

        for ($i = 0; $i < count($data); $i++) {
            $pos_height = PDF::GetStringHeight(360, $data[$i]['position']);
            $cat_height = PDF::GetStringHeight(360, $data[$i]['category']);
            $max_height = max($pos_height, $cat_height);

            if ($max_height > 25) {
                $max_height = $max_height + 15;
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(360, 0, $data[$i]['position'], '', 'L', 0, 0, '', '');
            PDF::MultiCell(360, $max_height, $data[$i]['category'], '', 'L', 0, 1, '', '');
            if (intVal($i) + 1 == $page) {
                $this->default_layout_header($params, $data);
                $page += $count;
            }
        }

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
} //end class
