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

class viewprojref
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'References';
    public $gridname = 'viewrefgrid';
    public $tablenum = 'transnum';
    private $companysetup;
    private $coreFunctions;
    private $table = 'lahead';
    private $othersClass;
    public $style = 'width:100%;';
    public $showclosebtn = true;
    public $issearchshow = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        return [];
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['module', 'docno', 'dateid', 'subproject', 'stage', 'ext']]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][5]['label'] = 'Amount';

        return $obj;
    }

    public function createtabbutton($config)
    {
        return 0;
    }


    private function selectqry()
    {
        $qry = "";
        return $qry;
    }

    private function loaddataperrecord($trno)
    {
        $qry = "";
        $data = [];
        return $data;
    }

    public function loaddata($config)
    {

        $trno = $config['params']['tableid'];
        $config['params']['trno'] = $config['params']['tableid'];
        if ($this->othersClass->isposted($config)) {
            $projectid = $this->coreFunctions->getfieldvalue("hpmhead", "projectid", "trno =?", [$trno]);
        } else {
            $projectid = $this->coreFunctions->getfieldvalue("pmhead", "projectid", "trno =?", [$trno]);
        }

        $qry = "select module,docno,left(dateid,10) as dateid,subproject,stage,format(ext," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext  from (select upper(pf.master) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext 
            from lahead as head left join lastock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.doc in ('RR','SJ','MI','MT','IS','AJ') and head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.master
            union all
            select upper(pf.master) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext 
            from glhead as head left join glstock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.doc in ('RR','SJ','MI','MT','IS','AJ') and head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.master
            union all
            select upper(pf.master) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.db),0) as ext 
            from lahead as head left join ladetail as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.doc in ('cv','cr','gj','pv','pb')  and head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.master
            union all
            select upper(pf.master) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.db),0) as ext 
            from glhead as head left join gldetail as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.doc in ('cv','cr','gj','pv','pb') and head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.master
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from sohead as head left join sostock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from hsohead as head left join hsostock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from johead as head left join jostock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from hjohead as head left join hjostock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from jchead as head left join jcstock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from hjchead as head left join hjcstock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from pohead as head left join postock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from hpohead as head left join hpostock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from prhead as head left join prstock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`
            union all
            select upper(pf.`master`) as module,head.docno,head.dateid,ifnull(s.subproject,'') as subproject,ifnull(st.stage,'') as stage,ifnull(sum(stock.ext),0) as ext
            from hprhead as head left join hprstock as stock on stock.trno = head.trno
            left join projectmasterfile as p on p.line = head.projectid
            left join subproject as s on s.line = head.subproject
            left join stagesmasterfile as st on st.line = stock.stageid
            left join profile as pf on pf.psection= head.doc
            where head.projectid = " . $projectid . "
            group by head.docno,head.dateid,s.subproject,st.stage,pf.`master`) as A
            order by dateid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class
