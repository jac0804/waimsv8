<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class mitagged
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'MI';
    public $gridname = 'inventory';
    private $fields = ['trno'];
    public $tablenum = 'transnum';
    private $table = 'lahead';
    private $htable = 'glhead';
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
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


        $stockbuttons = [];
        $columns = ['docno', 'client', 'clientname', 'dateid', 'wh', 'acnoname', 'empname', 'odostart', 'yourref', 'ourref'];


        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$odostart]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Client Code';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Client Name';
        $obj[0][$this->gridname]['columns'][$wh]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Account';
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$empname]['label'] = 'Deliver/Operator';
        $obj[0][$this->gridname]['columns'][$odostart]['label'] = 'ODO';

        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$client]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$wh]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$acnoname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$empname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$odostart]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$yourref]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$ourref]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;


        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$wh]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$odostart]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$yourref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$ourref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';




        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    private function selectqry()
    {
        $qry = "
       head.trno,head.docno,head.dateid,head.rem,
       warehouse.client as wh,warehouse.clientname as whname,client.client,client.clientname,head.yourref,head.ourref,info.odometer as odostart,coa.acnoname";
        return $qry;
    }
    public function loaddata($config)
    {

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $trno = $config['params']['tableid'];
        $qry = "select " . $select . " from " . $this->table . "  as  head
        left join client as warehouse on warehouse.client=head.client
        left join client on client.client=head.client
        left join cntnum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra 
        left join hcntnuminfo as info on info.trno = head.trno
        where head.doc='MI' and num.center = ? and info.jotrno = $trno
        union all 
        select " . $select . " from " . $this->htable . "  as  head
        left join client as warehouse on warehouse.clientid=head.whid
        left join client on client.clientid=head.clientid
        left join cntnum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra 
        left join hcntnuminfo as info on info.trno = head.trno
        where head.doc='MI' and num.center = ? and info.jotrno = $trno
        order by docno desc";
        
        $data = $this->coreFunctions->opentable($qry, [$config['params']['center'], $config['params']['center']]);
        return $data;
    }
}
