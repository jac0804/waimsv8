<?php

namespace App\Http\Classes\modules\warehousing;

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

class pi
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PRODUCT INQUIRY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $stock = 'item';
    public $prefix = '';
    public $tablelogs = '';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = ['itemname'];
    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 2032,
            'view' => 2033
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $barcode = 1;
        $partno = 2;
        $subcode = 3;
        $itemname = 4;
        $uom = 5;
        $dqty = 6;
        $amt = 7;
        $brand_desc = 8;
        $model_name = 9;
        $cl_name = 10;
        $compatible = 11;

        $getcols = [
            'action', 'barcode', 'partno', 'subcode', 'itemname', 'uom', 'dqty', 'amt',
            'brand_desc', 'model_name', 'cl_name', 'compatible'
        ];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$amt]['label'] = 'SRP';
        $cols[$dqty]['label'] = 'QTY Per Box';
        $cols[$partno]['label'] = 'Part No.';
        $cols[$partno]['align'] = 'text-left';
        $cols[$subcode]['label'] = 'Old SKU';
        $cols[$subcode]['align'] = 'text-left';

        $cols[$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$itemname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$amt]['style'] = 'text-align:right;width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$dqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align:right;';
        $cols[$partno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$subcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$cl_name]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$model_name]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$brand_desc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$compatible]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = ['searchby'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'searchby.label', 'Search by compatible');

        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);

        $data = $this->coreFunctions->opentable("select '' as searchby");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";

        $searchkey = $config['params']['search'];
        $searchby = isset($config['params']['doclistingparam']['searchby']['value']) ? $config['params']['doclistingparam']['searchby']['value'] : '';
        $compatiblefield = ", '' as compatible";

        $qryselect = "select
        item.itemid as clientid, item.barcode as client,
        item.barcode, item.itemname, item.uom, FORMAT(item.amt,2) as amt,
        ifnull(class.cl_name,'') as cl_name, ifnull(model.model_name,'') as model_name, ifnull(brand.brand_desc,'') as brand_desc, item.partno,
        FORMAT(item.dqty, 0) as dqty, item.subcode";

        $qry = "
        from item as item
        left join item_class as class on class.cl_id = item.class
        left join model_masterfile as model on model.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand ";

        $searcfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.partno', 'item.subcode'];
        if ($searchby != '') {
            $qry .= " left join itemcmodels as c on c.itemid=item.itemid left join cmodels as cm on cm.line=c.cmodelid";
        }

        switch ($searchby) {
            case 'class':
                $compatiblefield = ', group_concat(cm.classification) as compatible ';
                $qry .= " and ifnull(cm.classification,'') <>''";
                array_push($searcfield, 'cm.classification');
                break;

            case 'model':
                $compatiblefield = ', group_concat(cm.model) as compatible ';
                $qry .= " and ifnull(cm.model,'') <>''";
                array_push($searcfield, 'cm.model');
                break;

            case 'brand':
                $compatiblefield = ', group_concat(cm.brand) as compatible ';
                $qry .= " and ifnull(cm.brand,'') <>''";
                array_push($searcfield, 'cm.v');
                break;
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry .= " where 1=1 " . $filtersearch;

        if ($searchby != '') {
            $qry .= " group by item.itemid, item.barcode, item.barcode, item.itemname, item.uom, FORMAT(item.amt,2), class.cl_name, model.model_name, brand.brand_desc, item.partno";
        }

        $qry .= " order by item.barcode limit 5000";

        $qryselect .= $compatiblefield;
        $qryselect .= $qry;

        $data = $this->coreFunctions->opentable($qryselect);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
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

    public function createTab($access, $config)
    {
        $tab = [
            'tableentry' => [
                'action' => 'warehousingentry',
                'lookupclass' => 'entryproductinquiry',
                'label' => 'STATUS'
            ],
            'incomingpo' => [
                'action' => 'warehousingentry',
                'lookupclass' => 'entryincomingpo',
                'label' => 'INCOMING PO'
            ]
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        return [];
    }

    public function createHeadField($config)
    {
        $fields = ['client', 'itemname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.type', 'input');
        data_set($col1, 'client.label', 'Barcode');

        return array('col1' => $col1);
    }

    public function newclient($config)
    {
        return [];
    }

    private function resetdata($client = '')
    {
        return [];
    }

    function getheadqry($trno)
    {
        return "select barcode, itemname, itemid, itemid as clientid, barcode as client from item
        where itemid=" . $trno;
    }


    public function loadheaddata($config)
    {
        $trno = $config['params']['clientid'];
        $head = $this->coreFunctions->opentable($this->getheadqry($trno));
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $udpate)
    {
        return [];
    } // end function

    public function getlastclient($pref)
    {
        return '';
    }

    public function openstock($trno, $config)
    {
        return [];
    }

    public function deletetrans($config)
    {
        return [];
    } //end function

    // -> print function
    public function reportsetup($config)
    {
        $txtfield = $this->createreportfilter();
        $txtdata = $this->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }


    public function createreportfilter()
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        return [];
    }
} //end class
