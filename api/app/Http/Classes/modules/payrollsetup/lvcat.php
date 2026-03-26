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

class lvcat
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LEAVE CATEGORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'leavecategory';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['category', 'isinactive', 'colorcode', 'colorname'];
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
        $attrib = array('load' => 5027);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'category', 'colorname', 'isinactive']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:400px;whiteSpace: normal;min-width:100px;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'print', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['category'] = '';
        $data['isinactive'] = 'false';
        $data['colorcode'] = '';
        $data['colorname'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line,case when isinactive = 0 then 'false' else 'true' end as isinactive,category,colorname";
        // foreach ($this->fields as $key => $value) {
        //     $qry = $qry . ',' . $value;
        // }
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
            $qry = "select category as value from leavecategory where category = '" . $data['category'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if (!empty($checking)) {
                return ['status' => false, 'msg' => 'Cannot create category Already Exist. - ' . $data['category']];
            }

            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE' . ' - ' . $data['category'] . ' - ' . 'LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $qry = "select catid as value from leavetrans where catid = '" . $row['line'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            $qry = "select category  from " . $this->table . " where line = '" . $row['line'] . "'";
            $category = $this->coreFunctions->opentable($qry);
            if (trim($row['category']) != trim($category[0]->category)) {
                if (!empty($checking)) {
                    return ['status' => false, 'msg' => 'Cannot update category already used. - ' . $category[0]->category, 'data' => $data];
                }
            }
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                $this->logger->sbcmasterlog(
                    $row['line'],
                    $config,
                    'UPDATE' . ' - ' . $data['category'] . ' - ' . $row['line']
                );
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
                if ($data[$key]['line'] == 0) {
                    $qry = "select category as value from " . $this->table . " where category = '" . $data[$key]['category'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    if (!empty($checking)) {
                        $returndata = $this->loaddata($config);
                        return ['status' => false, 'msg' => 'Cannot create category already exist. - ' . $data[$key]['category'], 'data' => $data];
                    }
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['createby'] = $config['params']['user'];
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'CREATE' . ' - ' . $data[$key]['category'] . '' . ' - LINE' . $line
                    );
                } else {
                    $qry = "select catid as value from leavetrans where catid = '" . $data[$key]['line'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    $qry = "select category  from " . $this->table . " where line = '" . $data[$key]['line'] . "'";
                    $category = $this->coreFunctions->opentable($qry);
                    if (trim($data[$key]['category']) != trim($category[0]->category)) {

                        if (!empty($checking)) {
                            $returndata = $this->loaddata($config);
                            return ['status' => false, 'msg' => 'Cannot update category already used. - ' . $category[0]->category, 'data' => $data];
                        }
                    }


                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

                    $this->logger->sbcmasterlog(
                        $data[$key]['line'],
                        $config,
                        'UPDATE' . ' - ' . $data[$key]['category']  . ' - LINE' . $data[$key]['line']
                    );
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
        $qry1 = "select count(catid) as value from leavetrans where catid=?";
        $count = $this->coreFunctions->datareader($qry1, [$line]);

        if ($count != 0) {
            return ['clientid' => $line, 'status' => false, 'msg' => 'Already have transaction...'];
        }

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['category']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        // var_dump($qry);
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
            case 'lookup_catcolors':
                return $this->lookupcolors($config);
                break;
            case 'lookuplogs':
                return $this->lookuplogs($config);
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


    public function lookupcolors($config)
    {
        $rowindex = $config['params']['index'];
        $lookupclass2 = $config['params']['lookupclass2'];

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Colors',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'plotgrid',
            'plotting' => ['colorcode' => 'colorcode',  'colorname' => 'colorname']
        );

        $cols = array(
            array('name' => 'colorcode', 'label' => 'code', 'align' => 'left', 'field' => 'colorcode', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'colorname', 'label' => 'Name', 'align' => 'left', 'field' => 'colorname', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "select '#D891EF' as colorcode, 'Bright Lilac' as colorname
                union all
                select '#FF77FF' as colorcode, 'Fuchsia Pink' as colorname
                union all
                select '#5FFB17' as colorcode, 'Emerald Green' as colorname 
                union all
                select '#E8E4C9' as colorcode, 'Dirty White' as colorname 
                union all
                select '#FE632A' as colorcode, 'Fluro Orange' as colorname
                 union all
                select '#F535AA' as colorcode, 'Neon Pink' as colorname
                 union all
                select '#F8B88B' as colorcode, 'Pastel Orange' as colorname
                 union all
                select '#8B0000' as colorcode, 'DarkRed' as colorname
                 union all
                select '#007C80' as colorcode, 'Teal Blue' as colorname
                 union all
                select '#0AFFFF' as colorcode, 'Bright Cyan' as colorname";

        $data = $this->coreFunctions->opentable($qry);

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
        $fields = ['radioprint', 'print'];
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
        $query = "select line, category from leavecategory";
        $result = $this->coreFunctions->opentable($query);
        return $result;
    } //end fn

    public function reportdata($config)
    {
        $data = $this->report_default_query($config);
        $str = $this->rpt_rank_PDF($data, $config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    private function rpt_rank_PDF_header_PDF($data, $filters)
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

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');


        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(800, 20, "LEAVE CATEGORY LIST", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(720, 20, "Page " . PDF::PageNo() . "  ", '', 'R', false);

        PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
        PDF::MultiCell(300, 20, "Category Name", '', 'L', false, 0);
        PDF::MultiCell(300, 20, "", '', 'L', false, 0);
        PDF::MultiCell(120, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(120, 0, "", 'T', 'L', false);
    }

    private function rpt_rank_PDF($data, $filters)
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
        $this->rpt_rank_PDF_header_PDF($data, $filters);
        $i = 0;
        foreach ($data as $key => $value) {
            $i++;
            PDF::SetFont($fontbold, 'B', $fontsize);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
            // PDF::MultiCell(300, 10, $data[$i]['stockgrp_code'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(15, 10, $i . '.', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 10, $value->category, '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(405, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

            if (intVal($i) + 1 == $page) {
                $this->rpt_rank_PDF_header_PDF($data, $filters);
                $page += $count;
            }
        }

        PDF::MultiCell(0, 0, "\n\n\n\n");
        return PDF::Output($this->modulename . '.pdf', 'S');
    } //end fn
































} //end class
