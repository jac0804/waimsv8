<?php

namespace App\Http\Classes\modules\modulereport\afti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


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

class sj
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
        $fields = ['approved', 'cur', 'forex', 'radiosjafti', 'radiopoafti', 'radiosjaftilogo', 'print'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radiopoafti.label', 'Formats');
        data_set($col1, 'radiopoafti.options', [
            ['label' => 'Original', 'value' => 'og', 'color' => 'red'],
            ['label' => 'Duplicate', 'value' => 'dp', 'color' => 'red']
        ]);

        data_set($col1, 'approved.type', 'lookup');
        data_set($col1, 'approved.action', 'lookuppreparedby');
        data_set($col1, 'approved.lookupclass', 'approved');
        data_set($col1, 'approved.readonly', true);

        data_set($col1, 'cur.type', 'input');
        data_set($col1, 'cur.readonly', false);
        data_set($col1, 'forex.readonly', false);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
            'PDFM' as print,
            'deliverynote' as radiosjafti,
            'og' as radiopoafti,
            'woutlogo' as radiosjaftilogo,
            '' as approved,'PHP' as cur , '1' as forex
            "
        );
    }

    public function report_sj_query($trno)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $query = "select right(head.docno,6) as docnum,stock.line, stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.sizeid, ag.clientname as agname, item.brand,head.vattype,
            wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,
            bill.addr as billaddr,concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact,cp.contactno as billcontactno,
            ship.addr as shipaddr,concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact,scp.contactno as shipcontactno,cm.cat_name as bstyle,
            sostock.isqty as soqty,head.taxdef,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry, head.cur, emp.clientname as opl
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            left join client as emp on ag.empid=emp.clientid
            left join part_masterfile as part on part.part_id = item.part
            left join billingaddr as bill on bill.clientid = client.clientid and bill.line = head.billid
            left join billingaddr as ship on ship.clientid = client.clientid and ship.line = head.shipid
            left join contactperson as cp on cp.clientid = client.clientid and cp.line = head.billcontactid
            left join contactperson as scp on scp.clientid = client.clientid and scp.line = head.shipcontactid
            left join hqsstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
            left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
            left join category_masterfile as cm on cm.cat_id = client.category
            where head.doc='sj' and head.trno='$trno' 
            UNION ALL
            select right(head.docno,6) as docnum,stock.line, stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.sizeid, ag.clientname as agname, item.brand,head.vattype,
            wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,
            bill.addr as billaddr,concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact,cp.contactno as billcontactno,
            ship.addr as shipaddr,concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact,scp.contactno as shipcontactno,cm.cat_name as bstyle,
            sostock.isqty as soqty,head.taxdef,
            bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince, bill.country as bcountry,
            ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as szipcode, ship.province as sprovince, ship.country as scountry, head.cur, emp.clientname as opl
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            left join client as emp on ag.empid=emp.clientid
            left join part_masterfile as part on part.part_id = item.part
            left join billingaddr as bill on bill.clientid = client.clientid and bill.line = head.billid
            left join billingaddr as ship on ship.clientid = client.clientid and ship.line = head.shipid
            left join contactperson as cp on cp.clientid = client.clientid and cp.line = head.billcontactid
            left join contactperson as scp on scp.clientid = client.clientid and scp.line = head.shipcontactid
            left join hqsstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
            left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
            left join category_masterfile as cm on cm.cat_id = client.category
            where head.doc='sj' and head.trno='$trno' 
            order by line
            ";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    private function stockquery($trno)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $query = "select 
            stock.trno, stock.line,
            stock.rem as srem, 
            item.barcode,
            item.itemname, 
            stock.isqty as qty, 
            stock.uom, 
            stock.isamt as amt,
            stock.disc, 
            stock.ext, 
            
            case uom.factor when 1 then stock.amt else stock.amt*uom.factor end as netamt, 
            concat(brands.brand_desc,' (',m.model_name,')') as brand_desc,

            iteminfo.itemdescription, 
            iteminfo.accessories,
            stockinfo.rem as inforem,
            sostock.isqty as soqty, 
            head.vattype,head.ewtrate,
            sostock.voidqty,
            sostock.iss as iss,
            sostock.sjqa,
            sostock.sjqa/uom.factor as qa,
            stock.sortline,
            uom.factor,head.taxdef

            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join model_masterfile as m on m.model_id = item.model
            left join iteminfo on iteminfo.itemid = item.itemid
            left join stockinfo  on stockinfo.trno = stock.trno and stockinfo.line = stock.line
            left join hqsstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
            left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            where head.doc='sj' and head.trno='$trno' and stock.noprint = 0
             group by stock.trno, stock.line,stock.rem, item.barcode,
            item.itemname, stock.isqty, stock.uom, stock.isamt,stock.amt, stock.disc, stock.ext, brands.brand_desc,
            iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem,sostock.isqty,sostock.iss,head.vattype,ewtrate,m.model_name,sostock.voidqty,sostock.sjqa,uom.uom,uom.factor,stock.sortline,head.taxdef
            
            union all
            select stock.trno, stock.line,stock.rem as srem, item.barcode,
            item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext,case uom.factor when 1 then stock.amt else stock.amt*uom.factor end as netamt, concat(brands.brand_desc,' (',m.model_name,')') as brand_desc,
            iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem as inforem,sostock.isqty as soqty, head.vattype,head.ewtrate,sostock.voidqty,sostock.iss as iss,sostock.sjqa,sostock.sjqa/uom.factor as qa,stock.sortline,uom.factor,head.taxdef
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join model_masterfile as m on m.model_id = item.model
            left join iteminfo on iteminfo.itemid = item.itemid
            left join hstockinfo as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
            left join hqsstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
            left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            where head.doc='sj' and head.trno='$trno' and stock.noprint = 0 
             group by stock.trno, stock.line,stock.rem, item.barcode,
            item.itemname, stock.isqty, sostock.iss,stock.uom, stock.isamt,stock.amt, stock.disc, stock.ext, brands.brand_desc,
            iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem,sostock.isqty, head.vattype,head.ewtrate,m.model_name,sostock.voidqty,sostock.sjqa,uom.uom,uom.factor,stock.sortline,head.taxdef
            order by sortline,line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    private function serialquery($trno, $line)
    {
        $query = "select ifnull(concat(rr.serial,''),'') as serialno
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.doc='sj' and head.trno='$trno' and stock.line = '$line' and stock.noprint = 0 and item.isserial = 1
        union all
        select ifnull(concat(rr.serial,''),'') as serialno
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.doc='sj' and head.trno='$trno' and stock.line = '$line' and stock.noprint = 0 and item.isserial = 1
        order by serialno";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }
    //OLD FORMAT START
    public function reportplotting($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();
        $str .= $this->report_default_header($params, $data);
        return $str;
        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $config['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $totalext = $totalext + $data[$i]['ext'];

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // <--- Header
                $str .= $this->report_default_header($params, $data);

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            } //end if
        } //end for

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    private function report_default_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
        $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R P T I O N', '500px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        return $str;
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
            PDF::MultiCell(290, 0, 'SALES INVOICE - ORIGINAL', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'og') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(260, 0, 'SALES INVOICE - ORIGINAL', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        }

        PDF::SetFont($font, '', 14);
        if ($sjlogo == 'wlogo' && $ogordp == 'dp') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(290, 0, 'SALES INVOICE - DUPLICATE', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'dp') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(260, 0, 'SALES INVOICE - DUPLICATE', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
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
        $currencyparams   = $params['params']['dataparams']['cur'];
        $forexpara   = $params['params']['dataparams']['forex'];
        $options   = $params['params']['dataparams']['radiosjafti'];
        $forexparams = floatval($forexpara);


        $linetotal = 0;
        $unitprice = 0;
        $vatsales = 0;
        $vat = 0;
        $ewt = 0;
        $totalext = 0;

        $fontsize9 = "9";
        $fontsize11 = "11";
        $fontsize12 = "12";

        $font = '';
        if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
        }

        $this->report_SI_headerpdf($params, $data, $font);

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
        $datastock = $this->stockquery($trno);

        if (!empty($datastock)) {
            for ($i = 0; $i < count($datastock); $i++) {
                $inforem = '';

                if ($datastock[$i]['disc'] != "") {
                    $unitprice = $datastock[$i]['netamt'];
                } else {
                    $unitprice = $datastock[$i]['amt'];
                }

                $linetotal = $datastock[$i]['qty'] * $unitprice;
                
                //09.05.2025
                if ($options == 'salesinvoice') {
                    if ($forexparams != 1) {
                        $unitprice = $unitprice / $forexparams;
                        $linetotal =  $linetotal / $forexparams;
                    }
                }



                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(number_format($datastock[$i]['qty'], 0)) . ' ', 10));
                $countarrqty = count($arrqty);

                $arracurr = (str_split('PHP', 3));

                if ($options == 'salesinvoice') {
                    if ($forexparams != 1) {
                        $arracu = $currencyparams;
                        $arracurr = (str_split($arracu, 3));
                    }
                }

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

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemaccessories, $iteminfo], '24', 1);
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
                        PDF::MultiCell(50, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(60, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(140, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(50, 0, $vattype, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(50, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(25, 0, isset($arracurr[$r]) ? $arracurr[$r] : '', 'L', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(75, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'R', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true); //imultiply sa forx
                        PDF::MultiCell(25, 0, isset($arracurr[$r]) ? $arracurr[$r] : '', 'L', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(75, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'R', 'R', false, 1, '', '', true, 1, true, true, 0, 'B', true);

                        if (PDF::getY() >= $page) {
                            $this->addrowsj('LRB');
                            $this->report_SI_headerpdf($params, $data, $font);
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

    
        if(!empty($datastock)){
            if ($datastock[0]['ewtrate'] != 0) {
                $ewt = $vatsales *  ($datastock[0]['ewtrate'] / 100);
            }

            if ($datastock[0]['vattype'] == 'VATABLE') {
                if ($datastock[0]['taxdef'] != 0) {
                    $vat = round($datastock[0]['taxdef'], 2);
                } else {
                    $vat = round($vatsales * .12, 2);
                }
                //$vat = round($vatsales * .12, 2);
                $totalext = round(($vatsales + $vat), 2);
                $tamtdue = round(($vatsales + $vat) - $ewt, 2);
            } else {
                $vat = 0;
            }

            $cur = $data[0]['cur'];
            $nonvat = '0.00';
            if ($datastock[0]['vattype'] != 'VATABLE') {
                $nonvat = number_format($totalext, 2);
            }
            if ($options == 'salesinvoice') {
                if ($forexparams != 1) {
                    $cur = $currencyparams;
                }
            }
        }else{
            $ewt =0;
            $vat = 0;
            $cur = $data[0]['cur'];
            $nonvat = '0.00';
        }
       

        $tamtdue = $totalext;

       

        if (PDF::getY() > 620) {
            $this->addrowsj('LRB');
            $newpageadd = 1;
            $this->report_SI_headerpdf($params, $data, $font);
        }
        do {
            $this->addrowsj('LR');
        } while (PDF::getY() < 620);

        


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


        //5
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        PDF::MultiCell(110, 10, '(SC/PWD/NAAC/MOV/SP) ', 'BLR', 'R', false, 0);
        PDF::MultiCell(100, 10, '', 'BLR', 'R', false);

        //6
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
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
        PDF::MultiCell(70, 10, number_format($ewt, 2), 'TBR', 'R', false);

        //8
        PDF::MultiCell(165, 10, '', '', 'R', false, 0);
        PDF::MultiCell(85, 10, '', '', 'R', false, 0);
        PDF::MultiCell(105, 10, '', '', 'R', false, 0);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(110, 10, 'TOTAL AMOUNT DUE ', 'TBLR', 'R', false, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(30, 10, " " . $cur, 'TB', 'L', false, 0);
        PDF::MultiCell(70, 10, number_format($tamtdue, 2), 'TBR', 'R', false);

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

    public function report_DR_headerpdf($params, $data, $font)
    {
        $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];

        $center = $params['params']['center'];
        $ogordp = $params['params']['dataparams']['radiopoafti'];

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
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
        $fontsize12 = "12";
        $fontsize13 = '13';
        $fontsize14 = "14";
        $border = "1px solid ";

        // SetFont(family, style, size)
        // MultiCell(width, height, txt, border, align, x, y)
        // write2DBarcode(code, type, x, y, width, height, style, align)

        // PDF::SetFont($font, '', 14);

        // if ($sjlogo == 'wlogo') {
        //     PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
        //     PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
        //     PDF::SetFont($font, 'B', $fontsize11);
        //     PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        // } else {
        //     PDF::SetFont($font, '', 10);
        //     PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
        //     PDF::SetFont($font, 'B', $fontsize11);
        //     PDF::MultiCell(300, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        // }


        PDF::SetFont($font, '', 14);
        if ($sjlogo == 'wlogo' && $ogordp == 'og') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'og') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - ORIGINAL', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        }

        PDF::SetFont($font, '', 14);
        if ($sjlogo == 'wlogo' && $ogordp == 'dp') {
            PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 210, 50);
            PDF::MultiCell(390, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - DUPLICATE', '', 'C', 0, 0, '300', '26', false, 0, false, false, 0);
        } else if ($sjlogo == 'woutlogo' && $ogordp == 'dp') {
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(390, 0, '         A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
            PDF::SetFont($font, 'B', $fontsize11);
            PDF::MultiCell(320, 0, 'DELIVERY RECEIPT - DUPLICATE', '', 'C', 0, 0, '315', '27', false, 0, false, false, 0);
        }
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 15, $headerdata[0]->name, '', 'L', false, 0, '', '55');
        PDF::MultiCell(90, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(60, 15, ' ' . 'DO No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 15, ' ' . (isset($data[0]['docnum']) ? $data[0]['docnum'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 15, $headerdata[0]->address, '', 'L', false, 0);
        PDF::MultiCell(90, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(60, 15, ' ' . 'DO Date',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 15, ' ' . date("F d, Y", strtotime($data[0]['dateid'])), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 0, '', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(60, 15, ' ' . 'PO Ref No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 15, ' ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 15, '', '', 'L', false, 0);
        PDF::MultiCell(90, 15, '', '', 'L', false, 0);
        PDF::SetFillColor(211, 211, 211);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(60, 15, ' ' . 'Payment',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 15, ' ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 15, '', '', 'L', false, 0);
        PDF::MultiCell(90, 15, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(60, 15, ' ' . 'Page No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 15, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(270, 5, '', '', 'L', false, 0);
        PDF::MultiCell(90, 5, '', '', 'L', false, 0);
        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(60, 5, ' ' . '',  '', 'L', 0, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(150, 5, '', '', 'L', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(130, 0, '  ' . 'CUSTOMER NAME', 'LT', 'L', 1, 0);
        PDF::MultiCell(150, 0, '', 'TR', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '  ' . 'SHIP TO', 'LT', 'L', 1, 0);
        PDF::MultiCell(180, 0, '', 'TR', 'L', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(280, 20, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false, 0);
        PDF::MultiCell(10, 20, '', '', 'L', false, 0);
        PDF::MultiCell(280, 20, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false);

        PDF::SetFont($font, '', $fontsize9);

        $baddr1 = $data[0]['baddrline1'];
        $baddr2 = $data[0]['baddrline2'];
        $bcity = $data[0]['bcity'] . ' ' . $data[0]['bzipcode'];
        $bprovince = $data[0]['bprovince'];
        $bcountry = $data[0]['bcountry'];

        $saddr1 = $data[0]['saddrline1'];
        $saddr2 = $data[0]['saddrline2'];
        $scity = $data[0]['scity'] . ' ' . $data[0]['szipcode'];
        $sprovince = $data[0]['sprovince'];
        $scountry = $data[0]['scountry'];

        $arrabddrline = $this->reporter->fixcolumn([$baddr1, $baddr2, $bcity, $bprovince, $bcountry], 50, 0);
        $caddrbline1 = count($arrabddrline);
        $arrasddrline = $this->reporter->fixcolumn([$saddr1, $saddr2, $scity, $sprovince, $scountry], 50, 0);
        $caddrsline1 = count($arrasddrline);

        $maxrow = max($caddrbline1, $caddrsline1);
        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(275, 0, isset($arrabddrline[$r]) ? ' ' . $arrabddrline[$r] : '', 'R', 'L', false, 0);
            PDF::MultiCell(10, 0, '', '', 'L', false, 0);
            PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
            PDF::MultiCell(275, 0, isset($arrasddrline[$r]) ? ' ' . $arrasddrline[$r] : '', 'R', 'L', false);
        }

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(280, 0, '', 'LR', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(280, 0, '', 'LR', 'L', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(75, 15, '  ' . 'Contact Name: ', 'BL', 'L', false, 0);
        PDF::MultiCell(205, 15, '  ' . (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'BR', 'L', false, 0);
        PDF::MultiCell(10, 15, '', '', 'L', false, 0);
        PDF::MultiCell(75, 15, '  ' . 'Contact Name: ', 'BL', 'L', false, 0);
        PDF::MultiCell(205, 15, '  ' . (isset($data[0]['shipcontact']) ? $data[0]['shipcontact'] : ''), 'BR', 'L', false);

        PDF::MultiCell(0, 0, "");

        $hheight = PDF::getStringHeight(40, 'Qty Ordered');
        PDF::SetFont($font, '', $fontsize9);
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(25, 0, 'No.', '1', 'C', 1, 0);
        PDF::MultiCell(80, 0, 'Order Code', '1', 'C', 1, 0);
        PDF::MultiCell(60, 0, 'Mfr', '1', 'C', 1, 0);
        PDF::MultiCell(185, 0, 'Description', '1', 'C', 1, 0);
        PDF::MultiCell(65, 0, 'Qty Send', '1', 'C', 1, 0);
        PDF::MultiCell(65, 0, 'Qty Ordered', '1', 'C', 1, 0);
        PDF::MultiCell(45, 0, 'B/O', '1', 'C', 1, 0);
        PDF::MultiCell(45, 0, 'U/M', '1', 'C', 1);
        // PDF::MultiCell(0, 10, "");

    }

    public function reportdeliveryreceiptpdf($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $trno = $params['params']['dataid'];
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

        $this->report_DR_headerpdf($params, $data, $font);

        $arritemname = array();
        $countarr = 0;

        $arrordercode = array();
        $countarrcode = 0;


        $arrmfr = array();
        $countarrmfr = 0;

        $totalctr = 0;

        $totalqty = 0;

        $newpageadd = 0;
        $datastock = $this->stockquery($trno);
        if (!empty($datastock)) {

            for ($i = 0; $i < count($datastock); $i++) {
                $inforem = '';
                $itemserialno = '';

                $unitprice = $datastock[$i]['amt'];
                $linetotal = $datastock[$i]['qty'] * $unitprice;

                if ($unitprice == 0) {
                    $unitprice = 0;
                }

                if ($linetotal == 0) {
                    $linetotal = 0;
                }

                $arrqty = (str_split(trim(intval($datastock[$i]['qty']) . ' ' . $datastock[$i]['uom']) . ' ', 10));
                $countarrqty = count($arrqty);

                $arrprice = (str_split(trim('PHP ' . number_format($unitprice, $decimalprice)) . ' ', 14));
                $countarrprice = count($arrprice);

                $arrlinetotal = (str_split(trim('PHP ' . number_format($linetotal, $decimalqty)) . ' ', 14));
                $countarrlinetotal = count($arrlinetotal);

                $trno = $datastock[$i]['trno'];
                $line = $datastock[$i]['line'];

                $serialdata = $this->serialquery($trno, $line);

                $itemcode = $datastock[$i]['itemname'];
                $itembrand = $datastock[$i]['brand_desc'];
                $itemdescription = $datastock[$i]['itemdescription'];

                if (!empty($serialdata)) {
                    foreach ($serialdata as $key => $value) {
                        $itemserialno .= $value['serialno'] . " \t ";
                    }
                    if ($itemserialno) {
                        $itemserialno = "Serial No:   " . $itemserialno;
                    }
                }

                $itemaccessories = $datastock[$i]['accessories'];
                $iteminfo = $datastock[$i]['inforem'];


                $arrordercode = $this->reporter->fixcolumn([$itemcode], '9', 1);
                $countarrcode = count($arrordercode);

                $arrmfr = $this->reporter->fixcolumn([$itembrand], '8', 1);
                $countarrmfr = count($arrmfr);

                $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemserialno, $itemaccessories, $iteminfo], '30', 1);
                $countarrcol = count($itemcoldes);

                $maxrow = 1;
                $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

                if ($datastock[$i]['itemname'] == '') {
                } else {
                    if ($newpageadd == 1) {
                        $newpageadd = 0;
                        $this->addrow('LRB');
                    }
                    for ($r = 0; $r < $maxrow; $r++) {
                        if ($r == 0) {
                            $inum = $i + 1;

                            $soqty = $datastock[$i]['soqty'];
                            $qty = $datastock[$i]['qty'];
                            $voidqty = $datastock[$i]['voidqty'];
                            $qa = $datastock[$i]['qa'];
                            $sjqa = $datastock[$i]['sjqa'];
                            $iss = $datastock[$i]['iss'];
                            $uom = $datastock[$i]['uom'];
                            $totalqty = ($iss - $sjqa - $voidqty) / $datastock[$i]['factor'];

                            $soqty = number_format($datastock[$i]['soqty'], 0);
                            $qty = number_format($datastock[$i]['qty'], 0);
                            $voidqty = number_format($datastock[$i]['voidqty'], 0);
                            $qa = number_format($datastock[$i]['qa'], 0);
                            $sjqa = number_format($datastock[$i]['sjqa'], 0);
                            $iss = number_format($datastock[$i]['iss'], 0);
                        } else {
                            $inum = '';
                            $soqty = '';
                            $qty = '';
                            $uom = '';
                            $totalqty = '';
                            $qa = '';
                            $sjqa = '';
                            $iss = '';
                        }


                        PDF::SetFont($font, '', $fontsize9);
                        PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(80, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(60, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : ' ', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(185, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(65, 0, $qty . ' ', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(65, 0, $soqty . ' ', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(45, 0, $totalqty . ' ', 'LR', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                        PDF::MultiCell(45, 0, $uom, 'LR', 'C', false, 1, '', '', true, 1, true, true, 0, 'B', true);
                        if (PDF::getY() >= $page) {
                            $this->addrow('LRB');
                            $this->report_DR_headerpdf($params, $data, $font);
                        }
                    }
                }

                if ($datastock[0]['vattype'] == 'VATABLE') {
                    $vatsales = $vatsales + $linetotal;
                } else {
                    $vatsales = 0;
                    $totalext = $totalext + $linetotal;
                }

                if (PDF::getY() >= $page) {
                    $this->addrow('LRB');
                    $newpageadd = 1;
                    $this->report_DR_headerpdf($params, $data, $font);
                }
            }
        }

        if(!empty($datastock)){
            if ($datastock[0]['vattype'] == 'VATABLE') {
                $vat = $vatsales * .12;
                $totalext = $vatsales + $vat;
            } else {
                $vat = 0;
            }
        }else{
            $vat = 0;
        }

        

        if (PDF::getY() > 670) {
            $this->addrow('LRB');
            $newpageadd = 1;
            $this->report_DR_headerpdf($params, $data, $font);
        }
        do {
            $this->addrow('LR');
        } while (PDF::getY() < 670);

        $this->addrow('T');

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(30, 20, ' ', '', 'L', false, 0, 10, PDF::getY());
        PDF::SetFillColor(211, 211, 211);
        PDF::MultiCell(140, 20, 'PREPARED BY:',  '', 'C', 1, 0);
        PDF::MultiCell(41, 20, '', '', 'L', false, 0);
        PDF::MultiCell(140, 20, 'APPROVED BY: ',  '', 'C', 1, 0);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(50, 20, '', '', 'L', false, 0);
        PDF::MultiCell(140, 20, 'Received The Aboved item(s) In Good Order and Condition', '', 'C', false, 0);
        PDF::MultiCell(50, 20, '', '', 'R', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(40, 25, '', '', 'L', false, 0);
        PDF::MultiCell(140, 25, '',  '', 'C', false, 0);
        PDF::MultiCell(41, 25, '', '', 'L', false, 0);
        PDF::MultiCell(140, 25, '',  '', 'C', false, 0);
        PDF::MultiCell(50, 25, '', '', 'L', false, 0);
        PDF::MultiCell(140, 25, '', '', 'C', false, 0);
        PDF::MultiCell(50, 25, '', '', 'R', false);

        PDF::SetFont($font, 'B', $fontsize9);
        PDF::MultiCell(40, 0, '', '', 'L', false, 0);
        PDF::MultiCell(140, 0, '',  '', 'C', false, 0);
        PDF::MultiCell(31, 0, '', '', 'L', false, 0);
        PDF::MultiCell(140, 0, $params['params']['dataparams']['approved'],  'B', 'C', false, 0);
        PDF::MultiCell(49, 0, '', '', 'L', false, 0);
        PDF::MultiCell(140, 0, '', '', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'R', false);

        PDF::MultiCell(0, 25, "");

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(40, 20, '', '', 'L', false, 0);
        PDF::MultiCell(140, 20, '',  '', 'C', false, 0);
        PDF::MultiCell(41, 20, '', '', 'L', false, 0);
        PDF::MultiCell(140, 20, '',  '', 'C', false, 0);
        PDF::MultiCell(43, 20, '', '', 'L', false, 0);
        PDF::MultiCell(140, 20, 'Signature Over Printed Name', 'T', 'C', false, 0);
        PDF::MultiCell(50, 20, '', '', 'R', false);

        PDF::MultiCell(0, 10, "");

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(30, 0, '', '', 'L', false, 0);
        PDF::MultiCell(450, 0, 'BIR Permit No. 11-20-13-047-CGAR-000570', '', 'L', false, 0);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    //ADD ROWS
    private function addrowsj($border)
    {

        PDF::MultiCell(25, 0, '', $border, 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(60, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(140, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', $border, 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, '', $border, 'R', false, 1, '', '', false, 1);
    }

    private function addrow($border)
    {
        PDF::MultiCell(25, 0, '', $border, 'C', false, 0, '', '', true, 1);
        PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(60, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(185, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(65, 0, '', $border, 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(65, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(45, 0, '', $border, 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(45, 0, '', $border, 'R', false, 1, '', '', false, 1);
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
    }
}
