<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class material_transfer_report
{
    public $modulename = 'Material Transfer Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $fields = ['radioprint', 'start', 'end', 'dprojectname', 'dprojectname2', 'wh', 'client', 'reportusers', 'dcentername', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        // data_set($col1, 'wh.label', 'Source Warehouse');

        data_set($col1, 'projectname.lookupclass', 'projectcode');
        data_set($col1, 'dprojectname.label', 'Project Source');
        data_set($col1, 'dprojectname.required', true);
        data_set($col1, 'dprojectname2.lookupclass', 'projectcode2');
        data_set($col1, 'dprojectname2.label', 'Project Destination');
        data_set($col1, 'dprojectname2.required', true);

        data_set($col1, 'wh.label', 'Source Warehouse');
        data_set($col1, 'client.label', 'Destination Warehouse');
        data_set($col1, 'client.lookupclass', 'whtslip');

        $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as `end`,
            '' as userid,
            '' as username,
            '' as approved,
            '0' as posttype,
            '0' as reporttype, 
            'ASC' as sorting,
            '' as center,
            '' as dcentername,
            '' as dclientname,
            '' as reportusers,
            '' as dprojectname,'' as dprojectname2, '' as projectname, 
            '' as projectname2, '' as projectcode, '0' as projectid,
            '0' as projectid2, '' as projectcode2,
            '' as client, '' as clientname, '0' as clientid,
            '' as wh, '' as whid, '' as whname");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': // SUMMARIZED
                $query = $this->default_QUERY_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $query = $this->default_QUERY_DETAILED($config);
                break;
        }


        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY_DETAILED($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $fcenter    = $config['params']['dataparams']['center'];
        $clientid    = $config['params']['dataparams']['clientid'];
        $client    = $config['params']['dataparams']['client']; //wh destination
        $whid    = $config['params']['dataparams']['whid']; //wh source
        $sourceprj    = $config['params']['dataparams']['projectcode']; //prj source
        $sourceprjid = $config['params']['dataparams']['projectid']; //prj source id
        $destprj    = $config['params']['dataparams']['projectcode2']; //prj destination
        $destprjid    = $config['params']['dataparams']['projectid2']; //prj destination id


        $filter = "";
        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }
        if ($prefix != "") {
            $filter .= " and cntnum.bref = '$prefix' ";
        }
        if ($fcenter != "") {
            $filter .= " and cntnum.center = '$fcenter'";
        }


        if ($whid != "") { //wh source
            $filter .= " and client.clientid = '$whid'";
        }

        if ($client != "") { //wh destination
            $filter .= " and cl.clientid = '$clientid' ";
        }


        if ($sourceprj != "") { //prj source
            $filter .= " and project.line = '$sourceprjid'";
        }

        if ($destprj != "") { //prj source
            $filter .= " and project2.line = '$destprjid'";
        }

        switch ($posttype) {
            case 0: // posted
                $query = "select * from ( select head.docno,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
                    client.clientname as sourcewh,head.clientname as destinationwh,
                    ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,
                    head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,
                    stock.ref,head.rem as hrem,cntnum.center,stock.amt,stock.ext as cost
                    from glstock as stock
                    left join glhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.whid
                    left join client as cl on cl.clientid = head.clientid 
                    left join projectmasterfile as project on project.line=head.projectid
                    left join projectmasterfile as project2 on project2.line=head.projectto 
                    where head.doc='MT'  and date(head.dateid) between '$start' and '$end' $filter
                    ) as a order by docno,center $sorting";
                break;

            case 1: // unposted
                $query = "select * from (
                    select head.docno,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
                    client.clientname as sourcewh,head.client as destinationwh,
                    ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,
                    head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,
                    stock.ref,head.rem as hrem,cntnum.center,stock.amt, stock.ext as cost
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.wh
                    left join client as cl on cl.client = head.client
                    left join projectmasterfile as project on project.line=head.projectid
                    left join projectmasterfile as project2 on project2.line=head.projectto 
                    where head.doc='MT' and date(head.dateid) between '$start' and '$end' $filter
                   ) as a order by docno,center $sorting";
                break;

            default: // sana all
                $query = "select * from ( 
                    
                  select head.docno,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
                    client.clientname as sourcewh,head.clientname as destinationwh,
                    ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,
                    head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,
                    stock.ref,head.rem as hrem,cntnum.center,stock.amt,stock.ext as cost
                    from glstock as stock
                    left join glhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.whid
                    left join client as cl on cl.clientid = head.clientid 
                    left join projectmasterfile as project on project.line=head.projectid
                    left join projectmasterfile as project2 on project2.line=head.projectto 
                    where head.doc='MT' and date(head.dateid) between '$start' and '$end' $filter
    
             union all
    
                select head.docno,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
                    client.clientname as sourcewh,head.client as destinationwh,
                    ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,
                    head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,
                    stock.ref,head.rem as hrem,cntnum.center,stock.amt, stock.ext as cost
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.wh
                    left join client as cl on cl.client = head.client
                    left join projectmasterfile as project on project.line=head.projectid
                    left join projectmasterfile as project2 on project2.line=head.projectto 
                    where head.doc='MT'  and date(head.dateid) between '$start' and '$end' $filter
                    ) as a order by docno,center $sorting";

                break;
        }

        return $query;
    }



    public function default_QUERY_SUMMARIZED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $fcenter    = $config['params']['dataparams']['center'];
        $clientid    = $config['params']['dataparams']['clientid'];
        $client    = $config['params']['dataparams']['client']; //wh destination
        $whid    = $config['params']['dataparams']['whid']; //wh source
        $sourceprj    = $config['params']['dataparams']['projectcode']; //prj source
        $destprj    = $config['params']['dataparams']['projectcode2']; //prj destination

        $sourceprjid = $config['params']['dataparams']['projectid']; //prj source id
        $destprjid    = $config['params']['dataparams']['projectid2']; //prj destination id


        $filter = "";

        if ($whid != "") { //wh source
            $filter .= " and client.clientid = '$whid'";
        }

        if ($client != "") { //wh destination
            $filter .= " and cl.clientid = '$clientid' ";
        }

        if ($sourceprj != "") { //prj source
            $filter .= " and project.line = '$sourceprjid'";
        }

        if ($destprj != "") { //prj source
            $filter .= " and project2.line = '$destprjid'";
        }

        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }
        if ($prefix != "") {
            $filter .= " and cntnum.bref = '$prefix' ";
        }

        if ($fcenter != "") {
            $filter .= " and cntnum.center = '$fcenter'";
        }

        switch ($posttype) {
            case 0: // posted
                $query = "select docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,sum(ext) as ext, hrem,center, yourref, postdate from ( 
                            select head.docno,date(head.dateid) as dateid,client.clientname as sourcewh,head.clientname as destinationwh,
                            ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,stock.ext,
                            item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,
                            head.createby,stock.rem,
                            stock.ref,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
                            from glstock as stock
                            left join glhead as head on head.trno=stock.trno
                            left join item on item.itemid=stock.itemid
                            left join cntnum on cntnum.trno=head.trno
                            left join client on client.clientid=head.whid
                            left join client as cl on cl.clientid = head.clientid 
                            left join projectmasterfile as project on project.line=head.projectid
                            left join projectmasterfile as project2 on project2.line=head.projectto 
                            where head.doc='MT'  and date(head.dateid) between '$start' and '$end' $filter
                            ) as a 
                            group by docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,hrem,center, yourref, postdate
                            order by docno $sorting";
                break;

            case 1: // unposted
                $query = " select docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,sum(ext) as ext, hrem,center, yourref, postdate from (
                            select head.docno, date(head.dateid) as dateid,client.clientname as sourcewh,head.client as destinationwh,
                            ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,stock.ext,
                            item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,
                            head.createby,stock.rem,
                            stock.ref,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate

                            from lastock as stock
                            left join lahead as head on head.trno=stock.trno
                            left join item on item.itemid=stock.itemid
                            left join cntnum on cntnum.trno=head.trno
                            left join client on client.client=head.wh
                            left join client as cl on cl.client = head.client
                            left join projectmasterfile as project on project.line=head.projectid
                            left join projectmasterfile as project2 on project2.line=head.projectto 
                            where head.doc='MT' and date(head.dateid) between '$start' and '$end' $filter
                            ) as a 
                            group by docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,hrem,center, yourref, postdate
                            order by docno $sorting";

                break;

            default: // sana all
                $query = "select docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,sum(ext) as ext, hrem,center, yourref, postdate from ( 
                            select head.docno,date(head.dateid) as dateid,client.clientname as sourcewh,head.clientname as destinationwh,
                            ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,stock.ext,
                            item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,
                            head.createby,stock.rem,
                            stock.ref,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
                            from glstock as stock
                            left join glhead as head on head.trno=stock.trno
                            left join item on item.itemid=stock.itemid
                            left join cntnum on cntnum.trno=head.trno
                            left join client on client.clientid=head.whid
                            left join client as cl on cl.clientid = head.clientid 
                            left join projectmasterfile as project on project.line=head.projectid
                            left join projectmasterfile as project2 on project2.line=head.projectto 
                            where head.doc='MT'  and date(head.dateid) between '$start' and '$end' $filter
                    
                      union all
                          select head.docno, date(head.dateid) as dateid,client.clientname as sourcewh,head.client as destinationwh,
                            ifnull(project.name,'') as prjsource,ifnull(project2.name,'') as prjdestination,stock.ext,
                            item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,
                            head.createby,stock.rem,
                            stock.ref,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
                            from lastock as stock
                            left join lahead as head on head.trno=stock.trno
                            left join item on item.itemid=stock.itemid
                            left join cntnum on cntnum.trno=head.trno
                            left join client on client.client=head.wh
                            left join client as cl on cl.client = head.client
                            left join projectmasterfile as project on project.line=head.projectid
                            left join projectmasterfile as project2 on project2.line=head.projectto 
                            where head.doc='MT' and date(head.dateid) between '$start' and '$end' $filter
                            ) as a 
                            group by docno,dateid,sourcewh,destinationwh,prjsource,prjdestination,hrem,center, yourref, postdate
                            order by docno $sorting";
                break;
        }

        return $query;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $destination    = $config['params']['dataparams']['clientname']; //wh destination
        $source    = $config['params']['dataparams']['whname']; //wh source
        $sourceprj    = $config['params']['dataparams']['projectname']; //prj source
        $destprj    = $config['params']['dataparams']['projectname2']; //prj destination

        if ($sorting == 'ASC') {
            $sorting = 'Ascending';
        } else {
            $sorting = 'Descending';
        }

        switch ($posttype) {
            case 0:
                $posttype = 'Posted';
                break;

            case 1:
                $posttype = 'Unposted';
                break;

            default:
                $posttype = 'All';
                break;
        }

        if ($reporttype == 0) {
            $reporttype = 'Summarized';
        } else {
            $reporttype = 'Detailed';
        }

        if ($destination == '') { //wh
            $dest = 'ALL';
        } else {
            $dest = $destination;
        }

        if ($source == '') { //wh
            $srcwh = 'ALL';
        } else {
            $srcwh = $source;
        }

        if ($sourceprj == '') { // pr
            $srcprj = 'ALL';
        } else {
            $srcprj = $sourceprj;
        }

        if ($destprj == '') { // pr
            $dest = 'ALL';
        } else {
            $dest = $destprj;
        }



        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        if ($filterusername != "") {
            $user = $filterusername;
        } else {
            $user = "ALL USERS";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Material Transfer Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Sorting By: ' . $sorting, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Source WH: ' . $srcwh, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Destination WH: ' . $destination, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Source Project: ' . $sourceprj, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Destination Project: ' . $dest, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $companyid = $config['params']['companyid'];
        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $docno = "";
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '800', null, false, $border,  'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->endtable();
                }

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Date: ' . $data->dateid, '400', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Barcode', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Reference', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->barcode, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->ref, '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();
                if ($docno == $data->docno) {
                    $total += $data->qty;
                }
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' . number_format($total, 2), '800', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }


    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];

        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $docno = "";
        $total = 0;
        $i = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->hrem, '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->endrow();

                $total = $total + $data->ext;


                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if
                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Grand Total :', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Amount', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Remarks', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }
}//end class