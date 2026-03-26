<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;
use App\Http\Classes\modules\cdo\tr;

class hi
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'INCIDENT REPORT';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'hrisnum';
    public $head = 'incidenthead';
    public $hhead = 'hincidenthead';
    public $detail = 'incidentdtail';
    public $hdetail = 'hincidentdtail';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $hrislookup;

    private $fields = [
        'trno',
        'docno',
        'tempid',
        'fempid',
        'dateid',
        'idescription',
        'iplace',
        'idetails',
        'icomments',
        'idate',
        'tempjobid',
        'fempjobid',
        'artid',
        'sectid',
        'notedid'
    ];
    private $except = ['trno'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->hrislookup = new hrislookup;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 1179,
            'edit' => 1180,
            'new' => 1181,
            'save' => 1182,
            'change' => 1183,
            'delete' => 1184,
            'print' => 1185,
            'post' => 1186,
            'unpost' => 1187,
            'lock' => 1703,
            'unlock' => 1704,
            'additem' => 1181,
            'edititem' => 1182,
            'deleteitem' => 1184
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'tempname', 'fempname'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[4]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[1]['align'] = 'text-left';

        return $cols;
    }

    public function loaddoclisting($config)
    {
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5227);
        $id = $config['params']['adminid'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['h.docno', 'c.clientname', 'c.client'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        if ($id != 0) {
            if ($viewaccess == '0') {
                $condition .= " and (emp.supervisorid = $id or emp.empid=$id) ";
            }
        }


        $qry = "select h.trno, h.docno, date(h.dateid) as dateid, 
            c.client as empcode, c.clientname as tempname, cf.clientname as fempname, 'DRAFT' as status
            from " . $this->head . " as h 
            left join client as c on c.clientid=h.tempid
            left join client as cf on cf.clientid=h.fempid 
            left join " . $this->tablenum . " as num on num.trno=h.trno
            left join employee as emp on emp.empid=h.fempid
            where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and 
                 CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            union all
            select h.trno, h.docno, date(h.dateid) as dateid, 
            c.client as empcode, c.clientname as tempname, cf.clientname as fempname, 'POSTED' as status
            from " . $this->hhead . " as h left join client as c on c.clientid=h.tempid
            left join client as cf on cf.clientid=h.fempid 
            left join " . $this->tablenum . " as num on num.trno=h.trno
            left join employee as emp on emp.empid=h.fempid
            where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and 
                  CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
            order by docno desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'post',
            'unpost',
            'lock',
            'unlock',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton 

    public function createTab($access, $config)
    {
        $columns = ['action', 'empcode', 'empname', 'jobtitle'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['showtotal'] = false;
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$empcode]['style'] = "width:230px;whiteSpace: normal;min-width:230px;";
        $obj[0][$this->gridname]['columns'][$empname]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
        $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = "width:750px;whiteSpace: normal;min-width:750px;";
        $obj[0][$this->gridname]['columns'][$jobtitle]['readonly'] = true;
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'PERSONNEL INVOLVED';

        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addempgrid', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = 'addempgrid';
        $obj[1]['label'] = 'Delete all';
        return $obj;
    }

    public function createHeadField($config)
    {
        if ($config['params']['companyid'] == 58) { //cdo
            $fields = [['docno', 'dateid'], 'fempcode', 'fempname', 'fjobtitle', 'artcode', 'sectioncode'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'fempcode.action', 'lookupemployee');
            data_set($col1, 'fempcode.lookupclass', 'hifromjtitle');
            data_set($col1, 'fempname.label', 'Person completing this form');
            data_set($col1, 'fempcode.required', false);
            data_set($col1, 'fempname.required', false);
            data_set($col1, 'fjobtitle.required', false);
            data_set($col1, 'fempname.readonly', true);
            data_set($col1, 'fjobtitle.readonly', true);
            data_set($col1, 'fempname.class', 'csempname sbccsreadonly');
            data_set($col1, 'fjobtitle.class', 'csjobtitle sbccsreadonly');
            data_set($col1, 'sectioncode.type', 'input');
            data_set($col1, 'artcode.lookupclass', 'hiarticle');

            $fields = ['tempcode', 'tempname', 'tjobtitle', 'sectionname'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'tempcode.action', 'lookupemployee');
            data_set($col2, 'tempcode.lookupclass', 'hitojtitle');
            data_set($col2, 'tempname.label', 'Personnel Involve');
            data_set($col2, 'sectionname.type', 'textarea');

            $fields = ['iplace', ['idate', 'itime'], 'idetails']; //'idescription', 
            $col3 = $this->fieldClass->create($fields);
            data_set($col3, 'iplace.type', 'cinput');
            data_set($col3, 'idetails.type', 'textarea');

            $fields = ['icomments', 'notedby1'];
            $col4 = $this->fieldClass->create($fields);

            data_set($col4, 'icomments.type', 'textarea');
            data_set($col4, 'icomments.label', 'Action Taken / Recommendation');

            data_set($col4, 'notedby1.readonly', true);
            data_set($col4, 'notedby1.type', 'lookup');
            data_set($col4, 'notedby1.action', 'lookupemployee');
            data_set($col4, 'notedby1.lookupclass', 'emp1lookup');
            data_set($col4, 'notedby1.label', 'HR Manager');
            data_set($col4, 'notedby1.class', 'csnotedby1 sbccsreadonly');
        } else {
            $fields = ['docno', 'tempcode', 'tempname', 'tjobtitle'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'tempcode.action', 'lookupemployee');
            data_set($col1, 'tempcode.lookupclass', 'hitojtitle');
            data_set($col1, 'tempname.label', 'Personnel Involve');

            $fields = ['dateid', 'fempcode', 'fempname', 'fjobtitle'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'fempcode.action', 'lookupemployee');
            data_set($col2, 'fempcode.lookupclass', 'hifromjtitle');
            data_set($col2, 'fempname.label', 'Person completing this form');

            data_set($col2, 'fempcode.required', false);
            data_set($col2, 'fempname.required', false);
            data_set($col2, 'fjobtitle.required', false);

            data_set($col2, 'fempname.readonly', false);
            data_set($col2, 'fjobtitle.readonly', false);
            data_set($col2, 'fempname.class', 'csempname');
            data_set($col2, 'fjobtitle.class', 'csjobtitle');

            $fields = ['idescription', 'iplace', 'idate', 'itime'];
            $col3 = $this->fieldClass->create($fields);
            data_set($col3, 'idescription.type', 'cinput');
            data_set($col3, 'iplace.type', 'cinput');

            $fields = ['idetails', 'icomments'];
            $col4 = $this->fieldClass->create($fields);
            data_set($col4, 'idetails.type', 'ctextarea');
            data_set($col4, 'icomments.type', 'ctextarea');
            data_set($col4, 'icomments.label', 'Action Taken / Recommendation');
        }



        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        return $this->resetdata($docno, $params);
    }

    public function resetdata($docno = '', $params = [])
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['tempid'] = 0;
        $data[0]['tempcode'] = '';
        $data[0]['tempname'] = '';
        $data[0]['tempjobid'] = 0;
        $data[0]['tjobtitle'] = '';
        $data[0]['fempid'] = 0;
        $data[0]['fempcode'] = '';
        $data[0]['fempname'] = '';
        $data[0]['fempjobid'] = 0;
        $data[0]['fjobtitle'] = '';
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['idescription'] = '';
        $data[0]['iplace'] = '';
        $data[0]['icomments'] = '';
        $data[0]['idetails'] = '';
        $data[0]['ipdate'] = $this->othersClass->getCurrentDate();
        $data[0]['idate'] = $this->othersClass->getCurrentDate();
        $data[0]['itime'] = '00:00';

        $data[0]['artid'] = 0;
        $data[0]['sectid'] = 0;
        $data[0]['artcode'] = '';
        $data[0]['sectioncode'] = '';
        $data[0]['sectionname'] = '';

        $data[0]['notedid'] = 0;
        $data[0]['notedby1'] = '';


        if ($params['companyid'] == 58) { //cdo-hris
            if ($params['adminid'] != 0) {
                $user = $this->coreFunctions->opentable("select client.clientid, client.client, client.clientname, dept.client as deptcode, emp.deptid, emp.jobid, 
                            ifnull(job.docno,'') as docno, ifnull(job.jobtitle,'') as jobtitle
                            from employee as emp 
                            left join client on client.clientid=emp.empid
                            left join client as dept on dept.clientid=emp.deptid 
                            left join jobthead as job on job.line=emp.jobid
                            where emp.empid=?", [$params['adminid']]);
                if (!empty($user)) {
                    $data[0]['fempid'] = $user[0]->clientid;
                    $data[0]['fempcode'] = $user[0]->client;
                    $data[0]['fempname'] = $user[0]->clientname;
                    $data[0]['fempjobid'] = $user[0]->jobid;
                    $data[0]['fjobtitle'] = $user[0]->jobtitle;
                }
            }
        }


        return $data;
    }

    public function loadheaddata($config)
    {
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5227);
        $id = $config['params']['adminid'];
        $doc = $config['params']['doc'];
        $trno = $this->othersClass->val($config['params']['trno']);
        $center = $config['params']['center'];

        if ($trno == 0) $trno = $this->getlasttrno();
        $config['params']['trno'] = $trno;

        $center = $config['params']['center'];

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;

        $addselect = '';
        $leftjoin = '';
        $condition = '';
        if ($id != 0) {
            if ($viewaccess == '0') {
                $condition .= " and head.fempid=$id ";
            }
        }

        if ($config['params']['companyid'] == 58) { //cdo
            $addselect = ",note.clientname as notedby1, head.notedid";
            $leftjoin = "left join client as note on note.clientid=head.notedid";
        }

        $qryselect = "select num.trno,num.docno,ifnull(head.tempid, 0) as tempid,c.client as tempcode, 
                            c.clientname as tempname,ifnull(head.fempjobid, 0) as fempjobid, 
                            ifnull(head.fempid, 0) as fempid,cf.client as fempcode, 
                            (case when head.fempid = 0 then head.fempname else cf.clientname end) as fempname,
                            (case when head.fempid = 0 then head.fjobtitle else jf.jobtitle end) as fjobtitle,
                            ifnull(head.tempjobid, 0) as tempjobid,jt.jobtitle as tjobtitle,
                            head.dateid,head.idescription,head.iplace,head.idetails,head.icomments,
                            date(head.idate) as idate,time(head.idate) as itime,head.artid,head.sectid,
                            chead.description as artcode,cdetail.section as sectioncode,
                            cdetail.description as sectionname $addselect";

        $qry = $qryselect . " from " . $table . " as head  
            left join client as c on c.clientid=head.tempid
            left join client as cf on cf.clientid=head.fempid
            left join jobthead as jf on jf.line=head.fempjobid
            left join jobthead as jt on jt.line=head.tempjobid
            left join $tablenum as num on num.trno = head.trno   
            left join codehead as chead on chead.artid=head.artid
            left join codedetail as cdetail on cdetail.artid=head.artid and cdetail.line =head.sectid
            $leftjoin
            where num.trno = ? and num.doc='" . $doc . "' and num.center=? $condition
            union all " . $qryselect . " from " . $htable . " as head
            left join client as c on c.clientid=head.tempid
            left join client as cf on cf.clientid=head.fempid
            left join jobthead as jf on jf.line=head.fempjobid
            left join jobthead as jt on jt.line=head.tempjobid    
            left join $tablenum as num on num.trno = head.trno
            left join codehead as chead on chead.artid=head.artid
            left join codedetail as cdetail on cdetail.artid=head.artid  and cdetail.line =head.sectid
            $leftjoin
            where num.trno = ? and num.doc='" . $doc . "' and num.center=? $condition";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head = $this->resetdata('', $config['params']);
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function getlasttrno()
    {
        $last_id = $this->coreFunctions->datareader("
        select trno as value 
        from " . $this->head . " 
        union all
        select trno as value 
        from " . $this->hhead . " 
        order by value DESC LIMIT 1");

        return $last_id;
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
        }

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if    
            }
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['idate'] = $data['idate'] . " " . $head['itime'];
        $data['fempname'] = $head['fempname'];
        $data['fjobtitle'] = $head['fjobtitle'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
        }
    } // end function  

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->detail . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';
        $qry = "insert into " . $this->hhead . " (trno, docno, dateid, idescription, idate, iplace, idetails, icomments, 
                        createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, 
                        doc, tempjobid, fempjobid,fempname,fjobtitle,artid,sectid,notedid)
                select trno, docno, dateid, idescription, idate, iplace, idetails, icomments, createby, createdate, 
                        editby, editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, doc, tempjobid, 
                        fempjobid,fempname,fjobtitle,artid,sectid,notedid from " . $this->head . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($result === 1) {

            $qry = "insert into " . $this->hdetail . " (trno, line, empid, jobid) select trno, line, empid, jobid from " . $this->detail . " where trno=?";
            $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($result === 1) {
            } else {
                $msg = "Posting failed. Kindly check the detail.";
            }
        } else {
            $msg = "Posting failed. Kindly check the head data.";
        }

        if ($msg === '') {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $user];
            $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);

            $this->coreFunctions->execqry("delete from pendingapp where doc='HI' and trno=" . $trno, 'delete');

            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];
        $msg = '';

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . " (trno, docno, dateid, idescription, idate, iplace, idetails, icomments, 
                        createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, 
                        doc, tempjobid, fempjobid,fempname,fjobtitle,artid,sectid,notedid)
                select trno, docno, dateid, idescription, idate, iplace, idetails, icomments, createby, createdate, editby, 
                        editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, doc, tempjobid, fempjobid,
                        fempname,fjobtitle,artid,sectid,notedid
                from " . $this->hhead . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result === 1) {

            $qry = "insert into " . $this->detail . " (trno, line, empid, jobid) select trno, line, empid, jobid from " . $this->hdetail . " where trno=?";
            $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($result === 1) {
            } else {
                $msg = "Unposting failed. Kindly check the detail.";
            }
        } else {
            $msg = "Unposting failed. Kindly check the head data.";
        }

        if ($msg === '') {
            $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
            $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function


    private function getstockselect($config)
    {
        $qry = "select '' as bgcolor, i.trno, i.line, i.empid,c.clientid, c.client as empcode, c.clientname as empname, i.jobid, j.jobtitle";
        return $qry;
    }

    public function openstock($trno, $config)
    {
        $select = $this->getstockselect($config);

        $qry = $select . " from " . $this->detail . " as i
            left join jobthead as j on j.line=i.jobid
            left join client as c on c.clientid=i.empid  
            where i.trno=?
            union all "
            . $select . " from " . $this->hdetail . " as i 
            left join jobthead as j on j.line=i.jobid
            left join client as c on c.clientid=i.empid  
            where i.trno=?";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    } //end function

    public function openstockline($config)
    {
        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];

        $qry = $sqlselect . " 
        from " . $this->detail . " as i 
        left join jobthead as j on j.line=i.jobid
        left join client as c on c.clientid=i.empid  
        where i.trno=? and i.line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    } // end function

    public function stockstatus($config)
    {
        $lookupclass = $config['params']['action'];
        switch ($lookupclass) {
            case 'addempgrid':
                return $this->lookupcallback($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
        }
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $row = $config['params']['rows'];
        $data = [];

        $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

        if ($checking > 0) {
            return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
        }

        foreach ($row  as $key2 => $value) {
            $config['params']['data']['line'] = 0;
            $config['params']['data']['trno'] = $id;
            $config['params']['data']['empid'] = $value['clientid'];
            $config['params']['data']['empcode'] = $value['client'];
            $config['params']['data']['empname'] = $value['clientname'];
            $config['params']['data']['jobid'] = $value['jobid'];
            $config['params']['data']['jobtitle'] = $value['jobtitle'];
            $config['params']['data']['bgcolor'] = 'bg-blue-2';
            $return = $this->save($config, 'data');

            if ($return['status']) {
                array_push($data, $return['row'][0]);
            }
        }

        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } // end function


    public function save($config, $row = 'row')
    {
        $fields = ['empid', 'jobid'];
        $data = [];
        $row = $config['params'][$row];
        $doc = $config['params']['doc'];
        $id = $config['params']['trno'];
        $line = 0;

        foreach ($fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $data['trno'] = $config['params']['trno'];

        $checking = $this->coreFunctions->datareader("select count(postdate) as value 
        from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

        if ($checking > 0) {
            return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
        }

        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;
            if ($this->coreFunctions->sbcinsert($this->detail,  $data)) {
                $config['params']['line'] = $line;
                $returnrow = $this->openstockline($config);
                $this->logger->sbcwritelog(
                    $data['trno'],
                    $config,
                    'ADD WITNESS',
                    'WITNESS - Line:' . $line
                        . ' NAME: ' . $row['empname']
                        . ' JOB TITLE: ' . $row['jobtitle']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
                $config['params']['line'] = $line;
                $returnrow = $this->openstockline($config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function


    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->detail . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->logger->sbcwritelog($trno, $config, 'WITNESS', 'REMOVED - Line:' . $line . ' WITNESS:' . $data[0]->empname);
        return ['status' => true, 'msg' => 'Successfully deleted employee.'];
    } // end function

    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }


    // report startto

    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }


    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
