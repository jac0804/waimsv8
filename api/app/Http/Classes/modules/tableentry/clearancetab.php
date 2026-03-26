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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class clearancetab
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB DESCRIPTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'jobtdesc';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['description'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;
    private $reportheader;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->reportheader = new reportheader;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['count', 'issued', 'docno', 'status', 'amount']]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";
        $obj[0][$this->gridname]['columns'][4]['style'] = "width:520px;whiteSpace: normal;min-width:520px; text-align:left;";
        $obj[0][$this->gridname]['columns'][1]['label'] = "Issued Date";
        $obj[0][$this->gridname]['columns'][2]['label'] = "Bus. Cert.";

        $obj[0][$this->gridname]['columns'][0]['type'] = "label";
        $obj[0][$this->gridname]['columns'][1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][3]['type'] = "label";
        $obj[0][$this->gridname]['columns'][4]['type'] = "label";
        return $obj;
    }

    public function createtabbutton($config)
    {
        return 0;
    }


    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($trno, $line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $data = $this->getissuedclearance($trno);
        return $data;
    }


    private function getissuedclearance($trno)
    {
        $qry = "select 1 as count ,date(dateid) as issued,docno,'Unposted' as status,format(head.amount,2) as amount
                from lahead as head
                left join client on client.client=head.client
                where doc='BC' and client.clientid = ?
                union all
                select 1 as count ,date(dateid) as issued,docno,'Posted' as status,format(head.amount,2) as amount
                from glhead as head where doc='BC' and head.clientid = ?";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
} //end class
