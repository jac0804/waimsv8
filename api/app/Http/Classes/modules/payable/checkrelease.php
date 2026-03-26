<?php

namespace App\Http\Classes\modules\payable;

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
use App\Http\Classes\builder\helpClass;

class checkrelease
{


    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Check Releasing';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $tablelogs = 'table_log';
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4025,
            'save' => 4026,
            'print' => 4027
        );
        return $attrib;
    }



    public function createHeadbutton($config)
    {
        $btns = array(
            'others'
        );
        $buttons = $this->btnClass->create($btns);

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];

            return $buttons;
        }
    }


    public function createHeadField($config)
    {

        $fields = ['start', 'end', 'prepared', 'refresh'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'refresh.action', 'load');
        data_set($col1, 'prepared.label', 'Search');
        data_set($col1, 'prepared.name', 'searchby');
        // data_set($col1, 'refresh.confirm', true);
        // data_set($col1, 'refresh.confirmlabel', 'Are you sure you want to release check/s?');
        // 'confirm' => true,
        //         'confirmlabel' => 'Are you sure want to cancel ?'

        $fields = ['radiosortby'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radiosortby.options', [
            ['label' => 'Transaction Date', 'value' => 'dateid', 'color' => 'orange'],
            ['label' => 'Check Date', 'value' => 'checkdate', 'color' => 'orange'],
            ['label' => 'Document #', 'value' => 'docno', 'color' => 'orange']
        ]);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function createTab($config)
    {
        $isselected = 0;
        $dateid = 1;
        $checkdate = 2;
        $docno = 3;
        $clientname = 4;
        $checkno = 5;
        $amount = 6;

        $tab = [
            $this->gridname => ['gridcolumns' => ['isselected', 'dateid', 'checkdate', 'docno', 'clientname', 'checkno',  'amount']]
        ];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['label'] = 'UNRELEASED CHECKS';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$checkdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$checkno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Transaction Date';
        $obj[0][$this->gridname]['columns'][$checkdate]['label'] = 'Check Date';
        $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Document Number';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Payee';
        $obj[0][$this->gridname]['columns'][$checkno]['label'] = 'Check No.';

        $obj[0][$this->gridname]['columns'][$isselected]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';



        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "RELEASE";
        $obj[0]['access'] = "save";
        return $obj;
    }




    public function paramsdata($config)
    {

        $data = $this->coreFunctions->opentable("
        select adddate(left(now(), 10),-360) as start,
        left(now(), 10) as end,'' as searchby, 'dateid' as sortby,curdate() as releasedate");
        if (!empty($data)) {
            return $data[0];
        } else {
            return [];
        }
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

    public function headtablestatus($config)
    {
        $action = $config['params']["action2"];
        switch ($action) {
            case "load":
                return $this->loaddetails($config);
                break;

            case 'saveallentry':
                $result = $this->releasecheck($config);
                if ($result["status"]) {
                    return $this->loaddetails($config);
                } else {
                    return $result;
                }
                break;


            default:
                return ['status' => false, 'msg' => 'Action ' . $action . ' is not yet setup in the headtablestatus.'];
                break;
        }
    }

    private function loaddetails($config)
    {
        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];
        $search = $config['params']['dataparams']['searchby'];
        $sort = $config['params']['dataparams']['sortby'];

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        $qry = "
        select 'false' as isselected, ledger.trno,ledger.line,
        head.dateid,date(ledger.checkdate) as checkdate,head.docno,payee.clientname,ledger.checkno,format(ledger.cr,2) as amount from
        cbledger as ledger left join glhead as head on head.trno = ledger.trno
        left join gldetail as detail on detail.trno=ledger.trno and detail.line = ledger.line
        left join client as payee on payee.clientid=head.clientid
        where head.doc='CV' and detail.cr<>0 and ledger.releasedate is null 
        and date(head.dateid) between '$date1' and '$date2'
        and (payee.clientname like '%" . $search . "%' or head.docno like '%" . $search . "%' or ledger.checkno like '%" . $search . "%')
        order by $sort asc";

        $result = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $result]];
    }

    private function releasecheck($config)
    {

        $date1 = $config['params']['dataparams']['start'];
        $date2 = $config['params']['dataparams']['end'];
        $releasedate = $config['params']['dataparams']['releasedate'];
        $user = $config['params']['user'];

        if ($date1 == null) {
            return ['status' => false, 'msg' => 'Invalid start date', 'action' => 'load', 'griddata' => []];
        }

        if ($date2 == null) {
            return ['status' => false, 'msg' => 'Invalid end date', 'action' => 'load', 'griddata' => []];
        }



        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);
        $releasedate = $this->othersClass->sbcdateformat($releasedate);
        $rows = $config['params']['rows'];


        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Please select Checks that you want to release'];
        } else {
            foreach ($rows as $key => $val) {
                $data = [];

                if ($val["isselected"] == "true") {

                    $data['releasedate'] = $releasedate;
                    $data['releaseby'] = $user;
                    $this->coreFunctions->sbcupdate("cbledger", $data, ['trno' => $val["trno"], 'line' => $val["line"]]);
                    $this->logger->sbcwritelog($val["trno"], $config, 'RELEASE', $val['docno'] . ', Check#: ' . $val['checkno'] . ', Release Date: ' . $releasedate);
                }
            }
        }

        return ['status' => true, 'msg' => 'Check/s Released'];
    }


    // public function reportsetup($config){


    //     $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    //     $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);   

    //     $modulename = $this->modulename;
    //     $data = [];
    //     $style = 'width:500px;max-width:500px;';

    //     return ['status'=>true,'msg'=>'Loaded Success','modulename'=>$modulename,'data'=>$data,'txtfield'=>$txtfield,'txtdata'=>$txtdata,'style'=>$style,'directprint'=>false]; 
    //   }

    //   public function reportdata($config){
    //     // $this->logger->sbcviewreportlog($config);
    //     // $str = $this->reportplotting($config);

    //     $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    //     $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    //     return ['status'=>true,'msg'=>'Generating report successfully.','report'=>$str];
    //   }


}
