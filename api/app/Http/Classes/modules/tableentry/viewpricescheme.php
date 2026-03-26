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

class viewpricescheme
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Price Scheme';
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
            'load' => 5390
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['startdate', 'enddate', 'docno', 'barcode', 'itemname',  'uom', 'isamt', 'disc', 'ext'];
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

        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        // $obj[0][$this->gridname]['columns'][$isamt]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$isamt]['style'] = "text-align: right; width: 80px;whiteSpace: normal;min-width:80px;max-width:80px";

        $obj[0][$this->gridname]['columns'][$disc]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";



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

        $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$isamt]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$isamt]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$disc]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$disc]['type'] = "label";

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
         date_format(left(head.due,10),'%m/%d/%Y') as enddate,
         date_format(head.createdate,'%Y-%m-%d') as createdate,head.isall,head.voiddate,
         item.itemid,item.itemname,item.barcode,stock.uom,
         FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
         FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
          stock.disc
         from hpahead as head
         left join hpastock as stock on stock.trno=head.trno
         left join item as item on item.itemid=stock.itemid  where item.itemid=$tableid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class