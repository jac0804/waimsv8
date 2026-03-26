<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;

class reassignment
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $sqlquery;
    private $logger;

    public $modulename = 'Re-Assignment';
    public $gridname = 'inventory';
    private $fields = ['branchid', 'category', 'tdate1', 'roleid', 'divid', 'todeptid', 'sectid', 'rem', 'supid', 'ndesid', 'tobranchid', 'locname'];
    private $table = 'rasstock';

    public $tablelogs = 'hrisnum_log';
    public $style = 'width:50%;max-width:50%;height:100%';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
    }

    public function createHeadField($config)
    {
        $doc = $config['params']['doc'];

        $fields = [
            'lblcleared',
            'empcode',
            'lblitemdesc',
            'branchname',
            'lblaccessories',
            'rsbranch',
            ['lblcostuom', 'lbldepreciation'],
            ['tdate1', 'rolename'],
            ['lbllocation', 'lblvehicleinfo'],
            ['categoryname', 'divname'],
            ['lblsource', 'lbldestination'],
            ['jobcode', 'deptname'],
            ['lblpassbook', 'lblreconcile'],
            ['supervisor', 'sectionname'],
            'lblearned',
            'rem',
            'refresh'
        ]; //'lblbilling', 'locname',
        $col1 = $this->fieldClass->create($fields);

        //employee
        data_set($col1, 'lblcleared.label', 'Employee Name');
        data_set($col1, 'empcode.style', 'font-weight:bold');

        data_set($col1, 'empcode.name', 'employee');
        data_set($col1, 'empcode.label', '');
        data_set($col1, 'empcode.type', 'input');

        //From Destination
        data_set($col1, 'lblitemdesc.label', 'From Destination');
        data_set($col1, 'branchname.type', 'input');
        data_set($col1, 'branchname.style', 'width:100%');
        data_set($col1, 'branchname.label', '');

        //To Destination
        data_set($col1, 'lblaccessories.label', 'To Destination');
        data_set($col1, 'rsbranch.label', '');

        //Location
        // data_set($col1, 'lblbilling.label', 'Location');
        // data_set($col1, 'locname.label', '');

        //Date Effective
        data_set($col1, 'lblcostuom.label', 'Date Effective');
        data_set($col1, 'lblcostuom.style', 'font-weight:bold');
        data_set($col1, 'tdate1.label', '');
        data_set($col1, 'tdate1.required', true);
        data_set($col1, 'tdate1.readonly', false);
        data_set($col1, 'tdate1.class', 'cstdate1 sbccsreadonly');

        //Role
        data_set($col1, 'lbldepreciation.label', 'Role');
        data_set($col1, 'lbldepreciation.style', 'font-weight:bold');
        data_set($col1, 'rolename.label', '');
        data_set($col1, 'rolename.lookupclass', 'rsrole');

        //Category
        data_set($col1, 'lbllocation.label', 'Category');
        data_set($col1, 'lbllocation.style', 'font-weight:bold');
        data_set($col1, 'categoryname.label', '');
        data_set($col1, 'categoryname.lookupclass', 'rscategory');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.required', true);

        //Company
        data_set($col1, 'lblvehicleinfo.label', 'Company');
        data_set($col1, 'lblvehicleinfo.style', 'font-weight:bold');
        data_set($col1, 'divname.label', '');


        //New Designation
        data_set($col1, 'lblsource.label', 'New Designation');
        data_set($col1, 'jobcode.action', 'lookupjobtitle');
        data_set($col1, 'jobcode.lookupclass', 'newdesignation');
        data_set($col1, 'jobcode.label', '');

        //New Department
        data_set($col1, 'lbldestination.label', 'New Department');
        data_set($col1, 'deptname.label', '');

        //Immediate Superior
        data_set($col1, 'lblpassbook.label', 'Immediate Superior');
        data_set($col1, 'sectionname.label', '');
        data_set($col1, 'supervisor.label', '');
        data_set($col1, 'supervisor.readonly', true);
        // data_set($col1, 'supervisor.lookupclass', 'lookupsuperior');
        // data_set($col1, 'supervisor.action', 'lookupemployee');
        // data_set($col1, 'supervisor.type', 'lookup');
        data_set($col1, 'supervisor.class', 'cssupervisor sbccsreadonly');

        //Section        
        data_set($col1, 'lblreconcile.label', 'Section');

        //Notation
        data_set($col1, 'lblearned.label', 'Notation - Details');
        data_set($col1, 'rem.label', '');
        data_set($col1, 'rem.readonly', false);

        data_set($col1, 'refresh.label', 'update');

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        return $this->getheaddata($config, $trno, $line, $config['params']['doc']);
    }

    public function getheaddata($config, $trno, $line, $doc)
    {

        // 2026 Mar 12 [FMM] - revised query to gather latest info from employee instead of rasstock
        // due to rasstock details is empty if employee is newly added

        $maintable = 'stock';
        $joblink = 'stock.ndesid';
        $suplink = 'stock.supid';
        $loclink = 'stock.locname';
        $exist_designation = $this->coreFunctions->datareader("select trno as value from designation where trno=" . $trno . " and line=" . $line, [], '', true);
        if ($exist_designation == 0) {
            $maintable = 'emp';
            $joblink = 'emp.jobid';
            $suplink = 'emp.supervisorid';
            $loclink = 'emp.emploc';
        }
        $qry = "select stock.trno,stock.line,stock.empid,concat(emp.emplast,',',emp.empfirst,' ',emp.empmiddle) as employee,
                    ifnull(branch.clientname,'') as branchname,ifnull(branch.client,'') as branchcode,stock.branchid,
                    stock.tobranchid, tobranch.client as tobranchcode,concat(tobranch.client,'~',tobranch.clientname)  as rsbranch,
                    tobranch.clientname as tobranchname,
                    date(stock.tdate1) as tdate1,stock.roleid,role.name as rolename,
                    ifnull(cat.category,'') as categoryname,ifnull(cat.line,'') as category,
                    stock.divid,ifnull(division.divname, '') as divname, stock.deptid,ifnull(d.clientname,'') as deptname,
                    superv.clientname as supervisor,ifnull(j.line,0) as ndesid,j.jobtitle as jobcode,
                    stock.sectid,ifnull(sect.sectname, '') as sectionname,stock.rem,ifnull(loc.locname,'') as locname,stock.supid,stock.froleid,loc.line as locid
                from rasstock as stock
                left join employee as emp on emp.empid=stock.empid
                left join rashead as head on head.trno=stock.trno
                left join hrisnum as num on num.trno = head.trno
                left join client as branch on branch.clientid = " . $maintable . ".branchid
                left join client as tobranch on tobranch.clientid = stock.tobranchid
                left join rolesetup as role on role.line = " . $maintable . ".roleid
                left join reqcategory as cat on cat.line=stock.category
                left join division on division.divid = " . $maintable . ".divid
                left join client as d on d.clientid = " . $maintable . ".deptid
                left join client as superv on superv.clientid = " . $suplink . "
                left join section as sect on sect.sectid = " . $maintable . ".sectid
                left join jobthead as j on j.line=" . $joblink . "
                left join emploc as loc on loc.locname=" . $loclink . "
                where stock.trno=? and stock.line=?";

        $this->coreFunctions->LogConsole($trno . '-' . $line);
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

        if (!empty($data)) {
            return $data;
        } else {
            $data = [];
            $row['trno'] = $trno;
            $row['linex'] =  $line;
            $row['line'] = 0;
            $row['branchid'] = 0;
            $row['branchcode'] = '';
            $row['branchname'] = '';

            $row['rsbranch'] = '';
            $row['tobranchid'] = 0;

            $row['locid'] = 0;
            $row['locname'] = '';
            $row['tdate1'] = $this->othersClass->getCurrentDate();

            $row['froleid'] = 0;
            $row['roleid'] = 0;
            $row['rolename'] = '';
            $row['divid'] = 0;
            $row['divname'] = '';
            $row['deptid'] = 0;
            $row['dept'] = '';
            $row['deptname'] = '';
            $row['sectid'] = 0;
            $row['sectname'] = 0;

            $row['category'] = 0;
            $row['categoryname'] = '';

            $row['ndesid'] = 0;
            $row['jobcode'] = '';

            $row['supid'] = 0;
            $row['supervisor'] = '';

            $row['rem'] = '';


            array_push($data, $row);
            return $data;
        }
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $designation = [];
        $designation2 = [];
        $data3 = [];
        $trno = $config['params']['dataparams']['trno'];
        $line = $config['params']['dataparams']['line'];

        $empname = $config['params']['dataparams']['employee'];
        $empid = $config['params']['dataparams']['empid'];
        $tobranchid = $config['params']['dataparams']['tobranchid'];
        $tdate1 = $config['params']['dataparams']['tdate1'];
        $roleid = $config['params']['dataparams']['roleid'];
        $category = $config['params']['dataparams']['category'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $divid = $config['params']['dataparams']['divid'];
        $deptid = $config['params']['dataparams']['deptid'];
        $supid = $config['params']['dataparams']['supid'];
        $ndesid = $config['params']['dataparams']['ndesid'];
        $sectid = $config['params']['dataparams']['sectid'];
        $rem = $config['params']['dataparams']['rem'];
        $locname = $config['params']['dataparams']['locname'];
        $locid = $config['params']['dataparams']['locid'];

        if (empty($category)) {
            $category = 0;
        }

        if (empty($locname)) {
            $locid = 0;
        }

        if ($empid == $supid) {
            return ['status' => false, 'msg' => 'Invalid immediate supervisor.'];
        }

        //stock table
        $data = [
            'trno' => $trno,
            'line' => $line,
            'empid' => $empid,
            'tobranchid' => $tobranchid,
            'tdate1' => $tdate1,
            'roleid' => $roleid,
            'category' => $category,
            'divid' => $divid,
            'deptid' => $deptid,
            'ndesid' => $ndesid,
            'sectid' => $sectid,
            'rem' => $rem,
            'locname' => $locname
        ];

        //employee table
        $data2 = [
            'empid' => $empid,
            'branchid' => $tobranchid,
            'roleid' => $roleid,
            'jobid' => $ndesid,
            'emploc' => $locname,
            'divid' => $divid,
            'deptid' => $deptid,
            'sectid' => $sectid
        ];

        //designation table
        $designation = [
            'trno' => $trno,
            'linex' => $line,
            'empid' => $empid,
            'branchid' => $tobranchid,
            'roleid' => $roleid,
            'jobid' => $ndesid,
            'locid' => $locid,
            'effectdate' => $tdate1,
            'category' => $category,
            'notation' => $rem,
            'divid' => $divid,
            'deptid' => $deptid,
            'sectid' => $sectid,
            'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
            'encodedby' => $config['params']['user']
        ];


        $tablename = 'rasstock';

        foreach ($data as $key => $v) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }


        $data['supid'] = $supid;
        $data2['supervisorid'] = $supid;
        $designation['supervisorid'] = $supid;


        ////////////////////

        // $categoryname = $config['params']['dataparams']['categoryname'];

        // if ($categoryname == 'REASSIGNED') {
        //     $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$empid]);

        //     if ($issupervisor == 1) {

        //         if ($empid != $supid) {
        //             $this->logger->sbcwritelog($roleid, $config, 'REASSIGNMENT', 'Auto-update supervisor', 'masterfile_log');

        //             $updaterole = $this->coreFunctions->execqry("update rolesetup set supervisorid='" . $empid . "', 
        //                 editdate='" . $this->othersClass->getCurrentTimeStamp() . "', editby='" . $config['params']['user'] . "' where line='" . $roleid . "'", 'update');

        //             // var_dump($updaterole);
        //             if ($updaterole) {
        //                 $qry = "select empid,branchid,roleid,jobid,divid,deptid,sectid from employee where roleid = '" . $roleid . "' and empid <> '" . $empid . "'";

        //                 $empid2 = $this->coreFunctions->opentable($qry);
        //                 $category2 = $this->coreFunctions->getfieldvalue("reqcategory", "line", "category='ALTERED'");

        //                 for ($i = 0; $i < count($empid2); $i++) {

        //                     //employee - update supervisor
        //                     $data3 = [
        //                         'supervisorid' => $empid,
        //                         'editby' => $this->othersClass->getCurrentTimeStamp(),
        //                         'editby' => $config['params']['user'],
        //                     ];

        //                     //designation - since change of supervisor auto insert in designation table
        //                     $designation2 = [
        //                         'trno' => $trno,
        //                         'linex' => $line,
        //                         'empid' => $empid2[$i]->empid,
        //                         'branchid' => $empid2[$i]->branchid,
        //                         'roleid' => $empid2[$i]->roleid,
        //                         'jobid' => $empid2[$i]->jobid,
        //                         'effectdate' => $tdate1,
        //                         'category' => $category2,
        //                         'notation' => $rem,
        //                         'divid' => $empid2[$i]->divid,
        //                         'deptid' => $empid2[$i]->deptid,
        //                         'sectid' => $empid2[$i]->sectid,
        //                         'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
        //                         'encodedby' => $config['params']['user'],
        //                         'supervisorid' => $empid
        //                     ];

        //                     $emp2 = $this->coreFunctions->sbcupdate('employee', $data3, ['empid' => $empid2[$i]->empid]);
        //                     if ($emp2) {
        //                         $this->logger->sbcwritelog($empid2[$i]->empid, $config, 'REASSIGNMENT', 'Auto-update supervisor', 'client_log');

        //                         $this->coreFunctions->sbcinsert('designation', $designation2);
        //                     }
        //                 }
        //             }
        //             // 2026 Jan 12 [FMM] - remove, dapat hindi ma-assign supervisor sa sarili nya
        //             // $data['supid'] = $empid;
        //             // $data2['supervisorid'] = $empid;
        //             // $designation['supervisorid'] = $empid;
        //         }
        //     }
        // }


        /////////////////

        $blnPending = false;

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
        if ($designation['effectdate'] <= $this->othersClass->getCurrentDate()) {
            // update only if effective date is today
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $emp = $this->coreFunctions->sbcupdate('employee', $data2, ['empid' => $empid]);
        } else {
            $emp = 1;
            $blnPending = true;
        }
        if ($emp) {

            $qry = "select line as value from designation where empid= ? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['empid']]);
            if ($line == '') {
                $line = 0;
            }

            $designation['line'] = $line + 1;
            if ($this->coreFunctions->sbcinsert('designation', $designation)) {
                $this->logger->sbcwritelog($trno, $config, 'UPDATE EMPLOYEE', $empname . ' Category: ' . $categoryname);

                if ($blnPending) {
                    if ($config['params']['adminid'] != 0) {
                        $url = 'App\Http\Classes\modules\hris\\' . 'rs';
                        $this->coreFunctions->execqry("delete from pendingapp where trno=" . $trno . " and line = '" . $config['params']['dataparams']['line'] . "' and  doc='RS'", 'delete');
                        $this->othersClass->insertUpdatePendingapp($trno, $config['params']['dataparams']['line'], 'RS', $data, $url, $config, $config['params']['adminid'], false, true, 'REASSIGNMENT');
                    }
                }
            }
        }

        $doc = $config['params']['doc'];
        $modtype = $config['params']['moduletype'];
        $path = 'App\Http\Classes\modules\\' . strtolower($modtype) . '\\' . strtolower($doc);
        $config['params']['trno'] = $trno;
        $detail = app($path)->openstock($trno, $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadgriddata' => ['inventory' => $detail]];
    }
}
