<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewenginehistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Engine History';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1500px;max-width:1500px;';
    public $issearchshow = true;
    public $showclosebtn = true;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function createTab($config)
    {
        $itemid = $config['params']['row']['itemid'];
        $item = $this->othersClass->getitemname($itemid);
        $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
        $column = ['dateid', 'docno', 'serial', 'chassis', 'pnp', 'csr', 'createdate', 'brand',  'loc', 'loc2', 'status', 'remarks', 'createby'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['totalfield'] = [];

        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'DATE';
        $obj[0][$this->gridname]['columns'][$docno]['label'] = 'REFFERENCE';
        $obj[0][$this->gridname]['columns'][$status]['label'] = 'STATUS';
        $obj[0][$this->gridname]['columns'][$loc]['label'] = 'LOCATION FROM';
        $obj[0][$this->gridname]['columns'][$loc2]['label'] = 'LOCATION TO';
        $obj[0][$this->gridname]['columns'][$serial]['label'] = 'ENGINE #';
        $obj[0][$this->gridname]['columns'][$brand]['label'] = 'BRAND';
        $obj[0][$this->gridname]['columns'][$chassis]['label'] = 'CHASSIS #';
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'REMARKS';
        $obj[0][$this->gridname]['columns'][$createby]['label'] = 'PERSONNEL';
        $obj[0][$this->gridname]['columns'][$createdate]['label'] = 'PNP/CSR UPDATE';
        $obj[0][$this->gridname]['columns'][$loc2]['style'] = 'text-align:left;width:150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0][$this->gridname]['columns'][$serial]['style'] = 'text-align:left;width:150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0][$this->gridname]['columns'][$chassis]['style'] = 'text-align:left;width:150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0][$this->gridname]['columns'][$status]['style'] = 'text-align:left;';
        $obj[0][$this->gridname]['columns'][$createby]['style'] = 'text-align:left;width:250px;whiteSpace: normal;min-width:250px;max-width:300px';
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
        $fields = [['yourref', 'refresh']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.action', 'history');
        data_set($col1, 'refresh.label', 'Search Engine');
        data_set($col1, 'yourref.label', 'Enter Engine');
        data_set($col1, 'yourref.readonly', false);
        data_set($col1, 'yourref.maxlength', 40);
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $itemid = $config['params']['row']['itemid'];
        $wh = $this->companysetup->getwh($config['params']);
        return $this->coreFunctions->opentable("
      	select '$wh' as wh, '' as yourref
      ");
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $itemid = $config['params']['itemid'];
        $center = $config['params']['center'];
        //unposted
        $filter = '';
        if (isset($config['params']['dataparams']['yourref'])) {
            if ($config['params']['dataparams']['yourref'] != '') {
                $filter = " and (sin.serial like '%" . $config['params']['dataparams']['yourref'] . "%' or sout.serial like '%" . $config['params']['dataparams']['yourref'] . "%')";
            }
        }

        $qry = "select head.trno,head.doc,head.dateid,head.docno,case head.doc when 'ST' then sout.rem else head.rem end as remarks,brand.brand_desc as brand,stock.line,
                    concat(upper(head.createby),' ',date_format(head.createdate,'%Y %b %d - %h : %i %p')) as createby,
                    item.itemname, case stock.qty when 0 then sout.color else sin.color end as color,
                    case stock.qty when 0 then sout.serial else sin.serial end as serial,
                    case stock.qty when 0 then sout.chassis else sin.chassis end as chassis,
                    case stock.qty when 0 then sout.pnp else sin.pnp end as pnp,
                    case stock.qty when 0 then sout.csr else sin.csr end as csr,
                    case stock.qty when 0 then '' else date_format(sin.dateid,'%m/%d/%Y') end as createdate,
                    (case when head.doc = 'RR' then client.clientname when head.doc in ('TS','ST') then wh.clientname else wh.clientname end) as loc,
                    (case when head.doc in ('TS','ST') then client.clientname when head.doc = 'RR' then wh.clientname else '' end) as loc2,
                    (case when head.doc = 'RR' then 'Brand New' when head.doc in ('SJ','CI') then concat('Sold (',mode.name,' )') else 'Transfer' end) as status
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
                    left join serialout as sout on sout.trno = stock.trno and sout.line = stock.line
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    left join client on client.clientid = head.clientid
                    left join client as wh on wh.clientid=head.whid
                    left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
                    where stock.itemid = $itemid  $filter  
                    union all
                    select head.trno,head.doc,head.dateid,head.docno,case head.doc when 'ST' then sout.rem else head.rem end  as remarks,brand.brand_desc as brand,stock.line,
                    concat(upper(head.createby),' ',date_format(head.createdate,'%Y %b %d - %h : %i %p')) as createby,  
                    item.itemname, case stock.qty when 0 then sout.color else sin.color end as color,
                    case stock.qty when 0 then sout.serial else sin.serial end as serial,
                    case stock.qty when 0 then sout.chassis else sin.chassis end as chassis,
                    case stock.qty when 0 then sout.pnp else sin.pnp end as pnp,
                    case stock.qty when 0 then sout.csr else sin.csr end as csr,
                    case stock.qty when 0 then '' else date_format(sin.dateid,'%m/%d/%Y') end as createdate,
                    (case when head.doc = 'RR' then client.clientname when head.doc in ('TS','ST') then wh.clientname else wh.clientname end) as loc,
                    (case when head.doc in ('TS','ST') then client.clientname when head.doc = 'RR' then wh.clientname else '' end) as loc2,
                    (case when head.doc = 'RR' then 'Brand New' when head.doc in ('SJ','CI') then concat('Sold (',mode.name,' )') else 'Transfer' end) as status
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
                    left join serialout as sout on sout.trno = stock.trno and sout.line = stock.line
                    left join item on item.itemid = stock.itemid
                    left join frontend_ebrands as brand on brand.brandid = item.brand
                    left join client on client.client = head.client
                    left join client as wh on wh.client=head.wh
                    left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
                    where stock.itemid = $itemid $filter order by dateid";


        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
    } //end function


} //end class
