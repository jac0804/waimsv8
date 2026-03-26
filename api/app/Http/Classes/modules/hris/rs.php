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

class rs
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Re-Assignment Module';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'hrisnum';
    public $head = 'rashead';
    public $hhead = 'hrashead';
    public $detail = 'rasstock';
    public $hdetail = 'hrasstock';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    public $hrispic = 'hrisnum_picture';
    private $hrislookup;

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'ourref',
        'notedid',
        'branchid',
        'tdate1',
        'category',
        'ndesid',
        'deptid',
        'rem',
        'supid',
        'manid',
        'roleid',
        'divid',
        'sectid',
        'category'
    ];

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
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
            'view' => 5144,
            'edit' => 5145,
            'new' => 5146,
            'save' => 5147,
            'change' => 5167,
            'delete' => 5168,
            'print' => 5169,
            'unpost' => 5171,
            'lock' => 5172,
            'unlock' => 5173,
            'additem' => 5180,
            'edititem' => 5181,
            'deleteitem' => 5182
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'tempname', 'createby', 'notes', 'dategiven'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[4]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[4]['label'] = 'HRD OWN SERIAL';
        $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[1]['align'] = 'text-left';
        $cols[6]['label'] = 'Noted By';
        $cols[7]['label'] = 'Date Modified';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['h.docno', 'h.createby', 'c.clientname', 'h.editdate', 'h.ourref'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and h.lockdate is null ';
                break;
            case 'locked':
                $condition = ' and h.lockdate is not null ';
                break;
        }
        $qry = "select h.trno, h.docno, date(h.dateid) as dateid,h.createby,c.clientname as notes,date(h.editdate) as dategiven,h.ourref as tempname,
            if(h.lockdate is not null,'LOCKED','DRAFT') as status
            from " . $this->head . " as h 
            left join " . $this->tablenum . " as num on num.trno=h.trno
            left join client as c  on c.clientid=h.notedid
            where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
            order by docno desc";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2]);

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
            'lock',
            'unlock',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        data_set($buttons, 'unpost.disable', true);
        return $buttons;
    } // createHeadbutton 

    public function createTab($access, $config)
    {
        $columns = ['action', 'categoryname', 'empcode', 'empname', 'jobtitle', 'branch'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['delete', 'reassignbtn', 'viewrsdetail'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['showtotal'] = false;
        $obj[0][$this->gridname]['columns'][$categoryname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$empcode]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$empname]['style'] = "width:450px;whiteSpace: normal;min-width:450px;";
        $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
        $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:550px;whiteSpace: normal;min-width:550px;";

        $obj[0][$this->gridname]['columns'][$jobtitle]['label'] = "Prev Position";
        $obj[0][$this->gridname]['columns'][$branch]['label'] = "Old Branch";
        $obj[0][$this->gridname]['columns'][$jobtitle]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$categoryname]['align'] = 'text-left';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'PERSONS INVOLVED';
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
        $tbuttons = ['addempgrid', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = 'addempgrid';
        $obj[1]['label'] = "SAVE ALL";
        $obj[2]['label'] = 'Delete all';
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'dateid', 'ourref'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'HRIS Serial Number');
        data_set($col1, 'dateid.label', 'Date Memo');
        data_set($col1, 'ourref.label', 'HRD Own Serial');

        $fields = ['createby', 'notedby1'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'createby.type', 'input');
        data_set($col2, 'createby.label', 'Create By');
        data_set($col2, 'createby.class', 'cscreateby sbccsreadonly');
        data_set($col2, 'notedby1.readonly', true);
        data_set($col2, 'notedby1.type', 'lookup');
        data_set($col2, 'notedby1.action', 'lookupemployee');
        data_set($col2, 'notedby1.lookupclass', 'emp1lookup');
        data_set($col2, 'notedby1.label', 'Noted By');
        data_set($col2, 'notedby1.class', 'csnotedby sbccsreadonly');

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function createnewtransaction($docno, $params)
    {
        return $this->resetdata($docno, $params);
    }

    public function resetdata($docno = '', $params)
    {
        $createby = $this->coreFunctions->datareader("select name as value from (select name from useraccess 
                    where username = '" . $params['user'] . "' union all select clientname as name from client
                    where email = '" . $params['user'] . "' ) as xyz");

        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['ourref'] = '';
        $data[0]['createby'] =  $createby;
        $data[0]['notedid'] = 0;
        $data[0]['categoryname'] = '';
        $data[0]['category'] = 0;

        return $data;
    }

    public function loadheaddata($config)
    {
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

        $qryselect = "select head.trno,head.docno,date(head.dateid) as dateid, head.ourref,
                        client.clientname as notedby1,head.notedid,head.createby ";
        $qry = $qryselect . " from " . $table . " as head  
            left join $tablenum as num on num.trno = head.trno 
            left join client on client.clientid=head.notedid
            where num.trno = ? and num.doc='" . $doc . "' and num.center=? 
            union all " . $qryselect . " from " . $htable . " as head 
            left join client on client.clientid=head.notedid  
            left join $tablenum as num on num.trno = head.trno   
            where num.trno = ? and num.doc='" . $doc . "' and num.center=? ";
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
            $head = $this->resetdata('', $config);
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
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['head']['createby'];
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
        $this->coreFunctions->execqry('delete from ' . $this->hrispic . " where trno=?", 'delete', [$trno]);

        $this->coreFunctions->execqry('delete from designation where trno=?', 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    private function getstockselect($config)
    {
        $qry = "select '' as bgcolor, i.trno, i.line, i.empid,c.clientid, c.client as empcode,
         c.clientname as empname, i.jobid, j.jobtitle,i.branchid,branch.clientname as branch,
         i.supervisorid,i.deptid,i.editdate,i.editby,i.category,cat.category as categoryname ";
        return $qry;
    }

    public function openstock($trno, $config)
    {
        $select = $this->getstockselect($config);

        $qry = $select . " from " . $this->detail . " as i
            left join jobthead as j on j.line=i.jobid
            left join client as c on c.clientid=i.empid
            left join client as branch on branch.clientid =i.branchid
            left join reqcategory as cat on cat.line=i.category
            where i.trno=?
            union all "
            . $select . " from " . $this->hdetail . " as i 
            left join jobthead as j on j.line=i.jobid
            left join client as c on c.clientid=i.empid  
            left join client as branch on branch.clientid =i.branchid
            left join reqcategory as cat on cat.line=i.category
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
        left join client as br on br.clientid=c.empid
        left join client as branch on branch.clientid =i.branchid
         left join reqcategory as cat on cat.line=i.category
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
            case 'saveitem': //save all item edited
                return $this->updateitem($config);
                break;
        }
    }


    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            if ($value['line'] != 0) {
                $this->additem('update', $config);
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';

        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
        }
    } //end function


    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $category = $config['params']['data']['category'];
        $categoryname = $config['params']['data']['categoryname'];
        $line = $config['params']['data']['line'];

        // var_dump($config['params']['data']);
        $data['category'] = $category;

        switch ($categoryname) {
            case 'ASSIGNED':
                $data['ndesid'] = $config['params']['data']['jobid'];
                break;
                // case 'ALTERED':
                //     break;
        }

        // 

        return $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);
    } // end function

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
            $config['params']['data']['branchid'] = $value['branchid'];
            $config['params']['data']['supervisorid'] = $value['supervisorid'];
            $config['params']['data']['froleid'] = $value['roleid'];
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
        $fields = ['empid', 'jobid', 'branchid', 'supervisorid', 'froleid'];
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

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

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
                    'ADD EMPLOYEE',
                    'EMPLOYEE - Line:' . $line
                        . ' NAME: ' . $row['empname']
                        . ' JOB TITLE: ' . $row['jobtitle']
                );
                $this->coreFunctions->sbcupdate($this->head, ['editdate' =>  $data['editdate'], 'editby' => $data['editby']], ['trno' => $data['trno']]);
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
        $success = $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        if ($success) {
            $this->coreFunctions->execqry('delete from designation where trno=? and linex=?', 'delete',  [$trno, $line]);
            $editdate = $this->othersClass->getCurrentTimeStamp();
            $editby = $config['params']['user'];
            $this->logger->sbcwritelog($trno, $config, 'EMPLOYEE', 'REMOVED - Line:' . $line . ' EMPLOYEE:' . $data[0]->empname);
            $this->coreFunctions->sbcupdate($this->head, ['editdate' =>  $editdate, 'editby' => $editby], ['trno' => $trno]);
            return ['status' => true, 'msg' => 'Successfully deleted employee.'];
        } else {
            return ['status' => false, 'msg' => 'Deletion failed.'];
        }
    } // end function

    public function deleteallitem($config)
    {
        // $isallow = true;
        $trno = $config['params']['trno'];
        $editdate = $this->othersClass->getCurrentTimeStamp();
        $editby = $config['params']['user'];
        $success = $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete',  [$trno]);
        if ($success) {
            $this->coreFunctions->execqry('delete from designation where trno=? ', 'delete',  [$trno]);
            $editdate = $this->othersClass->getCurrentTimeStamp();
            $editby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head,   ['editdate' =>  $editdate, 'editby' => $editby], ['trno' => $trno]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
            return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
        } else {
            return ['status' => false, 'msg' => 'Unable to delete employee(s).', 'inventory' => []];
        }
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
