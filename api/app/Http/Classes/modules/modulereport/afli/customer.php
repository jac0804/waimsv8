<?php

namespace App\Http\Classes\modules\modulereport\afli;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class customer
{

    private $modulename;
    private $reportheader;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->reportheader = new reportheader;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'docno', 'start', 'radiocustreporttype', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);

        data_set($col1, 'radiocustreporttype.options', [
            ['label' => 'Accounts Receivable', 'value' => 'ar', 'color' => 'red'],
            ['label' => 'Accounts Payable', 'value' => 'ap', 'color' => 'red'],
            ['label' => 'Postdated Checks', 'value' => 'pdc', 'color' => 'red'],
            ['label' => 'Return Checks', 'value' => 'rc', 'color' => 'red'],
            ['label' => 'Inventory', 'value' => 'stock', 'color' => 'red'],
            ['label' => 'Borrower Loan', 'value' => 'bloan', 'color' => 'red']
        ]);

        data_set($col1, 'docno.action', 'lookupapprovedloan');
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $user = $config['params']['user'];
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
        $paramstr = "select
            'PDFM' as print,
            '' as docno,
            '' as trno,
            left(now(),10) as start,
            'ar' as reporttype,
            '' as approved,
            '' as received,'' as prepared ";

        return $this->coreFunctions->opentable($paramstr);
    }

    public function generateResult($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        switch ($reporttype) {
            case 'ar':
                $query = $this->default_AR_QUERY($config);
                break;
            case 'ap':
                $query = $this->default_AP_QUERY($config);
                break;
            case 'pdc':
                $query = $this->default_PDC_QUERY($config);
                break;
            case 'rc':
                $query = $this->default_RC_QUERY($config);
                break;
            case 'stock':
                $query = $this->default_STOCK_QUERY($config);
                break;
            case 'bloan':
                $query = $this->bheadloan_QUERY($config);
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function reportplotting($config, $data)
    {
        ini_set('memory_limit', '-1');
        $data = $this->generateResult($config);
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 'ar':
                $str = $this->reportdefaultAR_PDF($config, $data);
                break;
            case 'ap':
                $str = $this->reportdefaultAP_PDF($config, $data);
                break;
            case 'pdc':
                $str = $this->reportdefaultPDC_PDF($config, $data);
                break;
            case 'rc':
                $str = $this->reportdefaultRC_PDF($config, $data);
                break;
            case 'stock':
                $str = $this->reportdefaultSTOCK_PDF($config, $data);
                break;
            case 'bloan':
                $gqry = $this->gridloan_QUERY($config);
                $gdata = $this->coreFunctions->opentable($gqry);
                $str = $this->reportbloan_PDF($config, $data, $gdata);
                break;
        }
        return $str;
    }

    public function default_AR_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref,
        t.agent, t.rem, t.status,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,
        client.email,client.contact,client.fax,t.dateid as dateid2 from
        (
        select cntnum.doc as doc,arledger.docno,arledger.trno as trno,
        arledger.line as line,arledger.dateid as dateid,arledger.db as db,
        arledger.cr as cr,arledger.bal,
        arledger.clientid as clientid,arledger.ref as ref,agent.client as agent,
        (detail.rem) as rem,((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,
        0 as fbal,head.ourref as reference,'posted' as status from ((((arledger
        left join cntnum on((cntnum.trno = arledger.trno))) left join gldetail as detail
        on(((detail.trno = arledger.trno) and (detail.line = arledger.line))))
        left join glhead as head on((head.trno = cntnum.trno))) left join client agent
        on((agent.clientid = arledger.agentid))) left join client on client.clientid = arledger.clientid where md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'
        and cntnum.center = '$center'
        union all
        select head.doc as doc,head.docno,head.trno as trno,detail.line as line,head.dateid as dateid,
        detail.db as db,detail.cr as cr,round(abs((detail.db - detail.cr)),2) as bal,
        client.clientid as clientid,'' as ref,'' as agent,detail.rem as rem,
        abs((detail.db - detail.cr)) as balance,0 as fbal,'' as reference,'' as status
        from (((lahead as head left join ladetail as detail on((detail.trno = head.trno)))
        left join client on((client.client = head.client))) left join coa on((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)= '$clientid'  and head.dateid>='$start' and
        left(coa.alias,2) = 'ar'  and cntnum.center = '$center'
        union all
        select cntnum.doc as doc,arledger.docno,arledger.trno as trno,arledger.line as line,
        arledger.dateid as dateid,arledger.db as db,arledger.cr as cr,arledger.bal,
        arledger.clientid as clientid,arledger.ref as ref,agent.client as agent,(detail.rem) as rem,
        ((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,0 as fbal,
        head.ourref as reference,'posted' as status from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join cntnum on cntnum.trno = head.trno
        left join arledger on arledger.trno = detail.trno and arledger.line = detail.line
        left join client agent on agent.clientid = arledger.agentid left join client on client.clientid = arledger.clientid where
        md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'  and cntnum.center = '$center'
        ) as t left join client on client.clientid = t.clientid
        order by dateid2, docno";
        return $query;
    }

    public function default_AP_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref, t.rem, t.status,
        client.client,client.agent,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax from
        (select cntnum.doc as doc,apledger.docno,apledger.trno as trno,apledger.line as line,
        apledger.dateid as dateid,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,
        (detail.rem) as rem,((case when (apledger.db > 0) then 1 else -(1) end) * apledger.bal) as balance,
        0 as fbal,head.ourref as reference,'posted' as status from ((((apledger
        left join cntnum on((cntnum.trno = apledger.trno))) left join gldetail as detail
        on(((detail.trno = apledger.trno) and (detail.line = apledger.line))))
        left join glhead as head on((head.trno = cntnum.trno)))) left join client on client.clientid = apledger.clientid where md5(apledger.clientid)='$clientid' and apledger.dateid>='$start'
        and cntnum.center='$center'
        union all
        select head.doc as doc,head.docno,head.trno as trno,detail.line as line,head.dateid as dateid,
        detail.db as db,detail.cr as cr,round(abs((detail.db - detail.cr)),2) as bal,
        client.clientid as clientid,'' as ref,'' as agent,detail.rem as rem,
        abs((detail.db - detail.cr)) as balance,0 as fbal,'' as reference,'' as status
        from (((lahead as head left join ladetail as detail on((detail.trno = head.trno)))
        left join client on((client.client = head.client))) left join coa on((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)='$clientid' and head.dateid>='$start' and
        left(coa.alias,2) = 'ap'  and md5(cntnum.center) = '$center'
        union all
        select cntnum.doc as doc,apledger.docno,apledger.trno as trno,apledger.line as line,
        apledger.dateid as dateid,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,(detail.rem) as rem,
        ((case when (apledger.db > 0) then 1 else -(1) end) * apledger.bal) as balance,0 as fbal,
        head.ourref as reference,'posted' as status from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join cntnum on cntnum.trno = head.trno
        left join apledger on apledger.trno = detail.trno and apledger.line = detail.line left join client on client.clientid = apledger.clientid
        where  md5(apledger.clientid)='$clientid'  and apledger.dateid>='$start'  and md5(cntnum.center) = '$center'
        ) as t left join client on client.clientid = t.clientid  order by dateid, docno";

        return $query;
    }

    public function default_PDC_QUERY($config)
    {
        ini_set('max_execution_time', 0);
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select customerpdc.trno, customerpdc.doc, customerpdc.docno, customerpdc.checkno, customerpdc.checkdate, customerpdc.db,
        customerpdc.cr,ifnull(customerpdc.rem,'') as rem,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax, customerpdc.ref, customerpdc.agentname
        from (
        select glhead.doc,coa.alias,glhead.trno, glhead.docno, gldetail.checkno, gldetail.postdate as checkdate, gldetail.db,
        gldetail.cr,crledger.depodate,concat(gldetail.rem,'  ',deposit.docno) as rem,client.clientid, gldetail.ref,
        agent.clientname as agentname
        from glhead
        left join gldetail on gldetail.trno=glhead.trno
        left join crledger on crledger.trno=gldetail.trno
        left join client on client.clientid = gldetail.clientid
        left join coa on coa.acnoid=gldetail.acnoid
        left join client as agent on glhead.agentid = client.clientid
        left join deposit on deposit.refx = crledger.trno and deposit.linex = crledger.line where left(coa.alias,2)='cr' and crledger.depodate is null and glhead.doc='cr'
        union all
        select lahead.doc,coa.alias,lahead.trno, lahead.docno, ladetail.checkno, ladetail.postdate, ladetail.db,
        ladetail.cr,null as depodate,ladetail.rem as rem,client.clientid, ladetail.ref,
        agent.clientname as agentname
        from lahead
        left join ladetail on ladetail.trno=lahead.trno
        left join client on client.client = ladetail.client
        left join client as agent on agent.client = lahead.agent
        left join coa on coa.acnoid=ladetail.acnoid where
        left(coa.alias,2)='cr'
        ) as customerpdc
        left join client on client.clientid = customerpdc.clientid where md5(client.clientid) ='$clientid' and  checkdate>='$start'";

        return $query;
    }

    public function default_RC_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select cntnum.doc as doc,arledger.docno as docno,arledger.trno as trno,arledger.line as line,
        arledger.dateid as dateid,arledger.db as db,
        arledger.cr as cr,(case when (arledger.bal = 0) then 'applied' else ltrim(arledger.bal) end) as bal,arledger.clientid as clientid,
        arledger.ref as ref,agent.client as agent,gldetail.rem as rem,
        arledger.bal as balance
        from arledger
        left join glhead on arledger.trno = glhead.trno
        left join cntnum on cntnum.trno = arledger.trno
        left join coa on coa.acnoid = arledger.acnoid
        left join gldetail on gldetail.trno = arledger.trno
        and gldetail.line = arledger.line
        left join client as agent on agent.clientid = arledger.agentid
        where coa.alias = 'arb'
        and md5(arledger.clientid)= '$clientid'
        and glhead.dateid>='$start'
        and cntnum.center = '$center'
        union all
        select lahead.doc as doc,lahead.docno as docno,lahead.trno as trno,ladetail.line as line,lahead.dateid as dateid,ladetail.db as db,
        ladetail.cr as cr,abs((ladetail.db - ladetail.cr)) as bal,client.clientid as clientid,ladetail.ref as ref,'' as agent,
        ladetail.rem as rem,abs((ladetail.db - ladetail.cr)) as balance
        from lahead
        left join ladetail on ladetail.trno = lahead.trno
        left join client on client.client = ladetail.client
        left join coa on coa.acnoid = ladetail.acnoid
        left join cntnum on cntnum.trno = lahead.trno
        where coa.alias = 'arb'
        and md5(client.clientid)= '$clientid'
        and lahead.dateid>='$start'
        and cntnum.center = '$center'";

        return $query;
    }

    public function default_STOCK_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select glhead.trno as trno,
        concat(cntnum.bref,cntnum.seq) as docno,
        glhead.dateid as dateid,item.itemname,
        glstock.uom as uom,
        glstock.disc as disc,glstock.isamt as cost,
        glstock.isqty as isqty,glstock.rrqty as rrqty,
        client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.contact,client.fax, item.barcode
        from glstock
        left join glhead on glstock.trno = glhead.trno
        left join item on item.itemid = glstock.itemid
        left join client on client.clientid = glhead.clientid
        left join cntnum on cntnum.trno = glhead.trno
        where md5(client.clientid) ='$clientid'
        and glhead.dateid>='$start'
        and cntnum.center ='$center'
        union all
        select lahead.trno as trno,
        concat(cntnum.bref,cntnum.seq) as docno,
        lahead.dateid as dateid,
        item.itemname as itemname,lastock.uom as uom,lastock.disc as disc,
        lastock.isamt as cost,lastock.isqty as isqty,lastock.rrqty as rrqty,
        client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.contact,client.fax, item.barcode
        from lastock
        left join lahead on lastock.trno = lahead.trno
        left join item on item.itemid=lastock.itemid
        left join client on client.client = lahead.client
        left join cntnum on cntnum.trno = lahead.trno
        where  md5(client.clientid) ='$clientid'
        and lahead.dateid>='$start'
        and cntnum.center ='$center'
        group by lahead.trno,
        cntnum.bref,cntnum.seq,lahead.dateid,
        item.itemname,lastock.uom,lastock.disc,
        lastock.isamt,lastock.isqty,lastock.rrqty,
        client.client,client.clientname,client.addr,
        client.tel,client.email,client.tin,
        client.contact,client.fax, item.barcode";

        return $query;
    }

    public function bheadloan_QUERY($config)
    {
        // $center   = $config['params']['center'];
        // $username = $config['params']['user'];
        // $clientid = md5($config['params']['dataid']);

        // $reporttype = $config['params']['dataparams']['reporttype'];
        // $prepared   = $config['params']['dataparams']['prepared'];
        // $approved   = $config['params']['dataparams']['approved'];
        // $received   = $config['params']['dataparams']['received'];
        // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $trno   = $config['params']['dataparams']['trno'];


        $query = "select head.docno,head.client,head.clientname,head.interest,head.terms,
            info.blklot,info.area,info.pricesqm,info.tcp as contractamt,info.disc,info.outstanding,info.penaltyamt,info.amount,info.pf,info.amortization,info.subdivision,
            sum(dinfo.interest) as totalinterest,info.amount+sum(dinfo.interest) as totalpayable
            from heahead as head
            left join heainfo as info on info.trno=head.trno
            left join htempdetailinfo as dinfo on dinfo.trno=head.trno
            where head.trno = $trno
            group by head.docno,head.client,head.clientname,head.interest,head.terms,
            info.blklot,info.area,info.pricesqm,info.tcp,info.disc,info.outstanding,info.penaltyamt,info.amount,info.pf,info.amortization,info.subdivision,
            info.amount";

        return $query;
    }

    
    public function gridloan_QUERY($config)
    {
        // $center   = $config['params']['center'];
        // $username = $config['params']['user'];
        // $clientid = md5($config['params']['dataid']);

        // $reporttype = $config['params']['dataparams']['reporttype'];
        // $prepared   = $config['params']['dataparams']['prepared'];
        // $approved   = $config['params']['dataparams']['approved'];
        // $received   = $config['params']['dataparams']['received'];
        // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $trno   = $config['params']['dataparams']['trno'];

        
            // from htempdetailinfo as dinfo 
            // left join heahead as head on head.trno=dinfo.trno
            // left join heainfo as info on info.trno=dinfo.trno
            // left join transnum as num on num.trno=head.trno
            
            // left join gldetail as detail on detail.trno = num.cvtrno and dinfo.dateid = detail.postdate
            // left join gldetail as pay on pay.refx = detail.trno and pay.linex=detail.line
            // left join glhead as payhead on payhead.trno = pay.trno


        $query = "
            select g.due,g.cvpostdate,g.datepaid,
            g.amortization,g.principal,g.interest,
            #g.cvdb,g.cvcr,
            sum(g.crdb) as crdb,sum(g.crcr) as crcr,
            g.rem,group_concat(distinct g.paydoc) as paydoc from(
                select dinfo.dateid,
                date_format(dinfo.dateid,'%b %d, %Y') as due,detail.postdate as cvpostdate,date_format(payhead.dateid,'%c/%d/%Y') as datepaid,
                info.amortization,dinfo.principal,dinfo.interest,
                detail.db as cvdb,detail.cr as cvcr,pay.db as crdb,pay.cr as crcr,
                concat(SUBSTR(detail.rem, 1, INSTR(detail.rem, ' ') - 1),' M.A.') AS rem,
                cnum.seq as paydoc
                #group_concat(distinct cnum.trno) as paydoc
                from gldetail as detail                
                left join gldetail as pay on pay.refx = detail.trno and pay.linex=detail.line
                left join cntnum as cnum on cnum.trno=pay.trno
                left join glhead as payhead on payhead.trno = pay.trno
                left join transnum as num on num.cvtrno=detail.trno
                left join htempdetailinfo as dinfo on dinfo.trno=num.trno and dinfo.dateid=detail.postdate
                left join heahead as head on head.trno=dinfo.trno
                left join heainfo as info on info.trno=dinfo.trno                
                left join coa as c on c.acnoid = detail.acnoid
                where dinfo.trno = $trno and left(c.alias,2)='AR'                 
            ) as g
            group by 
                
            g.due,g.cvpostdate,g.datepaid,
            g.amortization,g.principal,g.interest,
            
            g.rem

            order by g.dateid asc
            ";

        return $query;
    }

    //PDF Layout - not okay pa
    public function reportdefaultAR_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];


        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";


        if ($companyid == 8) {

            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
        } else {
            if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
            }
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



        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS RECEIVABLE", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(95, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data) {
            $maxrow = 1;
            $docno = $data->docno;
            $dateid = $data->dateid;
            $agent = $data->agent;
            $rem = $data->rem;
            $debit = number_format($data->db, $decimalcurr);
            $credit = number_format($data->cr, $decimalcurr);
            $balance = number_format($data->bal, $decimalcurr);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            if ($data->cr != 0) {
                $balance = $balance < 0 ? '-' : $balance * -1;
            }
            $ref = $data->ref;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
            $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultAP_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        // if ($companyid) {
        //     if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
        //         $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
        //         $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        //     }
        // } else {
        //     if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
        //         $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
        //         $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        //     }
        // }


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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS PAYABLE", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(95, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data) {
            $maxrow = 1;
            $docno = $data->docno;
            $dateid = $data->dateid;
            $agent = $data->agent;
            $rem = $data->rem;
            $debit = number_format($data->db, $decimalcurr);
            $credit = number_format($data->cr, $decimalcurr);
            $balance = number_format($data->balance, $decimalcurr);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;
            $ref = $data->ref;
            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref, $arr_dateid]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultPDC_PDF($config, $data)
    {

        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - POSTDATED CHECKS", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(80, 20, "Agent : ", '', 'L', false, 0);
        PDF::MultiCell(150, 20, isset($data[0]->agentname) ? $data[0]->agentname : "", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(185, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $checkdate = date('Y-m-d', strtotime($data1->checkdate));
            $agentname = $data1->agentname;
            $rem = $data1->rem;
            $debit = number_format($data1->db, $decimalcurr);
            $credit = number_format($data1->cr, $decimalcurr);
            $ref = $data1->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_checkdate = $this->reporter->fixcolumn([$checkdate], '16', 0);
            $arr_agentname = $this->reporter->fixcolumn([$agentname], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_checkdate, $arr_agentname, $arr_rem, $arr_debit, $arr_credit, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_checkdate[$r]) ? $arr_checkdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agentname[$r]) ? $arr_agentname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(185, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb += $data1->db;
            $totalcr += $data1->cr;
            $totalbal += $totaldb - $totalcr;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(185, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultRC_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - RETURN CHECKS", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(95, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data) {
            $maxrow = 1;
            $docno = $data->docno;
            $dateid = $data->dateid;
            $agent = $data->agent;
            $rem = $data->rem;
            $debit = number_format($data->db, $decimalcurr);
            $credit = number_format($data->cr, $decimalcurr);
            $balance = number_format($data->balance, $decimalcurr);
            $ref = $data->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultSTOCK_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = 11;
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - INVENTORY", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Item Code", '', 'C', false, 0);
        PDF::MultiCell(270, 10, "Description", '', 'C', false, 0);
        PDF::MultiCell(40, 10, "Unit", '', 'C', false, 0);
        PDF::MultiCell(50, 10, "Discount", '', 'C', false, 0);
        PDF::MultiCell(40, 10, "Price", '', 'R', false, 0);
        PDF::MultiCell(40, 10, "In", '', 'R', false, 0);
        PDF::MultiCell(40, 10, "Out", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $dateid = date('Y-m-d', strtotime($data1->dateid));
            $barcode = $data1->barcode;
            $itemname = $data1->itemname;
            $uom = $data1->uom;
            $disc = $data1->disc;
            $cost = number_format($data1->cost, $decimalcurr);
            $rrqty = number_format($data1->rrqty, $decimalqty);
            $isqty = number_format($data1->isqty, $decimalqty);
            $cost = $cost < 0 ? '-' : $cost;
            $rrqty = $rrqty < 0 ? '-' : $rrqty;
            $isqty = $isqty < 0 ? '-' : $isqty;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '65', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_cost = $this->reporter->fixcolumn([$cost], '13', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '13', 0);
            $arr_isqty = $this->reporter->fixcolumn([$isqty], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_barcode, $arr_itemname, $arr_uom, $arr_disc, $arr_cost, $arr_rrqty, $arr_isqty]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize - 3);
                PDF::MultiCell(70, 18, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 18, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 18, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(270, 18, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(50, 18, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_isqty[$r]) ? $arr_isqty[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function bloan_header_PDF($config, $head)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";

        // $birblogo = URL::to('/images/reports/birbarcode.png');

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        // PDF::SetTitle($this->modulename);
        // PDF::SetAuthor('Solutionbase Corp.');
        // PDF::SetCreator('Solutionbase Corp.');
        // PDF::SetSubject($this->modulename . ' Module Report');
        

        // PDF::SetFont($font, '', 9);
        // PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        // PDF::MultiCell(0, 0, "\n");
        // PDF::MultiCell(0, 0, "\n");

        // PDF::SetFont($font, '', 15);
        // PDF::MultiCell(960, 20, "Happy Homes 2 House and Lot", '', 'C', false);
        // PDF::MultiCell(960, 30, "Customer Ledger", '', 'C', false);
        
        // // .(isset($head[0]->blklot) ? $head[0]->blklot : '')

        // PDF::MultiCell(0, 0, "\n");

        // if ($sjlogo == 'wlogo') {
        
        PDF::Image(public_path() . $this->companysetup->getlogopath($config['params']).'afli.jpg', '280', '50', 200, 150);
        // PDF::Image($this->companysetup->getlogopath($config['params']).'afli.png', '0', '0', 60, 50);
        // PDF::Image('/images/afli/afli.png', '0', '0', 60, 50);
        // PDF::Image('public/images/afli/afli.png', '0', '0', 60, 50);
        // PDF::Image('/public/images/afli/afli.png', '0', '0', 60, 50);

        // PDF::Image(public_path() . $this->companysetup->getlogopath($config['params']).'afli.png', '0', '0', 60, 50);
        // PDF::Image($this->companysetup->getlogopath($config['params']).'afli.png', '0', '0', 60, 50);
        // PDF::Image('/images/afli/afli.png', '0', '0', 60, 50);
        // PDF::Image('http://localhost:8000/public/images/afli/afli.png', '0', '0', 60, 50);
        // PDF::Image('/http://localhost:8000/public/images/afli/afli.png', '0', '0', 60, 50);

        // PDF::Image('/http://localhost/waimsv2_backend/laravels/public/images/afli/afli.png', '0', '0', 60, 50);
        // PDF::Image('http://localhost/waimsv2_backend/laravels/public/images/afli/afli.png', '0', '0', 60, 50);

        // PDF::Image('http://localhost:8000/waimsv2_backend/laravels/public/images/afli/afli.png', '0', '0', 100, 100);

        
            
            
    // PDF::Image($this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '35', '30', 60, 50);
    PDF::MultiCell(0, 180, "\n");
            // PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            // PDF::SetFont($font, 'B', $fontsize11);
            // PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        // } else {
        //     PDF::SetFont($font, '', 10);
        //     PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
        //     PDF::SetFont($font, 'B', $fontsize11);
        //     PDF::MultiCell(300, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '310', '28', false, 0, false, false, 0);
        // }


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(720, 20, "ASCEND FINANCE AND LEASING (AFLI) INC.", '', 'C', false);

        PDF::SetFont($font, '', 15);
        PDF::MultiCell(720, 50, "Punta Dulog Commercial Complex, St. Joesph Ave. Pueblo de Panay Township, Brgy. Lawaan Roxas City, Capiz", '', 'C', false);
        
        $title = (isset($head[0]->subdivision) ? $head[0]->subdivision : '');
        

        PDF::SetFont($font, '', 15);
        PDF::MultiCell(720, 20, $title, '', 'C', false);
        
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(720, 30, "Customer Ledger", '', 'C', false);
        
        // .(isset($head[0]->blklot) ? $head[0]->blklot : '')

        PDF::MultiCell(0, 0, "\n");
        
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(150, 20, "Blk & Lot No. :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->blklot) ? $head[0]->blklot : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Processing Fee :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->pf) ? $head[0]->pf : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Area (Sqm) :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->area) ? $head[0]->area : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Interest Rate :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->interest) ? $head[0]->interest.'%' : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Price / Sqm :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->pricesqm) ? $head[0]->pricesqm : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Interest Amount :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->totalinterest) ? number_format($head[0]->totalinterest,2) : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Contract Price :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->contractamt) ? $head[0]->contractamt : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Monthly Amortization :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->amortization) ? number_format($head[0]->amortization,2) : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Discount :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->disc) ? $head[0]->disc : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Period :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->terms) ? $head[0]->terms : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Outstanding Balance :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->outstanding) ? number_format($head[0]->outstanding,2) : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, "Total Payable :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->totalpayable) ? number_format($head[0]->totalpayable,2) : ''), '', 'L', false);

        PDF::MultiCell(150, 20, "Penalty :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->penaltyamt) ? number_format($head[0]->penaltyamt,2) : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, " ", '', 'R', false, 0);
        PDF::MultiCell(210, 20, '', '', 'L', false);

        PDF::MultiCell(150, 20, "Loanable Amount :", '', 'L', false, 0);
        PDF::MultiCell(210, 20, (isset($head[0]->amount) ? number_format($head[0]->amount,2) : ''), '', 'L', false, 0);
        PDF::MultiCell(150, 20, " ", '', 'R', false, 0);
        PDF::MultiCell(210, 20, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(85, 0, "Due Date", 'TLR', 'C', false, 0);
        PDF::MultiCell(85, 0, "Amortization", 'TLR', 'C', false, 0);
        PDF::MultiCell(75, 0, "Principal", 'TLR', 'C', false, 0);
        PDF::MultiCell(75, 0, "Interest", 'TLR', 'C', false, 0);
        PDF::MultiCell(80, 0, "Balance O/S", 'TLR', 'C', false, 0);
        PDF::MultiCell(80, 0, "Amt. Paid", 'TLR', 'C', false, 0);
        PDF::MultiCell(80, 0, "Date Paid", 'TLR', 'C', false, 0);
        PDF::MultiCell(70, 0, "Receipt No", 'TLR', 'C', false, 0);
        PDF::MultiCell(90, 0, "Remarks", 'TLR', 'C', false);
    }

    public function reportbloan_PDF($config, $head, $grid)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");


        $this->bloan_header_PDF($config, $head);
        
        $balance = $head[0]->amount;

        PDF::SetFont($font, '', 10);
        
        PDF::MultiCell(85, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(85, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(75, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(75, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(80, 0, number_format($balance,2), 'TBLR', 'R', false, 0);
        PDF::MultiCell(80, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(80, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(70, 0, '', 'TBLR', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'TBLR', 'C', false);

        $totalamortization = 0;
        $totalprincipal = 0;
        $totalinterest = 0;

        foreach ($grid as $key => $value) {
            $balance -= $value->crcr;
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(85, 0, $value->due, 'LB', 'C', false, 0);
            PDF::MultiCell(85, 0, number_format($value->amortization,2), 'LB', 'C', false, 0);
            PDF::MultiCell(75, 0, number_format($value->principal,2), 'LB', 'C', false, 0);
            PDF::MultiCell(75, 0, number_format($value->interest,2), 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, number_format($balance,2), 'LB', 'R', false, 0);
            PDF::MultiCell(80, 0, number_format($value->crcr,2), 'LB', 'R', false, 0);
            PDF::MultiCell(80, 0, $value->datepaid, 'LB', 'C', false, 0);
            PDF::MultiCell(70, 0, $value->paydoc, 'LB', 'C', false, 0);
            PDF::MultiCell(90, 0, $value->rem, 'BLR', 'L', false);
            


            $totalamortization += $value->amortization;
            $totalprincipal += $value->principal;
            $totalinterest += $value->interest;
            
            if (PDF::getY() > 900) {
                PDF::setPageUnit('px');
                PDF::AddPage('p', [800, 1000]);
                PDF::SetMargins(40, 40);
                
                        
                PDF::MultiCell(0, 0, "\n");


                PDF::SetFont($fontbold, '', 11);
                PDF::MultiCell(85, 0, "Due Date", 'TBLR', 'C', false, 0);
                PDF::MultiCell(85, 0, "Amortization", 'TBLR', 'C', false, 0);
                PDF::MultiCell(75, 0, "Principal", 'TBLR', 'C', false, 0);
                PDF::MultiCell(75, 0, "Interest", 'TBLR', 'C', false, 0);
                PDF::MultiCell(80, 0, "Balance O/S", 'TBLR', 'C', false, 0);
                PDF::MultiCell(80, 0, "Amt. Paid", 'TBLR', 'C', false, 0);
                PDF::MultiCell(80, 0, "Date Paid", 'TBLR', 'C', false, 0);
                PDF::MultiCell(70, 0, "Receipt No", 'TBLR', 'C', false, 0);
                PDF::MultiCell(90, 0, "Remarks", 'TBLR', 'C', false);
                
            }
        }

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(85, 0, '', 'T', 'C', false, 0);
        PDF::MultiCell(85, 0, number_format($totalamortization,2), 'T', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalprincipal,2), 'T', 'C', false, 0);
        PDF::MultiCell(75, 0, number_format($totalinterest,2), 'T', 'C', false, 0);
        PDF::MultiCell(80, 0, '', 'T', 'R', false, 0);
        PDF::MultiCell(80, 0, '', 'T', 'R', false, 0);
        PDF::MultiCell(80, 0, '', 'T', 'C', false, 0);
        PDF::MultiCell(70, 0, '', 'T', 'C', false, 0);
        PDF::MultiCell(90, 0, '', 'T', 'L', false);

        // PDF::MultiCell(0, 0, "\n");

        // $totaldb = 0;
        // $totalcr = 0;
        // $totalbal = 0;
        // $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);

        // foreach ($data as $key => $data) {
        //     $maxrow = 1;
        //     $docno = $data->docno;
        //     $dateid = $data->dateid;
        //     $agent = $data->agent;
        //     $rem = $data->rem;
        //     $debit = number_format($data->db, $decimalcurr);
        //     $credit = number_format($data->cr, $decimalcurr);
        //     $balance = number_format($data->bal, $decimalcurr);
        //     $debit = $debit < 0 ? '-' : $debit;
        //     $credit = $credit < 0 ? '-' : $credit;
        //     if ($data->cr != 0) {
        //         $balance = $balance < 0 ? '-' : $balance * -1;
        //     }
        //     $ref = $data->ref;

        //     $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
        //     $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
        //     $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
        //     $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
        //     $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        //     $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        //     $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
        //     $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

        //     $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

        //     for ($r = 0; $r < $maxrow; $r++) {
        //         PDF::SetFont($font, '', $fontsize);
        //         PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        //         PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
        //     }


        //     $totaldb = $totaldb + $data->db;
        //     $totalcr = $totalcr + $data->cr;
        //     $totalbal = $totalbal + $data->bal;
        // }

        // PDF::SetFont($font, '', 5);
        // PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        // PDF::MultiCell(60, 0, "", 'B', 'L', false);

        // PDF::MultiCell(0, 0, "\n");
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        // PDF::MultiCell(0, 0, "\n\n\n\n");
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        // PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        // PDF::MultiCell(0, 0, "\n\n");
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        // PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
