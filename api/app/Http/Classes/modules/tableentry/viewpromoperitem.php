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

class viewpromoperitem
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Promo Per Item';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    public $style = 'width:100%';
    public $showclosebtn = false;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5391
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['startdate', 'enddate', 'docno', 'barcode', 'itemname', 'start', 'end', 'prqty'];
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
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$end]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$start]['style'] = "width:100px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$prqty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$startdate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$enddate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$barcode]['label'] = "Item Barcode";
        $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";


        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$end]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$end]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$start]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$start]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$prqty]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$prqty]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$prqty]['label'] = "Promo Item Count";

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
        $tableid = $config['params']['tableid'];
        //posted lang kinuha 
        $qry = "select head.docno,
         date_format(left(head.dateid,10),'%m/%d/%Y') as startdate,
         date_format(left(head.due,10),'%m/%d/%Y') as enddate,head.isall,head.voiddate,
         item.itemid,item.itemname,item.barcode,item.uom,stock.pqty as prqty,stock.pend as end,stock.pstart as start
            from hpphead as head
            left join hppstock as stock on stock.trno=head.trno
            left join item as item on item.itemid=stock.itemid where item.itemid=$tableid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class