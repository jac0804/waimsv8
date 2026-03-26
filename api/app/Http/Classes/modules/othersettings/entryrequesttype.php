<?php

namespace App\Http\Classes\modules\othersettings;

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

class entryrequesttype
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Request Type';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['reqtype', 'istype', 'ists', 'isgeneratefa'];
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
            'load' => 3765,
            'save' => 3765
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $action = 0;
        $category = 1;
        $ists = 2;
        $isgeneratefa = 3;
        $companyid = $config['params']['companyid'];

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'reqtype', 'ists', 'isgeneratefa']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:70%;whiteSpace: normal;min-width:70%;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
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

        $companyid = $config['params']['companyid'];
        $data = [];
        $data['line'] = 0;
        $data['reqtype'] = '';
        $data['istype'] = 1;
        $data['ists'] = 'false';
        $data['isgeneratefa'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry($config)

    {

        $companyid = $config['params']['companyid'];
        if ($companyid == 16) {
            $add = ", case when ists=0 then 'false' else 'true' end as ists, if(isgeneratefa=0,'false','true') as isgeneratefa";
        }

        $qry = "
        head.line, head.reqtype, head.iscat, head.istype $add";

        return $qry;
    }

    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {

                    $project_id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($project_id, $config, ' CREATE - ' . $data[$key]['reqtype']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
        $companyid = $config['params']['companyid'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line, $config);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['reqtype']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line'], $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $used = $this->coreFunctions->opentable("select h.docno from headinfotrans as info left join prhead as h on h.trno=info.trno where info.reqtypeid=?
                union all
                select h.docno from hheadinfotrans as info left join hprhead as h on h.trno=info.trno where info.reqtypeid=? limit 1", [$row['line'], $row['line']]);

        $msg = 'Successfully deleted.';
        if ($used) {
            $msg = 'Unable to delete ' . $row['reqtype'] . ' already used in ' . $used[0]->docno;
            return ['status' => false, 'msg' => $msg];
        } else {
            $qry = "delete from " . $this->table . " where line=?";
            $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
            $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['reqtype']);
            return ['status' => true, 'msg' => $msg];
        }
    }


    private function loaddataperrecord($line, $config)
    {
        $select = $this->selectqry($config);

        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
            from " . $this->table . " as head
            where line=? and istype = 1 ";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $filtersearch = "";
        $searcfield = ['head.reqtype'];
        $limit = "1000";

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

        // if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
        //     $limit = "25";
        // }
        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
        from " . $this->table . " as head
        where istype = 1 " . $filtersearch . "
        order by head.line limit " . $limit;
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
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
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
    // public function reportsetup($config)
    // {
    //     $txtfield = $this->createreportfilter($config);
    //     $txtdata = $this->reportparamsdata($config);
    //     $modulename = $this->modulename;
    //     $data = [];
    //     $style = 'width:500px;max-width:500px;';
    //     return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    // }


    // public function createreportfilter($config)
    // {
    //     $fields = ['prepared', 'approved', 'received', 'print'];
    //     $col1 = $this->fieldClass->create($fields);
    //     if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
    //         data_set($col1, 'prepared.readonly', true);
    //         data_set($col1, 'prepared.type', 'lookup');
    //         data_set($col1, 'prepared.action', 'lookupclient');
    //         data_set($col1, 'prepared.lookupclass', 'prepared');

    //         data_set($col1, 'approved.readonly', true);
    //         data_set($col1, 'approved.type', 'lookup');
    //         data_set($col1, 'approved.action', 'lookupclient');
    //         data_set($col1, 'approved.lookupclass', 'approved');

    //         data_set($col1, 'received.readonly', true);
    //         data_set($col1, 'received.type', 'lookup');
    //         data_set($col1, 'received.action', 'lookupclient');
    //         data_set($col1, 'received.lookupclass', 'received');
    //     }
    //     return array('col1' => $col1);
    // }

    // public function reportparamsdata($config)
    // {
    //     return $this->coreFunctions->opentable(
    //         "select 
    //     'PDFM' as print,
    //     '' as prepared,
    //     '' as approved,
    //     '' as received
    //     "
    //     );
    // }

    // private function report_default_query($config)
    // {
    //     $trno = $config['params']['dataid'];
    //     $query = "select line, duration, days from duration
    //   order by line";
    //     $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    //     return $result;
    // } //end fn


    // public function reportdata($config)
    // {
    //     $data = $this->report_default_query($config);
    //     if ($config['params']['dataparams']['print'] == "default") {
    //         $str = $this->rpt_model_masterfile_layout($data, $config);
    //     } else if ($config['params']['dataparams']['print'] == "PDFM") {
    //         $str = $this->rpt_model_PDF($data, $config);
    //     }
    //     return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    // }

    // private function rpt_default_header($data, $filters)
    // {

    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];
    //     $companyid = $filters['params']['companyid'];

    //     $str = '';
    //     if ($companyid == 3) {
    //         $qry = "select name,address,tel from center where code = '" . $center . "'";
    //         $headerdata = $this->coreFunctions->opentable($qry);
    //         $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    //         $str .= $this->reporter->begintable('800');
    //         $str .= $this->reporter->startrow();
    //         $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
    //         $str .= $this->reporter->endrow();

    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    //         $str .= $this->reporter->endrow();

    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    //         $str .= $this->reporter->endrow();
    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    //         $str .= $this->reporter->endrow();
    //         $str .= $this->reporter->endtable();
    //     } else {
    //         $str .= $this->reporter->begintable('800');
    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->letterhead($center, $username);
    //         $str .= $this->reporter->endrow();
    //         $str .= $this->reporter->endtable();
    //     }
    //     $str .= '<br/><br/>';

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('DURATION SETUP', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    //     $str .= $this->reporter->pagenumber('Page');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     // $str .= $this->reporter->col('Code','400',null,false,'1px solid ','B','L','Century Gothic','12','B','','2px');
    //     $str .= $this->reporter->col('DURATION', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    //     $str .= $this->reporter->col('DAYS', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    //     $str .= $this->reporter->endrow();
    //     return $str;
    // }

    // private function rpt_model_masterfile_layout($data, $filters)
    // {
    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];

    //     $str = '';
    //     $count = 35;
    //     $page = 35;

    //     $str .= $this->reporter->beginreport();
    //     $str .= $this->rpt_default_header($data, $filters);
    //     $totalext = 0;
    //     for ($i = 0; $i < count($data); $i++) {
    //         $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->addline();
    //         // $str .= $this->reporter->col($data[$i]['model_code'],'400',null,false,'1px solid ','','L','Century Gothic','11','','','3px');
    //         $str .= $this->reporter->col($data[$i]['model_name'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
    //         $str .= $this->reporter->endrow();

    //         if ($this->reporter->linecounter == $page) {
    //             $str .= $this->reporter->endtable();
    //             $str .= $this->reporter->page_break();
    //             $str .= $this->rpt_default_header($data, $filters);
    //             $str .= $this->reporter->printline();
    //             $page = $page + $count;
    //         }
    //     }

    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->printline();
    //     $str .=  '<br/>';
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    //     $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    //     $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .=  '<br/>';
    //     $str .= $this->reporter->begintable('800');
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    //     $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    //     $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();

    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->endreport();
    //     return $str;
    // } //end fn

    // private function rpt_model_PDF_header_PDF($data, $filters)
    // {
    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];
    //     $companyid = $filters['params']['companyid'];

    //     $font = "";
    //     $fontbold = "";
    //     $fontsize = 11;

    //     if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //         $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //         $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    //     }

    //     $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    //     $headerdata = $this->coreFunctions->opentable($qry);
    //     $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    //     PDF::SetTitle($this->modulename);
    //     PDF::SetAuthor('Solutionbase Corp.');
    //     PDF::SetCreator('Solutionbase Corp.');
    //     PDF::SetSubject($this->modulename . ' Module Report');
    //     PDF::setPageUnit('px');
    //     PDF::AddPage('p', [800, 1000]);
    //     PDF::SetMargins(20, 20);

    //     switch ($companyid) {
    //         case 3:
    //         case 14:
    //         case 15:
    //         case 17: //unihome
    //          case 39: //CBBSI
    //             $reporttimestamp = $this->reporter->setreporttimestamp($filters, $username, $headerdata);
    //             PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    //             break;

    //         default:

    //             PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    //             break;
    //     }

    //     PDF::SetFont($fontbold, '', 12);
    //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    //     PDF::SetFont($fontbold, '', 14);
    //     PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

    //     PDF::SetFont($font, '', 9);
    //     PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(300, 20, "DURATION", '', 'L', false, 0);
    //     PDF::MultiCell(300, 20, "DAYS", '', 'L', false, 0);
    //     PDF::MultiCell(100, 20, "", '', 'L', false);

    //     PDF::SetFont($font, '', 5);
    //     PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    //     PDF::MultiCell(100, 0, "", 'T', 'L', false);
    // }

    // private function rpt_model_PDF($data, $filters)
    // {
    //     $companyid = $filters['params']['companyid'];
    //     $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    //     $center = $filters['params']['center'];
    //     $username = $filters['params']['user'];

    //     $count = 35;
    //     $page = 35;
    //     $font = "";
    //     $fontbold = "";
    //     $border = "1px solid ";
    //     $fontsize = "10";

    //     if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //         $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //         $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    //     }
    //     $this->rpt_model_PDF_header_PDF($data, $filters);

    //     for ($i = 0; $i < count($data); $i++) {
    //         PDF::SetFont($font, '', $fontsize);
    //         // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    //         PDF::MultiCell(300, 10, $data[$i]['duration'], '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(300, 10, $data[$i]['days'], '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

    //         if (intVal($i) + 1 == $page) {
    //             $this->rpt_model_PDF_header_PDF($data, $filters);
    //             $page += $count;
    //         }
    //     }

    //     PDF::MultiCell(0, 0, "\n\n\n\n");

    //     PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    //     PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    //     PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    //     PDF::MultiCell(0, 0, "\n");

    //     PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    //     PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    //     PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    //     return PDF::Output($this->modulename . '.pdf', 'S');
    // } //end fn






























} //end class
