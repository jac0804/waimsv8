<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class updatepostedinfo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'UPDATE INFO';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'transnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';

    public $tablelogs = 'transnum_log';

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 24: //goodfound
                switch ($doc) {
                    case 'PACKHOUSELOADING':
                        $fields = [['assignedlane'], ['batchno'], 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                    case 'RELEASED':
                        $fields = [['weightin', 'weightout'], ['weightintime', 'weightouttime'], ['kilo', 'refresh']];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                }

                break;

            case 28: //xcomp
                $fields = [];
                if ($doc == 'RR') {
                    $fields = ['yourref', 'rem', 'refresh'];
                }
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'yourref.readonly', false);
                data_set($col1, 'rem.readonly', false);
                data_set($col1, 'refresh.label', 'Update');

                $fields = [];
                $col2 = $this->fieldClass->create($fields);
                break;
            case 8: //maxipro
                $fields = ['transtyperr',  'refresh'];
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'refresh.label', 'Update');
                data_set($col1, 'transtyperr.lookupclass', 'lookuptranstypeposted');
                $fields = [];
                $col2 = $this->fieldClass->create($fields);
                break;
            case 56: //homeworks

                switch ($doc) {
                    case 'RR':
                        $fields = ['checkdate', 'checkno', 'dvattype', 'dewt', 'refresh']; #action : lookupewt, lookupvattype
                        $col1 = $this->fieldClass->create($fields);                        #lookupclass: ewt, vattype
                        data_set($col1, 'checkdate.label', 'Counter Date');                #plotledger
                        data_set($col1, 'checkno.label', 'Counter #');
                        data_set($col1, 'checkdate.readonly', false);
                        data_set($col1, 'checkno.readonly', false);
                        data_set($col1, 'refresh.label', 'Update');

                        data_set($col1, 'dvattype.lookupclass', 'lookuppostedvattype');
                        data_set($col1, 'dewt.lookupclass', 'lookuppostedewt');
                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                    case 'PO':
                        $this->style = 'width:40%;max-width:40%;';
                        $fields = ['expiration', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'expiration.type', 'date');
                        data_set($col1, 'expiration.readonly', false);
                        data_set($col1, 'refresh.label', 'Update');
                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                }
                break;

            case 16: //ati
                switch ($doc) {
                    case 'PR':

                        $fields = ['categoryname', 'client', 'clientname', 'sadesc', 'svsdesc', 'podesc', 'potype',  'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'categoryname.type', 'lookup');
                        data_set($col1, 'categoryname.action', 'lookupreqcategory');
                        data_set($col1, 'categoryname.lookupclass', 'lookupreqcategoryposted');
                        data_set($col1, 'client.lookupclass', 'lookupprclientposted');

                        data_set($col1, 'potype.lookupclass', 'lookuppotypeposted');
                        data_set($col1, 'categoryname.label', 'Category');
                        data_set($col1, 'refresh.label', 'Update');

                        data_set($col1, 'svsdesc.lookupclass', 'lookupsvsdesc');
                        data_set($col1, 'podesc.lookupclass', 'lookuppodesc');

                        $fields = ['rem'];
                        $col2 = $this->fieldClass->create($fields);
                        data_set($col2, 'rem.readonly', false);
                        break;

                    case 'CV':
                        $fields = ['yourref', 'ourref', 'modeofpayment', 'rem',  'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'yourref.readonly', false);
                        data_set($col1, 'ourref.readonly', false);
                        data_set($col1, 'rem.readonly', false);
                        data_set($col1, 'modeofpayment.label', 'Payment Type');
                        data_set($col1, 'modeofpayment.action', 'lookuppaymenttype');
                        data_set($col1, 'modeofpayment.lookupclass', 'lookuppaymenttypeposted');
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;

                    case 'RR':
                        $fields = ['ourref', 'rem', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'ourref.readonly', false);
                        data_set($col1, 'rem.readonly', false);
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;

                    case 'OQ':
                        $fields = ['rem', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'rem.readonly', false);
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                    case 'PO':
                        $fields = ['client', 'clientname', 'yourref', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'yourref.readonly', false);
                        data_set($col1, 'yourref.label', 'PO No.');
                        data_set($col1, 'refresh.label', 'Update');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                }
                break;
            case 58: //cdo hrispayroll
            case 25:

                $systemtype = $this->companysetup->getsystemtype($config['params']);

                if ($systemtype == 'HRISPAYROLL') {
                    $fields = [['startdate', 'enddate'], 'remark', 'refresh'];
                    $col1 = $this->fieldClass->create($fields);
                    data_set($col1, 'startdate.readonly', false);
                    data_set($col1, 'startdate.label', 'Date Start');
                    data_set($col1, 'enddate.label', 'Date Completed');
                    data_set($col1, 'refresh.label', 'Update');
                    $fields = [];
                    $col2 = $this->fieldClass->create($fields);
                }

                break;
        }

        return array('col1' => $col1, 'col2' =>  $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 24: //goodfound
                switch ($doc) {
                    case 'PACKHOUSELOADING':
                        $select = "select " . $trno . " as trno,ifnull(info.batchno,'') as batchno,ifnull(info.assignedlane,'') as assignedlane from cntnuminfo as info where info.trno=$trno";
                        $data = $this->coreFunctions->opentable($select);
                        break;
                    case 'RELEASED':
                        $select = "select " . $trno . " as trno,ifnull(info.weightout,0) as weightout,ifnull(info.weightouttime,'') as weightouttime,ifnull(info.weightin,0) as weightin,ifnull(info.weightintime,'') as weightintime,
                        ifnull(info.kilo,0) as kilo 
                        from cntnuminfo as info where info.trno=$trno";
                        $data = $this->coreFunctions->opentable($select);
                        break;
                }
                break;

            case 28: //xcomp
                if ($doc == 'RR') {
                    $select = "select " . $trno . " as trno, h.yourref, h.rem from glhead as h where h.trno=$trno";
                    $data = $this->coreFunctions->opentable($select);
                }
                break;
            case 8: //maxipro
                if ($doc == 'RR') {
                    $select = "select " . $trno . " as trno, h.ourref, h.yourref, h.rem,info.transtype as transtyperr
                               from glhead as h 
                               left join hcntnuminfo as info on info.trno=h.trno where h.trno=?";
                    $data = $this->coreFunctions->opentable($select, [$trno]);
                }
                break;

            case 56: //homeworks
                switch ($doc) {
                    case 'RR':
                        $select = "select " . $trno . " as trno, h.checkdate,h.checkno,h.ewtrate as ewtid,h.ewt,h.tax,h.vattype,h.vattype as dvattype,h.ewt as dewt
                         from glhead as h where h.trno=$trno";
                        $data = $this->coreFunctions->opentable($select);
                        break;
                    case 'PO':
                        $select = "select " . $trno . " as trno, h.expiration from hpohead as h where h.trno=$trno";
                        $data = $this->coreFunctions->opentable($select);
                        break;
                }
                break;
            case 16: //ati
                switch ($doc) {
                    case 'PR':
                        $select = "select " . $trno . " as trno, h.ourref, ifnull(cat.category,'') as categoryname,
                            h.sano, ifnull(sa.sano,'') as sadesc,
                            h.svsno, ifnull(svs.sano,'') as svsdesc,
                            h.pono, ifnull(po.sano,'') as podesc, h.rem, h.potype, h.client, h.clientname,
                            ifnull(dept.client,'') as dept, ifnull(dept.clientname,'') as deptname,h.deptid
                            from hprhead as h 
                            left join reqcategory as cat on cat.line=h.ourref 
                            left join clientsano as sa on sa.line=h.sano
                            left join clientsano as svs on svs.line=h.svsno
                            left join clientsano as po on po.line=h.pono
                            left join client as dept on dept.clientid = h.deptid
                            where trno=?";
                        $data = $this->coreFunctions->opentable($select, [$trno]);
                        break;

                    case 'CV':
                    case 'RR':
                        $select = "select " . $trno . " as trno, h.ourref, h.yourref, h.rem, h.modeofpayment from lahead as h where trno=?";
                        $data = $this->coreFunctions->opentable($select, [$trno]);
                        break;
                    case 'OQ':
                        $select = "select " . $trno . " as trno, h.rem from oqhead as h where trno=?";
                        $data = $this->coreFunctions->opentable($select, [$trno]);
                        break;
                    case 'PO':
                        $select = "select " . $trno . " as trno, client,clientname,yourref from hpohead as h where trno=?";
                        $data = $this->coreFunctions->opentable($select, [$trno]);
                        break;
                }
                break;
            case 58: //cdo hrispayroll
            case 25:
                if ($systemtype == 'HRISPAYROLL') {
                    switch ($doc) {
                        case 'HQ':
                            $select = "select " . $trno . " as trno, startdate, enddate, remark
                               from hpersonreq where trno=?";
                            $data = $this->coreFunctions->opentable($select, [$trno]);
                            break;
                    }
                }

                break;
        }
        return $data;
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
        $companyid = $config['params']['companyid'];
        $backlisting = true;
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        switch ($companyid) {
            case 24: //goodfound
                $backlisting = false;
                switch ($doc) {
                    case 'RELEASED':
                        $trno = $config['params']['dataparams']['trno'];
                        $weightout = $config['params']['dataparams']['weightout'];
                        $weightouttime = $config['params']['dataparams']['weightouttime'];
                        $weightin = $config['params']['dataparams']['weightin'];
                        $weightintime = $config['params']['dataparams']['weightintime'];

                        $qry = "select sum(stock.iss) as value from lastock as stock
                        left join item as i on i.itemid=stock.itemid
                        where stock.trno =$trno
                        and stock.iscomponent=0 and i.fg_isfinishedgood=1
                        and i.body not in
                        ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')";

                        $qty = floatval($this->coreFunctions->datareader($qry));

                        $weightin = isset($weightin) ? $weightin : 0;
                        $weightout = isset($weightout) ? $weightout : 0;

                        $kilo = ($weightout - $weightin) / $qty;


                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'weightout' => $weightout,
                            'weightouttime' => $weightouttime,
                            'weightin' => $weightin,
                            'weightintime' => $weightintime,
                            'kilo' => $kilo
                        ];

                        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
                        break;
                    case 'PACKHOUSELOADING':
                        $trno = $config['params']['dataparams']['trno'];
                        $batchno = $config['params']['dataparams']['batchno'];
                        $assignedlane = $config['params']['dataparams']['assignedlane'];

                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'batchno' => $batchno,
                            'assignedlane' => $assignedlane
                        ];

                        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
                        break;
                }
                break;

            case 28: //xcomp
                if ($doc) {
                    $trno = $config['params']['dataparams']['trno'];
                    $yourref = $config['params']['dataparams']['yourref'];
                    $rem = $config['params']['dataparams']['rem'];

                    $data = [
                        'trno' => $trno,
                        'editby' => $config['params']['user'],
                        'editdate' => $this->othersClass->getCurrentTimeStamp(),
                        'yourref' =>  $yourref,
                        'rem' =>  $rem
                    ];

                    $this->coreFunctions->sbcupdate('glhead', $data, ['trno' => $trno]);
                }
                break;

            case 56: //homeworks
                switch ($doc) {
                    case 'RR':
                        $trno = $config['params']['dataparams']['trno'];
                        $checkdate = $config['params']['dataparams']['checkdate'];
                        $checkno = $config['params']['dataparams']['checkno'];

                        $vattype = $config['params']['dataparams']['vattype'];
                        $tax = $config['params']['dataparams']['tax'];
                        $ewt = $config['params']['dataparams']['ewt'];
                        $ewtid = $config['params']['dataparams']['ewtid'];

                        $data = [
                            'trno' => $trno,
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'checkdate' =>  $checkdate,
                            'checkno' =>  $checkno,
                            'vattype' => $vattype,
                            'tax' => $tax,
                            'ewtrate' => $ewtid,
                            'ewt' => $ewt
                        ];

                        $this->coreFunctions->sbcupdate('glhead', $data, ['trno' => $trno]);
                        break;
                    case 'PO':
                        $trno = $config['params']['dataparams']['trno'];
                        $expiration = $config['params']['dataparams']['expiration'];


                        $data = [
                            'trno' => $trno,
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'expiration' =>  $expiration
                        ];

                        $this->coreFunctions->sbcupdate('hpohead', $data, ['trno' => $trno]);
                        break;
                }
                break;

            case 16: //ati
                $backlisting = false;
                switch ($doc) {
                    case 'PR':
                        $trno = $config['params']['dataparams']['trno'];
                        $ourref = $config['params']['dataparams']['ourref'];
                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'ourref' =>  $ourref,
                            'client' => $config['params']['dataparams']['client'],
                            'clientname' => $config['params']['dataparams']['clientname'],
                            'sano' => $config['params']['dataparams']['sano'],
                            'svsno' => $config['params']['dataparams']['svsno'],
                            'pono' => $config['params']['dataparams']['pono'],
                            'potype' => $config['params']['dataparams']['potype'],
                            'rem' => $config['params']['dataparams']['rem']
                        ];

                        $this->coreFunctions->LogConsole(json_encode($data));
                        $this->coreFunctions->sbcupdate('hprhead', $data, ['trno' => $trno]);

                        $cdqry = "select trno,reqtrno,reqline from cdstock where reqtrno=?
                                  union all
                                  select trno,reqtrno,reqline from hcdstock where reqtrno=?";
                        $cd = $this->coreFunctions->opentable($cdqry, [$trno, $trno]);

                        foreach ($cd as $key2 => $value) {
                            $cdstock = [
                                'catid' => $ourref
                            ];
                            $cdhead = [
                                'yourref' => $config['params']['dataparams']['potype']
                            ];

                            $this->coreFunctions->sbcupdate("hcdhead", $cdhead, ['trno' => $cd[$key2]->trno]);
                            $this->coreFunctions->sbcupdate("cdhead", $cdhead, ['trno' => $cd[$key2]->trno]);
                            $this->coreFunctions->sbcupdate("hcdstock", $cdstock, ['reqtrno' => $trno]);
                            $this->coreFunctions->sbcupdate("cdstock", $cdstock, ['reqtrno' => $trno]);
                        }

                        $poqry = "select trno,reqtrno,reqline from postock where reqtrno=?
                                  union all
                                  select trno,reqtrno,reqline from hpostock where reqtrno=?";
                        $po = $this->coreFunctions->opentable($poqry, [$trno, $trno]);

                        foreach ($po as $key3 => $value) {
                            $pohead = [
                                'ourref' => $config['params']['dataparams']['potype']
                            ];

                            $poinfo = [
                                'categoryid' => $ourref
                            ];

                            $this->coreFunctions->sbcupdate("pohead", $pohead, ['trno' => $po[$key3]->trno]);
                            $this->coreFunctions->sbcupdate("hpohead", $pohead, ['trno' => $po[$key3]->trno]);
                            $this->coreFunctions->sbcupdate("headinfotrans", $poinfo, ['trno' => $po[$key3]->trno]);
                            $this->coreFunctions->sbcupdate("hheadinfotrans", $poinfo, ['trno' => $po[$key3]->trno]);
                        }

                        break;

                    case 'CV':
                        $trno = $config['params']['dataparams']['trno'];

                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'ourref' =>  $config['params']['dataparams']['ourref'],
                            'yourref' =>  $config['params']['dataparams']['yourref'],
                            'modeofpayment' =>  $config['params']['dataparams']['modeofpayment'],
                            'rem' =>  $config['params']['dataparams']['rem']
                        ];
                        $this->coreFunctions->sbcupdate('lahead', $data, ['trno' => $trno]);
                        break;
                    case 'RR':
                        $trno = $config['params']['dataparams']['trno'];

                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'ourref' =>  $config['params']['dataparams']['ourref'],
                            'yourref' =>  $config['params']['dataparams']['yourref'],
                            'rem' =>  $config['params']['dataparams']['rem']
                        ];



                        $this->coreFunctions->sbcupdate('lahead', $data, ['trno' => $trno]);
                        break;

                    case 'OQ':
                        $trno = $config['params']['dataparams']['trno'];

                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'rem' =>  $config['params']['dataparams']['rem']
                        ];

                        $this->coreFunctions->sbcupdate('oqhead', $data, ['trno' => $trno]);
                        break;
                    case 'PO':
                        $trno = $config['params']['dataparams']['trno'];

                        $data = [
                            'editby' => $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'yourref' =>  $config['params']['dataparams']['yourref']
                        ];

                        $this->coreFunctions->sbcupdate('hpohead', $data, ['trno' => $trno]);
                        break;
                }
                break;
            case 8: //maxipro
                $backlisting = false;
                switch ($doc) {
                    case 'RR':
                        $trno = $config['params']['dataparams']['trno'];
                        $chktranstype = $this->coreFunctions->getfieldvalue("hcntnuminfo", "trno", "trno=?", [$trno]);
                        $info = [];
                        if (empty($chktranstype)) {
                            $info['trno'] = $trno;
                            $info['transtype'] = $config['params']['dataparams']['transtyperr'];
                            $this->coreFunctions->sbcinsert('hcntnuminfo', $info);
                        } else {
                            $info['transtype'] = $config['params']['dataparams']['transtyperr'];
                            $this->coreFunctions->sbcupdate('hcntnuminfo', $info, ['trno' => $trno]);
                        }
                        break;
                }
                break;
            case 58: //cdo hrispayroll
            case 25:
                if ($systemtype == 'HRISPAYROLL') {
                    $backlisting = false;
                    switch ($doc) {
                        case 'HQ': //Personnel Requisition
                            $trno = $config['params']['dataparams']['trno'];
                            $info = [
                                'startdate' => $config['params']['dataparams']['startdate'],
                                'enddate' => $config['params']['dataparams']['enddate'],
                                'remark' =>  $config['params']['dataparams']['remark']
                            ];

                            $this->coreFunctions->sbcupdate('hpersonreq', $info, ['trno' => $trno]);
                            $startdatehere = $this->coreFunctions->datareader("select d.startdate as value from hpersonreq as d  where d.trno=?", [$trno]);
                            $enddatehere = $this->coreFunctions->datareader("select d.enddate as value from hpersonreq as d  where d.trno=?", [$trno]);

                            if (!empty($startdatehere) && !empty($enddatehere)) {  //kapag parehas may data, idedelete sa pending app
                                $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno, 'delete');
                            }

                            break;
                    }
                }


                break;
        }


        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [],  'backlisting' => $backlisting, 'reloadhead' => true];
    }
}
