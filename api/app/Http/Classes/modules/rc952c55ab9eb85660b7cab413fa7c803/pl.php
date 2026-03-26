<?php

namespace App\Http\Classes\modules\rc952c55ab9eb85660b7cab413fa7c803;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use Exception;

class pl
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PACKING LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    private $reporter;
    private $helpClass;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'cntnum';
    public $statlogs = 'cntnum_stat';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    public $defaultContra = 'AR1';
    private $stockselect;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
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
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 1860,
            'edit' => 1861,
            'new' => 1862,
            'save' => 1863,
            // 'change' => 1864, remove change doc
            'delete' => 1865,
            'print' => 1866,
            'lock' => 1867,
            'unlock' => 1868,
            'post' => 1870,
            'unpost' => 1871,
            'additem' => 1872,
            'edititem' => 1873,
            'deleteitem' => 1874
        );
        return $attrib;
    }

    public function createdoclisting()
    {
        $yourref = 6;
        $postdate = 7;
        $getcols = [
            'action',
            'liststatus',
            'listdocument',
            'listdate',
            'yourref',
            'postdate',
            'listpostedby',
            'listcreateby',
            'listeditby',
            'listviewby'
        ];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

        $cols[$yourref]['label'] = 'PO No.';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$yourref]['name'] = 'yourref';
        $cols[$postdate]['label'] = 'Post Date';
        return $cols;
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
            'toggledown',
            'help',
            'others'
        );

        $buttons = $this->btnClass->create($btns);
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    }
    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['dateid'];
        $col2 = $this->fieldClass->create($fields);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function createTab($config)
    {
        $column = ['action', 'docno', 'dateid', 'amount'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => ['viewref', 'viewdiagram']
            ]
        ];

        $stockbuttons = ['delete', 'showpackinglist'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['label'] = 'PO List';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['showtotal'] = false;

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';

        $obj[0][$this->gridname]['headgridbtns']['viewref']['label'] = 'ITEM DETAILS';

        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = ['pendingsi'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];

        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = '';

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and head.lockdate is null and num.postdate is null ';
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                break;
        }


        if ($searchfilter == "") $limit = 'limit 150';
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = [
                'head.docno',
                'head.clientname',
                'head.yourref',
                'head.ourref',
                'num.postedby',
                'head.createby',
                'head.editby',
                'head.viewby',
                'head.rem'
            ];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        $qry = "select date(head.dateid) as dateid,head.trno,head.docno,head.clientname, 
        case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end as status, case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor,
        head.createby,head.editby,head.viewby,num.postedby,
        head.yourref, head.ourref
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center = ? and date(head.dateid) between ? and ? " . $condition . " " . $filtersearch . "

        union all

        select date(head.dateid) as dateid,head.trno,head.docno,head.clientname,'POSTED' as status,'grey' as statuscolor,
        head.createby,head.editby,head.viewby, num.postedby,
        head.yourref, head.ourref
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center = ? and date(head.dateid) between ? and ?  " . $condition . " " . $filtersearch . "
        order by dateid desc, docno desc $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }



    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $tablenum = $this->tablenum;
        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $center = $config['params']['center'];

        $select = "select head.clientname from lahead as head 
        left join client on client.client = head.client";

        $query = "";
        $head = $this->coreFunctions->opentable($query, [$trno, $doc, $center]);
    }
}
