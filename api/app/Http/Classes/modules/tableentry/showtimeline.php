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
use App\Http\Classes\lookup\enrollmentlookup;

class showtimeline
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'TIMELINE';
    public $gridname = 'inventory';
    public $tablenum = 'cntnum';
    public $tablelogs = 'table_log';
    private $logger;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 3746, 'view' => 3746);
        return $attrib;
    }

    public function createTab($config)
    {
        $encodeddate = 0;
        $encodedby = 1;
        $postdate = 2;
        $docno = 3;
        $category = 4;
        $stat = 5;

        $tab = [$this->gridname => ['gridcolumns' => ['encodeddate', 'encodedby', 'postdate', 'docno', 'category', 'stat']]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$encodeddate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$encodedby]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$postdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$stat]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$encodeddate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$encodedby]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$postdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$stat]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        $obj[0][$this->gridname]['columns'][$encodeddate]['label'] = 'Created / Approved date';
        $obj[0][$this->gridname]['columns'][$encodedby]['label'] = 'Created / Approved by';
        $obj[0][$this->gridname]['columns'][$stat]['label'] = 'Status';
        $obj[0][$this->gridname]['columns'][$postdate]['label'] = 'Post date';
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
        $trno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0;
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0;

        $qry = "select s.encodeddate, s.encodedby, h.docno, 'Purchase Request' as category, 'Draft' as stat, null as postdate from prstock as s left join prhead as h on h.trno=s.trno where s.trno=" . $trno . " and s.line=" . $line . " and s.void =0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Request' as category,concat('Draft - Void',' (Qty: ',s.voidqty,')') as stat, null as postdate from prstock as s left join prhead as h on h.trno=s.trno where s.trno=" . $trno . " and s.line=" . $line . " and s.void =1
                union all
                select h.lockdate as encodeddate, h.lockuser as encodedby, h.docno, 'Purchase Request' as category, 'Locked' as stat, null as postdate from prstock as s left join prhead as h on h.trno=s.trno where s.trno=" . $trno . " and s.line=" . $line . " and h.lockdate is not null and s.void=0
                union all
                select h.lockdate as encodeddate, h.lockuser as encodedby, h.docno, 'Purchase Request' as category, concat('Locked - Void',' (Qty: ',s.voidqty,')') as stat, null as postdate from prstock as s left join prhead as h on h.trno=s.trno where s.trno=" . $trno . " and s.line=" . $line . " and h.lockdate is not null and s.void=1
                union all
                select h.lockdate as encodeddate, h.lockuser as encodedby, h.docno, 'Purchase Request' as category, 'Locked' as stat, num.postdate from hprstock as s left join hprhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.trno=" . $trno . " and s.line=" . $line . " and h.lockdate is not null
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Request' as category, concat('Posted',' (Total Qty: ',format(s.rrqty - s.voidqty,2),')') as stat, num.postdate from hprstock as s left join hprhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.trno=" . $trno . " and s.line=" . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Request' as category, concat('Posted - Void', ' (Qty: ',s.voidqty,')') as stat, num.postdate from hprstock as s left join hprhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.trno=" . $trno . " and s.line=" . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Canvass Sheet' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from transnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat,null as postdate from cdstock as s left join cdhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Canvass Sheet' as category, concat('Draft - Void',' Qty: ',format(s.rrqty,2),')') as stat, null as postdate from cdstock as s left join cdhead as h on h.trno=s.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Canvass Sheet' as category, 'Posted' as stat, num.postdate from hcdstock as s left join hcdhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Canvass Sheet' as category, concat('Posted - Void',' (Qty: ',format(s.rrqty,2),', Void Qty: ',format(s.voidqty,2),')') as stat, num.postdate from hcdstock as s left join hcdhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.void=1
                union all
                select s.approveddate as encodeddate, s.approvedby as encodedby, h.docno, (case when s.status=1 then 'Approved Canvass Sheet' when s.status=2 then 'Reject Canvass Sheet'  else '' end) as category, 'Posted' as stat, num.postdate from hcdstock as s left join hcdhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.approveddate is not null
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Oracle Code Request' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from transnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat,null as postdate from oqstock as s left join oqhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.reqtrno=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Oracle Code Request' as category, 'Posted' as stat, num.postdate from hoqstock as s left join hoqhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.refx=" . $trno . " and s.linex=" . $line . " and s.reqtrno=0 
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Oracle Code Request' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from transnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat, null as postdate from oqstock as s left join oqhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Oracle Code Request' as category, 'Posted' as stat, num.postdate from hoqstock as s left join hoqhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'OSI' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from transnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat,null as postdate from omstock as s left join omhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'OSI' as category, 'Posted' as stat, num.postdate from homstock as s left join homhead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Order' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from transnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat, null as postdate from postock as s left join pohead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Order' as category, concat('Draft - Void',' - ',format(s.rrqty,2),')') as stat, null as postdate from postock as s left join pohead as h on h.trno=s.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Order' as category, 'Posted' as stat, num.postdate from hpostock as s left join hpohead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Order' as category, concat('Posted - Void',' (Qty: ',format(s.rrqty,2),', Void Qty: ',format(s.voidqty,2),')')  as stat, num.postdate from hpostock as s left join hpohead as h on h.trno=s.trno left join transnum as num on num.trno=h.trno where s.reqtrno=" . $trno . " and s.reqline=" . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Receiving Report' as category, (case when num.statid = 0 then 'DRAFT' else (select oldversion from cntnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat,null as postdate from lastock as s left join lahead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno where h.doc='RR' and s.reqtrno=" . $trno . " and s.reqline = " . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Receiving Report' as category, concat('Draft - Void', ' (Qty: ',format(s.rrqty,2),')') as stat, null as postdate from lastock as s left join lahead as h on h.trno=s.trno where h.doc='RR' and s.reqtrno=" . $trno . " and s.reqline = " . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Receiving Report' as category, 'Posted' as stat, num.postdate from glstock as s left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno where h.doc='RR' and s.reqtrno= " . $trno . " and s.reqline = " . $line . " and s.void=0
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Receiving Report' as category, concat('Posted - Void',' (Qty: ',format(s.rrqty,2),')') as stat, num.postdate from glstock as s left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno where h.doc='RR' and s.reqtrno= " . $trno . " and s.reqline = " . $line . " and s.void=1
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Return' as category, 'Draft' as stat, null as postdate from lastock as s left join lahead as h on h.trno=s.trno left join glstock as rr on rr.trno=s.refx and rr.line=s.linex where h.doc='DM' and rr.reqtrno= " . $trno . " and rr.reqline = " . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Purchase Return' as category, 'Posted' as stat, num.postdate from glstock as s left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno left join glstock as rr on rr.trno=s.refx and rr.line=s.linex where h.doc='DM' and rr.reqtrno= " . $trno . " and rr.reqline = " . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Stock Issuance' as category, 'Draft' as stat, null as postdate from lastock as s left join lahead as h on h.trno=s.trno where h.doc='SS' and s.reqtrno=" . $trno . " and s.reqline = " . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Stock Issuance' as category, 'Posted' as stat, num.postdate from glstock as s left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno where h.doc='SS' and s.reqtrno= " . $trno . " and s.reqline = " . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Cash/Check Voucher' as category, (case when c.statid = 0 then 'DRAFT' else (select oldversion from cntnum_stat as stat where stat.trno=h.trno order by stat.dateid desc limit 1) end) as stat,null as postdate from ladetail as s left join lahead as h on h.trno=s.trno left join glstock as rr on rr.trno=s.refx left join cntnum as c on c.trno=h.trno where rr.reqtrno = " . $trno . " and rr.reqline = " . $line . "
                union all
                select s.encodeddate, s.encodedby, h.docno, 'Cash/Check Voucher' as category, 'Posted' as stat, num.postdate from gldetail as s left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno left join glstock as rr on rr.trno=s.refx  where rr.reqtrno = " . $trno . " and rr.reqline = " . $line . " 
                union all
                select d.encodeddate, d.encodedby, h.docno, 'Cash/Check Voucher' as category, 'Draft' as stat, null as postdate from cvitems as cv left join hpostock as po on po.trno=cv.refx and po.line=cv.linex left join lahead as h on h.trno=cv.trno left join ladetail as d on d.trno=h.trno and d.line=cv.line left join cntnum as c on c.trno=h.trno where po.reqtrno=" . $trno . " and po.reqline=" . $line . " and h.trno is not null
                union all
                select d.encodeddate, d.encodedby, h.docno, 'Cash/Check Voucher' as category, 'Posted' as stat, c.postdate from hcvitems as cv left join hpostock as po on po.trno=cv.refx and po.line=cv.linex left join glhead as h on h.trno=cv.trno left join gldetail as d on d.trno=h.trno and d.line=cv.line left join cntnum as c on c.trno=h.trno where po.reqtrno=" . $trno . " and po.reqline=" . $line . " and h.trno is not null
                order by encodeddate desc";
        return $this->coreFunctions->opentable($qry);
    }
}
