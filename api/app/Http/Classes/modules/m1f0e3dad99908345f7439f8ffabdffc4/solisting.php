<?php

namespace App\Http\Classes\modules\m1f0e3dad99908345f7439f8ffabdffc4;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;

class solisting
{
    public $modulename = 'PENDING SALES ORDER MONITORING';
    public $gridname = 'inventory';

    public $tablenum = 'transnum';
    public $head = 'sohead';
    public $stock = 'sostock';

    public $hhead = 'hsohead';
    public $hstock = 'hsostock';

    public $tablelogs = 'transnum_log';

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $helpClass;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;

    public $showfilterlabel = [];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->helpClass = new helpClass;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5020,
            'view' => 5020,
            'edit' => 5020,
            'viewrem' => 5021,
            'editrem' => 5022,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5021);
        $addaccess = $this->othersClass->checkAccess($config['params']['user'], 5022);

        $columns = ['action', 'docno', 'clientname', 'address', 'shipto', 'agent', 'terms', 'postdate', 'elapsed',  'amt', 'prref', 'rem'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['jumpmodule'];

        if ($viewaccess) {
            array_push($stockbuttons, 'showremhistory');
        }
        if ($addaccess) {
            array_push($stockbuttons, 'customformremarks');
        }

        $cols = $this->tabClass->createdoclisting($columns, $stockbuttons);

        $cols[$action]['style'] = 'width:140px;whiteSpace: normal;min-width:140px; max-width:140px;';
        $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:250px; max-width:300px;';
        $cols[$address]['style'] = 'width:300px;whiteSpace: normal;min-width:250px; max-width:300px;';
        $cols[$terms]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$postdate]['style'] = 'width:110px;whiteSpace: normal;min-width:110px; max-width:110px;';
        $cols[$elapsed]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$rem]['style'] = 'width:450px;whiteSpace: normal;min-width:450px; max-width:450px;';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$rem]['label'] = 'Remarks';
        $cols[$amt]['label'] = 'Total Pending Amount';
        $cols[$clientname]['label'] = 'Customer Name';
        $cols[$prref]['label'] = 'Type';
        $cols[$shipto]['label'] = 'Shipping Address';
        $cols[$shipto]['style'] = 'width:300px;whiteSpace: normal;min-width:250px; max-width:300px;';
        return $cols;
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function loaddoclisting($config)
    {
        // - filter by dept
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);
        $adminid = $config['params']['adminid'];
        $view = $this->othersClass->checkAccess($config['params']['user'], 5021);
        $add = $this->othersClass->checkAccess($config['params']['user'], 5022);

        if ($config['params']['date1'] == 'Invalid date') {
            $config['params']['date1'] =  $config['params']['date2'];
        }
        $date1 = $this->othersClass->datefilter($config['params']['date1']);
        $date2 = $this->othersClass->datefilter($config['params']['date2']);

        $dateidfield = 'date(head.dateid)';
        $filtersearch = "";
        $filtersearchpo = "";
        if (isset($config['params']['search'])) {
            $searchfield = [];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }

            $limit = '';
        }

        $date = $this->othersClass->getCurrentTimeStamp(); //
        $qry = "select head.trno,head.docno,head.clientname,head.terms, date(num.postdate) as postdate,
                        terms.days,TIMESTAMPDIFF(day, num.postdate,'" . $date . "' ) AS elapsed,
                        format(sum((stock.iss-stock.qa) * stock.amt)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as amt,
                        (select rem from headprrem as rem where trno=head.trno order by createdate desc limit 1) as rem,
                        (select prref from headprrem as rem where trno=head.trno order by createdate desc limit 1) as prref,head.address, ag.clientname as agent,
                        head.shipto, head.doc, '/module/sales/' as url, 'module' as moduletype
                from hsohead as head
                left join transnum as num on num.trno=head.trno
                left join hsostock as stock on stock.trno=head.trno
                left join terms on terms.terms=head.terms
                left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom 
                left join client as ag on ag.client=head.agent
                where " . $dateidfield . " between '" . $date1 . "' and '" . $date2 . "' $filtersearch
                    and stock.iss>stock.qa and stock.void=0 and num.postdate is not null
                    group by head.trno,head.docno,head.terms, num.postdate,
                    head.rem,terms.days,head.dateid,head.clientname,head.address, ag.clientname,head.shipto,head.doc
                order by terms.days, TIMESTAMPDIFF(day, num.postdate,'" . $date . "' ) desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', 'qry' => $qry];
    }


    public function paramsdatalisting($config)
    {

        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return ['status' => true, 'txtfield' => ['col1' => $col1]];
    }

    public function createTab($access, $config)
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

    public function createHeadField($config) {}
}
