<?php

namespace App\Http\Classes\modules\tableentry;

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

class issuedclearance
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ISSUED CLEARANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    public $hhead = 'glhead';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['doc'];
        switch ($doc) {
            case 'TL':
                $columns = ['dateid', 'docno', 'yourref', 'ourref', 'amount', 'status'];
                break;
            
            case 'INFRA':
                $columns = ['dateid', 'docno', 'amount', 'status'];
                break;

            default:
                $columns = ['dateid', 'docno', 'purpose', 'rem', 'yourref', 'ourref', 'status'];
                break;
        }
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Issued Date";

        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$docno]['label'] = "Brgy. Cert";
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$status]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$status]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";

        if($doc!='INFRA'){
            $obj[0][$this->gridname]['columns'][$yourref]['type'] = "label";
            $obj[0][$this->gridname]['columns'][$yourref]['label'] = "RC No";
            $obj[0][$this->gridname]['columns'][$yourref]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

            $obj[0][$this->gridname]['columns'][$ourref]['type'] = "label";
            $obj[0][$this->gridname]['columns'][$ourref]['label'] = "RC Place";
            $obj[0][$this->gridname]['columns'][$ourref]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        }

        

        switch ($doc) {
            case 'TL':
                $obj[0][$this->gridname]['columns'][$docno]['label'] = "CLearance No.";
                $obj[0][$this->gridname]['columns'][$amount]['type'] = "label";
                $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
                $obj[0][$this->gridname]['columns'][$ourref]['label'] = "Bonafide";
                $obj[0][$this->gridname]['columns'][$yourref]['label'] = "Tru Type";
                break;
            case 'INFRA':
                $obj[0][$this->gridname]['columns'][$docno]['label'] = "Infrastructure No.";
                $obj[0][$this->gridname]['columns'][$amount]['type'] = "label";
                $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
                
                break;
            default:
                $obj[0][$this->gridname]['columns'][$purpose]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
                $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

                $obj[0][$this->gridname]['columns'][$purpose]['label'] = "Purpose";
                $obj[0][$this->gridname]['columns'][$rem]['label'] = "Purpose Detail";

                $obj[0][$this->gridname]['columns'][$purpose]['type'] = "label";
                $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";
                break;
        }

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
        $tableid = $config['params']['tableid'];

        switch ($doc) {
            case 'TL':
                $qry = "select date(head.dateid) as dateid, concat('BT','-',right(head.docno,5)) as docno,
                ifnull(tru.description,'') as yourref,ifnull(bona.description,'') as ourref,head.clientid,
                format(head.amount,2) as amount,'Posted' as status
                from " . $this->hhead . " as head
                left join cntnum as num on num.trno = head.trno
                left join reqcategory as tru on tru.line = head.truid
                left join reqcategory as bona on bona.line = head.bonafideid
                where num.doc = 'BT' and head.clientid= $tableid
                order by head.dateid, head.docno";
                break;
            case 'BG':
                $qry = "select date(head.dateid) as dateid, concat('BC','-',right(head.docno,5)) as docno,
                locl.clearance as purpose,head.rem,
                head.yourref, head.ourref, head.clientid,'Posted' as status
                from " . $this->hhead . " as head
                left join locclearance as locl on locl.line = head.purposeid
                left join cntnum as num on num.trno = head.trno
                where num.doc = 'BD' and head.clientid=$tableid
                order by head.dateid, head.docno";
                break;
            case 'INFRA':
                $qry = "select date(head.dateid) as dateid, concat('BI','-',right(head.docno,5)) as docno,
                '' as yourref,'' as ourref,head.clientid,
                format(head.amount,2) as amount,'Posted' as status
                from " . $this->hhead . " as head
                left join cntnum as num on num.trno = head.trno
                where num.doc = 'BI' and head.clientid= $tableid
                order by head.dateid, head.docno";
                break;
        }

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class