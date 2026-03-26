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

class holidayloc
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'HOLIDAY LOCATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'holidayloc';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['dateid', 'description', 'daytype', 'location', 'branchid'];
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
            'load' => 5216
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];

        $columns = ['action', 'dateid', 'description', 'dayname', 'location', 'branch'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:93px;whiteSpace: normal;min-width:93px;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$dayname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        if ($companyid == 58) {
            $obj[0][$this->gridname]['columns'][$location]['type'] = "coldel";

            $obj[0][$this->gridname]['columns'][$branch]['type'] = "lookup";
            $obj[0][$this->gridname]['columns'][$branch]['lookupclass'] = "lookupbranch";
            $obj[0][$this->gridname]['columns'][$branch]['action'] = "lookupsetup";
            $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        } else {
            $obj[0][$this->gridname]['columns'][$branch]['type'] = "coldel";

            $obj[0][$this->gridname]['columns'][$location]['type'] = "editlookup";
            $obj[0][$this->gridname]['columns'][$location]['lookupclass'] = "locationlist";
            $obj[0][$this->gridname]['columns'][$location]['action'] = "lookupsetup";
            $obj[0][$this->gridname]['columns'][$location]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$location]['class'] = 'sbccsenablealways';
            $obj[0][$this->gridname]['columns'][$location]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
        $data['dateid'] = date('Y-m-d');
        $data['description'] = '';
        $data['daytype'] = '';
        $data['dayname'] = '';
        $data['location'] = '';
        $data['branch'] = '';
        $data['branchid'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line, left(loc.dateid,10) as dateid, loc.description, loc.daytype, loc.location,
    (case when loc.daytype='LEG' then 'LEGAL' when loc.daytype='SP' then 'SPECIAL' else '' end) as dayname, br.clientname as branch, loc.branchid";
        return $qry;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $data['dateid'] = date("Y-m-d", strtotime($data['dateid']));

        $date = $data['dateid'];
        if (!empty($date)) {
            $qrydate = "select dateid from holidayloc where left(dateid,10) = '" . $date . "' and branchid=" . $row['branchid'];
            $countdate = $this->coreFunctions->opentable($qrydate);
        }

        if ($row['line'] == 0) {
            if (count($countdate) > 0) {
                return ['status' => false, 'msg' => 'Duplicate Date...'];
            } else {
                $line = $this->coreFunctions->insertGetId($this->table, $data);
                if ($line != 0) {
                    $returnrow = $this->loaddataperrecord($line);
                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'CREATE -' .
                            ' DATE: ' . $data['dateid'] .
                            ' DESC: ' . $data['description'] .
                            ' DAYTYPE: ' . $row['dayname']
                    );
                    return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                } else {
                    return ['status' => false, 'msg' => 'Saving failed.'];
                }
            }
        } else {
            if (count($countdate) > 1) {
                return ['status' => false, 'msg' => 'Duplicate Date...'];
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
        }
    } //end function

    public function delete($config)
    {
        $companyid = $config['params']['companyid'];

        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        if ($companyid == 58) {
            $this->logger->sbcdelmaster_log(
                $row['line'],
                $config,
                'REMOVE -' .
                    ' DESC: ' . $row['description'] .
                    ' DAYTYPE: ' . $row['dayname'] .
                    ' BRANCH: ' . $row['branch']
            );
        } else {
            $this->logger->sbcdelmaster_log(
                $row['line'],
                $config,
                'REMOVE -' .
                    ' DESC: ' . $row['description'] .
                    ' DAYTYPE: ' . $row['dayname'] .
                    ' LOCATION: ' . $row['location']
            );
        }

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as loc left join client as br on br.clientid=loc.branchid where loc.line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as loc left join client as br on br.clientid=loc.branchid order by dateid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];

        switch ($lookupclass) {
            case 'lookupdaytype':
                return $this->lookupdaytype($config);
                break;
            case 'locationlist':
                return $this->locationlisting($config);
                break;
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            case 'lookupbranch':
                return $this->lookupbranch($config);
                break;
        }
    }



    public function saveallentry($config)
    {
        $companyid = $config['params']['companyid'];

        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data[$key]['dateid'] = date("Y-m-d", strtotime($data[$key]['dateid']));

                $date = $data[$key]['dateid'];

                if (!empty($date)) {
                    $qrydate = "select dateid from holidayloc where left(dateid,10) = '" . $date . "' and branchid=" . $data2['branchid'];
                    $countdate = $this->coreFunctions->opentable($qrydate);
                }

                if ($data[$key]['line'] == 0) {
                    if (count($countdate) > 0) {
                        return ['status' => false, 'msg' => 'Duplicate Date...'];
                    } else {

                        $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                        $data2['encodedby'] = $config['params']['user'];

                        $line = $this->coreFunctions->insertGetId($this->table, $data2);

                        if ($companyid == 58) {
                            $this->logger->sbcmasterlog(
                                $line,
                                $config,
                                'CREATE -' .
                                    ' DATE: ' . $data[$key]['dateid'] .
                                    ' DESC: ' . $data[$key]['description'] .
                                    ' DAYTYPE: ' . $data[$key]['dayname'] .
                                    ' BRANCH: ' . $data[$key]['branch']
                            );
                        } else {
                            $this->logger->sbcmasterlog(
                                $line,
                                $config,
                                'CREATE -' .
                                    ' DATE: ' . $data[$key]['dateid'] .
                                    ' DESC: ' . $data[$key]['description'] .
                                    ' DAYTYPE: ' . $data[$key]['dayname'] .
                                    ' LOCATION: ' . $data[$key]['location']
                            );
                        }
                    }
                } else {
                    if (count($countdate) > 1) {
                        return ['status' => false, 'msg' => 'Duplicate Date...'];
                    } else {
                        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data2['editby'] = $config['params']['user'];
                        $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    }
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
    } // end function 

    public function lookupdaytype($config)
    {
        $plotting = array('daytype' => 'daytype', 'dayname' => 'dayname');
        $plottype = 'plotgrid';
        $title = 'List of Day Type';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'dayname', 'label' => 'Day Type', 'align' => 'left', 'field' => 'dayname', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select 'SPECIAL' as dayname, 'SP' as daytype  union all select 'LEGAL' as dayname, 'LEG' as daytype ";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } //end function

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


    public function locationlisting($config)
    {
        $plotting = array('location' => 'location');
        $plottype = 'plotgrid';
        $title = 'Locations';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:500px;max-width:500px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'location', 'label' => 'Locations', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select locname as location from emploc ";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } //end function


    public function lookupbranch($config)
    {
        //default
        $plotting = array('branch' => 'clientname', 'branchid' => 'clientid');
        $plottype = 'plotgrid';
        $title = 'List of Branches';
        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = array();
        array_push($cols,  array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

        $qry = "select clientid,client,clientname from client where isbranch=1 order by client";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }

    // -> print function
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
        $fields = ['prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
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
        $query = "select line, date(dateid) as dateid, description, daytype,location from holidayloc
        order by dateid";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportdata($config)
    {
        $data = $this->report_default_query($config);
        if ($config['params']['dataparams']['print'] == "default") {
            $str = $this->rpt_holiday_masterfile_layout($data, $config);
        } else if ($config['params']['dataparams']['print'] == "PDFM") {
            $str = $this->rpt_holiday_PDF($data, $config);
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
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('HOLIDAY SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('Description', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('Date Type', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('Location', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->endrow();
        return $str;
    }

    private function rpt_holiday_masterfile_layout($data, $filters)
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
        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['dateid'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
            $str .= $this->reporter->col($data[$i]['description'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
            $str .= $this->reporter->col($data[$i]['daytype'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
            $str .= $this->reporter->col($data[$i]['location'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
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
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .=  '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

    private function rpt_holiday_PDF_header_PDF($data, $filters)
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
        PDF::SetMargins(20, 20);

        if ($companyid == 3) {
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        } else {
            PDF::SetFont($font, '', 9);
            PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        }

        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(800, 20, "HOLIDAY TABLE LIST", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

        PDF::SetFont($fontbold, '', 11);
        // PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Date", '', 'L', false, 0);
        PDF::MultiCell(300, 20, "Description", '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Date Type", '', 'L', false, 0);
        PDF::MultiCell(200, 20, "Location", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'T', 'L', false);
    }

    private function rpt_holiday_PDF($data, $filters)
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
        $this->rpt_holiday_PDF_header_PDF($data, $filters);
        $i = 0;
        for ($i = 0; $i < count($data); $i++) {
            PDF::SetFont($font, '', $fontsize);
            // var_dump($data[$i]['dateid']);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
            // PDF::MultiCell(300, 10, $data[$i]['stockgrp_code'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(150, 10, $data[$i]['dateid'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(300, 10, $data[$i]['description'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(150, 10, $data[$i]['daytype'], '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(200, 10, $data[$i]['location'], '', 'L', 0, 1, '', '', true, 0, false, false);

            if (intVal($i) + 1 == $page) {
                $this->rpt_holiday_PDF_header_PDF($data, $filters);
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
