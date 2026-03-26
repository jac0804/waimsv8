<?php

namespace App\Http\Classes\modules\customform;

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

class viewtsdetail
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK TRANSFER';
    public $gridname = 'customformacctg';
    public $head = 'lahead';
    public $stock = 'lastock';

    public $hhead = 'glhead';
    public $hstock = 'glstock';

    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablenum = 'cntnum';
    public $htablelogs = 'htable_log';
    private $companysetup;
    private $coreFunctions;
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';
    private $othersClass;
    public $style = 'width:80%;max-width:80%;';
    public $showclosebtn = true;
    public $fields = ['prqty'];
    public $logger;
    public $issearchshow = false;



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
        $attrib = array(
            'load' => 882
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $cols = ['itemname', 'isqty', 'uom', 'serialno', 'pnp'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $cols
            ]
        ];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
        $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'label';

        if ($config['params']['companyid'] == 56) {
            // $obj[0][$this->gridname]['columns'][$pnp]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$uom]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
            $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
            $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-align: right; width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
            unset($obj[0][$this->gridname]['columns'][$pnp]);
            unset($obj[0][$this->gridname]['columns'][$serialno]);
        } else {
            $obj[0][$this->gridname]['columns'][$serialno]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$serialno]['label'] = 'Engine/Chassis#';
            $obj[0][$this->gridname]['columns'][$serialno]['type'] = 'textarea';
            $obj[0][$this->gridname]['columns'][$serialno]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:250px;';
            $obj[0][$this->gridname]['columns'][$pnp]['type'] = 'textarea';
            $obj[0][$this->gridname]['columns'][$pnp]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$pnp]['label'] = 'PNP/CSR#';
            $obj[0][$this->gridname]['columns'][$pnp]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        }
        return $obj;
    }

    public function createHeadField($config)
    {
        $col1 = [];
        $post = $this->othersClass->checkAccess($config['params']['user'], 891);
        if ($post == 1) {
            $fields = ['refresh'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'refresh.label', 'Post');
            data_set($col1, 'refresh.isclose', true);
        }

        return array('col1' => $col1);
    }

    public function paramsdata()
    {
        return [];
    }

    public function data($config)
    {
        $trno = isset($config['params']['row']['trno']) ?  $config['params']['row']['trno'] : $config['params']['sourcerow']['trno'];
        $data = $this->coreFunctions->opentable("select s.trno,s.line,s.itemid,i.itemname,
        s.isqty as  isqty, s.uom,'' as bgcolor,
        ifnull(group_concat(concat('Engine/Chassis#: ',rr.serial,'/',rr.chassis,'\\n','Color: ',rr.color) separator '\\n\\r'),'') as serialno,
        ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr) separator '\\n\\r'),'') as pnp from lastock as s left join item as i on i.itemid=s.itemid
        left join uom on uom.itemid = i.itemid and uom.uom = s.uom 
        left join serialout as rr on rr.trno = s.trno and rr.line = s.line 
        where s.trno = ? group by  s.trno,s.line,s.itemid,i.itemname,
        s.isqty, s.uom ", [$trno]);
        return $data;
    }


    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }
    public function saveandclose($config)
    {
        return ['status' => true, 'msg' => 'Posted successfully.', 'reloaddata' => true];
    } //end function

    public function tableentrystatus($config)
    {
        return [];
    }
    public function loaddata($config)
    {
        $companyid =  $config['params']['companyid'];
        $trno = $config['params']['rows'][0]['trno'];
        $config['params']['trno'] = $trno;
        $msg = '';
        $status = true;
        switch ($companyid) {
            case 40: //cdo
                $path = 'App\Http\Classes\modules\cdo\st';
                break;
            default:
                $path = 'App\Http\Classes\modules\issuance\st';
                break;
        }

        $return = app($path)->posttrans($config);
        if (!$return['status']) {
            return ['status' => false, 'msg' => 'Posting failed. ' . $return['msg'], 'reloaddata' => true];
        } else {
            $ret =  $this->othersClass->generateShortcutTransaction($config, '', 'STJV', 'JVDR');
        }

        $wh = $this->companysetup->getwh($config['params']);
        $qry = "select head.trno, head.docno, date(head.dateid) as dateid, d.clientname as loc2, wh.clientname as loc,'tableentries/tableentry/postingst' as url,head.rem        
        from lahead as head left join cntnum on cntnum.trno=head.trno 
        left join client as wh on wh.client=head.wh left join client as d on d.client = head.client
        where head.doc='ST' 
        and head.client ='" . $wh . "' and head.lockdate is not null";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Posted successfully', 'tableentrydata' => $data];
    }
} //end class
