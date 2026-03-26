<?php

namespace App\Http\Classes\modules\dashboard;

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

class sj
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SJ TRANSACTION';
    public $gridname = 'customformacctg';
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
    }

    public function createTab($config)
    {
        $column = ['action', 'status', 'dateid', 'docno', 'db', 'cr', 'bal', 'ref', 'rem'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];

        $stockbuttons = [];
        // 'action'
        $stockbuttons = ['jumpmodule'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // 3 = db
        $obj[0][$this->gridname]['columns'][$db]['align'] = 'right';
        // 4 = cr
        $obj[0][$this->gridname]['columns'][$cr]['align'] = 'right';
        // 5 = bal
        $obj[0][$this->gridname]['columns'][$bal]['align'] = 'right';


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['dateid'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.readonly', false);

        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'rr');

        $fields = [['db', 'cr']];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['bal'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['lookupclass'];
        $classid = $config['params']['classid'];
        switch ($classid) {
            case 'posted':
                $this->modulename = 'POSTED - SJ TRANSACTION';
                break;
            case 'unposted':
                $this->modulename = 'UNPOSTED - SJ TRANSACTION';
                break;
        }
        return $this->coreFunctions->opentable("select left(now(),10) as dateid, 0.0 as db, 0.0 as cr,0.0 as bal,? as classid, '" . $doc . "' as doc ", [$classid]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['dataparams']['doc'];
        $url = $this->checkdoc($doc, $companyid);
        $center = $config['params']['center'];
        $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $classid = $config['params']['dataparams']['classid'];
        $filter = " and date(head.dateid) >='$date'";
        if ($companyid == 47){//kstar        
            $filter = " and date(head.dateid) ='$date' ";
        }
        switch ($classid) {
            case 'posted':
                $qry = "select trno,line,docno,doc,clientname as client,dateid,format(db,2) as db,format(cr,2) as cr,format(bal,2) as bal,rem,ref,status,'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype from (
                    select ar.trno,ar.line,ar.docno,num.doc,date(
                    ar.dateid) as dateid,client.clientname,ar.db,ar.cr,ar.bal,detail.rem,head.ourref as ref,'POSTED'status 
                    from arledger as ar
                    left join gldetail as detail on detail.trno = ar.trno and detail.line = ar.line
                    left join cntnum as num on num.trno = ar.trno
                    left join glhead as head on head.trno = detail.trno
                    left join client on client.clientid = head.clientid
                    where num.doc = '$doc' and num.center = '$center' and ar.bal > 0 $filter
                    ) as sj order by dateid,docno desc";
                break;
            case 'unposted':
                $qry = "select trno,docno,doc,dateid,clientname as client,format(cr,2) as cr,format(db,2) as db,format(bal,2) as bal,rem,'' as ref,status,
                'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype from (
                select head.trno,head.docno,head.doc,date(head.dateid) as dateid, client.clientname,
                ifnull(sum(detail.ext),0) as db,0 as cr,ifnull(sum(detail.ext),0) as bal,detail.rem,'' as ref,'UNPOSTED' as status
                from lahead as head
                left join lastock as detail on detail.trno = head.trno
                left join cntnum as num on num.trno = head.trno
                left join client on client.client = head.client
                where num.doc = '$doc' and num.center = '$center' $filter
                group by head.trno,head.docno,head.doc,head.dateid,client.clientname,detail.rem
                ) as sj order by dateid,docno desc";
                break;
        }

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
    }
    public function checkdoc($doc, $companyid)
    {
        $url = '';
        switch (strtolower($doc)) {
            case 'sj':
                $folderloc = 'sales';
                if ($companyid == 47) { //kstar
                    $folderloc = 'kitchenstar';
                }
                $url = "/module/" . $folderloc . "/";
                break;
        }
        return $url;
    }
} //end class
