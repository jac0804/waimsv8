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

class construction_order_report
{
    public $modulename = 'Construction Order Report';
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
        $company = $config['params']['companyid'];

        $fields = [
            'radioprint',
            'start',
            'end',
            'dclientname',
            'dcentername',
            'reportusers',
            'dprojectname',
            'phase',
            'housemodel',
            'blklot'
        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'reportusers.lookupclass', 'user');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'projectname.lookupclass', 'fproject');
        data_set($col1, 'phase.addedparams', ['projectid']);
        data_set($col1, 'housemodel.addedparams', ['projectid']);
        data_set($col1, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);


        $fields = ['radioposttype', 'radioreporttype'];
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
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'0' as clientid,
                            '' as client,'' as clientname, '' as userid,'' as username,'0' as posttype,
                            '0' as reporttype,'' as dclientname,'' as reportusers ,
                            '" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '' as dprojectname, '' as projectcode, '' as projectname,
                            '' as phase,'' as housemodel, '' as blklot";
        return $this->coreFunctions->opentable($paramstr);
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
            case 0:
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case 1:
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientname     = $config['params']['dataparams']['clientname'];
        $filterusername  = $config['params']['dataparams']['username'];
        $clientid     = $config['params']['dataparams']['clientid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $posttype   = $config['params']['dataparams']['posttype'];


        $dprojectname   = $config['params']['dataparams']['dprojectname'];
        $phase   = $config['params']['dataparams']['phase'];
        $housemodel   = $config['params']['dataparams']['housemodel'];
        $blklot   = $config['params']['dataparams']['blklot'];


        $leftjoin="";

        $filter = "";
        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }

        if ($client != "") {
            $leftjoin .= " left join client as cl on cl.client=head.client";
            $filter .= " and cl.clientid = '$clientid' ";
        }

        $fcenter    = $config['params']['dataparams']['center'];
        if ($fcenter != "") {
            $filter .= " and transnum.center = '$fcenter'";
        }

        if ($dprojectname != "") {
            $projectid    = $config['params']['dataparams']['projectid'];
            $filter .= " and head.projectid = '$projectid' ";
        }

        if ($phase != "") {
            $phaseid    = $config['params']['dataparams']['phaseid'];
            $filter .= " and head.phaseid = '$phaseid' ";
        }

        if ($housemodel != "") {
            $modelid    = $config['params']['dataparams']['modelid'];
            $filter .= " and head.modelid = '$modelid' ";
        }

        if ($blklot != "") {
            $blklotid    = $config['params']['dataparams']['blklotid'];
            $filter .= " and head.blklotid = '$blklotid' ";
        }

        switch ($reporttype) {
            case 0: // summarized
                switch ($posttype) {
                    case 0: // posted
                        $query = "select 'POSTED' as status, head.docno, head.clientname,
                                        sum(stock.rrqty) as qty,wh.clientname as warehouse, head.createby,
                                        left(head.dateid,10) as dateid,project.code as projcode,project.name as projname,
                                        phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,
                                        head.blklotid,bl.lot,head.citrno,ci.docno as cidocno,phase.name as phasename
                                from hcohead as head
                                left join hcostock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join transnum on transnum.trno=head.trno
                                left join client as wh on wh.client = head.wh
                                left join projectmasterfile as project on project.line=head.projectid
                                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                                left join hcihead as ci on ci.trno=head.citrno $leftjoin
                                where head.doc='CC' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                                group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,project.code,project.name,
                                        phase.code,head.phaseid,model.model,head.modelid,bl.blk,head.blklotid,bl.lot,head.citrno,ci.docno,phase.name
                                order by docno";
                        break;

                    case 1: // unposted
                        $query = "select 'UNPOSTED' as status, head.docno, head.clientname,
                                        sum(stock.rrqty) as qty,wh.clientname as warehouse, head.createby,
                                        left(head.dateid,10) as dateid,project.code as projcode,project.name as projname,
                                        phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,
                                        head.blklotid,bl.lot,head.citrno,ci.docno as cidocno,phase.name as phasename
                                from cohead as head
                                left join costock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join transnum on transnum.trno=head.trno
                                left join client as wh on wh.client = head.wh
                                left join projectmasterfile as project on project.line=head.projectid
                                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                                left join hcihead as ci on ci.trno=head.citrno $leftjoin
                                where head.doc='CC' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                                group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,project.code,project.name,
                                        phase.code,head.phaseid,model.model,head.modelid,bl.blk,head.blklotid,bl.lot,head.citrno,ci.docno,phase.name
                                order by docno";
                        break;

                    default: // all
                        $query = "select 'UNPOSTED' as status, head.docno, head.clientname,
                                        sum(stock.rrqty) as qty,wh.clientname as warehouse, head.createby,
                                        left(head.dateid,10) as dateid,project.code as projcode,project.name as projname,
                                        phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,
                                        head.blklotid,bl.lot,head.citrno,ci.docno as cidocno,phase.name as phasename
                                from cohead as head
                                left join costock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join transnum on transnum.trno=head.trno
                                left join client as wh on wh.client = head.wh
                                left join projectmasterfile as project on project.line=head.projectid
                                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                                left join hcihead as ci on ci.trno=head.citrno  $leftjoin
                                where head.doc='CC' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                                group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,project.code,project.name,
                                        phase.code,head.phaseid,model.model,head.modelid,bl.blk,head.blklotid,bl.lot,head.citrno,ci.docno,phase.name
                                union all
                                select 'POSTED' as status, head.docno, head.clientname,
                                        sum(stock.rrqty) as qty,wh.clientname as warehouse, head.createby,
                                        left(head.dateid,10) as dateid,project.code as projcode,project.name as projname,
                                        phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,
                                        head.blklotid,bl.lot,head.citrno,ci.docno as cidocno,phase.name as phasename
                                from hcohead as head
                                left join hcostock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join transnum on transnum.trno=head.trno
                                left join client as wh on wh.client = head.wh
                                left join projectmasterfile as project on project.line=head.projectid
                                left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                left join housemodel as model on model.line=head.modelid and model.projectid=head.modelid
                                left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
                                left join hcihead as ci on ci.trno=head.citrno $leftjoin
                                where head.doc='CC' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                                group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,project.code,project.name,
                                        phase.code,head.phaseid,model.model,head.modelid,bl.blk,head.blklotid,bl.lot,head.citrno,ci.docno,phase.name";
                        break;
                } // end switch posttype
                break;

            case 1: // detailed
                switch ($posttype) {
                    case 0: // posted
                        $query = "select yourref,docno,clientname,left(dateid,10) as dateid,barcode,itemname,rem,
                                        qa,sum(rrqty) as rrqty,uom,lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                from (select head.yourref,head.docno,head.clientname,head.createby,head.dateid,
                                            stock.barcode,stock.itemname,stock.rem,round((stock.qty-stock.qa)/ case 
                                            when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                            stock.rrqty,stock.uom,stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.lot,stock.amenity,stock.subamenity,proj.code as project, 
                                            proj.name as projectname,phase.code as phasename,model.model as housemodel,
                                            am.description as amenityname,subam.description as subamenityname,bl.blk,
                                            wh.clientname as warehouse,head.address
                                    from hcohead as head
                                    left join hcostock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join transnum on transnum.trno=head.trno
                                    left join client as wh on wh.clientid=stock.whid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join projectmasterfile as proj on proj.line=head.projectid
                                    left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                    left join housemodel as model on model.line=head.modelid and 
                                                model.projectid=head.modelid
                                    left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid 
                                                and bl.projectid=head.projectid
                                    left join amenities as am on am.line= stock.amenity
                                    left join subamenities as subam on subam.line=stock.subamenity 
                                              and subam.amenityid=stock.amenity $leftjoin
                                    where head.doc='CC' and date(head.dateid) between  '" . $start . "' 
                                          and '" . $end . "' " . $filter . ") as a
                                group by yourref,docno,clientname,dateid,barcode,itemname,rem,qa,uom,
                                         lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                order by a.docno";
                        break;

                    case 1: // unposted
                        $query = "select yourref,docno,clientname,left(dateid,10) as dateid,barcode,itemname,rem,
                                         qa,sum(rrqty) as rrqty,uom,lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                from (select head.yourref,head.docno,head.clientname,head.createby,head.dateid,
                                            stock.barcode,stock.itemname,stock.rem,round((stock.qty-stock.qa)/ case 
                                            when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                            stock.rrqty,stock.uom,stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.lot,stock.amenity,stock.subamenity,proj.code as project, 
                                            proj.name as projectname,phase.code as phasename,model.model as housemodel,
                                            am.description as amenityname,subam.description as subamenityname,bl.blk,
                                            wh.clientname as warehouse,head.address
                                    from cohead as head
                                    left join costock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join transnum on transnum.trno=head.trno
                                    left join client as wh on wh.clientid=stock.whid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join projectmasterfile as proj on proj.line=head.projectid
                                    left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                    left join housemodel as model on model.line=head.modelid and 
                                                model.projectid=head.modelid
                                    left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid 
                                                and bl.projectid=head.projectid
                                    left join amenities as am on am.line= stock.amenity
                                    left join subamenities as subam on subam.line=stock.subamenity 
                                              and subam.amenityid=stock.amenity $leftjoin
                                    where head.doc='CC' and date(head.dateid) between  '" . $start . "' 
                                          and '" . $end . "' " . $filter . ") as a
                                group by yourref,docno,clientname,dateid,barcode,itemname,rem,qa,uom,
                                         lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                order by a.docno";
                        break;

                    default: // all
                        $query = "select yourref,docno,clientname,left(dateid,10) as dateid,barcode,itemname,
                                        rem,qa,sum(rrqty) as rrqty,uom,lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                from (select head.yourref,head.docno,head.clientname,head.createby,head.dateid,
                                            stock.barcode,stock.itemname,stock.rem,round((stock.qty-stock.qa)/ case 
                                            when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                            stock.rrqty,stock.uom,stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.lot,stock.amenity,stock.subamenity,proj.code as project, 
                                            proj.name as projectname,phase.code as phasename,model.model as housemodel,
                                            am.description as amenityname,subam.description as subamenityname,bl.blk,
                                            wh.clientname as warehouse,head.address
                                    from hcohead as head
                                    left join hcostock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join transnum on transnum.trno=head.trno
                                    left join client as wh on wh.clientid=stock.whid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join projectmasterfile as proj on proj.line=head.projectid
                                    left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                    left join housemodel as model on model.line=head.modelid and 
                                                model.projectid=head.modelid
                                    left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid 
                                                and bl.projectid=head.projectid
                                    left join amenities as am on am.line= stock.amenity
                                    left join subamenities as subam on subam.line=stock.subamenity 
                                              and subam.amenityid=stock.amenity $leftjoin
                                    where head.doc='CC' and date(head.dateid) between  '" . $start . "' 
                                          and '" . $end . "' " . $filter . "
                                    union all
                                    select head.yourref,head.docno,head.clientname,head.createby,head.dateid,
                                            stock.barcode,stock.itemname,stock.rem,round((stock.qty-stock.qa)/ case 
                                            when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                            stock.rrqty,stock.uom,stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.lot,stock.amenity,stock.subamenity,proj.code as project, 
                                            proj.name as projectname,phase.code as phasename,model.model as housemodel,
                                            am.description as amenityname,subam.description as subamenityname,bl.blk,
                                            wh.clientname as warehouse,head.address
                                    from cohead as head
                                    left join costock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join transnum on transnum.trno=head.trno
                                    left join client as wh on wh.clientid=stock.whid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join projectmasterfile as proj on proj.line=head.projectid
                                    left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
                                    left join housemodel as model on model.line=head.modelid and 
                                                model.projectid=head.modelid
                                    left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid 
                                                and bl.projectid=head.projectid
                                    left join amenities as am on am.line= stock.amenity
                                    left join subamenities as subam on subam.line=stock.subamenity 
                                              and subam.amenityid=stock.amenity $leftjoin
                                    where head.doc='CC' and date(head.dateid) between  '" . $start . "' 
                                          and '" . $end . "' " . $filter . ") as a
                                group by yourref,docno,clientname,dateid,barcode,itemname,rem,qa,uom,
                                         lot,project,projectname,phasename,housemodel,amenityname,subamenityname,blk,warehouse,address
                                order by a.docno";
                        break;
                }
                break;
        }

        return $query;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];


        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $docno = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $str .= '<br/>';

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Document#: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->docno, '620', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->col('Date: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->dateid, '220', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Customer: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->clientname, '620', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->col('House Model: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->housemodel, '220', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Address: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->address, '620', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->col('Block: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->blk, '220', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Project: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->projectname, '620', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->col('Lot: ', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col($data->lot, '220', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('Item Description', '280', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('Warehouse', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('Amenity', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->col('Sub-Amenity', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data->itemname, '280', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data->warehouse, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data->amenityname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data->subamenityname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '2px');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $total += $data->rrqty;
                }
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $filterusername  = $config['params']['dataparams']['username'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $posttype   = $config['params']['dataparams']['posttype'];

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

        $str = '';


        $layoutsize = '1000';
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
        $str .= $this->reporter->col('Construction Order Report (' . $reporttype . ')', '500', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '8px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $filterusername  = $config['params']['dataparams']['username'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $count = 61;
        $page = 60;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1500';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);


        $totalqty = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->projname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->housemodel, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->phasename, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->blklot, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->lot, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->warehouse, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->status, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

                $totalqty = $totalqty + $data->qty;
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL :', '150', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalqty, 2), '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '950', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $companyid = $config['params']['companyid'];
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QUANTITY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PROJECT', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HOUSE MODEL', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PHASE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BLOCK', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LOT', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WAREHOUSE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class