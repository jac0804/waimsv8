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

class entryarea
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Area';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'area';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['code', 'area', 'inactive'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;
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
            'load' => 4651
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $col = ['action', 'code', 'area', 'inactive'];
        foreach ($col as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $col
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

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
        $data['code'] = '';
        $data['area'] = '';
        $data['inactive'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "code,line,area,
         (case when inactive = 1 then 'true' else 'false' end) as inactive";
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        foreach ($data as $key => $value) {
            $active = $this->coreFunctions->getfieldvalue("center", "areaid", "areaid=?", [$data[$key]['line']]);
            if ($active != 0) {
                unset($this->fields[2]);
                unset($data['inactive']);
            }
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if ($data[$key]['line'] == 0 && $data[$key]['area'] != '') {
                    $qry = "select area from area where area = '" . $data[$key]['area'] . "' limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata =  json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['area'])) {
                        if (trim($resultdata[0]['area']) == trim($data[$key]['area'])) {
                            return ['status' => false, 'msg' => ' Area ( ' . $resultdata[0]['area'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                        }
                    }
                }

                if (trim($data[$key]['area'] == '')) {
                    return ['status' => false, 'msg' => 'Area is empty'];
                }
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['area']);
                } else {

                    if ($data[$key]['line'] != 0 && $data[$key]['area'] != '') {
                        $qry = "select area,line from area where area = '" . $data[$key]['area'] . "' limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata =  json_decode(json_encode($opendata), true);
                        if (!empty($resultdata[0]['area'])) {
                            if (trim($resultdata[0]['area']) == trim($data[$key]['area'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => 'Area ( ' . $resultdata[0]['area'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                                update:
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];

                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['area']);
                            }
                        } else {
                            goto update;
                        }
                    }
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
        if ($row['line'] == 0 && $row['area'] != '') {
            $qry = "select area from area where area = '" . $row['area'] . "' limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['area'])) {
                if (trim($resultdata[0]['area']) == trim($row['area'])) {
                    return ['status' => false, 'msg' => 'Area ( ' . $resultdata[0]['area'] . ' )' . 'is already exist', 'data' => [$resultdata]];
                }
            }
        }
        if (trim($row['area'] == '')) {
            return ['status' => false, 'msg' => 'Area is empty'];
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['area']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {

            if ($row['line'] != 0 && $row['area'] != '') {
                $qry = "select area,line from area where area = '" . $row['area'] . "' limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['area'])) {
                    if (trim($resultdata[0]['area']) == trim($row['area'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => 'Mode Name ( ' . $resultdata[0]['area'] . ' )' . 'is already exist', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                    } else {
                        update:
                        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data2['editby'] = $config['params']['user'];

                        $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line']]);
                        $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $row['area']);
                    }
                } else {
                    goto update;
                }
            }

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

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "select area as value from area where area=?";
        $count = $this->coreFunctions->datareader($qry, [$row['line']]);

        if ($count != '') {
            return ['rowid' => $row['line'], 'status' => false, 'msg' => 'Already have transaction...'];
        }

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['area']);
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
        $filtersearch = "";
        $searcfield = $this->fields;
        $limit = "1000";
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
            $l = " limit " . $limit;
        }

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where 1=1 " . $filtersearch . " order by area " . $l;

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
            'title' => 'Area Logs',
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


    // -> Print Function
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
        '' as received";

        $paramstr .= " ,'' as prepared ";

        return $this->coreFunctions->opentable($paramstr);
    }

    private function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select area from area
      order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];

        if ($companyid == 40) { // cdo
            $dataparams = $config['params']['dataparams'];
            if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
            if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
            if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        }

        $data = $this->report_default_query($config);
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_mode_masterfile_layout($data, $config);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            $str = $this->rpt_mode_PDF($data, $config);
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
        if ($companyid == 3) { //conti
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
        $str .= $this->reporter->col('Area', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Mode Name', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->endrow();
        return $str;
    }

    private function rpt_mode_masterfile_layout($data, $filters)
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
            $str .= $this->reporter->col($data[$i]['area'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
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

    private function rpt_mode_PDF_header_PDF($data, $filters)
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
        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(600, 20, "Area", '', 'L', false, 0);
        PDF::MultiCell(100, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'T', 'L', false);
    }

    private function rpt_mode_PDF($data, $filters)
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
        $this->rpt_mode_PDF_header_PDF($data, $filters);

        for ($i = 0; $i < count($data); $i++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(600, 10, $data[$i]['area'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

            if (intVal($i) + 1 == $page) {
                $this->rpt_mode_PDF_header_PDF($data, $filters);
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
































} //end class
