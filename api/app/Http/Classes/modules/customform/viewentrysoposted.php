<?php

namespace App\Http\Classes\modules\customform;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class viewentrysoposted
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK SO POSTED';
    public $gridname = 'tableentry';
    private $companysetup;
    private $coreFunctions;
    public $tablenum = 'transnum';
    private $table = 'omso';
    private $htable = 'homso';
    private $othersClass;
    private $logger;
    public $style = 'width:100%;max-width:40%';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $fields = ['trno', 'line', 'soline',  'sono', 'rtno', 'rem', 'qty'];
    public $showclosebtn = true;
    public $issearchshow = true;
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
    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }
    public function paramsdata($config)
    {

        $qry = "select '" . $config['params']['row']['trno'] . "'  as trno,'" . $config['params']['row']['line'] . "'  as line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
    public function data()
    {
        return [];
    }
    public function createTab($config)
    {
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysoposted', 'label' => 'STOCK SO']];
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
}
