<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewiteminfo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Item Information';
    public $gridname = 'tableentry';
    private $fields = ['itemdescription', 'accessories'];
    private $table = 'iteminfo';

    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';

    public $style = 'width:100%;max-width:70%;';
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
    }

    public function getAttrib()
    {
        $attrib = array('load' => 22, 'edit' => 23);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $doc = $config['params']['doc'];
        $this->style = 'width:100%;max-width:100%;';
        switch ($doc) {
            case 'QT':
            case 'QS':
            case 'OS':
            case 'SQ':
            case 'SR':
            case 'PR':
            case 'SU':
            case 'RF':
            case 'JB':
            case 'PX';
                break;
            default:
                if (isset($config['params']['clientid'])) {
                    if ($config['params']['clientid'] != 0) {
                        $itemid = $config['params']['clientid'];
                        $item = $this->othersClass->getitemname($itemid);
                        $this->modulename = 'ITEM INFORMATION - ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
                    }
                }
                break;
        }

        $fields = [['lblitemdesc', 'lblaccessories'], ['itemdescription', 'accessories']];

        switch ($doc) {
            case 'STOCKCARD':
                array_push($fields, 'refresh');
                break;

            case 'QT':
            case 'QS':
                if (isset($config['params']['row'])) {
                    $trno = $config['params']['row']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['row']['itemname'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['dataparams']['itemname'];
                }

                $isposted = $this->othersClass->isposted2($trno, "transnum");

                array_push($fields, 'lblrem');
                array_push($fields, 'rem');

                array_push($fields, 'lbltimesetting');

                array_push($fields, ['leaddur']);

                if (!$isposted) {
                    array_push($fields, 'refresh');
                }
                break;

            case 'OS':
                if (isset($config['params']['row'])) {
                    $trno = $config['params']['row']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['row']['itemname'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['dataparams']['itemname'];
                }

                $isposted = $this->othersClass->isposted2($trno, "transnum");

                array_push($fields, 'lblrem');
                array_push($fields, 'rem');

                array_push($fields, 'lbltimesetting');
                array_push($fields, ['leadtimesettings', 'otherleadtime', 'leaddur', 'validity', 'isvalid', 'ovaliddate']);

                if (!$isposted) {
                    array_push($fields, 'refresh');
                }
                break;
            case 'SQ':
                array_push($fields, 'rem', 'leaddur');
                break;
            case 'BARCODEASSIGNING':
                if (isset($config['params']['row'])) {
                    $trno = $config['params']['row']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['row']['itemname'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['dataparams']['itemname'];
                }
                $fields = [['lblctrlno', 'ctrlno'], ['lblitemname', 'itemname'], ['lblspecs', 'specs'], ['lbluom', 'uom'], ['lblrequestor', 'requestorname'], ['lbldepartment', 'department'], ['lblprojname', 'projectname']];
                break;
            default:

                if (isset($config['params']['row'])) {
                    $trno = $config['params']['row']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['row']['itemname'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                    $this->modulename = 'ITEM INFORMATION - ' . $config['params']['dataparams']['itemname'];
                }

                switch ($doc) {
                    case 'RR':
                    case 'AC':
                    case 'DM':
                    case 'SJ':
                    case 'CM':
                    case 'AI':
                    case 'PC':
                    case 'AJ':
                    case 'TS':
                    case 'IS':
                        $isposted = $this->othersClass->isposted2($trno, "cntnum");
                        break;
                    default:
                        $isposted = $this->othersClass->isposted2($trno, "transnum");
                        break;
                }

                array_push($fields, 'lblrem');
                array_push($fields, 'rem');

                if (!$isposted) {
                    array_push($fields, 'refresh');
                }

                break;
        }

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'leaddur.label', 'Lead Time');
        switch ($doc) {
            case 'STOCKCARD':
                data_set($col1, 'refresh.label', 'Save');
                break;

            case 'QS':
            case 'QT':
                data_set($col1, 'itemdescription.readonly', true);
                data_set($col1, 'accessories.readonly', true);

                data_set($col1, 'refresh.label', 'update');
                data_set($col1, 'rem.class', 'csrem');
                data_set($col1, 'rem.type', 'wysiwyg');
                data_set($col1, 'rem.readonly', false);
                data_set($col1, 'rem.maxlength', '1000');

                if ($isposted) {
                    data_set($col1, 'leadfrom.readonly', true);
                    data_set($col1, 'leadto.readonly', true);
                    data_set($col1, 'leaddur.readonly', true);
                }
                break;

            case 'OS':

                data_set($col1, 'itemdescription.readonly', true);
                data_set($col1, 'accessories.readonly', true);

                data_set($col1, 'refresh.label', 'update');
                data_set($col1, 'leaddur.label', 'Lead Time');
                data_set($col1, 'rem.class', 'csrem');
                data_set($col1, 'rem.readonly', false);
                data_set($col1, 'rem.maxlength', '500');
                data_set($col1, 'leaddur.type', 'cinput');
                data_set($col1, 'validity.type', 'lookup');
                data_set($col1, 'validity.action', 'lookuprandom');
                data_set($col1, 'validity.lookupclass', 'lookup_validity');
                data_set($col1, 'validity.label', 'Validity Period');
                data_set($col1, 'validity.readonly', true);
                data_set($col1, 'leaddur.maxlength', '100');
                data_set($col1, 'validity.maxlength', '100');
                data_set($col1, 'isvalid.label', 'NON-RETURNABLE AND NON-CANCELLABLE');
                if ($isposted) {
                    data_set($col1, 'leaddur.readonly', true);
                    data_set($col1, 'validity.readonly', true);
                    data_set($col1, 'rem.readonly', true);
                    data_set($col1, 'otherleadtime.readonly', true);
                    data_set($col1, 'isvalid.readonly', true);
                    data_set($col1, 'ovaliddate.readonly', true);
                }
                break;
            case 'SQ':
                data_set($col1, 'itemdescription.readonly', true);
                data_set($col1, 'accessories.readonly', true);
                data_set($col1, 'leaddur.readonly', true);
                data_set($col1, 'rem.readonly', true);
                data_set($col1, 'rem.maxlength', '500');
                break;
            case 'BARCODEASSIGNING':
                data_set($col1, 'itemname.label', '');
                data_set($col1, 'itemname.type', 'textarea');
                data_set($col1, 'specs.label', '');
                data_set($col1, 'specs.readonly', true);
                data_set($col1, 'specs.type', 'textarea');
                data_set($col1, 'uom.label', '');
                data_set($col1, 'requestorname.type', 'input');
                data_set($col1, 'requestorname.label', '');
                data_set($col1, 'department.label', '');
                data_set($col1, 'projectname.label', '');
                data_set($col1, 'projectname.type', 'textarea');
                break;
            default:
                data_set($col1, 'itemdescription.readonly', true);
                data_set($col1, 'accessories.readonly', true);
                data_set($col1, 'rem.maxlength', '500');
                data_set($col1, 'refresh.label', 'update');
                data_set($col1, 'rem.class', 'csrem');
                data_set($col1, 'rem.readonly', false);
                data_set($col1, 'leaddur.readonly', true);
                if ($isposted) {
                    data_set($col1, 'rem.readonly', true);
                }
                break;
        }

        data_set($col1, 'itemdescription.type', 'textarea');
        data_set($col1, 'accessories.type', 'textarea');

        switch ($doc) {
            case 'BARCODEASSIGNING':
                $fields = [['lblpono', 'pono'], ['lblpodocno', 'podocno'], ['lblsupplier', 'supplier'], ['lblrrcost', 'rrcost'], ['lblgrossprofit', 'rrqty'], ['lblext', 'ext'], ['lblporem', 'porem']];
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'pono.type', 'input');
                data_set($col2, 'pono.label', '');
                data_set($col2, 'supplier.type', 'textarea');
                data_set($col2, 'rrcost.label', '');
                data_set($col2, 'rrcost.readonly', true);
                data_set($col2, 'ext.label', '');
                data_set($col2, 'lblext.label', 'Sub total:');
                data_set($col2, 'rrqty.label', '');
                data_set($col2, 'rrqty.readonly', true);
                data_set($col2, 'lblgrossprofit.label', 'Quantity:');
                data_set($col2, 'lblgrossprofit.style', 'font-weight:bold;font-size:16px');

                $fields = [['lblrrno', 'rrno'], ['lbldropwh', 'dropoffwarehouse'], ['lblmainwh', 'mainwh'], ['lblrrrem', 'rrrem']];
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'dropoffwarehouse.type', 'textarea');
                data_set($col3, 'dropoffwarehouse.label', '');
                data_set($col3, 'mainwh.type', 'textarea');
                break;
        }

        if ($doc == 'BARCODEASSIGNING') {
            return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
        } else {
            return array('col1' => $col1);
        }
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];
        $data = $this->getheaddata($config, $doc);
        if (empty($data)) {
            $data = $this->coreFunctions->opentable("select 0 as itemid, '' as itemdescription, '' as accessories,
        '' as rem, 0 as trno, 0 as line, 0 as isnew, 0 as leadfrom, 0 as leadto, '' as leaddur, '' as validity, null as otherleadtime,'0' as isvalid,null as ovaliddate, '' as leadtimesettings");
        }

        switch ($doc) {
            case 'STOCKCARD':
                break;

            default:
                if (isset($config['params']['row'])) {
                    $trno = $config['params']['row']['trno'];
                    $line = $config['params']['row']['line'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                    $line = $config['params']['dataparams']['line'];
                }

                switch ($doc) {
                    case 'RR':
                    case 'AC':
                    case 'DM':
                    case 'SJ':
                    case 'CM':
                    case 'AI':
                    case 'PC':
                    case 'AJ':
                    case 'TS':
                    case 'IS':
                        $isposted = $this->othersClass->isposted2($trno, "cntnum");
                        break;
                    default:
                        $isposted = $this->othersClass->isposted2($trno, "transnum");
                        break;
                }

                if ($isposted) {
                    $tablename = 'hstockinfotrans';
                } else {
                    $tablename = 'stockinfotrans';
                }

                switch ($doc) {
                    case 'AC':
                    case 'DM':
                    case 'SJ':
                    case 'CM':
                    case 'AI':
                    case 'AJ':
                    case 'TS':
                    case 'IS':
                        if ($isposted) {
                            $tablename = 'hstockinfo';
                        } else {
                            $tablename = 'stockinfo';
                        }
                        break;
                }

                $qry = "select trno, line, rem, 0 as isnew, ifnull(leadfrom,0) as leadfrom,
                ifnull(leadto,0) as leadto, ifnull(leaddur,'') as leaddur, ifnull(validity,'') as validity,otherleadtime as otherleadtime,ifnull(isvalid,'0')  as isvalid, ovaliddate, leadtimesettings from " . $tablename . " where trno=? and line=?";
                $res = $this->coreFunctions->opentable($qry, [$trno, $line]);

                if (!empty($res)) {
                    $data[0]->rem = $res[0]->rem;
                    $data[0]->trno = $res[0]->trno;
                    $data[0]->line = $res[0]->line;
                    $data[0]->isnew = $res[0]->isnew;
                    $data[0]->leadfrom = $res[0]->leadfrom;
                    $data[0]->leadto = $res[0]->leadto;
                    $data[0]->leaddur = $res[0]->leaddur;
                    $data[0]->validity = $res[0]->validity;
                    $data[0]->otherleadtime = $res[0]->otherleadtime;
                    $data[0]->isvalid = $res[0]->isvalid;
                    $data[0]->ovaliddate = $res[0]->ovaliddate;
                    $data[0]->leadtimesettings = $res[0]->leadtimesettings;
                } else {
                    $data[0]->rem = '';
                    $data[0]->trno = $trno;
                    $data[0]->line = $line;
                    $data[0]->isnew = 1;
                    $data[0]->leadfrom = 0;
                    $data[0]->leadto = 0;
                    $data[0]->leaddur = '';
                    $data[0]->validity = '';
                    $data[0]->otherleadtime = null;
                    $data[0]->isvalid = '0';
                    $data[0]->ovaliddate = null;
                    $data[0]->leadtimesettings = '';
                }
                break;
        }

        return $data;
    }

    public function getheaddata($config, $doc)
    {

        switch ($doc) {
            case 'STOCKCARD':
                $clientid = $config['params']['clientid'];
                break;
            case 'BARCODEASSIGNING':
                $data = $config['params']['row'];
                switch ($data['doc']) {
                    case 'PR':
                        $qry = "select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,'' as pono, '' as podocno, '' as supplier, '' as rrno, '' as dropoffwarehouse,
                            '' as mainwh, sinfo.requestorname, dept.clientname as department, head.clientname as projectname, '' as porem, '' as rrrem, stock.trno as reqtrno, stock.line as reqline, 0.0 as rrcost, 0.0 as ext, 0.0 as rrqty
                            from prhead as head
                            left join prstock as stock on stock.trno=head.trno
                            left join stockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                            left join client as dept on dept.clientid=head.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'] . "
                        union all
                        select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,'' as pono, '' as podocno, '' as supplier, '' as rrno, '' as dropoffwarehouse,
                        '' as mainwh, sinfo.requestorname, dept.clientname as department, head.clientname as projectname, '' as porem, '' as rrrem, stock.trno as reqtrno, stock.line as reqline, 0.0 as rrcost, 0.0 as ext, 0.0 as rrqty
                            from hprhead as head
                            left join hprstock as stock on stock.trno=head.trno
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                            left join client as dept on dept.clientid=head.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'];
                        break;
                    case 'CD':
                        $qry = "select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,
                            '' as pono, '' as podocno, head.clientname as supplier, '' as rrno, '' as dropoffwarehouse,
                            '' as mainwh, sinfo.requestorname, dept.clientname as department, prh.clientname as projectname, stock.rem as porem, '' as rrrem, stock.trno as reqtrno, stock.line as reqline, format(stock.rrcost,2) as rrcost, format(stock.ext,2) as ext, stock.rrqty
                            from cdhead as head
                            left join cdstock as stock on stock.trno=head.trno
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.reqtrno and sinfo.line=stock.reqline
                            left join hprhead as prh on prh.trno=sinfo.trno
                            left join client as dept on dept.clientid=prh.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'] . " and stock.void=0
                            union all
                            select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,
                            '' as pono, '' as podocno, head.clientname as supplier, '' as rrno, '' as dropoffwarehouse,
                            '' as mainwh, sinfo.requestorname, dept.clientname as department, prh.clientname as projectname, stock.rem as porem, '' as rrrem, stock.trno as reqtrno, stock.line as reqline, format(stock.rrcost,2) as rrcost, format(stock.ext,2) as ext, stock.rrqty
                            from hcdhead as head
                            left join hcdstock as stock on stock.trno=head.trno
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.reqtrno and sinfo.line=stock.reqline
                            left join hprhead as prh on prh.trno=sinfo.trno
                            left join client as dept on dept.clientid=prh.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'] . " and stock.void=0";
                        break;
                    case 'PO':
                        $qry = "select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,
                            head.yourref as pono, head.docno as podocno, client.clientname as supplier, '' as rrno, '' as dropoffwarehouse,
                            '' as mainwh, sinfo.requestorname, dept.clientname as department, prh.clientname as projectname, stock.rem as porem, '' as rrrem, stock.reqtrno, stock.reqline, format(stock.rrcost,2) as rrcost, format(stock.ext,2) as ext, stock.trno, stock.line, stock.rrqty
                            from pohead as head
                            left join postock as stock on stock.trno=head.trno
                            left join client on client.client=head.client
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.reqtrno and sinfo.line=stock.reqline
                            left join hprhead as prh on prh.trno=sinfo.trno
                            left join client as dept on dept.clientid=prh.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'] . " and stock.void=0
                            union all
                            select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,
                            head.yourref as pono, head.docno as podocno, client.clientname as supplier, '' as rrno, '' as dropoffwarehouse,
                            '' as mainwh, sinfo.requestorname, dept.clientname as department, prh.clientname as projectname, stock.rem as porem, '' as rrrem, stock.reqtrno, stock.reqline, format(stock.rrcost,2) as rrcost, format(stock.ext,2) as ext, stock.trno, stock.line, stock.rrqty
                            from hpohead as head
                            left join hpostock as stock on stock.trno=head.trno
                            left join client on client.client=head.client
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.reqtrno and sinfo.line=stock.reqline
                            left join hprhead as prh on prh.trno=sinfo.trno
                            left join client as dept on dept.clientid=prh.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'] . " and stock.void=0";
                        break;
                    case 'RR':
                        $qry = "select sinfo.ctrlno, sinfo.itemdesc as itemname, sinfo.specs, sinfo.unit as uom,
                            pohead.yourref as pono, pohead.docno as podocno, client.clientname as supplier, head.docno as rrno, dowh.clientname as dropoffwarehouse,
                            rrwh.clientname as mainwh, sinfo.requestorname, dept.clientname as department, prhead.clientname as projectname, '' as porem, stock.rem as rrrem, format(stock.rrcost,2) as rrcost, format(stock.ext,2) as ext, stock.rrqty
                            from lahead as head
                            left join lastock as stock on stock.trno=head.trno
                            left join client on client.client=head.client
                            left join hpohead as pohead on pohead.trno=stock.refx
                            left join cntnuminfo as rrinfo on rrinfo.trno=head.trno
                            left join client as dowh on dowh.clientid=rrinfo.dropoffwh
                            left join client as rrwh on rrwh.client=head.wh
                            left join hstockinfotrans as sinfo on sinfo.trno=stock.reqtrno and sinfo.line=stock.reqline
                            left join hprhead as prhead on prhead.trno=sinfo.trno
                            left join client as dept on dept.clientid=prhead.deptid
                            where stock.trno=" . $data['trno'] . " and stock.line=" . $data['line'];
                        break;
                }
                $returndata =  $this->coreFunctions->opentable($qry);

                switch ($data['doc']) {
                    case 'PR':
                        $qry = "select h.docno, h.yourref as pono, h.clientname, s.rem, s.rrcost, s.ext, rrqty from postock as s left join pohead as h on h.trno=s.trno where s.void=0 and s.reqtrno=" . $returndata[0]->reqtrno . " and s.reqline=" . $returndata[0]->reqline . "
                                union all
                                select h.docno, h.yourref as pono, h.clientname, s.rem, s.rrcost, s.ext, rrqty from hpostock as s left join hpohead as h on h.trno=s.trno where s.void=0 and s.reqtrno=" . $returndata[0]->reqtrno . " and s.reqline=" . $returndata[0]->reqline;
                        $prdata =  $this->coreFunctions->opentable($qry);
                        foreach ($prdata as $k) {
                            $returndata[0]->pono = $k->pono;
                            $returndata[0]->podocno = $k->docno;
                            $returndata[0]->porem = $k->rem;
                            $returndata[0]->supplier = $k->clientname;
                            $returndata[0]->rrcost = number_format($k->rrcost, 2);
                            $returndata[0]->ext = number_format($k->ext, 2);
                            $returndata[0]->rrqty = $k->rrqty;
                        }

                        $qry = "select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from lastock as s left join lahead as h on h.trno=s.trno left join client as wh on wh.client=h.wh left join cntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.reqtrno=" . $returndata[0]->reqtrno . " and s.reqline=" . $returndata[0]->reqline . "
                                union all
                                select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from glstock as s left join glhead as h on h.trno=s.trno left join client as wh on wh.clientid=h.whid left join hcntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.reqtrno=" . $returndata[0]->reqtrno . " and s.reqline=" . $returndata[0]->reqline;
                        $rrdata =  $this->coreFunctions->opentable($qry);
                        foreach ($rrdata as $k) {
                            $returndata[0]->rrno = $k->docno;
                            $returndata[0]->mainwh = $k->whname;
                            $returndata[0]->dropoffwarehouse = $k->whname2;
                            $returndata[0]->rrnotes = $k->rem;
                        }
                        break;

                    case 'CD':
                        $qry = "select h.docno, h.yourref as pono, h.clientname, s.rem, s.trno, s.line from postock as s left join pohead as h on h.trno=s.trno where s.void=0 and s.cdrefx=" . $returndata[0]->reqtrno . " and s.cdlinex=" . $returndata[0]->reqline . "
                                union all
                                select h.docno, h.yourref as pono, h.clientname, s.rem, s.trno, s.line from hpostock as s left join hpohead as h on h.trno=s.trno where s.void=0 and s.cdrefx=" . $returndata[0]->reqtrno . " and s.cdlinex=" . $returndata[0]->reqline;
                        $prdata =  $this->coreFunctions->opentable($qry);
                        foreach ($prdata as $k) {
                            $returndata[0]->pono = $k->pono;
                            $returndata[0]->podocno = $k->docno;
                            $returndata[0]->ponotes = $k->rem;
                            $returndata[0]->supplier = $k->clientname;

                            $qry = "select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from lastock as s left join lahead as h on h.trno=s.trno left join client as wh on wh.client=h.wh left join cntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.refx=" . $k->trno . " and s.reqline=" . $k->line . "
                                union all
                                select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from glstock as s left join glhead as h on h.trno=s.trno left join client as wh on wh.clientid=h.whid left join hcntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.refx=" . $k->trno . " and s.reqline=" . $k->line;
                            $rrdata =  $this->coreFunctions->opentable($qry);
                            foreach ($rrdata as $k) {
                                $returndata[0]->rrno = $k->docno;
                                $returndata[0]->mainwh = $k->whname;
                                $returndata[0]->dropoffwarehouse = $k->whname2;
                                $returndata[0]->rrnotes = $k->rem;
                            }
                        }
                        break;

                    case 'PO':
                        $qry = "select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from lastock as s left join lahead as h on h.trno=s.trno left join client as wh on wh.client=h.wh left join cntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.refx=" . $returndata[0]->trno . " and s.reqline=" . $returndata[0]->line . "
                                union all
                                select h.docno, s.rem, wh.clientname as whname, wh2.clientname as whname2
                                from glstock as s left join glhead as h on h.trno=s.trno left join client as wh on wh.clientid=h.whid left join hcntnuminfo as info on info.trno=h.trno left join client as wh2 on wh2.clientid=info.dropoffwh
                                where s.refx=" . $returndata[0]->trno . " and s.reqline=" . $returndata[0]->line;
                        $rrdata =  $this->coreFunctions->opentable($qry);
                        foreach ($rrdata as $k) {
                            $returndata[0]->rrno = $k->docno;
                            $returndata[0]->mainwh = $k->whname;
                            $returndata[0]->dropoffwarehouse = $k->whname2;
                            $returndata[0]->rrnotes = $k->rem;
                        }
                        break;
                }

                return $returndata;
                break;
            default:
                if (isset($config['params']['row'])) {
                    $clientid = $config['params']['row']['itemid'];
                } else {
                    $clientid = $config['params']['dataparams']['itemid'];
                }
                break;
        }

        $qry = "select itemid, ifnull(itemdescription,'') as itemdescription, ifnull(accessories,'') as accessories,
        '' as rem, 0 as trno, line as line, 0 as isnew ,'' as otherleadtime ,'0' as isvalid,'' as ovaliddate,'' as validity
        from iteminfo
        where itemid = ? ";

        return $this->coreFunctions->opentable($qry, [$clientid]);
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
        $doc = $config['params']['doc'];

        switch ($doc) {
            case 'STOCKCARD':
                $data = [];
                $itemid  = $config['params']['itemid'];

                $data = [
                    'itemdescription' => $config['params']['dataparams']['itemdescription'],
                    'accessories' => $config['params']['dataparams']['accessories'],
                    'editby' => $config['params']['user'],
                    'editdate' => $this->othersClass->getCurrentTimeStamp()
                ];

                if (!$this->checkiteminfodata($itemid)) {
                    $data['itemid'] = $itemid;
                    $this->coreFunctions->sbcinsert("iteminfo", $data);

                    $this->logger->sbcwritelog(
                        $itemid,
                        $config,
                        "CREATE ITEM INFO",
                        "[ITEM DESCRIPTION] => " . $data['itemdescription'] . " 
                        [ACCESSORIES] => " . $data['accessories'],
                        "item_log"
                    );
                } else {
                    $this->coreFunctions->sbcupdate("iteminfo", $data, ['itemid' => $itemid]);
                }

                return ['status' => true, 'msg' => 'Successfully saved.', 'data' => []];

                break;
            default:

                $isnew = $config['params']['dataparams']['isnew'];
                $trno = $config['params']['dataparams']['trno'];
                $line = $config['params']['dataparams']['line'];
                $rem = $this->othersClass->sanitizekeyfield('rem', $config['params']['dataparams']['rem']);
                $leadfrom = $config['params']['dataparams']['leadfrom'];
                $leadto = $config['params']['dataparams']['leadto'];
                $leaddur = $config['params']['dataparams']['leaddur'];
                $validity = $config['params']['dataparams']['validity'];
                $otherlead = $config['params']['dataparams']['otherleadtime'];
                $isvalid = $config['params']['dataparams']['isvalid'];
                $ovaliddate = $config['params']['dataparams']['ovaliddate'];
                $leadtimesettings = $config['params']['dataparams']['leadtimesettings'];

                $itemid = $config['params']['dataparams']['itemid'];

                $rem = strip_tags($rem, '<strike><b><i><u><div>'); // striptag

                switch ($doc) {
                    case 'OS':
                        switch (strtoupper($leadtimesettings)) {
                            case 'EX-STOCK':
                            case '2-3 WEEKS':
                                $leaddur = "";
                                break;
                        }
                        break;
                }

                $data = [
                    'trno' => $trno,
                    'line' => $line,
                    'rem' => $rem,
                    'leadfrom' => $leadfrom,
                    'leadto' => $leadto,
                    'leaddur' => $leaddur,
                    'validity' => $validity,
                    'otherleadtime' => $otherlead,
                    'isvalid' => $isvalid,
                    'ovaliddate' => $ovaliddate,
                    'leadtimesettings' => $leadtimesettings
                ];


                $tablename = 'stockinfotrans';
                $tablelogs = 'transnum_log';
                $tablelogs_del = 'del_transnum_log';

                switch ($doc) {
                    case 'AC':
                    case 'DM':
                    case 'SJ':
                    case 'CM':
                    case 'AI':
                    case 'AJ':
                    case 'TS':
                    case 'IS':
                        $tablename = 'stockinfo';
                        $tablelogs = 'table_log';
                        $tablelogs_del = 'del_table_log';

                        $data = [
                            'trno' => $trno,
                            'line' => $line,
                            'rem' => $rem,
                            'leadfrom' => $leadfrom,
                            'leadto' => $leadto,
                            'leaddur' => $leaddur,
                            'validity' => $validity,
                            'otherleadtime' => $otherlead,
                            'isvalid' => $isvalid,
                            'ovaliddate' => $ovaliddate
                        ];

                        break;
                }

                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];

                if ($isnew) {

                    if (!$this->checkdata($trno, $line, $tablename)) {
                        $this->coreFunctions->sbcinsert($tablename, $data);
                        $this->logger->sbcwritelog($trno, $config, 'CREATE', "REMARKS => " . $rem . " LEAD FROM => " . $leadfrom . " LEAD TO => " . $leadto . " LEAD DURATION => " . $leaddur . " VALIDITY => " . $validity, $tablelogs);
                    } else {
                        $stockinfodata = $this->stockinfo($trno, $line, $tablename);
                        $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);

                        foreach ($stockinfodata as $key => $value) {
                            foreach ($value as $key2 => $value2) {
                                if (array_key_exists($key2, $data)) {
                                    if ($value2 != $data[$key2]) {
                                        $this->logger->sbcwritelog($trno, $config, 'UPDATE', $key2 . '  ' .
                                            $this->getitemname($itemid) . '(' . $this->getbarcode($itemid) . ')  ' .
                                            $value2 . ' => ' . $data[$key2], $tablelogs);
                                    }
                                }
                            }
                        }
                    }
                } else {

                    $stockinfodata = $this->stockinfo($trno, $line, $tablename);
                    $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);

                    foreach ($stockinfodata as $key => $value) {
                        foreach ($value as $key2 => $value2) {
                            if (array_key_exists($key2, $data)) {
                                if ($value2 != $data[$key2]) {
                                    $this->logger->sbcwritelog($trno, $config, 'UPDATE', $key2 . '  ' .
                                        $this->getitemname($itemid) . '(' . $this->getbarcode($itemid) . ')  ' .
                                        $value2 . ' => ' . $data[$key2], $tablelogs);
                                }
                            }
                        }
                    }
                }

                $txtdata = $this->paramsdata($config);

                return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata];
                break;
        }
    }

    private function getitemname($itemid)
    {
        $qry = "select itemname as value from item where itemid = ?";
        return $this->coreFunctions->datareader($qry, [$itemid]);
    }

    private function getbarcode($itemid)
    {
        $qry = "select barcode as value from item where itemid = ?";
        return $this->coreFunctions->datareader($qry, [$itemid]);
    }

    private function stockinfo($trno, $line, $tablename)
    {
        $qry = "select trno, line, rem, leadfrom, leadto, leaddur, validity,otherleadtime, isvalid,ovaliddate from $tablename
        where trno = ? and line = ?";
        return $this->coreFunctions->opentable($qry, [$trno, $line]);
    }

    private function checkdata($trno, $line, $tblname)
    {
        $qry = "select trno from " . $tblname . " where trno = ? and line = ?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

        if ($data) {
            return true;
        } else {
            return false;
        }
    } // end fn

    private function checkiteminfodata($itemid)
    {
        $qry = "select itemid from iteminfo where itemid = ?";
        $data = $this->coreFunctions->opentable($qry, [$itemid]);

        if (!empty($data)) {
            return true;
        } else {
            return false;
        }
    } // end fn
}
