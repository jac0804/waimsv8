<?php

namespace App\Http\Classes\modules\modulereport\afti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class qs
{
    private $modulename;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $reporter;
    private $logger;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createreportfilter($config)
    {
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 12:
                $fields = ['radioquotation', 'fdecimal', 'print'];
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'radioquotation.options', [
                    ['label' => 'Quotation Printing', 'value' => 'quoteprint', 'color' => 'red'],
                    ['label' => 'Instruction Form Printing', 'value' => 'instructionform', 'color' => 'red'],
                    ['label' => 'Proforma', 'value' => 'proforma', 'color' => 'red']
                ]);
                break;

            default:
                $fields = ['radioquotation', 'fdecimal','radiopoafti', 'radiosjaftilogo', 'print'];
                $col1 = $this->fieldClass->create($fields);
                        
                data_set($col1, 'radiopoafti.label', 'Formats');
                data_set($col1, 'radiopoafti.options', [
                    ['label' => 'Original', 'value' => 'og', 'color' => 'red'],
                    ['label' => 'Duplicate', 'value' => 'dp', 'color' => 'red']
                ]);
                data_set($col1, 'radioquotation.options', [
                    ['label' => 'Quotation Printing', 'value' => 'quoteprint', 'color' => 'red'],
                    ['label' => 'Quotation - VAT Inc', 'value' => 'vatinc', 'color' => 'red'],
                    ['label' => 'Instruction Form Printing', 'value' => 'instructionform', 'color' => 'red'],
                    ['label' => 'Proforma', 'value' => 'proforma', 'color' => 'red']
                ]);

                break;
        }
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            '2' as fdecimal,
            'og' as radiopoafti,
            'woutlogo' as radiosjaftilogo,
            'quoteprint' as radioquotation
            "
        );
    }

    public function report_quote_query($trno)
    {

        ini_set('memory_limit', '-1');
        $query = "select right(head.docno,6) as docnum,cat.cat_name as bstyle, cust.tel,cust.email,head.docno,head.trno, head.clientname, bill.addr as address,head.revisionref,
            date(head.dateid) as dateid, head.rem,head.agent,head.wh,
            item.barcode, item.itemname, stock.isamt as gross, (stock.amt*uom.factor) as netamt, stock.isqty as qty,stock.isamt as amt,
            stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
            item.sizeid,m.model_name as model, ifnull(brands.brand_desc,'') as brand_desc,iteminfo.itemdescription, iteminfo.accessories,
            agent.clientname as agentname,agent.tel as agtel,
            infotab.inspo,head.deldate, infotab.ispartial,infotab.instructions, infotab.period, 
            left(infotab.ovaliddate,10) as  ovaliddate,
            infotab.isvalid,head.termsdetails,head.vattype,
            bill.addr as billaddr, ship.addr as shipaddr,
            stockinfo.rem as inforem,  ifnull(concat(rc.category, '~',rc.reqtype),'') as industry,cust.tin,head.yourref,cust.addr as clientaddr,
            proforma.docno as proinvoice,proforma.dateid as prodate,
            concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
            concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry,
            stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,stockinfo.leaddur as itemleaddur,
            stockinfo.leaddur as itemleadtime,
            infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
            concat(infotab.leadfrom,' - ',infotab.leadto,' ',infotab.leaddur) as headleadtime,
            concat(conbill.salutation,' ',conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,conbill.email as billemail,conbill.contactno as billcontactno,
            concat(conship.salutation,' ',conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.mobile, ' / ', conship.email) as shipcontact,conship.email as shipemail,conship.contactno as shipcontactno,
            infotab.taxdef, if(head.cur = 'P', 'PHP',head.cur) as cur, infotab.dp, infotab.cod, infotab.outstanding,
            conbill.mobile as bcmobile, conship.mobile as scmobile, item.moq, item.mmoq, cust.groupid, infotab.mop1, infotab.mop2,stock.sortline,infotab.isshipmentnotif,infotab.shipmentnotif,infotab.rem2,ifnull(px.dtcno,'') as dtcno
            from qshead as head left join qsstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join client on client.client=head.wh
            left join client as cust on cust.client = head.client
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo on iteminfo.itemid = item.itemid
            left join client as agent on agent.client= head.agent
            left join headinfotrans as infotab on infotab.trno = head.trno
            left join billingaddr as bill on bill.line = head.billid and bill.clientid = cust.clientid
            left join billingaddr as ship on ship.line = head.shipid and ship.clientid = cust.clientid
            left join stockinfotrans as stockinfo on stockinfo.line = stock.line and stockinfo.trno = stock.trno
            left join proformainv as proforma on proforma.trno = head.trno
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join contactperson as conship on conship.line=head.shipcontactid
            left join category_masterfile as cat on cat.cat_id=cust.category
            left join reqcategory as rc on head.industryid = rc.line
            left join uom on uom.uom = stock.uom and uom.itemid = stock.itemid
            left join hpxhead as px on px.trno = infotab.dtctrno
            where head.doc='QS' and head.trno='$trno' and stock.noprint = 0

            union all

            select right(head.docno,6) as docnum,cat.cat_name as bstyle, cust.tel,cust.email,head.docno,head.trno, head.clientname, bill.addr as address,head.revisionref,
            date(head.dateid) as dateid, head.rem,head.agent,head.wh,
            item.barcode, item.itemname, stock.isamt as gross, (stock.amt*uom.factor) as netamt, stock.isqty as qty,stock.isamt as amt,
            stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
            item.sizeid,m.model_name as model,  ifnull(brands.brand_desc,'') as brand_desc,iteminfo.itemdescription, iteminfo.accessories,
            agent.clientname as agentname,agent.tel as agtel,
            infotab.inspo,head.deldate, infotab.ispartial,infotab.instructions, infotab.period, 
            left(infotab.ovaliddate,10) as  ovaliddate,
            infotab.isvalid,head.termsdetails,head.vattype,
            bill.addr as billaddr, ship.addr as shipaddr,
            stockinfo.rem as inforem,  ifnull(concat(rc.category, '~',rc.reqtype),'') as industry,cust.tin,head.yourref,cust.addr as clientaddr,
            proforma.docno as proinvoice,proforma.dateid as prodate,
            concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
            concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry,
            stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,stockinfo.leaddur as itemleaddur,
            stockinfo.leaddur as itemleadtime,
            infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
            concat(infotab.leadfrom,' - ',infotab.leadto,' ',infotab.leaddur) as headleadtime,
            concat(conbill.salutation,' ',conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,conbill.email as billemail,conbill.contactno as billcontactno,
            concat(conship.salutation,' ',conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.mobile, ' / ', conship.email) as shipcontact,conship.email as shipemail,conship.contactno as shipcontactno,
            infotab.taxdef, if(head.cur = 'P', 'PHP',head.cur) as cur, infotab.dp, infotab.cod, infotab.outstanding,
            conbill.mobile as bcmobile, conship.mobile as scmobile, item.moq, item.mmoq, cust.groupid, infotab.mop1, infotab.mop2,stock.sortline,infotab.isshipmentnotif,infotab.shipmentnotif,infotab.rem2,ifnull(px.dtcno,'') as dtcno
            from hqshead as head
            left join hqsstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join model_masterfile as m on m.model_id = item.model
            left join client on client.client=head.wh
            left join client as cust on cust.client = head.client
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo on iteminfo.itemid = item.itemid
            left join client as agent on agent.client= head.agent
            left join hheadinfotrans as infotab on infotab.trno = head.trno
            left join billingaddr as bill on bill.line = head.billid and bill.clientid = cust.clientid
            left join billingaddr as ship on ship.line = head.shipid and ship.clientid = cust.clientid
            left join hstockinfotrans as stockinfo on stockinfo.line = stock.line and stockinfo.trno = stock.trno
            left join proformainv as proforma on proforma.trno = head.trno
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join contactperson as conship on conship.line=head.shipcontactid
            left join category_masterfile as cat on cat.cat_id=cust.category
            left join uom on uom.uom = stock.uom and uom.itemid = stock.itemid
            left join reqcategory as rc on head.industryid = rc.line
             left join hpxhead as px on px.trno = infotab.dtctrno
            where head.doc='QS' and head.trno='$trno' and stock.noprint = 0

            union all

            select right(head.docno,6) as docnum,cat.cat_name as bstyle, cust.tel,cust.email,head.docno,head.trno, head.clientname, bill.addr as address,head.revisionref,
            date(head.dateid) as dateid, head.rem,head.agent,head.wh,
            item.barcode, item.itemname, stock.isamt as gross, (stock.amt*uom.factor) as netamt, stock.isqty as qty,stock.isamt as amt,
            stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
            item.sizeid,m.model_name as model,  ifnull(brands.brand_desc,'') as brand_desc,iteminfo.itemdescription, iteminfo.accessories,
            agent.clientname as agentname,agent.tel as agtel,
            infotab.inspo,head.deldate, infotab.ispartial,infotab.instructions, infotab.period, 
            left(infotab.ovaliddate,10) as  ovaliddate,
            infotab.isvalid,head.termsdetails,head.vattype,
            bill.addr as billaddr, ship.addr as shipaddr,
            stockinfo.rem as inforem, ifnull(concat(rc.category, '~',rc.reqtype),'') as industry,cust.tin,head.yourref,cust.addr as clientaddr,
            proforma.docno as proinvoice,proforma.dateid as prodate,
            concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
            concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry,
            stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,stockinfo.leaddur as itemleaddur,
            stockinfo.leaddur as itemleadtime,
            infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
            concat(infotab.leadfrom,' - ',infotab.leadto,' ',infotab.leaddur) as headleadtime,
            concat(conbill.salutation,' ',conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,conbill.email as billemail,conbill.contactno as billcontactno,
            concat(conship.salutation,' ',conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.mobile, ' / ', conship.email) as shipcontact,conship.email as shipemail,conship.contactno as shipcontactno,
            infotab.taxdef, if(head.cur = 'P', 'PHP',head.cur) as cur, infotab.dp, infotab.cod, infotab.outstanding,
            conbill.mobile as bcmobile, conship.mobile as scmobile, item.moq, item.mmoq, cust.groupid, infotab.mop1, infotab.mop2,stock.sortline,infotab.isshipmentnotif,infotab.shipmentnotif,infotab.rem2,ifnull(px.dtcno,'') as dtcno
            from qshead as head left join qtstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join client on client.client=head.wh
            left join client as cust on cust.client = head.client
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo on iteminfo.itemid = item.itemid
            left join client as agent on agent.client= head.agent
            left join headinfotrans as infotab on infotab.trno = head.trno
            left join billingaddr as bill on bill.line = head.billid and bill.clientid = cust.clientid
            left join billingaddr as ship on ship.line = head.shipid and ship.clientid = cust.clientid
            left join stockinfotrans as stockinfo on stockinfo.line = stock.line and stockinfo.trno = stock.trno
            left join proformainv as proforma on proforma.trno = head.trno
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join contactperson as conship on conship.line=head.shipcontactid
            left join category_masterfile as cat on cat.cat_id=cust.category
            left join uom on uom.uom = stock.uom and uom.itemid = stock.itemid
            left join reqcategory as rc on head.industryid = rc.line
             left join hpxhead as px on px.trno = infotab.dtctrno
            where head.doc='QS' and head.trno='$trno' and stock.noprint = 0

            union all

            select right(head.docno,6) as docnum,cat.cat_name as bstyle, cust.tel,cust.email,head.docno,head.trno, head.clientname, bill.addr as address,head.revisionref,
            date(head.dateid) as dateid, head.rem,head.agent,head.wh,
            item.barcode, item.itemname, stock.isamt as gross, (stock.amt*uom.factor) as netamt, stock.isqty as qty,stock.isamt as amt,
            stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
            item.sizeid,m.model_name as model,  ifnull(brands.brand_desc,'') as brand_desc,iteminfo.itemdescription, iteminfo.accessories,
            agent.clientname as agentname,agent.tel as agtel,
            infotab.inspo,head.deldate, infotab.ispartial,infotab.instructions, infotab.period, 
            left(infotab.ovaliddate,10) as  ovaliddate,
            infotab.isvalid,head.termsdetails,head.vattype,
            bill.addr as billaddr, ship.addr as shipaddr,
            stockinfo.rem as inforem,  ifnull(concat(rc.category, '~',rc.reqtype),'') as industry,cust.tin,head.yourref,cust.addr as clientaddr,
            proforma.docno as proinvoice,proforma.dateid as prodate,
            concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
            concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry,
            stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,stockinfo.leaddur as itemleaddur,
            stockinfo.leaddur as itemleadtime,
            infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
            concat(infotab.leadfrom,' - ',infotab.leadto,' ',infotab.leaddur) as headleadtime,
            concat(conbill.salutation,' ',conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,conbill.email as billemail,conbill.contactno as billcontactno,
            concat(conship.salutation,' ',conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.mobile, ' / ', conship.email) as shipcontact,conship.email as shipemail,conship.contactno as shipcontactno,
            infotab.taxdef, if(head.cur = 'P', 'PHP',head.cur) as cur, infotab.dp, infotab.cod, infotab.outstanding,
            conbill.mobile as bcmobile, conship.mobile as scmobile, item.moq, item.mmoq, cust.groupid, infotab.mop1, infotab.mop2,stock.sortline,infotab.isshipmentnotif,infotab.shipmentnotif,infotab.rem2,ifnull(px.dtcno,'') as dtcno
            from hqshead as head
            left join hqtstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join model_masterfile as m on m.model_id = item.model
            left join client on client.client=head.wh
            left join client as cust on cust.client = head.client
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo on iteminfo.itemid = item.itemid
            left join client as agent on agent.client= head.agent
            left join hheadinfotrans as infotab on infotab.trno = head.trno
            left join billingaddr as bill on bill.line = head.billid and bill.clientid = cust.clientid
            left join billingaddr as ship on ship.line = head.shipid and ship.clientid = cust.clientid
            left join hstockinfotrans as stockinfo on stockinfo.line = stock.line and stockinfo.trno = stock.trno
            left join proformainv as proforma on proforma.trno = head.trno
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join contactperson as conship on conship.line=head.shipcontactid
            left join category_masterfile as cat on cat.cat_id=cust.category
            left join reqcategory as rc on head.industryid = rc.line
            left join uom on uom.uom = stock.uom and uom.itemid = stock.itemid
             left join hpxhead as px on px.trno = infotab.dtctrno
            where head.doc='QS' and head.trno='$trno' and stock.noprint = 0 order by sortline,line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    

    //Quotation Printing - Layout 1
    public function reportquoteplottingpdf($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = 2; //$this->companysetup->getdecimal('price', $params['params']);
        $params_decimal = $params['params']['dataparams']['fdecimal'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 750;

        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $totalext = 0;
        $amount = 0;

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "10";
        $fontsize13 = '11';
        $fontsize14 = "12";

        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        $this->default_quote_headerpdf($params, $data, $font);

        PDF::MultiCell(0, 0, "");

        $arritemname = array();
        $countarr = 0;

        $arrordercode = array();
        $countarrcode = 0;

        $arrmfr = array();
        $countarrmfr = 0;

        $arrqty = array();
        $countarrqty = 0;

        $arrprice = array();
        $countarrprice = 0;

        $arrlinetotal = array();
        $countarrlinetotal = 0;

        $leadtime = '';
        $itemleadtime = '';

        PDF::MultiCell(0, 0, "");

        $newpageadd = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $inforem = '';
                $arrleaditem = [];

                $unitprice = $data[$i]['netamt'];
                $linetotal = $data[$i]['ext'];

                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(number_format(intval($data[$i]['qty']), 0) . ' ' . $data[$i]['uom']) . ' ', 10));
                $countarrqty = count($arrqty);

                $arrprice = (str_split(trim($data[0]['cur'] . ' ' . number_format($unitprice, $params_decimal)) . ' ', 18));
                $countarrprice = count($arrprice);

                $arrlinetotal = (str_split(trim($data[0]['cur'] . ' ' . number_format($linetotal, $decimalqty)) . ' ', 18));
                $countarrlinetotal = count($arrlinetotal);

                $itemcode = $data[$i]['itemname'];
                $itembrand = $data[$i]['brand_desc'];
                $moq = $data[$i]['moq'];
                $mmoq = $data[$i]['mmoq'];
                $itemdescription = $data[$i]['itemdescription'];
                $itemaccessories = $data[$i]['accessories'];
                $iteminfo = $data[$i]['inforem'];
                $itemleadtime = $data[$i]['itemleadtime'];
                if ($itemleadtime != '') {
                    $itemleadtime = "Lead Time: " . $data[$i]['itemleadtime'];
                }

                $arrordercode = $this->reporter->fixcolumn([$itemcode], '11', 1);
                $countarrcode = count($arrordercode);

                $arrmfr = $this->reporter->fixcolumn([$itembrand], '8', 1);
                $countarrmfr = count($arrmfr);

                $myoq = '';
                if ($moq != 0 && $mmoq != 0) {
                    $myoq = 'Minimum Order Qty:' . $moq . ' <br> Multiple Order Qty :' . $mmoq;
                } else if ($moq != 0) {
                    $myoq = 'Minimum Order Qty:' . $moq;
                } else if ($mmoq != 0) {
                    $myoq = 'Multiple Order Qty :' . $mmoq;
                }

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $myoq, $itemaccessories, $iteminfo, $itemleadtime], '33', 1);
                $countarrcol = count($itemcoldes);

                $maxrow = 1;
                $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $inum = $i + 1;
                        } else {
                            $inum = '';
                            $itemcode = '';
                        }
                        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

                        PDF::SetFont($font, '', $fontsize9);
                        PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(160, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(60, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(90, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'LR', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(100, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'LR', 'R', false, 1, '', '', true, 1, true, true, 0, 'B', true);

                        if (PDF::getY() >= $page) {
                            $newpageadd = 1;
                            $this->addrow('LRB');
                            $this->default_quote_headerpdf($params, $data, $font);
                            $this->addrow('LR');
                        }
                    }
                }

                //1-w, 2-h, 3-txt, 4-border = 0, 5-align = 'J', 6-fill = 0, 7-ln = 1, 8-x = '', 9-y = '', 10-reseth = true, 11-stretch = 0, 12-ishtml = false, 13-autopadding = true, 14-maxh = 0

                $this->addrow('LR');
                if ($data[0]['vattype'] == 'VATABLE') {
                    $vatsales = $vatsales + $linetotal;
                    $totalext = $totalext + $linetotal;
                } else {
                    $vatsales = 0;
                    $totalext = $totalext + $linetotal;
                }
            }
        }

        if ($data[0]['vattype'] == 'VATABLE') {
            $vat = round($vatsales * .12,2);
            $amount = round($totalext + $vat,2);
        } else {
            $vat = 0;
            $amount = $totalext;
        }

        if (PDF::getY() > 610) {
            $this->addrow('LRB');
            $newpageadd = 1;
            $this->default_quote_headerpdf($params, $data, $font);
        }
        do {
            $this->addrow('LR');
        } while (PDF::getY() < 610);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(125, 15, '*QUOTATION VALIDITY: ', 'T', 'L', false, 0, 10, PDF::getY());
        PDF::SetFont($font, '', $fontsize12);
        $valid = '';
        if ($data[0]['isvalid'] == 1 && $data[0]['period'] != '') {
            $valid = " " . $data[0]['period'] . " from " . date("m-d-Y", strtotime($data[0]['ovaliddate'])) . " ";
        } else if ($data[0]['isvalid'] == 1 && $data[0]['period'] == '') {
            $valid = " " . date("m-d-Y", strtotime($data[0]['ovaliddate'])) . " ";
        } else if ($data[0]['isvalid'] == 0 && $data[0]['period'] != '') {
            $valid = " " . $data[0]['period'];
        }
        PDF::MultiCell(260, 15, $valid, 'T', 'L', false, 0); // period quotation valid
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Vat Sales',  'TLRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'TB', 'L', false, 0);
        if ($data[0]['vattype'] == "ZERO-RATED") {
            PDF::MultiCell(75, 15, '0.00', 'TBR', 'R', false);
        } else {
            PDF::MultiCell(75, 15, number_format($totalext, $decimalprice), 'TBR', 'R', false);
        }


        if ($data[0]['vattype'] == 'VATABLE' && $data[0]['taxdef'] != 0) {
            $vat = $data[0]['taxdef'];
            $amount = $totalext + $vat;
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*STOCK SUBJECT TO PRIOR SALES* ', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, '12% VAT',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($vat, $decimalprice), 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*NON-CANCELLABLE AND NON-RETURNABLE* ', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Zero Rated',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        if ($data[0]['vattype'] == "ZERO-RATED") {
            PDF::MultiCell(75, 15, number_format($amount, $decimalprice), 'BR', 'R', false);
        } else {
            PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);
        }

        $leadtime = "";
        if ($data[0]['advised'] == 1) {
            $leadtime = 'To Be advised';
        } else {
            $leadtime = $data[0]['headleadtime'];
            if ($data[0]['headleadfrom'] == 0 && $data[0]['headleadto'] == 0) {
                $leadtime = "";
            }
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(80, 15, '*LEAD TIME: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(305, 15, $leadtime, '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Amount Due:',  'LBR', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($amount, $decimalprice), 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*Please review data specs and or item description*', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(90, 15, '',  '', 'C', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, '', '', 'L', false, 0);
        PDF::MultiCell(75, 15, '', '', 'R', false);

        PDF::MultiCell(0, 0, "");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 20, 'Contact Person: ', '', 'L', false, 0);
        PDF::MultiCell(125, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''),  '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(350, 20, 'All Goods Returned by reasons of client`s fault will be charged 20% re-stocking fee of invoice value and shall bear all the costs of returning the goods.', '', 'L', false);


        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 20, 'Contact Number: ', '', 'L', false, 0);
        PDF::MultiCell(125, 20, (isset($data[0]['agtel']) ? $data[0]['agtel'] : ''),  '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(350, 20, 'All Goods Returned must be reported within 7 (seven days and returned within 15 (fifteen) days from date of delivery undamaged and in its original packaging together with a written incidence report.', '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    //Quotation - VAT Inc - Layout 2
    public function report_vat_inc_plottingpdf($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = 1;
        $params_decimal = $params['params']['dataparams']['fdecimal'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 750;

        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $totalext = 0;
        $amount = 0;

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "10";
        $fontsize13 = '11';
        $fontsize14 = "12";

        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        $border = "1px solid ";
        // VATABLE
        if ($data[0]['vattype'] == "ZERO-RATED" || ($data[0]['vattype'] == "VATABLE" && $data[0]['taxdef'] != 0)) {
            PDF::SetTitle($this->modulename);
            PDF::SetAuthor('Solutionbase Corp.');
            PDF::SetCreator('Solutionbase Corp.');
            PDF::SetSubject($this->modulename . ' Module Report');
            PDF::setPageUnit('px');
            PDF::AddPage('p', [595, 842]);
            PDF::SetMargins(40, 20);

            PDF::SetFont($font, 'B', 64, $border);

            PDF::writeHTML($this->othersClass->notapplicable(), true, false, false, false, '');
            return PDF::Output($this->modulename . '.pdf', 'S');
        }
        $this->default_quote_headerpdf($params, $data, $font);
        PDF::MultiCell(0, 0, "");

        $arritemname = array();
        $countarr = 0;

        $arrordercode = array();
        $countarrcode = 0;

        $arrmfr = array();
        $countarrmfr = 0;

        $arrqty = array();
        $countarrqty = 0;

        $arrprice = array();
        $countarrprice = 0;

        $arrlinetotal = array();
        $countarrlinetotal = 0;

        $leadtime = '';

        PDF::MultiCell(0, 0, "");

        $newpageadd = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $inforem = '';
                $arrleaditem = [];
                $unitprice = $data[$i]['netamt'] * 1.12;
                $linetotal = $data[$i]['qty'] * $unitprice;

                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(number_format(intval($data[$i]['qty']), 0) . ' ' . $data[$i]['uom']) . ' ', 10));
                $countarrqty = count($arrqty);

                $arrprice = (str_split(trim($data[0]['cur'] . ' ' . number_format($unitprice, $params_decimal)) . ' ', 18));
                $countarrprice = count($arrprice);

                $arrlinetotal = (str_split(trim($data[0]['cur'] . ' ' . number_format($linetotal, $decimalqty)) . ' ', 18));
                $countarrlinetotal = count($arrlinetotal);


                $itemcode = $data[$i]['itemname'];
                $itembrand = $data[$i]['brand_desc'];
                $itemdescription = $data[$i]['itemdescription'];
                $itemaccessories = $data[$i]['accessories'];
                $iteminfo = $data[$i]['inforem'];
                $moq = $data[$i]['moq'];
                $mmoq = $data[$i]['mmoq'];
                $itemleadtime = $data[$i]['itemleadtime'];

                if ($itemleadtime != "") {
                    $itemleadtime = "Lead Time: " . $itemleadtime;
                }

                $arrordercode = $this->reporter->fixcolumn([$itemcode], '11', 1);
                $countarrcode = count($arrordercode);

                $arrmfr = $this->reporter->fixcolumn([$itembrand], '10', 1);
                $countarrmfr = count($arrmfr);

                $myoq = '';
                if ($moq != 0 && $mmoq != 0) {
                    $myoq = 'Minimum Order Qty:' . $moq . ' <br> Multiple Order Qty :' . $mmoq;
                } else if ($moq != 0) {
                    $myoq = 'Minimum Order Qty:' . $moq;
                } else if ($mmoq != 0) {
                    $myoq = 'Multiple Order Qty :' . $mmoq;
                }

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $myoq, $itemaccessories, $iteminfo, $itemleadtime], '30', 1);
                $countarrcol = count($itemcoldes);

                $maxrow = 1;
                $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $inum = $i + 1;
                        } else {
                            $inum = '';
                        }
                        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

                        PDF::SetFont($font, '', $fontsize9);
                        PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, false, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, false, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, false, true, 0, 'B', true);
                        PDF::MultiCell(160, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(60, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, false, true, 0, 'B', true);
                        PDF::MultiCell(90, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'LR', 'R', false, 0, '', '', true, 1, false, true, 0, 'B', true);
                        PDF::MultiCell(100, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'LR', 'R', false, 1, '', '', true, 1, false, true, 0, 'B', true);

                        if (PDF::getY() >= $page) {
                            $newpageadd = 1;
                            $this->addrow('LRB');
                            $this->default_quote_headerpdf($params, $data, $font);
                            $this->addrow('LR');
                        }
                    }
                }

                $vatsales = 0;
                $totalext = $totalext + $linetotal;
            }
        }
        $vat = 0;
        $amount = $totalext;

        if (PDF::getY() > 610) {
            $this->addrow('LRB');
            $newpageadd = 1;
            $this->default_quote_headerpdf($params, $data, $font);
        }
        do {
            $this->addrow('LR');
        } while (PDF::getY() < 610);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(125, 15, '*QUOTATION VALIDITY: ', 'T', 'L', false, 0, 10, PDF::getY());
        PDF::SetFont($font, '', $fontsize12);
        $valid = '';
        if ($data[0]['isvalid'] == 1 && $data[0]['period'] != '') {
            $valid = " " . $data[0]['period'] . " Days  from " . $data[0]['ovaliddate'] . " ";
        } else if ($data[0]['isvalid'] == 1 && $data[0]['period'] == '') {
            $valid = " " . $data[0]['ovaliddate'] . " ";
        } else if ($data[0]['isvalid'] == 0 && $data[0]['period'] != '') {
            $valid = " " . $data[0]['period'] . " ";
        }
        PDF::MultiCell(260, 15, $valid, 'T', 'L', false, 0); // period quotation valid
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Vat Sales',  'TLRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'TB', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($totalext, $params_decimal), 'TBR', 'R', false);

        if ($data[0]['vattype'] == 'VATABLE' && $data[0]['taxdef'] != 0) {
            $vat = $data[0]['taxdef'];
            $amount = $totalext + $vat;
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*STOCK SUBJECT TO PRIOR SALES* ', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, '12% VAT',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format(0, $params_decimal), 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*NON-CANCELLABLE AND NON-RETURNABLE* ', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Zero Rated',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        if ($data[0]['vattype'] == "ZERO-RATED") {
            PDF::MultiCell(75, 15, number_format($amount, $params_decimal), 'BR', 'R', false);
        } else {
            PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);
        }

        $leadtime = "";
        if ($data[0]['advised'] == 1) {
            $leadtime = 'To Be advised';
        } else {
            $leadtime = $data[0]['headleadtime'];
            if ($data[0]['headleadfrom'] == 0 && $data[0]['headleadto'] == 0) {
                $leadtime = "";
            }
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(80, 15, '*LEAD TIME: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(305, 15, $leadtime, '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Amount Due:',  'LBR', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($amount, $params_decimal), 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '*Please review data specs and or item description*', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(90, 15, '',  '', 'C', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, '', '', 'L', false, 0);
        PDF::MultiCell(75, 15, '', '', 'R', false);

        PDF::MultiCell(0, 0, "");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 20, 'Contact Person: ', '', 'L', false, 0);
        PDF::MultiCell(125, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''),  '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(350, 20, 'All Goods Returned by reasons of client`s fault will be charged 20% re-stocking fee of invoice value and shall bear all the costs of returning the goods.', '', 'L', false);


        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 20, 'Contact Number: ', '', 'L', false, 0);
        PDF::MultiCell(125, 20, (isset($data[0]['agtel']) ? $data[0]['agtel'] : ''),  '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(350, 20, 'All Goods Returned must be reported within 7 (seven days and returned within 15 (fifteen) days from date of delivery undamaged and in its original packaging together with a written incidence report.', '', 'L', false);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    //Instruction Form Printing
    public function reportinstructionformpdf($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 900;

        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $totalext = 0;

        $font = '';

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->modulename = app('App\Http\Classes\modules\sales\qs')->modulename;

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [595, 842]);
        PDF::SetMargins(40, 20);

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "10";
        $fontsize13 = '10';
        $fontsize14 = "10";
        $fontsize15 = "11";
        $border = "1px solid ";

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        PDF::MultiCell(0, 15, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(250, 20, 'INSTRUCTION FORM (should be attached to PO) ', '', 'L', false, 0);
        PDF::MultiCell(100, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize15);
        PDF::MultiCell(175, 20, ' ' . 'AFT#',  'TLRB', 'L', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(250, 20, 'INVOICE TO: ', '', 'L', false, 0);
        PDF::MultiCell(100, 20, '', '', 'L', false, 0);
        PDF::MultiCell(175, 20, '',  '', 'L', false);

        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(15, 0, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 15, 'Company Name: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        $groupid = "";
        if ($data[0]['groupid'] != "") {
            $groupid = " - " . $data[0]['groupid'];
        }

        PDF::MultiCell(225, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : ''), 'B', 'C', false, 0);
        PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(75, 15, 'PO No.: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 15, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        $c = ceil(strlen((isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : ' ')) / 45);

        for ($i = 0; $i < $c; $i++) {
            PDF::MultiCell(0, 0, "\n");
        }

        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(15, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 15, 'Contact Name: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(225, 15, (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'B', 'C', false, 0);
        PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(75, 15, 'TIN: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 15, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false);

        $c = ceil(strlen((isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ' ')) / 45);

        for ($i = 0; $i < $c; $i++) {
            PDF::MultiCell(0, 0, "\n");
        }

        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(15, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 15, 'Address: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(225, 15, (isset($data[0]['billingaddress']) ? $data[0]['billingaddress'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize13);
        PDF::MultiCell(75, 15, 'Contact No.: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 15, (isset($data[0]['billcontactno']) ? $data[0]['billcontactno'] : ''), 'B', 'L', false);

        $c = ceil(strlen((isset($data[0]['billingaddress']) ? $data[0]['billingaddress'] : ' ')) / 65);

        for ($i = 0; $i < $c; $i++) {
            PDF::MultiCell(0, 0, "\n");
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(115, 15, 'Industry / Vertical', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(225, 15, (isset($data[0]['industry']) ? $data[0]['industry'] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(175, 15, '', '', 'L', false);

        $c = ceil(strlen((isset($data[0]['industry']) ? $data[0]['industry'] : ' ')) / 45);

        for ($i = 0; $i < $c; $i++) {
            PDF::MultiCell(0, 0, "\n");
        }

        $trno = $params['params']['dataid'];
        $doc = $params['params']['doc'];

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $tbls = '';
        $qttbl = '';
        if ($isposted) {
            $tbl = 'h' . strtolower($doc) . 'head';
            $tbls = 'h' . strtolower($doc) . 'stock';
            $qttbl = 'hqtstock';
        } else {
            $tbl = strtolower($doc) . 'head';
            $tbls = strtolower($doc) . 'stock';
            $qttbl = 'qtstock';
        }

        $total = $this->coreFunctions->getfieldvalue($tbls, "sum(ext)", "trno=?", [$trno]);
        $tax = $this->coreFunctions->getfieldvalue($tbl, "tax", "trno=?", [$trno]);
        $total = $total + $this->coreFunctions->getfieldvalue($qttbl, "sum(ext)", "trno=?", [$trno]);

        if ($tax != 0) {
            $total = round($total * 1.12, 2);
        }

        $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db 
        from (select head.crref as docno,head.ourref,sum(detail.cr) as db
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('AR5','PD1')
        group by head.crref,head.ourref
        union all
        select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('AR5','PD1')
        group by head.crref,head.ourref) as a  ", [$trno, $trno]);


        $ewt = $this->coreFunctions->datareader("select ifnull(sum(db),0) as value from (select sum(detail.db - detail.cr) as db
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('WT2','ARWT')
        union all
        select ifnull(sum(detail.db - detail.cr),0) as db
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('WT2','ARWT')) as a
        ", [$trno, $trno]);

        $fp = "";
        $bal = "";
        $cr = "";
        $ptype = "";

        if (!empty($data2)) {
            if ($data2[0]->docno != "") {
                $fp = number_format($data2[0]->db - $ewt, 2);
                $bal = number_format($total - (($data2[0]->db - $ewt) + $ewt), 2);
                $ewt = number_format($ewt, 2);
                $cr = $data2[0]->docno;
                $ptype = $data2[0]->ourref;
            }
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(115, 25, 'COLLECTION DETAILS', '', 'L', false, 0);
        PDF::MultiCell(100, 25, 'AMT', '', 'C', false, 0);
        PDF::MultiCell(25, 25, '',  '', 'L', false, 0);
        PDF::MultiCell(100, 25, '  ' . 'CR#', '', 'C', false, 0);
        PDF::MultiCell(175, 25, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        if ($ptype == "FULL") {
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(115, 15, '', '', 'L', false, 0);
            PDF::MultiCell(25, 15, 'FP:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $fp, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, $cr, 'B', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(110, 15, '', '', 'L', false, 0);
            PDF::MultiCell(30, 15, 'EWT:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $ewt, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, '', '', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(115, 15, '', '', 'L', false, 0);
            PDF::MultiCell(25, 15, 'BAL:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $bal, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, '', '', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);
        } else {

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(115, 15, '', '', 'L', false, 0);
            PDF::MultiCell(25, 15, 'DP:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $fp, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, $cr, 'B', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(110, 15, '', '', 'L', false, 0);
            PDF::MultiCell(30, 15, 'EWT:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $ewt, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, '', '', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);

            PDF::MultiCell(0, 0, "\n");

            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(115, 15, '', '', 'L', false, 0);
            PDF::MultiCell(25, 15, 'BAL:', '', 'L', false, 0);
            PDF::MultiCell(90, 15, $bal, 'B', 'L', false, 0);
            PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(90, 15, '', '', 'L', false, 0);
            PDF::MultiCell(185, 15, '', '', 'L', false);
        }


        PDF::MultiCell(0, 30, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(20, 25, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize11);
        PDF::SetTextColor(127);
        PDF::MultiCell(405, 25, 'For new company with Credit Term, Provide accounting contact details', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(100, 25, '', '', 'L', false);

        PDF::SetTextColor(0, 0, 0, 100);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(5, 25, '', '', 'L', false, 0);
        PDF::MultiCell(175, 25, 'Other instruction Collection : ', '', 'L', false, 0);
        PDF::MultiCell(245, 25, '', 'B', 'L', false, 0);
        PDF::MultiCell(100, 25, '', '', 'C', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(180, 20, 'DELIVER TO:', '', 'L', false, 0);
        PDF::MultiCell(245, 20, '', '', 'L', false, 0);
        PDF::MultiCell(100, 20, '', '', 'C', false);

        $clientname = $this->reporter->fixcolumn([$data[0]['clientname']], '38', 0);
        $arrclientname = count($clientname);
        $maxrow = $arrclientname;

        for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
                $company = 'Company Name:';
            } else {
                $company = '';
            }

            $border = '';
            if ($r == $maxrow - 1) {
                $border = 'B';
            }
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(180, 15, $company, '', 'L', false, 0);
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(245, 15,  isset($clientname[$r]) ? $clientname[$r] : '', $border, 'L', false, 0);
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(100, 15, '', '', 'C', false);
        }


        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(180, 30, 'Contact Name / Number / Email:', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(245, 30, (isset($data[0]['shipcontact']) ? $data[0]['shipcontact'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 30, '', '', 'C', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(180, 30, 'Address:', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);

        PDF::MultiCell(245, 30, ("" . isset($data[0]['shippingaddress']) ? $data[0]['shippingaddress'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 30, '', '', 'C', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(0, 0, "\n");

        $ispartial = '';
        if ($data[0]['ispartial'] == 1) {
            $ispartial = 'yes';
        } else {
            $ispartial = 'no';
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(200, 15, 'PARTIAL DELIVERY ALLOWED:', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 15, $ispartial, '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(100, 15, 'Creation Date: ', 'B', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(125, 15, date("F d, Y", strtotime($data[0]['dateid'])), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(200, 15, 'Submitted by: ', '', 'L', false);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(265, 15, $data[0]['agentname'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(525, 15, 'Other Delivery Instructions: ', '', 'L', false);

        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(525, 15, 'DDR : ' . date("F d,Y", strtotime($data[0]['deldate'])), '', 'L', false);
        //public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) {
        PDF::MultiCell(400, 15, $data[0]['dtcno']."\n".$data[0]['rem2']."\n". $data[0]['instructions'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(200, 15, 'Shipment Permit Notification: ', '', 'L', false, 0);

        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(100, 15, $data[0]['isshipmentnotif'], '', 'L', false);
        PDF::MultiCell(525, 15, $data[0]['shipmentnotif'], '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    //Proforma
    public function reportproformaplottingpdf($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = 2;
        $params_decimal = $params['params']['dataparams']['fdecimal'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 750;

        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $totalext = 0;
        $amount = 0;

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "9";
        $fontsize13 = '10';

        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        $this->proformaplotting_headerpdf($params, $data, $font);

        $arritemname = array();
        $countarr = 0;

        $arrordercode = array();
        $countarrcode = 0;

        $arrmfr = array();
        $countarrmfr = 0;

        $arrqty = array();
        $countarrqty = 0;

        $arrprice = array();
        $countarrprice = 0;

        $arrlinetotal = array();
        $countarrlinetotal = 0;

        $totalctr = 0;


        $newpageadd = 0;
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $inforem = '';
                $arrleaditem = [];

                $unitprice = $data[$i]['netamt'];
                $linetotal = $data[$i]['ext'];

                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(intval($data[$i]['qty']) . ' ' . $data[$i]['uom']) . ' ', 10));
                $countarrqty = count($arrqty);

                $arrprice = (str_split(trim($data[0]['cur'] . ' ' . number_format($unitprice, $params_decimal)) . ' ', 18));
                $countarrprice = count($arrprice);

                $arrlinetotal = (str_split(trim($data[0]['cur'] . ' ' . number_format($linetotal, $decimalqty)) . ' ', 18));
                $countarrlinetotal = count($arrlinetotal);


                $itemcode = $data[$i]['itemname'];
                $itembrand = $data[$i]['brand_desc'];
                $itemdescription = $data[$i]['itemdescription'];
                $itemaccessories = $data[$i]['accessories'];
                $iteminfo = $data[$i]['inforem'];
                $itemleadtime = $data[$i]['itemleadtime'];

                if ($itemleadtime != "") {
                    $itemleadtime = "Lead Time: " . $itemleadtime;
                }

                $arrordercode = $this->reporter->fixcolumn([$itemcode], '12', 1);
                $countarrcode = count($arrordercode);

                $arrmfr = $this->reporter->fixcolumn([$itembrand], '10', 1);
                $countarrmfr = count($arrmfr);

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemaccessories, $iteminfo, $itemleadtime], '30', 1);
                $countarrcol = count($itemcoldes);

                $maxrow = 1;
                $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

                if ($data[$i]['itemname'] == '') {
                } else {
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $inum = $i + 1;
                        } else {
                            $inum = '';
                        }
                        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

                        PDF::SetFont($font, '', $fontsize9);
                        PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(160, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(60, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);

                        PDF::MultiCell(90, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'LR', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(100, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'LR', 'R', false, 1, '', '', true, 1, true, true, 0, 'B', true);

                        if (PDF::getY() >= $page) {
                            $newpageadd = 1;
                            $this->addrow('LRB');
                            $this->proformaplotting_headerpdf($params, $data, $font);
                            $this->addrow('LR');
                        }
                    }
                }


                //1-w, 2-h, 3-txt, 4-border = 0, 5-align = 'J', 6-fill = 0, 7-ln = 1, 8-x = '', 9-y = '', 10-reseth = true, 11-stretch = 0, 12-ishtml = false, 13-autopadding = true, 14-maxh = 0
                if ($data[0]['vattype'] == 'VATABLE') {
                    $vatsales = $vatsales + $linetotal;
                    $totalext = $totalext + $linetotal;
                } else {
                    $vatsales = 0;
                    $totalext = $totalext + $linetotal;
                }
            }
        }



        if ($data[0]['vattype'] == 'VATABLE') {
            $vat = $vatsales * .12;
            $amount = $totalext + $vat;
        } else {
            $vat = 0;
            $amount = $totalext;
        }

        if (PDF::getY() > 580) {
            $this->addrow('LRB');
            $newpageadd = 1;
            $this->proformaplotting_headerpdf($params, $data, $font);
        }
        do {
            $this->addrow('LR');
        } while (PDF::getY() < 580);


        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', 'T', 'L', false, 0, 10, PDF::getY());
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'VAT Sales',  'TLRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'TB', 'L', false, 0);
        if ($data[0]['vattype'] != "ZERO-RATED") {
            PDF::MultiCell(75, 15, number_format($totalext, $decimalprice), 'TBR', 'R', false);
        } else {
            PDF::MultiCell(75, 15, '0.00', 'TBR', 'R', false);
        }

        if ($data[0]['vattype'] == 'VATABLE' && $data[0]['taxdef'] != 0) {
            $vat = $data[0]['taxdef'];
            $amount = $totalext + $vat;
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, '12% VAT',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($vat, $decimalprice), 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'VAT Exempt',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Zero Rated',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);

        if ($data[0]['vattype'] != "ZERO-RATED") {
            PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);
        } else {
            PDF::MultiCell(75, 15, number_format($amount, $decimalprice), 'BR', 'R', false);
        }

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'LESS: WTax',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Delivery Charge',  'LRB', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(385, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(90, 15, 'Amount Due:',  'LBR', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
        PDF::MultiCell(75, 15, number_format($amount, $decimalprice), 'BR', 'R', false);

        if ($data[0]['mop1'] != '' || $data[0]['dp'] != 0) {
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(385, 15, '', '', 'L', false, 0);
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::SetFillColor(211, 211, 211);
            PDF::MultiCell(90, 15, $data[0]['mop1'],  'LBR', 'C', 1, 0);
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
            PDF::MultiCell(75, 15, number_format($data[0]['dp'], $decimalprice), 'BR', 'R', false);
        }

        if ($data[0]['mop2'] != '' || $data[0]['cod'] != 0) {
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(385, 15, '', '', 'L', false, 0);
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::SetFillColor(211, 211, 211);
            PDF::MultiCell(90, 15, $data[0]['mop2'],  'LBR', 'C', 1, 0);
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
            PDF::MultiCell(75, 15, number_format($data[0]['cod'], $decimalprice), 'BR', 'R', false);
        }

        if ($data[0]['outstanding'] != 0) {
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(385, 15, '', '', 'L', false, 0);
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::SetFillColor(211, 211, 211);
            PDF::MultiCell(90, 15, 'Outstanding',  'LBR', 'C', 1, 0);
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(25, 15, $data[0]['cur'] . ' ', 'B', 'L', false, 0);
            PDF::MultiCell(75, 15, number_format($data[0]['outstanding'], $decimalprice), 'BR', 'R', false);
        }

        PDF::SetFont($font, 'B', $fontsize13);
        PDF::MultiCell(5, 10, '', '', 'L', false, 0);
        PDF::MultiCell(500, 10, 'Approved by:', '', 'L', false, 0);
        PDF::MultiCell(240, 10, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize13);
        PDF::MultiCell(5, 10, '', '', 'L', false, 0);
        PDF::MultiCell(240, 10, '', 'B', 'L', false, 0);
        PDF::MultiCell(320, 10, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(5, 10, '', '', 'L', false, 0);
        PDF::MultiCell(540, 10, 'Term: In the event of default of non-payment, the customer shall pay 12% interest per annum on all accounts over due plus 25% for attorney`s fees and cost of collection. The parties hereby voluntarily submit the jurisdiction of the proper court in Makat in case of litigation.', '', 'L', false, 0);
        PDF::MultiCell(200, 10, '', '', 'L', false);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    //Extra funcs
    //Used in layout 1 and 2
    public function default_quote_headerpdf($params, $data, $font)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $companyid = $params['params']['companyid'];

        $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->modulename = app('App\Http\Classes\modules\sales\qs')->modulename;

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [595, 842]);
        PDF::SetMargins(10, 10);

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "10";
        $fontsize13 = '10';
        $fontsize14 = "11";
        $border = "1px solid ";

        $terms = (str_split(trim($data[0]['termsdetails']), 20));

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)
        PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 310, 80);
        PDF::MultiCell(0, 20, "\n");
        PDF::SetFont($font, 'B', 18, $border);
        PDF::MultiCell(320, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
        PDF::MultiCell(265, 0, 'QUOTATION', '', 'C', 0, 0, '', '', false, 0, false, false, 0);
        PDF::MultiCell(0, 30, "\n");

        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(245, 15, '', '', 'R', false, 0);
        PDF::MultiCell(75, 15, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(100, 15, ' ' . 'Quotation No.',  '1', 'L', 1, 0);
        $revisionref =  !empty(trim($data[0]['revisionref'])) ? '-' . $data[0]['revisionref'] : '';
        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(155, 15, ' ' . (isset($data[0]['docno']) ? $data[0]['docno'] . $revisionref : ''), $border, 'L', false);

        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(245, 15, '', '', 'R', false, 0);
        PDF::MultiCell(75, 15, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(100, 15, ' ' . 'Quotation Date',  '1', 'L', 1, 0);
        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(155, 15, ' ' . date("F d, Y", strtotime($data[0]['dateid'])), $border, 'L', false);

        $inspo = (isset($data[0]['inspo']) ? $data[0]['inspo'] : '');
        $arrinspo = $this->reporter->fixcolumn([$inspo], 30, 0);
        $cinspo = count($arrinspo);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
        if ($cinspo != 1) {
            $h = 0;
        } else {
            $h = 15;
        }
        for ($r = 0; $r < $cinspo; $r++) {
            if ($r == 0) {
                $lbl = "INQ Ref No.";
            } else {
                $lbl = "";
            }
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(245, $h, '', '', 'R', false, 0);
            PDF::MultiCell(75, $h, '', '', 'L', false, 0);
            PDF::SetFillColor(211, 211, 211);
            PDF::MultiCell(100, $h, ' ' . $lbl,  'LR', 'L', 1, 0);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(155, $h, ' ' . isset($arrinspo[$r]) ? ' ' . $arrinspo[$r] : '', 'LR', 'L', false);
        }

        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(245, 15, '', '', 'R', false, 0);
        PDF::MultiCell(75, 15, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(100, 15, ' ' . 'Payment Terms',  '1', 'L', 1, 0);
        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(155, 15, ' ' . (isset($terms[0]) ? $terms[0] : ''), $border, 'L', false);

        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(245, 15, $headerdata[0]->name, '', 'L', false, 0);
        PDF::MultiCell(75, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize11);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(100, 15, ' ' . 'Page No.',  '1', 'L', 1, 0);
        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(155, 15, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), $border, 'L', false);

        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(320, 15, $headerdata[0]->address, '', 'L', false, 0);
        PDF::MultiCell(50, 15, '', '', 'L', false, 0);
        PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
        PDF::MultiCell(155, 15, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(320, 15, $headerdata[0]->tel, '', 'L', false, 0);
        PDF::MultiCell(50, 15, '', '', 'L', false, 0);
        PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
        PDF::MultiCell(155, 15, '', '', 'L', false);

        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(320, 15, 'Email: ' . $headerdata[0]->email, '', 'L', false, 0);
        PDF::MultiCell(50, 15, '', '', 'L', false, 0);
        PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
        PDF::MultiCell(155, 15, '', '', 'L', false);

        if ($companyid == 10) {
            PDF::SetFont($font, '', $fontsize13);
            PDF::MultiCell(320, 15, 'VAT REG TIN: ' . $headerdata[0]->tin, '', 'L', false, 0);
            PDF::MultiCell(50, 15, '', '', 'L', false, 0);
            PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
            PDF::MultiCell(165, 15, '', '', 'L', false);
        }

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize9);

        $clientname = PDF::GetStringHeight(200, $data[0]['clientname']);
        $billcontact = PDF::GetStringHeight(200, $data[0]['billcontact']);
        $max_heights = max($clientname, $billcontact);

        PDF::SetFont($font, 'B', $fontsize13);
        PDF::MultiCell(5, $max_heights, '', 'TL', 'L', false, 0);
        PDF::MultiCell(275, $max_heights, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize12);
        PDF::MultiCell(10, $max_heights, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize12);
        PDF::MultiCell(5, $max_heights, '', 'TL', 'L', false, 0);
        PDF::MultiCell(75, $max_heights, 'Contact Name: ',  'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize11);
        PDF::MultiCell(200, $max_heights, (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'TR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        $arrphone = array();
        $countarrphone = 0;

        $groupid = "";
        if ($data[0]['groupid'] != "") {
            $groupid = "<b>" . $data[0]['groupid'] . "<b>";
        }
        $billcon = '<b>Phone:</b> ' . (isset($data[0]['billcontactno']) ? $data[0]['billcontactno'] : '');
        $bstyle = '<b>Bus Style:</b> ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '');
        $tin = '<b>TIN:</b> ' . (isset($data[0]['tin']) ? $data[0]['tin'] : '');
        $mobile = '<b>Mobile #:</b> ' . (isset($data[0]['bcmobile']) ? $data[0]['bcmobile'] : '');
        $billemail = '<b>Email Address:</b> ' . (isset($data[0]['billemail']) ? $data[0]['billemail'] : '');

        $arrbillcon = $this->reporter->fixcolumn([$billcon, $mobile, $billemail], 35, 0);
        $cbillcon = count($arrbillcon);

        $arrbstyle = $this->reporter->fixcolumn([$groupid, $bstyle, $tin], 35, 0);
        $cbstyle = count($arrbstyle);


        $maxrow = max($cbstyle, $cbillcon);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(5, 10, '', 'L', 'L', false, 0);
            PDF::MultiCell(275, 10, (isset($arrbstyle[$r]) ? $arrbstyle[$r] : ''), 'R', 'L', false, 0, '', '', true, 0, true);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(10, 10, '', '', 'L', false, 0);

            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(5, 10, '', 'L', 'L', false, 0);
            PDF::SetFont($font, '', $fontsize11);
            PDF::MultiCell(275, 10, (isset($arrbillcon[$r]) ? $arrbillcon[$r] : ''), 'R', 'L', false, 1, '', '', true, 0, true);
        }

        $arrabddrline = $this->reporter->fixcolumn([$data[0]['baddrline1'], $data[0]['baddrline2'], $data[0]['bcity'] . ' ' . $data[0]['bzipcode'], $data[0]['bprovince'], $data[0]['bcountry']], 55, 0);
        $caddrbline1 = count($arrabddrline);
        $arrasddrline = $this->reporter->fixcolumn([$billemail], 55, 0);
        $caddrsline1 = count($arrasddrline);

        $maxrow = max($caddrbline1, $caddrsline1);

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(275, 0, isset($arrabddrline[$r]) ? ' ' . $arrabddrline[$r] : '', 'R', 'L', false, 0);
            PDF::MultiCell(10, 0, '', '', 'L', false, 0);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(275, 10, '', 'R', 'L', false, 1, '', '', true, 0, true);
        }

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(280, 0, '', 'LRB', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(280, 0, '', 'LRB', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(25, 10, 'No.', '1', 'C', 1, 0);
        PDF::MultiCell(70, 10, 'Order Code', '1', 'C', 1, 0);
        PDF::MultiCell(70, 10, 'Mfr', '1', 'C', 1, 0);
        PDF::MultiCell(160, 10, 'Description', '1', 'C', 1, 0);
        PDF::MultiCell(60, 10, 'Quantity', '1', 'C', 1, 0);
        PDF::MultiCell(90, 10, 'Unit Price', '1', 'C', 1, 0);
        PDF::MultiCell(100, 10, 'Line Total', '1', 'C', 1, 0);
    }

    private function addrow($border)
    {
        PDF::MultiCell(25, 0, '', $border, 'C', false, 0, '', '', true, 1);
        PDF::MultiCell(70, 0, '', $border, 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(70, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(160, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(60, 0, '', $border, 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(90, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, '', $border, 'R', false, 1, '', '', false, 0);
    }


    public function proformaplotting_headerpdf($params, $data, $font)
    {
        $qslogo = URL::to('public/images/afti/qslogo.png');
        $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];
        $ogordp = $params['params']['dataparams']['radiopoafti'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->modulename = app('App\Http\Classes\modules\sales\qs')->modulename;

        //$width = PDF::pixelsToUnits($width);
        //$height = PDF::pixelsToUnits($height);
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [595, 842]);
        PDF::SetMargins(10, 10);

        $fontsize9 = "9";
        $fontsize11 = "9";
        $fontsize12 = "10";
        $fontsize13 = '10';
        $fontsize14 = "11";
        $border = "1px solid ";


        $yourref = (isset($data[0]['yourref']) ? $data[0]['yourref'] : '');
        $arryourref = $this->reporter->fixcolumn([$yourref], 30, 0);
        $cyourref = count($arryourref);

        $terms = (isset($data[0]['termsdetails']) ? $data[0]['termsdetails'] : '');
        $arrterms = $this->reporter->fixcolumn([$terms], 30, 1);
        $cterms = count($arrterms);


        $address = array();
        $addrarr = 0;

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 310, 80);
         
        // PDF::MultiCell(0, 20, "\n");
         if ($sjlogo == 'wlogo' && $ogordp == 'og') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 310, 65);
            // PDF::SetFont($font, 'B', $fontsize14, $border);
            // PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            // PDF::MultiCell(290, 15, 'PROFORMA INVOICE - ORIGINAL', '', 'C', 0, 0, '293', '26', false, 0, false, false);
            PDF::SetFont($font, 'B', $fontsize14, $border);
            PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::MultiCell(125, 15, 'PROFORMA INVOICE: ', '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($data[0]['proinvoice']) ? $data[0]['proinvoice'] : ''),  '', 'L', false);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'og') {
            PDF::SetFont($font, '', 10);
            // PDF::SetFont($font, 'B', $fontsize14);
            // PDF::MultiCell(235, 15, 'PROFORMA INVOICE - ORIGINAL', '', 'C', 0, 0, '321', '27', false, 0, false, false);
            PDF::SetFont($font, 'B', $fontsize14, $border);
            PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::MultiCell(125, 15, 'PROFORMA INVOICE: ', '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($data[0]['proinvoice']) ? $data[0]['proinvoice'] : ''),  '', 'L', false);
        }

           if ($sjlogo == 'wlogo' && $ogordp == 'dp') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 310, 80);
            PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            // PDF::SetFont($font, 'B', $fontsize14);
            // PDF::MultiCell(290, 15, 'PROFORMA INVOICE - DUPLICATE', '', 'C', 0, 0, '298', '26', false, 0, false, false);
            PDF::SetFont($font, 'B', $fontsize14, $border);
            PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::MultiCell(125, 15, 'PROFORMA INVOICE: ', '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($data[0]['proinvoice']) ? $data[0]['proinvoice'] : ''),  '', 'L', false);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'dp') {
            PDF::SetFont($font, '', 10);
            // PDF::SetFont($font, 'B', $fontsize14);
            // PDF::MultiCell(235, 15, 'PROFORMA INVOICE - DUPLICATE', '', 'C', 0, 0, '325', '27', false, 0, false, false);
            PDF::SetFont($font, 'B', $fontsize14, $border);
            PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::MultiCell(125, 15, 'PROFORMA INVOICE: ', '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($data[0]['proinvoice']) ? $data[0]['proinvoice'] : ''),  '', 'L', false);
        }

        // PDF::SetFont($font, 'B', $fontsize14, $border);
        // PDF::MultiCell(340, 15, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
        // PDF::MultiCell(125, 15, 'PROFORMA INVOICE: ', '', 'L', false, 0);
        // PDF::MultiCell(100, 15, (isset($data[0]['proinvoice']) ? $data[0]['proinvoice'] : ''),  '', 'L', false);

        PDF::SetFont($font, 'B', $fontsize11);
        PDF::MultiCell(340, 15, '', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize14);
        PDF::MultiCell(125, 15, 'Date: ', '', 'L', false, 0);
        PDF::MultiCell(100, 15, date("F d,Y", strtotime($data[0]['prodate'])),  '', 'L', false);

        for ($r = 0; $r < $cyourref; $r++) {
            $lbl = '';
            if ($r == 0) {
                $lbl = 'PO Ref: ';
            }
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(340, 15, '', '', 'R', false, 0);
            PDF::SetFont($font, '', $fontsize14);
            PDF::MultiCell(125, 15, $lbl, '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($arryourref[$r]) ? $arryourref[$r] : ''),  '', 'L', false);
        }

        for ($r = 0; $r < $cterms; $r++) {
            $lbl = '';
            if ($r == 0) {
                $lbl = 'Payment Terms: ';
            }
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(340, 15, '', '', 'R', false, 0);
            PDF::SetFont($font, 'B', $fontsize14);
            PDF::MultiCell(125, 15, $lbl, '', 'L', false, 0);
            PDF::MultiCell(100, 15, (isset($arrterms[$r]) ? $arrterms[$r] : ''),  '', 'L', false);
        }


        $headername = $headerdata[0]->name;
        $arrheadername = $this->reporter->fixcolumn([$headername], 50, 0);
        $cheadername = count($arrheadername);
        $groupid = "";
        if ($data[0]['groupid'] != "") {
            $groupid = " - " . $data[0]['groupid'];
        }
        $clientname = (isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : '');
        $arrclientname = $this->reporter->fixcolumn([$clientname], 50, 0);
        $cclientname = count($arrclientname);

        $maxrow = max($cclientname, $cheadername);

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize13);
            PDF::MultiCell(340, 0, (isset($arrheadername[$r]) ? $arrheadername[$r] : ''), '', 'L', false, 0);
            PDF::SetFont($font, 'B', $fontsize12);
            PDF::MultiCell(225, 0, (isset($arrclientname[$r]) ? $arrclientname[$r] : ''),  '', 'L', false);
        }

        $address = $headerdata[0]->address;
        $tel = $headerdata[0]->tel;
        $email = 'Email Address: ' . $headerdata[0]->email;
        $vat = 'VAT REG TIN: ' . $headerdata[0]->tin;
        $arraddress = $this->reporter->fixcolumn([$address, $tel, $email, $vat], 70, 0);
        $caddress = count($arraddress);

        $billingaddress = (isset($data[0]['billingaddress']) ? $data[0]['billingaddress'] : '');
        $billcontactno = '<b>Phone: </b>' . (isset($data[0]['billcontactno']) ? $data[0]['billcontactno'] : '');
        $billcontact = '<b>Contact Name: </b>' . (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : '');

        $arrbillingaddress = $this->reporter->fixcolumn([$billingaddress, $billcontactno, $billcontact], 50, 0);
        $cbillingaddress = count($arrbillingaddress);

        $maxrow = max($caddress, $cbillingaddress);

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize13);
            PDF::MultiCell(340, 0, (isset($arraddress[$r]) ? $arraddress[$r] : ''), '', 'L', false, 0, '', '', true, 0, true);
            PDF::SetFont($font, '', $fontsize12);
            PDF::MultiCell(225, 0, (isset($arrbillingaddress[$r]) ? $arrbillingaddress[$r] : ''),  '', 'L', false, 1, '', '', true, 0, true);
        }

        PDF::MultiCell(0, 10, "\n");

        PDF::SetFont($font, '', $fontsize12);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(25, 15, 'No.', '1', 'C', 1, 0);
        PDF::MultiCell(70, 15, 'Order Codes', '1', 'C', 1, 0);
        PDF::MultiCell(70, 15, 'Mfr', '1', 'C', 1, 0);
        PDF::MultiCell(140, 15, 'Description', '1', 'C', 1, 0);
        PDF::MultiCell(20, 15, '', '1', 'C', 1, 0);
        PDF::MultiCell(50, 15, 'Quantity', '1', 'C', 1, 0);
        PDF::MultiCell(25, 15, '', '1', 'C', 1, 0);
        PDF::MultiCell(75, 15, 'Unit Price', 'TB', 'C', 1, 0);
        PDF::MultiCell(100, 15, 'Line Total', '1', 'C', 1, 1);
    }


    public function blankpage($params, $data, $font)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->modulename = app('App\Http\Classes\modules\sales\qs')->modulename;

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [595, 842]);
        PDF::SetMargins(10, 10);

        PDF::MultiCell(0, 0, "\n");
        $this->addrow('TLR');
    }

    
    public function report_SI_headerpdf($params, $data, $font)
    {
        $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];
        $center = $params['params']['center'];
        $ogordp = $params['params']['dataparams']['radiopoafti'];

        $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $this->modulename = app('App\Http\Classes\modules\sales\sj')->modulename;

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [595, 842]);
        PDF::SetMargins(10, 10);

        $fontsize9 = "9";
        $fontsize11 = "11";
        $border = "1px solid ";

        PDF::SetFont($font, '', 14);
        if ($sjlogo == 'wlogo' && $ogordp == 'og') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(290, 0, 'PROFORMA INVOICE - ORIGINAL', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'og') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(260, 0, 'PROFORMA INVOICE - ORIGINAL', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        }

        PDF::SetFont($font, '', 14);
        if ($sjlogo == 'wlogo' && $ogordp == 'dp') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(290, 0, 'PROFORMA INVOICE - DUPLICATE', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'dp') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(260, 0, 'PROFORMA INVOICE - DUPLICATE', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        }


        $trno = $params['params']['dataid'];
        if ($this->coreFunctions->datareader("select count(trno) as  value from arledger where trno = " . $trno . " and bal = 0 ")) {
            PDF::SetTextColor(0, 0, 200);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', 40);
            PDF::StartTransform();
            PDF::Rotate(45);
            PDF::MultiCell(260, 0, 'PAID', '', 'C', 0, 0, '220', '135', false, 0, false, false, 0);
            PDF::StopTransform();
            PDF::SetTextColor(0, 0, 0);
        }

        PDF::MultiCell(0, 35, "\n");

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '55');
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Invoice No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . $data[0]['docnum'], $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(260, 0, $headerdata[0]->address, '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Invoice Date',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . date("F d, Y", strtotime($data[0]['dateid'])), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(260, 0, '', '', 'R', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Do No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . $data[0]['docnum'], $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(260, 0, '', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Cust PO No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(260, 0, ': ', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Payment Terms',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(160, 15, '  ' . 'CUSTOMER NAME', 'LT', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(160, 15, '', 'TR', 'L', false, 0);
        PDF::MultiCell(30, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(80, 15, ' ' . 'Page No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(140, 15, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        $customer = isset($data[0]['clientname']) ? $data[0]['clientname'] : '';
        $arrcustomer = $this->reporter->fixcolumn([$customer], 50, 0);
        $ccustomer = count($arrcustomer);

        for ($r = 0; $r < $ccustomer; $r++) {
            PDF::SetFont($font, 'B', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, '  ' . (isset($arrcustomer[$r]) ? $arrcustomer[$r] : ''), 'R', 'L', false, 0, '', '', true, 0, true);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        $tin = '<b>TIN:</b> ' . (isset($data[0]['tin']) ? $data[0]['tin'] : '');
        $arrtin = $this->reporter->fixcolumn([$tin], 50, 0);
        $ctin = count($arrtin);

        for ($r = 0; $r < $ctin; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, '  ' . (isset($arrtin[$r]) ? $arrtin[$r] : ''), 'R', 'L', false, 0, '', '', true, 0, true);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        $bstyle = '<b>Business Name/Style: </b> ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '');
        $arrbstyle = $this->reporter->fixcolumn([$bstyle], 60, 0);
        $cbstyle = count($arrbstyle);

        for ($r = 0; $r < $cbstyle; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, '  ' . (isset($arrbstyle[$r]) ? $arrbstyle[$r] : ''), 'R', 'L', false, 0, '', '', true, 0, true);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(320, 0, '', 'LR', 'L', false, 0);
        PDF::MultiCell(360, 0, '', '', 'L', false);

        if ($data[0]['baddrline1'] != '') {
            $arrabddrline = $this->reporter->fixcolumn([$data[0]['baddrline1']], 55, 1);
            $counbddr = count($arrabddrline);
            for ($r = 0; $r < $counbddr - 1; $r++) {
                PDF::SetFont($font, '', $fontsize9);
                PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
                PDF::MultiCell(315, 0, isset($arrabddrline[$r]) ? ' ' . $arrabddrline[$r] : '', 'R', 'L', false, 0);
                PDF::MultiCell(360, 0, '', '', 'L', false);
            }
        }

        if ($data[0]['baddrline2'] != '') {
            $arrabddrline = $this->reporter->fixcolumn([$data[0]['baddrline2']], 55, 1);
            $counbddr = count($arrabddrline);
            for ($r = 0; $r < $counbddr - 1; $r++) {
                PDF::SetFont($font, '', $fontsize9);
                PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
                PDF::MultiCell(315, 0, isset($arrabddrline[$r]) ? ' ' . $arrabddrline[$r] : '', 'R', 'L', false, 0);
                PDF::MultiCell(360, 0, '', '', 'L', false);
            }
        }

        if ($data[0]['bcity'] != '' || $data[0]['bzipcode']) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, ' ' . (isset($data[0]['bcity']) ? $data[0]['bcity'] : '') . ' ' . (isset($data[0]['bzipcode']) ? $data[0]['bzipcode'] : ''), 'R', 'L', false, 0);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        if ($data[0]['bprovince'] != '') {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, ' ' . (isset($data[0]['bprovince']) ? $data[0]['bprovince'] : '') . '', 'R', 'L', false, 0);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        if ($data[0]['bcountry']) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(315, 0, ' ' . (isset($data[0]['bcountry']) ? $data[0]['bcountry'] : '') . ' ', 'R', 'L', false, 0);
            PDF::MultiCell(360, 0, '', '', 'L', false);
        }

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(320, 0, '', 'LR', 'L', false, 0);
        PDF::MultiCell(360, 0, '', '', 'L', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(80, 15, '  ' . 'Contact Name: ', 'L', 'L', false, 0);
        PDF::MultiCell(240, 15, '  ' . (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'R', 'L', false, 0);
        PDF::MultiCell(440, 15, '', '', 'L', false);

        if ($data[0]['billcontactno'] != '') {
            $arrbillcontactno = $this->reporter->fixcolumn([$data[0]['billcontactno']], 55, 1);
            $countbca = count($arrbillcontactno);

            for ($r = 0; $r < $countbca - 1; $r++) {
                if ($r == 0) {
                    $label = '  ' . 'Contact No.: ';
                } else {
                    $label = '';
                }
                PDF::SetFont($font, 'B', $fontsize9);
                PDF::MultiCell(85, 15, $label, 'L', 'L', false, 0);
                PDF::MultiCell(235, 15, '  ' . isset($arrbillcontactno[$r]) ? $arrbillcontactno[$r] : '', 'R', 'L', false, 0);
                PDF::MultiCell(440, 15, '', '', 'L', false);
            }
        }

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(80, 0, '', 'BL', 'L', false, 0);
        PDF::MultiCell(240, 0, '', 'BR', 'L', false, 0);
        PDF::MultiCell(440, 0, '', '', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $hheight = PDF::getStringHeight(30, 'Trans.Type');
        PDF::SetFont($font, '', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(25, 0, 'No.', '1', 'C', 1, 0);
        PDF::MultiCell(50, 0, 'Part #', '1', 'C', 1, 0);
        PDF::MultiCell(60, 0, 'Mfr', '1', 'C', 1, 0);
        PDF::MultiCell(140, 0, 'Description', '1', 'C', 1, 0);
        PDF::MultiCell(50, 0, 'Trans.Type', '1', 'C', 1, 0);
        PDF::MultiCell(50, 0, 'Qty', '1', 'C', 1, 0);
        PDF::MultiCell(100, 0, 'Unit Price', '1', 'C', 1, 0);
        PDF::MultiCell(100, 0, 'Line Total', '1', 'C', 1);
    }

    public function reportsalesinvoicepdf($params, $data)
    {
        $trno = $params['params']['dataid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 780;

        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $totalext = 0;

        $fontsize9 = "9";
        $fontsize11 = "11";
        $fontsize12 = "12";

        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        $this->proformaplotting_headerpdf($params, $data, $font);

        $arritemname = array();
        $countarr = 0;

        $arrordercode = array();
        $countarrcode = 0;

        $arrmfr = array();
        $countarrmfr = 0;

        $arrqty = array();
        $countarrqty = 0;

        $arrprice = array();
        $countarrprice = 0;

        $arrlinetotal = array();
        $countarrlinetotal = 0;

        $totalctr = 0;

        $newpageadd = 0;
        $datastock = $data;

        if (!empty($datastock)) {
            for ($i = 0; $i < count($datastock); $i++) {
                $inforem = '';

                if ($datastock[$i]['disc'] != "") {
                    $unitprice = $datastock[$i]['netamt'];
                } else {
                    $unitprice = $datastock[$i]['amt'];
                }

                $linetotal = $datastock[$i]['qty'] * $unitprice;

                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(number_format($datastock[$i]['qty'], 0)) . ' ', 10));
                $countarrqty = count($arrqty);

                $arracurr = (str_split('PHP', 3));

                $arrprice = (str_split(trim('' . number_format($unitprice, $decimalprice)) . ' ', 18));
                $countarrprice = count($arrprice);

                $arrlinetotal = (str_split(trim('' . number_format($linetotal, $decimalqty)) . ' ', 18));
                $countarrlinetotal = count($arrlinetotal);

                $itemcode = $datastock[$i]['itemname'];
                $itembrand = $datastock[$i]['brand_desc'];
                $itemdescription = $datastock[$i]['itemdescription'];
                $itemaccessories = $datastock[$i]['accessories'];
                $iteminfo = $datastock[$i]['inforem'];

                $arrordercode = $this->reporter->fixcolumn([$itemcode], '8', 1);
                $countarrcode = count($arrordercode);

                $arrmfr = $this->reporter->fixcolumn([$itembrand], '8', 1);
                $countarrmfr = count($arrmfr);

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemaccessories, $iteminfo], '25', 1);
                $countarrcol = count($itemcoldes);

                $maxrow = 1;
                $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

                if ($datastock[$i]['itemname'] == '') {
                } else {
                    if ($newpageadd == 1) {
                        $newpageadd = 0;
                        $this->addrowsj('LRB');
                    }
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $inum = $i + 1;
                            if ($datastock[$i]['vattype'] == 'VATABLE') {
                                $vattype = 'V';
                            } else {
                                $vattype = 'ZRV';
                            }
                        } else {
                            $inum = '';
                            $vattype = '';
                        }

                        PDF::SetFont($font, '', $fontsize9);
                        PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(70, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(140, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(20, 0, $vattype, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(50, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        
                        PDF::MultiCell(25, 0, isset($arracurr[$r]) ? $arracurr[$r] : '', 'L', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(75, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'R', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(25, 0, isset($arracurr[$r]) ? $arracurr[$r] : '', 'L', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(75, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'R', 'R', false, 1, '', '', true, 1, true, true, 0, 'B', true);

                        if (PDF::getY() >= $page) {
                            $this->addrowsj('LRB');
                            $this->proformaplotting_headerpdf($params, $data, $font);
                        }
                    }
                }

                if ($datastock[0]['vattype'] == 'VATABLE') {
                    $vatsales = $vatsales + $linetotal;
                } else {
                    $vatsales = 0;
                    $totalext = $totalext + $linetotal;
                }
            }
        }

        if ($datastock[0]['vattype'] == 'VATABLE') {
            $vat = round($vatsales * .12, 2);
            $totalext = round($vatsales + $vat, 2);
        } else {
            $vat = 0;
        }

        if (PDF::getY() > 595) {
            $this->addrowsj('LRB');
            $newpageadd = 1;
            $this->proformaplotting_headerpdf($params, $data, $font);
        }
        do {
            $this->addrowsj('LR');
        } while (PDF::getY() < 595);

        $cur = $data[0]['cur'];
        $nonvat = '0.00';
        if ($datastock[0]['vattype'] != 'VATABLE') {
            $nonvat = number_format($totalext, 2);
        }


        //1
        PDF::MultiCell(150, 10, '', 'T', 'R', false, 0);
        PDF::MultiCell(90, 10, 'VATable Sales ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'T', 'L', false, 0);
        PDF::MultiCell(85, 10, number_format($vatsales, 2), 'TBR', 'R', false, 0);
        PDF::MultiCell(10, 10, '', 'T', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Total Sales(VAT Inclusive) ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($totalext, 2), 'TBR', 'R', false);


        //2
        PDF::SetFillColor(211, 211, 211);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, 'Approved By:', '', 'C', 1, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(90, 10, 'VAT ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'T', 'L', false, 0);
        PDF::MultiCell(85, 10, number_format($vat, 2), 'TBR', 'R', false, 0);
        // PDF::MultiCell(65, 10, number_format($vat, 2), 'TBLR', 'R', false,0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Less: VAT ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($vat, 2), 'TBR', 'R', false);


        //3
        PDF::MultiCell(150, 10, '', '', 'R', false, 0);
        PDF::MultiCell(90, 10, 'Zero-Rated Sales ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'T', 'L', false, 0);
        PDF::MultiCell(85, 10, $nonvat, 'TBR', 'R', false, 0);


        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        // PDF::MultiCell(65, 10, $nonvat, 'TBLR', 'R', false,0);
        PDF::MultiCell(110, 10, 'Amount: Net of VAT ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($vatsales, 2), 'TBR', 'R', false);


        //4
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(130, 10, '', 'B', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(90, 10, 'Vat-Exempt Sales ', 'TBLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'TBL', 'L', false, 0);
        PDF::MultiCell(85, 10, '0.00', 'TBR', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Less: Discount ', 'TLR', 'R', false, 0);
        PDF::MultiCell(30, 10, " " . $cur, 'T', 'L', false, 0);
        PDF::MultiCell(70, 10, '0.00', 'TR', 'R', false);


        //5 575
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, '(SC/PWD/NAAC/MOV/SP) ', 'BLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'BLR', 'R', false);

        //6
         PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(300, 10, '', '', 'R', false, 0);
        // PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(55, 10, '', '', 'R', false, 0);
       
        PDF::MultiCell(110, 10, 'Add: VAT ', 'TBLR', 'R', false, 0);

        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($vat, 2), 'TBR', 'R', false);


        //7
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Less: Withholding Tax ', 'TBLR', 'R', false, 0);

        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, '0.00', 'TBR', 'R', false);

        //8
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(110, 10, 'TOTAL AMOUNT DUE ', 'TBLR', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($totalext, 2), 'TBR', 'R', false);

        //9
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(65, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(135, 10, '', '', 'R', false, 0);
        PDF::MultiCell(115, 10, '', '', 'R', false);

        //10
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, '(SC/PWD/NAAC/MOV/SP) ', 'TLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'TLR', 'R', false);

        //11
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Solo Parent ID No.: ', 'BLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'BLR', 'R', false);

        //12
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, '(SC/PWD/NAAC/MOV/SP) ', 'TLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'TLR', 'R', false);

        //13
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(45, 10, 'Term:', 'B', 'L', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, 'Signature: ', 'BLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'BLR', 'R', false);

        PDF::MultiCell(165, 10, 'ACCN: ' . "<u>" . 'AC 047 082024 000368' . "</u>", '', 'L', false, 0, '', '', true, 0, true);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(340, 10, 'In the event of default non payment the customer shall pay 12% interest', '', 'L', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false);

        PDF::MultiCell(165, 10, 'Date issued: ' . "<u>August 1,2024</u>", '', 'L', false, 0, '', '', true, 0, true);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(350, 10, 'per annum on all accounts over due plus 25% for attorney`s fees and cost', '', 'L', false);

        PDF::MultiCell(225, 10, 'Approved Series Range: ' . "<u>" . '031000-999999' . "</u>", '', 'L', false, 0, '', '', true, 0, true);
        PDF::MultiCell(350, 10, 'of collection. The parties hereby voluntarily submit the jurisdiction of the', '', 'L', false);


        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(60, 10, '', '', 'R', false, 0);
        PDF::MultiCell(350, 10, 'proper court in Makati in case of litigation.', '', 'L', false);
         
        PDF::MultiCell(0, 10, "\n");

        PDF::MultiCell(575, 10, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX."', '', 'C', false);


        //"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX."


        $pdf = PDF::Output($this->modulename . '.pdf', 'S');
        /* sending email sample
        $str = $this->reportplotting($params,$data);
        $info['companyid'] = $params['params']['companyid'];
        $info['subject'] = 'LAST Offense';
        $info['view'] = 'emails.firstnotice';
        $info['msg'] = $str;  //'<div>Good Day!</div><br></div>This is friendly reminder that your account balance of '.$this->modulename.' </div><br><br><br><br><br></div>Thank You,</div><Br><div>xxxxxxx</div>';
        $info['filename']=$this->modulename;
        $info['pdf']=$pdf;
        Mail::to('erick0601@yahoo.com')->send(new SendMail($info));
        */

        return $pdf;
    }
    
    //ADD ROWS
    private function addrowsj($border)
    {

        // PDF::MultiCell(25, 0, '', $border, 'L', false, 0, '', '', true, 1);
        // PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
        // PDF::MultiCell(60, 0, '', $border, 'L', false, 0, '', '', false, 1);
        // PDF::MultiCell(140, 0, '', $border, 'L', false, 0, '', '', false, 1);
        // PDF::MultiCell(50, 0, '', $border, 'R', false, 0, '', '', false, 1);
        // PDF::MultiCell(50, 0, '', $border, 'L', false, 0, '', '', false, 1);
        // PDF::MultiCell(100, 0, '', $border, 'L', false, 0, '', '', false, 1);
        // PDF::MultiCell(100, 0, '', $border, 'R', false, 1, '', '', false, 1);

         PDF::MultiCell(25, 0, '', $border, 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(70, 0, '', $border, 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(70, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(140, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(20, 0, '', $border, 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(25, 0, '', 'L', 'L', false, 0, '', '', false, 1);
         PDF::MultiCell(75, 0, '', 'R', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(25, 0, '', 'L', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(75, 0, '', 'R', 'R', false, 1, '', '', false, 1);
    }
}
