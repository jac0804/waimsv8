<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class qnn
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'QUESTIONAIRE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'qnhead';
    public $stock = 'qnstock';
    public $prefix = 'QN';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = ['qid', 'docno', 'rem', 'instructions', 'qtype'];

    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = true;
    private $reporter;
    public $issearchshow = false;
    public $style = '';

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5207,
            'view' => 5207
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = [];
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createTab($config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['qtype', 'instructions', 'rem', 'totalpoints', 'lblresult', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'qtype.type', 'input');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'instructions.type', 'ctextarea');
        data_set($col1, 'instructions.class', 'scinstructions');
        data_set($col1, 'instructions.readonly', true);
        data_set($col1, 'qtype.required', true);
        data_set($col1, 'refresh.label', 'Start');
        data_set($col1, 'refresh.action', 'startquestionaire');
        data_set($col1, 'refresh.style', 'width:100%');

        return array('col1' => $col1);
    }

    public function loadheaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $user = $config['params']['user'];
        $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $user . "'");
        $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $user . "'");
        $filter = " and qa.empid=" . $empid;
        if ($config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') $filter = " and qa.appid=" . $appid;
        $qry = "select q.qid, q.qid as clientid, q.docno as client, q.docno, q.rem , q.instructions, q.qtype, 0 as objtype, qa.total from qnhead as q left join qahead as qa on qa.qid=q.qid where q.qid = ?";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            $stock = $this->openstock($clientid, $config);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
            $hideobj['qtype'] = true;

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'griddata' => ['inventory' => $stock], 'reloadtableentry' => true, 'hideobj' => $hideobj];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...', 'reloadtableentry' => true];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
        } else {
            $data['docno'] = $head['client'];
            $head['docno'] = $head['client'];
        }
        $clientid = 0;
        $msg = '';
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
            $clientid = $head['clientid'];
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        }

        $stock = $this->openstock($clientid, $config);
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['inventory' => $stock]];
    } // end function

    public function openstock($trno, $config)
    {
        $qry = 'select qid, line, sortline, section, question, a, b, c, d, e, ans, answord, points from qnstock where qid=? order by section, sortline, line';
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];

        $this->coreFunctions->execqry('delete from ' . $this->head . ' where qid=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where qid=?', 'delete', [$clientid]);

        return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function data($config)
    {
        $user = $config['params']['user'];
        $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $user . "'");
        $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $user . "'");
        $filter = " and ex.clientid=" . $empid;
        $addleft = " and qa.empid=ex.clientid";
        if ($config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') {
            $filter = " and ex.appid=" . $appid;
            $addleft = " and qa.appid=ex.appid";
        }
        $qry = "select ex.qid, q.qtype, 0 as objtype, q.rem, q.instructions, q.startdate, qa.total as totalpoints from examinees as ex left join qnhead as q on q.qid=ex.qid left join qahead as qa on qa.qid=ex.qid " . $addleft . " where 1=1 " . $filter;
        $head = $this->coreFunctions->opentable($qry);
        return $head[0];
    }

    public function headtablestatus($config)
    {
        $action = $config['params']['action2'];
        switch ($action) {
            case 'startquestionaire':
                return $this->getQuestiondata($config);
                break;
        }
    }

    public function getQuestiondata($config)
    {
        $appid = $empid = 0;
        $filter = '';
        $data = [];
        $date = $this->othersClass->getCurrentTimeStamp();
        if ($config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') {
            $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $config['params']['user'] . "'");
            $filter = " and appid='" . $appid . "'";
        } else {
            $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $config['params']['user'] . "'");
            $filter = " and empid='" . $empid . "'";
        }
        $check = $this->coreFunctions->opentable("select qid, startdate from qahead where qid='" . $config['params']['dataparams']['qid'] . "' " . $filter);
        // $check = $this->coreFunctions->datareader("select trno as value from qahead where qid='".$config['params']['dataparams']['qid']."' ".$filter);
        if (empty($check)) {
            $center = $config['params']['center'];
            $qatrno = $this->othersClass->generatecntnum($config, 'hrisnum', 'QA', 'QA');
            $qry = "select trno,docno from hrisnum where doc = ? and trno = ? and center = ?";
            $trno_ =  $this->coreFunctions->opentable($qry, ['QA', $qatrno, $center]);

            $this->coreFunctions->execqry("insert into qahead(trno, docno, dateid, createby, createdate, qid, appid, empid, startdate) values(" . $trno_[0]->trno . ", '" . $trno_[0]->docno . "', '" . $date . "', '" . $config['params']['user'] . "', '" . $date . "', '" . $config['params']['dataparams']['qid'] . "', '" . $appid . "', '" . $empid . "', '" . $date . "')", 'insert');
            $qnstock = $this->coreFunctions->opentable("select * from qnstock where qid=" . $config['params']['dataparams']['qid']);
            if (!empty($qnstock)) {
                foreach ($qnstock as $qn) {
                    $lastline = $this->coreFunctions->datareader("select qs.line as value from qastock as qs left join qahead as q on q.trno=qs.trno where q.qid=" . $config['params']['dataparams']['qid'] . " " . $filter . " order by qs.line desc limit 1");
                    if ($lastline == '') $lastline = 0;
                    $lastline += 1;
                    $this->coreFunctions->execqry("insert into qastock(trno, line, qid, qline) values(" . $trno_[0]->trno . ", " . $lastline . ", '" . $config['params']['dataparams']['qid'] . "', '" . $qn->line . "')");
                }
            }
        } else {
            $runtime = $this->coreFunctions->datareader("select runtime as value from qnhead where qid='" . $check[0]->qid . "'");
            if ($runtime == '') {
                return ['status' => false, 'msg' => 'Invalid runtime'];
            } else {
                $addtime = '+' . $runtime . ' minutes';
                $date1 = date('Y-m-d H:i', strtotime($addtime, strtotime($check[0]->startdate)));
                $date2 = $this->othersClass->getCurrentTimeStamp();

                if ($date2 > strtotime($date1)) {
                    return ['status' => false, 'msg' => 'Your time is up'];
                }
            }
        }

        $qhead = $this->coreFunctions->opentable("select q.qtype as title, q.instructions as subtitle, q.runtime as timer, qa.startdate from qnhead as q left join qahead as qa on qa.qid=q.qid where q.qid='" . $config['params']['dataparams']['qid'] . "'");
        if (!empty($qhead)) {
            $qhead = $qhead[0];
        } else {
            $qhead = ['title' => '', 'subtitle' => '', 'timer' => 0, 'startdate' => $date];
        }
        $qdata = [];
        $qsection = $this->coreFunctions->opentable("select section from qnstock where qid='" . $config['params']['dataparams']['qid'] . "' group by section order by cast(SUBSTRING_INDEX(section, '.', 1) as unsigned) asc");
        if (!empty($qsection)) {
            foreach ($qsection as $qs) {
                $questions = $this->coreFunctions->opentable("select qid as trno, line, question as data1, objtype as dtype, sortline,
                    `a` as d1, `b` as d2, `c` as d3, `d` as d4, `e` as d5, picture as dpic, '' as uans, 'hris' as moduletype, 'qnn' as doc
                    from qnstock where qid=1 and section='" . $qs->section . "'");
                if (!empty($questions)) {
                    foreach ($questions as $qss) {
                        switch ($qss->dtype) {
                            case 0:
                            case 2:
                            case 4:
                                if ($qss->dtype == 2) $qss->uans = [];
                                $qss->options = [];
                                if ($qss->d1 != '') array_push($qss->options, ['label' => 'A. ' . $qss->d1, 'value' => 'A', 'color' => 'primary']);
                                if ($qss->d2 != '') array_push($qss->options, ['label' => 'B. ' . $qss->d2, 'value' => 'B', 'color' => 'primary']);
                                if ($qss->d3 != '') array_push($qss->options, ['label' => 'C. ' . $qss->d3, 'value' => 'C', 'color' => 'primary']);
                                if ($qss->d4 != '') array_push($qss->options, ['label' => 'D. ' . $qss->d4, 'value' => 'D', 'color' => 'primary']);
                                if ($qss->d5 != '') array_push($qss->options, ['label' => 'E. ' . $qss->d5, 'value' => 'E', 'color' => 'primary']);
                                break;
                            case 3:
                                $qss->options = [
                                    ['label' => 'A', 'value' => 'A', 'color' => 'primary'],
                                    ['label' => 'B', 'value' => 'B', 'color' => 'primary'],
                                    ['label' => 'C', 'value' => 'C', 'color' => 'primary'],
                                    ['label' => 'D', 'value' => 'D', 'color' => 'primary'],
                                    ['label' => 'E', 'value' => 'E', 'color' => 'primary']
                                ];
                                break;
                        }
                    }
                }
                $qdata[$qs->section] = $questions;
            }
            $qdata['submit'][] = ['trno' => 0, 'line' => 0, 'data1' => 'Submit', 'dtype' => 5, 'sortline' => 999, 'd1' => '', 'd2' => '', 'd3' => '', 'd4' => '', 'd5' => '', 'dpic' => '', 'uans' => '', 'moduletype' => 'hris', 'doc' => 'qnn'];
        }
        $data['head'] = $qhead;
        $data['data'] = $qdata;
        return ['status' => true, 'msg' => '', 'data' => $data, 'action' => 'openblanklayout'];
    }

    public function submitquestionnaire($config)
    {
        $head = $config['params']['data']['head'];
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = $config['params']['data']['data'];
        $type = $config['params']['type'];
        $logintype = $config['params']['logintype'];
        $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $config['params']['user'] . "'");
        $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $config['params']['user'] . "'");
        $filter = " and empid=" . $empid;
        if ($logintype == '4e02771b55c0041180efc9fca6e04a77') $filter = " and appid=" . $appid;
        $totalpoints = 0;
        if (!empty($data)) {
            foreach ($data as $section) {
                foreach ($section as $d) {
                    $data2 = $d;
                    unset($data2['options']);
                    // $data2 = $this->othersClass->sanitize($data2, 'ARRAY');
                    $ans = $this->coreFunctions->opentable("select ans, answord, points from qnstock where qid=" . $data2['trno'] . " and line=" . $data2['line']);
                    switch ($data2['dtype']) {
                        case 0:
                        case 3:
                        case 4:
                            if ($ans[0]->ans == $data2['uans']) $totalpoints += $ans[0]->points;
                            break;
                        case 1:
                            if ($ans[0]->answord == $data2['uans']) $totalpoints += $ans[0]->points;
                            break;
                        case 2:
                            $answers = explode(',', $ans[0]->ans);
                            if (array_diff($answers, $data2['uans']) == [] && array_diff($data2['uans'], $answers) == []) $totalpoints += $ans[0]->points;
                            $data2['uans'] = implode(',', $data2['uans']);
                            break;
                    }
                    if ($data2['dtype'] == 1) {
                        $this->coreFunctions->execqry("update qastock set answord='" . $data2['uans'] . "' where qid=" . $data2['trno'] . " and qline=" . $data2['line'], 'update');
                    } else {
                        $this->coreFunctions->execqry("update qastock set ans='" . $data2['uans'] . "' where qid=" . $data2['trno'] . " and qline=" . $data2['line'], 'update');
                    }
                    if ($type == '') {
                        $this->coreFunctions->execqry("update qahead set enddate='" . $date . "', total='" . $totalpoints . "' where qid=" . $data2['trno'] . " " . $filter, 'update');
                    } else {
                        $this->coreFunctions->execqry("update qahead set rem='Time is up', total='" . $totalpoints . "' where qid=" . $data2['trno'] . " " . $filter, 'update');
                    }
                }
            }
            return ['status' => true, 'msg' => 'Result saved.'];
        }
    }

    public function hideobj($config)
    {
        $hideobj = [];
        $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $config['params']['user'] . "'");
        $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $config['params']['user'] . "'");
        $filter = " and ex.clientid=" . $empid;
        $addleft = " left join qahead as qa on qa.qid=ex.qid and qa.empid=ex.clientid";
        if ($config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') {
            $filter = " and ex.appid=" . $appid;
            $addleft = " left join qahead as qa on qa.qid=ex.qid and qa.appid=ex.appid";
        }
        $qry = "select ex.qid, q.qtype, 0 as objtype, q.rem, q.instructions, qa.startdate, qa.enddate, qa.rem
            from examinees as ex
            left join qnhead as q on q.qid=ex.qid " . $addleft . "
            where 1=1 " . $filter;
        $ex = $this->coreFunctions->opentable($qry);
        if (!empty($ex)) {
            if ($ex[0]->enddate != null || ($ex[0]->rem != '' || $ex[0]->rem != null)) {
                $hideobj['refresh'] = true;
            } else {
                $hideobj['lblresult'] = true;
                $hideobj['totalpoints'] = true;
            }
        }
        return $hideobj;
    }
}
