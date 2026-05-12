<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;
use DateTime;

class barangaylookup
{
    private $othersClass;
    private $sqlquery;
    private $coreFunctions;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
    }
    public function getisbrgyclient($config)
    {
        $doc = $config['params']['doc'];
        $lookupclass = $config['params']['lookupclass'];
        $plotting = '';

        switch ($lookupclass) {
            case 'getbrgyclient2':
                $plotting = array(
                    'brgy' => 'client',
                    'clientname' => 'clientname',
                    'lname' => 'lname',
                    'fname' => 'fname',
                    'mname' => 'mmname',
                    'addr' => 'address',
                    'province' => 'province',
                    'bday' => 'bday',
                    'bplace' => 'bplace',
                    'contactno' => 'contactno',
                    'employer' => 'employer',
                    'rem2' => 'rem',
                    'civilstatus' => 'civilstatus',
                    'sex' => 'sex',
                    'citizenship' => 'citizenship',
                    'position' => 'occupation1'
                );
                break;
            case 'lookupbrgyidclearance':
                $plotting = array(
                    'client' => 'client',
                    'clientid' => 'clientid',
                    'address' => 'address',
                    'addressno' => 'addressno',
                    'clientname' => 'clientname',
                    'bday' => 'bday',
                    'sex' => 'sex',
                    'addr' => 'address',
                    'civilstatus' => 'civilstatus',
                    'height' => 'height',
                    'weight' => 'weight',
                    'settlertype' => 'settlertype',
                    'contactno' => 'contactno',
                    'relation' => 'relation',
                    'names' => 'names'
                );
                break;
            default:
                $plotting = array('client' => 'client', 'clientid' => 'clientid', 'address' => 'address', 'addressno' => 'addressno', 'clientname' => 'clientname');
                break;
        }

        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'keyid',
            'title' => 'List of Barangay Member',
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => $plotting
        );

        // lookup columns
        $cols = array(
            array('name' => 'client', 'label' => 'Barangay ID', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Full Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'address', 'label' => 'Address', 'align' => 'left', 'field' => 'address', 'sortable' => true, 'style' => 'font-size:16px;'),
        );
        $contact = "ifnull(client.mobile,'') as contactno";
        if ($doc == 'BK') {
            $contact = "ifnull(info.contactno,'') as contactno";
        }
        $query = "  
      select client,concat(info.lname,', ',info.fname,' ',info.mname) as clientname,ifnull(info.address,'') as addressno,client.clientid as keyid, client.clientid,client.addr as address,
             ifnull(info.lname,'') as lname, ifnull(info.fname,'') as fname, ifnull(info.mname,'') as mmname,ifnull(client.province,'') as province,client.bday,
             ifnull(info.bplace,'') as bplace,$contact,ifnull(info.employer,'') as employer,ifnull(client.rem,'') as rem,
             info.civilstatus,client.sex,info.citizenship,ifnull(info.occupation1,'') as occupation1,info.height,info.weight,info.settlertype,ifnull(info.relation,'') as relation,ifnull(info.names,'') as names

    from client
    left join clientinfo as info on info.clientid = client.clientid 
    where isbrgy = 1";
        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function getbusinessclr($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'keyid',
            'title' => 'List of Business',
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
                'client' => 'client',
                'clientid' => 'clientid',
                'address' => 'addr',
                'bstype' => 'bstyle',
                'clientname' => 'clientname',
                'ownername' => 'owner',
                'owneraddr' => 'addr2',
                'contact' => 'contact',
                'rem' => 'rem',
                'ownertype' => 'building',
                'trnxtype' => 'clientpref',
                'plateno' => 'plateno'

            )
        );

        // lookup columns
        $cols = array(
            array('name' => 'client', 'label' => 'Barangay ID', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Business Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'addr', 'label' => 'Business Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'bstyle', 'label' => 'Business Type', 'align' => 'left', 'field' => 'bstyle', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $query = "  
    select client,clientname,addr,client.clientid as keyid, client.clientid,type,owner,addr2,bstyle,contact,rem,building,clientpref,plateno
    from client
    where isbusiness = 1";
        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function localclearace($config)
    {

        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'keyid',
            'title' => 'Local Clearance Rate Type',
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array('purposeid' => 'line', 'purpose' => 'purpose', 'amount' => 'price')
        );

        // lookup columns
        $cols = array(
            array('name' => 'purpose', 'label' => 'Clearance', 'align' => 'left', 'field' => 'purpose', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'price', 'label' => 'Rate', 'align' => 'left', 'field' => 'price', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $query = "select line as keyid,line, clearance as purpose,format(price, 2) as price from locclearance";

        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }


    public function brgycomplaint($config)
    {
        $doc = $config['params']['doc'];

        if ($doc == 'MH') {
            $title = 'Summon';
        } else {
            $title = 'List of Brgy Complaint';
        }

        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'keyid',
            'title' => $title,
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
                'layref' => 'docno',
                'clientname' => 'clientname',
                'address' => 'address',
                'contact' => 'contact',
                'ownername' => 'ownername',
                'owneraddr' => 'owneraddr',
                'orderno' => 'orderno',
                'crno' => 'crno',
                'conaddr' => 'conaddr',
                'creditinfo' => 'creditinfo',
                'ourref' => 'ourref',
                'bstype' => 'bstype',
                'refdate' => 'dateid'
            )
        );

        // lookup columns
        $cols = array(
            array('name' => 'docno', 'label' => 'Brgy Case #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Complainant Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'ownername', 'label' => 'Respondent Name', 'align' => 'left', 'field' => 'ownername', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'crno', 'label' => 'For', 'align' => 'left', 'field' => 'crno', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'conaddr', 'label' => 'T.D.P.O', 'align' => 'left', 'field' => 'conaddr', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        if ($doc == 'MH') {
            $doc = 'MN';
        } else {
            $doc = 'JU';
        }

        switch ($doc) {
            case 'MN':
                $table = 'hmnhead';
                break;
            case 'JU':
                $table = 'hjuhead';
                break;
        }

        $query = "select head.trno as keyid,head.docno, ifnull(head.clientname,'') as clientname,
        ifnull(head.address,'') as address,ifnull(head.contact,'') as contact,head.dateid,
        ifnull(head.bstype,'') as bstype, ifnull(head.ownername,'') as ownername,
        ifnull(head.owneraddr,'') as owneraddr,ifnull(head.orderno,'') as orderno,
        ifnull(head.ourref,'') as ourref, ifnull(head.crno,'') as crno,ifnull(head.conaddr,'') as conaddr,
        ifnull(head.creditinfo,'') as creditinfo
        from $table as head
        left join transnum as num on num.trno=head.trno
        where num.doc = '$doc' and head.isfinish <>1";
        $data = $this->coreFunctions->opentable($query);
        
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}
