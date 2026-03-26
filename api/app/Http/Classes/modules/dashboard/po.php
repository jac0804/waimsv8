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

class po
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PO TRANSACTION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    // private $table = 'cntnum';
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

        $column = ['action', 'status', 'dateid', 'docno', 'client', 'amount', 'rem'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];

        $stockbuttons = ['jumpmodule'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:5%;max-width:10%;';
        $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:5%;max-width:10%;';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer/Supplier';
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:15%';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:5%;max-width:10%;';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:5%;max-width:10%;';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = [];
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

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['lookupclass'];
        $classid = $config['params']['classid'];
        switch ($classid) {
            case 'posted':
                $this->modulename = 'POSTED - PO TRANSACTION';
                break;
            case 'unposted':
                $this->modulename = 'UNPOSTED - PO TRANSACTION';
                break;
        }
        return $this->coreFunctions->opentable("select left(now(),10) as dateid,? as classid, '" . $doc . "' as doc ", [$classid]);
    }

    public function data($config)
    {
        return [];
    }

    public function loaddata($config)
    {
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['dataparams']['doc'];
        $center = $config['params']['center'];
        $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $classid = $config['params']['dataparams']['classid'];
        $url = $this->checkdoc($doc, $companyid);
        switch ($classid) {
            case 'posted':
                $qry = "select trno,docno,doc,dateid,clientname as client,rem,format(amount,2) as amount,
format(db,2) as db,format(cr,2) as cr,format(bal,2) as bal,'DBTODO' as tabtype, '$url' as url,'module' as moduletype,'POSTED' as status
from (
select head.trno,head.docno,head.doc,date(head.dateid) as dateid,client.clientname,head.rem,sum(ifnull(stock.ext,0)) as amount,
0 as db,0 as cr,0 as bal
from hpohead as head
left join hpostock as stock on stock.trno = head.trno
left join client on client.client = head.client
left join transnum as num on num.trno = head.trno
where head.doc = '$doc' and num.center = '$center' and (head.dateid) >= '$date'
group by head.trno,head.docno,head.doc,date(head.dateid),client.clientname,head.rem
) as so order by dateid desc,docno";
                break;
            case 'unposted':
                $qry = "select trno,docno,doc,dateid,clientname as client,rem,format(amount,2) as amount,
format(db,2) as db,format(cr,2) as cr,format(bal,2) as bal,'DBTODO' as tabtype, '$url' as url,'module' as moduletype,'UNPOSTED' as status 
from (
select head.trno,head.docno,head.doc,date(head.dateid) as dateid,client.clientname,head.rem,sum(ifnull(stock.ext,0)) as amount,
0 as db,0 as cr,0 as bal
from pohead as head
left join postock as stock on stock.trno = head.trno
left join client on client.client = head.client
left join transnum as num on num.trno = head.trno
where head.doc = '$doc' and num.center = '$center' and (head.dateid) >= '$date'
group by head.trno,head.docno,head.doc,date(head.dateid),client.clientname,head.rem
) as so order by dateid desc,docno";
                break;
        }
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
    }


    public function checkdoc($doc, $companyid)
    {
        $url = '';
        switch (strtolower($doc)) {
            case 'po':
                $folderloc = 'purchase';
                $url = "/module/" . $folderloc . "/";
                break;
        }
        return $url;
    }
} //end class
