<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\Logger;
use Exception;

class viewcddetail
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;


    public $modulename = 'View Info';
    public $gridname = 'inventory';
    private $fields = ['uom'];
    private $table = 'uom';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:100%;';
    public $showclosebtn = true;
    public $issearchshow = true;


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
        $fields = ['itemname', 'rrqty2', 'rrcost', 'uom2', 'basepending', 'uom3', 'disc'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'uom2.label', 'Ref. Conversion UOM');
        data_set($col1, 'uom2.type', 'input');
        data_set($col1, 'uom2.readonly', true);
        data_set($col1, 'uom2.required', false);

        data_set($col1, 'uom3.label', 'Reference Conversion');
        data_set($col1, 'uom3.type', 'input');
        data_set($col1, 'uom3.readonly', true);
        data_set($col1, 'uom3.required', false);

        data_set($col1, 'rrcost.label', 'Amount');
        data_set($col1, 'rrcost.readonly', true);
        data_set($col1, 'rrqty2.label', 'Request Qty');
        data_set($col1, 'rrqty2.readonly', true);

        data_set($col1, 'disc.label', 'Discount');
        $fields = ['cost', 'rqcd', 'qa', 'waivedqty', 'canvasstatus', 'wh', 'category'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'cost.label', 'Base Price');
        data_set($col2, 'wh.type', 'input');
        data_set($col2, 'category.type', 'input');
        $fields = ['requestorname', 'purpose', 'dateneed', 'barcode', 'partno', 'itemdesc2', 'carem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'requestorname.type', 'input');
        data_set($col3, 'purpose.label', 'Purpose');
        data_set($col3, 'purpose.readonly', true);
        data_set($col3, 'barcode.type', 'input');
        data_set($col3, 'barcode.readonly', true);
        data_set($col3, 'partno.readonly', true);
        data_set($col3, 'itemdesc2.readonly', true);
        $fields = ['specs2', 'rem1', 'duration', 'sanodesc', 'ref', 'department', 'ismanual', 'void'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem1.label', 'Requestor Notes');
        data_set($col4, 'ref.type', 'input');
        data_set($col4, 'ref.readonly', true);
        data_set($col4, 'void.type', 'input');
        data_set($col4, 'void.readonly', true);
        data_set($col4, 'void.class', 'csvoid sbccsreadonly');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function data()
    {
        return [];
    }
    public function paramsdata($config)
    {
        $qry = "
    select " . $config['params']['row']['trno'] . " as trno," . $config['params']['row']['line'] . " as line,head.docno, 
    concat('" . $this->modulename . "',' ~ ',ifnull(info.itemdesc,''))  as modulename,
    item.partno,
    item.barcode,
    item.itemname,
    stock.uom,
    si.uom2,
    si.uom3,
    stock.cost,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext,2) as ext,
    FORMAT(stock.rrqty2," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty2,
    stock.disc,
    case when stock.void=0 then 'No' else 'Yes' end as void,
    case when stock.ismanual=0 then 'No' else 'Yes' end as ismanual,
    case when stock.waivedqty=0 then 'No' else 'Yes' end as waivedqty,
    case when stock.isprefer=0 then 'false' else 'true' end as isprefer,
    round((stock.qty-stock.qa)/ case when ifnull(uom2.factor,0)<>0 then uom2.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    CONCAT(CAST(FORMAT(stock.rqcd," . $this->companysetup->getdecimal('qty', $config['params']) . ") as CHAR),' ',stock.uom) as rqcd,
    FORMAT((stock.rrqty2 * case when ifnull(uom3.factor,0)=0 then 1 else uom3.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as basepending,
    stock.ref,
    warehouse.client as wh,
    ifnull(cust.clientname,'') as clientname,
    case 
      when stock.status = 0 then 'Pending'
      when stock.status = 1 then 'Approved'
      when stock.status = 2 then 'Rejected'
    end as canvasstatus,
    stock.rem,
    FORMAT(si.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
    FORMAT(si.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
    '' as bgcolor,
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.itemdesc2,'') as itemdesc2, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.specs2,'') as specs2, ifnull(info.purpose,'') as purpose, date(info.dateneeded) as dateneed,
    ifnull(info.requestorname,'') as requestorname, ifnull(info.rem,'') as rem1, ifnull(sa.sano,'') as sanodesc, si.sono, ifnull(dept.clientname,'') as department,
    ifnull(d.duration,'') as duration,ifnull(cat.category,'') as category,info.ctrlno,'' as carem
    from cdstock as stock
    left join cdhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid 
    left join stockinfotrans as si on si.trno=stock.trno and si.line=stock.line
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join client as cust on cust.clientid=stock.suppid 
    left join clientsano as sa on sa.line=stock.sano 
    left join client as dept on dept.clientid=stock.deptid 
    left join reqcategory as cat on cat.line=stock.catid 
    left join duration as d on d.line=info.durationid
    left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
    left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
    where stock.trno= " . $config['params']['row']['trno'] . "  and stock.line = " . $config['params']['row']['line'] . "";
        $data = $this->coreFunctions->opentable($qry);
        $this->modulename = $data[0]->modulename;
        return $data;
    }
    public function createTab($config)
    {
        $columns = [];
        $stockbuttons = [];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
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
