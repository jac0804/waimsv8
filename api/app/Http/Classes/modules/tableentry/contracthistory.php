<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class contracthistory
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;

    public $modulename = 'CONTRACT HISTORY';
    public $gridname = 'inventory';
    private $table = 'contracts';

    public $tablelogs = 'contracts_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = false;
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
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function createTab($config)
    {
        $datefrom = 0;
        $dateto = 1;
        $descr = 2;

        $stockbuttons = [];
        $columns = ['datefrom', 'dateto', 'descr'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$datefrom]['label'] = 'Hired Date';
        $obj[0][$this->gridname]['columns'][$datefrom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$datefrom]['style'] = "width: 50px; min-width: 50px; max-width: 50px;";
        $obj[0][$this->gridname]['columns'][$datefrom]['align'] = "center";



        $obj[0][$this->gridname]['columns'][$dateto]['label'] = 'Resigned Date';
        $obj[0][$this->gridname]['columns'][$dateto]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateto]['style'] = "width: 50px; min-width: 50px; max-width: 50px;";
        $obj[0][$this->gridname]['columns'][$dateto]['align'] = "center";

        $obj[0][$this->gridname]['columns'][$descr]['label'] = 'Contract Remarks';
        $obj[0][$this->gridname]['columns'][$descr]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$descr]['align'] = "left";


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
        $empid = $config['params']['tableid'];

        $qry = "select line, date(datefrom) as datefrom, date(dateto) as dateto, descr, '' as bgcolor
        from " . $this->table . "
        where empid = ?
        and line < (select max(line) from " . $this->table . " where empid = ?)
        order by line desc";

        $data = $this->coreFunctions->opentable($qry, [$empid, $empid]);
        return $data;
    }
}
