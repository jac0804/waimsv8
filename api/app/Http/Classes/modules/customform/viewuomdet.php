<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewuomdet
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'Uom Detail';
    public $gridname = 'editgrid';
    private $fields = [];
    public $tablenum = 'transnum';
    private $logger;
    public $tablelogs = 'transnum_log';

    public $style = 'width:60%;max-width:80%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }
    public function createTab($config)
    {
        $docno = 0;
        $unit = 1;
        $uom = 2;
        $uom2 = 3;
        $uom3 = 4;


        $tab = [
            $this->gridname => [
                'gridcolumns' => ['docno', 'unit', 'uom', 'uom2', 'uom3']
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = null;

        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";


        $obj[0][$this->gridname]['columns'][$unit]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$unit]['label'] = 'Request UOM';
        $obj[0][$this->gridname]['columns'][$unit]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom]['label'] = 'Canvass UOM';
        $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';


        $obj[0][$this->gridname]['columns'][$uom2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom2]['label'] = 'Ref. Conversion UOM';
        $obj[0][$this->gridname]['columns'][$uom2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$uom3]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom3]['label'] = 'Reference Conversion';
        $obj[0][$this->gridname]['columns'][$uom3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

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
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        $trno = $config['params']['row']['reqtrno'];
        $line = $config['params']['row']['reqline'];
        $select = "
        select  head.docno,stock.trno,
        stock.line,
        stock.refx,
        stock.linex,
        stock.uom,
        si.uom2,
        si.uom3,
        ifnull(info.unit,'') as unit
       from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
      left join hstockinfotrans as si on si.trno=stock.trno and si.line=stock.line
      left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
      where stock.reqtrno = ? and stock.reqline = ? order by line";
        
        $data = $this->coreFunctions->opentable($select, [$trno, $line]);
        return $data;

        
    } //end function

    public function loaddata($config, $reqtrno, $reqline)
    {

        
    } //end function
































} //end class
