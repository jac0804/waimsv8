<?php

namespace App\Http\Classes\modules\payrollsetup;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class accountingaccount
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Accounting Accounts';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'aaccount';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['code', 'codename', 'type'];
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
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 4804
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $column = ['action', 'code', 'codename', 'type'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];


        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
        $obj[0][$this->gridname]['columns'][$type]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$type]['label'] = "Type";
        $obj[0][$this->gridname]['columns'][$type]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$type]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$type]['lookupclass'] = "lookuptypeofaccount";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['code'] = '';
        $data['codename'] = '';
        $data['type'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {

        $qry = "line";
        foreach ($this->fields as $key => $value) {
            if ($value == 'type') {
                $value = "case when type = 'D' then 'DEBIT' when type = 'C' then 'CREDIT' else '' end as type";
            }
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
        if ($data['type'] != "") {
            $data['type'] = substr($data['type'], 0, 1);
        }
        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE' . ' - ' . $data['code'] . ' - ' . $data['codename'] . 'LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);

                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
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
                if ($data[$key]['type'] != "") {
                    $data2['type'] = substr($data[$key]['type'], 0, 1);
                }
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'CREATE' . ' - ' . $data[$key]['code'] . ' - ' . $data[$key]['codename'] . ' - LINE' . $line
                    );
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $line = $config['params']['row']['line'];
        $qry1 = "select line as value from employee where line=?";
        $count = $this->coreFunctions->datareader($qry1, [$line], '', true);

        if (($count != 0)) {
            return ['clientid' => $line, 'status' => false, 'msg' => 'Already have transaction...'];
        }

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['code'] . ' - ' . $row['codename']);
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
            case 'lookuptypeofaccount':
                return $this->lookuptypeofaccount($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
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

        $qry = "
      select trno, doc, task, dateid, user
      from " . $this->tablelogs . "
      where doc = ?
      union all 
      select trno, doc, task, dateid, user
      from " . $this->tablelogs_del . "
      where doc = ?
      order by dateid desc
    ";

        $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
    public function lookuptypeofaccount($config)
    {
        $rowindex = $config['params']['index'];
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Type of Account',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $plotsetup = array(
            'plottype' => 'plotgrid',
            'plotting' => array('type' => 'type')
        );

        $cols = array(
            array('name' => 'type', 'label' => 'Type of Account', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select 'DEBIT' as type
            union all
            select 'CREDIT' as type";

        $data = $this->coreFunctions->opentable($qry);
        // return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
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
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
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
        $query = "
      select divid, divcode, divname from division
    ";
        $result = $this->coreFunctions->opentable($query);
        return $result;
    } //end fn

    public function reportdata($config)
    {
        $data = $this->report_default_query($config);
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_DEFAULT_division_MASTER_LAYOUT($data, $config);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            $str = $this->rpt_division_PDF($data, $config);
        }
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    private function rpt_default_header($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        if ($companyid == 3) {
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DIVISION LIST', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        $str .= $this->reporter->col('DIVISION NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
        return $str;
    }

    private function rpt_DEFAULT_division_MASTER_LAYOUT($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';
        $layoutsize = '1000';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();

        $str .= $this->rpt_default_header($data, $filters);

        foreach ($data as $key => $value) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($value->divcode, '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($value->divname, '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                $str .= $this->rpt_default_header($data, $filters);

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

    private function rpt_division_PDF_header_PDF($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];
        $companyid = $filters['params']['companyid'];

        $font = "";
        $fontbold = "";
        $fontsize = 11;

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);


        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($filters, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(800, 20, "DIVISION LIST", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
        PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
        PDF::MultiCell(300, 20, "Division Name", '', 'L', false, 0);
        PDF::MultiCell(100, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'T', 'L', false);
    }

    private function rpt_division_PDF($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $count = 35;
        $page = 35;
        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "10";

        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->rpt_division_PDF_header_PDF($data, $filters);
        $i = 0;
        foreach ($data as $key => $value) {
            PDF::SetFont($font, '', $fontsize);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
            // PDF::MultiCell(300, 10, $data[$i]['stockgrp_code'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(300, 10, $value->divcode, '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(300, 10, $value->divname, '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

            if (intVal($i) + 1 == $page) {
                $this->rpt_division_PDF_header_PDF($data, $filters);
                $page += $count;
            }
            $i++;
        }

        PDF::MultiCell(0, 0, "\n\n\n\n");

        PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
        PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn
} //end class
