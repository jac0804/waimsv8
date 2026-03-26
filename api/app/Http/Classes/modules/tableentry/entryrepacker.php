<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryrepacker
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'REPACKER SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['category','isrepacker','reqtype','code','position'];
    public $showclosebtn = false;
    private $reporter;
    public $logger;
    private $reportheader;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->reportheader = new reportheader;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5549
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action', 'category','reqtype','code','position'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:125px;whiteSpace: normal;min-width:125px;';
        $obj[0][$this->gridname]['columns'][$reqtype]['style'] = 'width:125px;whiteSpace: normal;min-width:125px;';
        
        $obj[0][$this->gridname]['columns'][$code]['style'] = 'width:125px;whiteSpace: normal;min-width:125px;';
        $obj[0][$this->gridname]['columns'][$position]['style'] = 'width:125px;whiteSpace: normal;min-width:125px;';

        $obj[0][$this->gridname]['columns'][$category]['label'] = 'Group';
        $obj[0][$this->gridname]['columns'][$reqtype]['label'] = 'Repacker 1';
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Repacker 2';
        $obj[0][$this->gridname]['columns'][$position]['label'] = 'Repacker 3';
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['category'] = '';
        $data['reqtype'] = '';
        $data['code'] = '';
        $data['position'] = '';
        $data['isrepacker'] = 1;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = " r.line,r.category,r.reqtype, r.isrepacker,r.code,r.position";
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }


                if ($data[$key]['line'] == 0 && $data[$key]['category'] != '') {
                    $qry = "select category from reqcategory where category = '" . $data[$key]['category'] . "' and isrepacker=1 limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata =  json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['category'])) {
                        if (trim($resultdata[0]['category']) == trim($data[$key]['category'])) {
                            return ['status' => false, 'msg' =>  $resultdata[0]['category']  . '  already exist', 'data' => [$resultdata]];
                        }
                    }
                }
                if (trim($data[$key]['category'] == '')) {
                    return ['status' => false, 'msg' => 'Group is empty'];
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $params = $config;
                    $params['params']['doc'] = strtoupper('entryrepacker');
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - Group : ' . $data[$key]['category'].' '.' Repacker 1 : '. $data[$key]['reqtype'].' '.' Repacker 2 : '. $data[$key]['code'].' '.'Repacker 3 : '. $data[$key]['position']);
                } else {
                    if ($data[$key]['line'] != 0 && $data[$key]['category'] != '') {
                        $qry = "select category,line from reqcategory where category = '" . $data[$key]['category'] . "'  and isrepacker=1 and line <> ".$data[$key]['line']." limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata =  json_decode(json_encode($opendata), true);
                        if (!empty($resultdata)) {
                            return ['status' => false, 'msg' => $resultdata[0]['category']  . ' already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                        } else {
                            update:
                            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                            $data2['editby'] = $config['params']['user'];
                            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                        }
                    } //end if
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0 && $row['category'] != '') {
            $qry = "select category from reqcategory where category = '" . $row['category'] . "'  and isrepacker=1 limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['category'])) {
                if (trim($resultdata[0]['category']) == trim($row['category'])) {
                    return ['status' => false, 'msg' =>  $resultdata[0]['category'] . ' already exist', 'data' => [$resultdata]];
                }
            }
        }
        if (trim($row['category'] == '')) {
            return ['status' => false, 'msg' => 'Group is empty'];
        }

        if ($row['line'] == 0) {

            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $params = $config;
                $params['params']['doc'] = strtoupper('entryrepacker');
                $this->logger->sbcmasterlog($line, $config, ' CREATE - Group :  ' . $data['category'].' '.'Repacker 1 : '. $data['reqtype'].' '.' Repacker 2 : '. $data['code'].' '.'Repacker 3 : '. $data['position']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($row['category'] != '') {
                $qry = "select line,category,reqtype from  reqcategory where category = '" . $row['category'] . "' and line <> ".$row['line']. " and isrepacker=1 limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata)) {
                    return ['status' => false, 'msg' =>  $resultdata[0]['category'] . ' already exist', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                } else {
                    update:
                    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['editby'] = $config['params']['user'];
                    if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                        $returnrow = $this->loaddataperrecord($row['line']);
                        // $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['category']);
                        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                    } else {
                        return ['status' => false, 'msg' => 'Saving failed.'];
                    }
                }
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['category']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as r  where  r.isrepacker = 1  and line=?";

        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $limit = '';
        $filtersearch = "";
        $searcfield = $this->fields;
        $search = '';

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }
        $qry = "select " . $select . " from " . $this->table . " as r  where r.isrepacker = 1  and 1=1 " . $filtersearch . " order by line $l";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }


    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Transaction Type Master Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = $config['params']['tableid'];

        $qry = "
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    // // -> Print Function
    public function reportsetup($config)
    {
        $txtfield = $this->createreportfilter($config);
        $txtdata = $this->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }


    public function createreportfilter($config)
    {
        $fields = ['prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $user = $config['params']['user'];
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
        $paramstr = "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received,
        '' as prepared";
        return $this->coreFunctions->opentable($paramstr);
    }

    private function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select line, category, reqtype, code, position from reqcategory
         order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $data = $this->report_default_query($config);
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_transactiontype_masterfile_layout($data, $config);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            $str = $this->rpt_transtype_PDF($data, $config);
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

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('List of Repackers', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CODE', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->endrow();
        return $str;
    }

    private function rpt_transactiontype_masterfile_layout($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->rpt_default_header($data, $filters);
        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['category'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->rpt_default_header($data, $filters);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .=  '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .=  '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

    private function rpt_class_PDF_header_PDF($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

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


        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($filters);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(800, 20, 'List of Repackers', '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(202, 20, "Group", '', 'L', false, 0);
        PDF::MultiCell(166, 20, "Repacker 1", '', 'L', false, 0);
        PDF::MultiCell(166, 20, "Repacker 2", '', 'L', false, 0);
        PDF::MultiCell(166, 20, "Repacker 3", '', 'L', false, 1);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'T', 'L', false);
    }

    private function rpt_transtype_PDF($data, $filters)
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
        $this->rpt_class_PDF_header_PDF($data, $filters);

        for ($i = 0; $i < count($data); $i++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(202, 10, $data[$i]['category'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(166, 10, $data[$i]['reqtype'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(166, 10, $data[$i]['code'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(166, 10, $data[$i]['position'], '', 'L', 0, 1, '', '', true, 0, true, false);

            if (intVal($i) + 1 == $page) {
                $this->rpt_class_PDF_header_PDF($data, $filters);
                $page += $count;
            }
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



} //end loantype
