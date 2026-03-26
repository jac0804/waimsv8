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

class pendingtodo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PENDING TO DO';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:900px;max-width:900px;';
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

        $tab = [
            $this->gridname => [
                'gridcolumns' => ['action', 'docno', 'user', 'seendate']
            ]
        ];

        $stockbuttons = ['jumpmodule'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
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
    }

    public function paramsdata($config)
    {
        $row = $config['params']['row'];
        $doc = $row['doc'];
        $user = $config['params']['user'];
        switch ($doc) {
            case 'PO':
            case 'PR':
            case 'SO':
            case 'PC':
            case 'PQ':
            case 'SV':
            case 'KR':
            case 'OQ':
                $todo = 'transnumtodo';
                $num = 'transnum';
                $head = strtolower($doc . 'head');
                break;
            case 'SJ':
            case 'CM':
            case 'RR':
            case 'DM':
            case 'AJ':
            case 'TS':
            case 'IS':
            case 'AP':
            case 'PV':
            case 'CV':
            case 'AR':
            case 'CR':
            case 'GJ':
            case 'GD':
            case 'GC':
            case 'DS':
                $head = 'lahead';
                $todo = 'cntnumtodo';
                $num = 'cntnum';
                break;
        }
        $url = $this->checkdoc($doc);

        $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);

        $qry = "select todo.line,todo.trno,t.doc,ph.docno,'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype,(select clientname as users from client where client.clientid=todo.clientid
                union all
                select name as users from useraccess where useraccess.userid=todo.userid) as user,todo.seendate
                from $todo as todo
                left join $num as t on t.trno=todo.trno
                left join $head as ph on ph.trno=todo.trno
                where todo.createby= $userid and t.doc='$doc' and t.postdate is null and todo.donedate is null";
        return $this->coreFunctions->opentable($qry);
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

    public function loaddata($config)
    {
    }


    public function checkdoc($doc)
    {
        $url = '';
        switch (strtolower($doc)) {
            case 'dm':
            case 'rr':
            case 'po':
            case 'pr':
                $url = "/module/purchase/";
                break;
            case 'oq':
                $url = "/module/ati/";
                break;
            case 'so':
            case 'sj':
            case 'cm':
                $url = "/module/sales/";
                break;
            case 'pc':
            case 'aj':
            case 'ts':
            case 'is':
                $url = "/module/inventory/";
                break;
            case 'pq':
            case 'sv':
            case 'ap':
            case 'pv':
            case 'cv':
                $url = "/module/payable/";
                break;
            case 'ar':
            case 'cr':
            case 'kr':
                $url = "/module/receivable/";
                break;
            case 'gj':
            case 'gd':
            case 'gc':
            case 'ds':
                $url = "/module/accounting/";
                break;
        }
        return $url;
    }
} //end class
