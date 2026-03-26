<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entrycanvasssummary
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CANVASS SUMMARY';
    public $gridname = 'inventory';
    private $companysetup;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'cdhead';
    public $hhead = 'hcdhead';
    public $stock = 'cdstock';
    public $hstock = 'hcdstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $style = 'width:100%;';
    private $coreFunctions;
    private $othersClass;
    private $fields = ['rem', 'isprefer'];
    public $showclosebtn = false;
    public $showfilteroption = true;
    public $showfilter = true;
    // public $showcreatebtn = true;
    public $logger;

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
            'load' => 4627,
            'view' => 4628

        );
        return $attrib;
    }

    public function createTab($config)
    {


        $columns = ['action', 'isprefer', 'ctrlno', 'itemdesc', 'specs', 'rrqty', 'uom', 'unit', 'clientname', 'docno', 'ext', 'rem'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = ['viewcddetail', 'save', 'jumpmodule'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$clientname]['align'] = "left";
        $obj[0][$this->gridname]['columns'][$specs]['align'] = "left";
        $obj[0][$this->gridname]['columns'][$itemdesc]['align'] = "left";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$specs]['style'] = "width:100px;whiteSpace: normal;min-width:100px;word-break:break-word;";
        $obj[0][$this->gridname]['columns'][$rrqty]['style'] = "text-align:right;width:60px;whiteSpace: normal;min-width:60px;";
        $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right; width: 120px;whiteSpace: normal;min-width:120px;max-width:120px';

        $obj[0][$this->gridname]['columns'][$rem]['label'] = "Notes";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = "Item name";
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$specs]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$ext]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rrqty]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rrqty]['align'] = "right";

        $obj[0][$this->gridname]['columns'][$ctrlno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$unit]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$unit]['label'] = 'Unit Price';
        $obj[0][$this->gridname]['columns'][$unit]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[0]['label'] = 'REFRESH';
        $obj[0]['icon'] = 'refresh';
        return $obj;
    }

    public function saveallentry($config)
    {
        $returndata = $this->loaddata($config);

        return ['status' => true, 'msg' => 'Data fetched.', 'data' => $returndata];
    }

    private function selectqry($config)
    {
        $qry = " head.docno,head.clientname,left(head.dateid,10) as dateid,stock.trno ,stock.line,stock.uom,stock.editdate,stock.editby,
              stat.status as status,ifnull(info.itemdesc,'') as itemdesc,info.ctrlno,ifnull(info.unit,'') as unit,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
              FORMAT(stock.ext,2) as ext,stock.rem,ifnull(info.specs,'') as specs,
              case when stock.isprefer=0 then 'false' else 'true' end as isprefer,'' as bgcolor,'CD' as doc, '/module/ati/' as url,'module' as moduletype";
        return $qry;
    }
    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['editby'] = $config['params']['user'];
        $data['editdate'] =  $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcupdate($this->stock, $data, ['line' => $row['line'], 'trno' => $row['trno']]);
        $returnrow = $this->loaddataperrecord($config, $row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } //end function

    private function loaddataperrecord($config, $trno, $line)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry($config);
        $query = "select " . $select . " 
              from cdhead as head
              left join cdstock as stock on stock.trno = head.trno
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join transnum as num on num.trno=head.trno
              left join trxstatus as stat on stat.line=num.statid
              where head.doc= 'cd' and num.center = $center and num.statid=45 and stock.trno = ? and stock.line = ?";
        $data = $this->coreFunctions->opentable($query, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry($config);
        $query = "select " . $select . " 
              from cdhead as head
              left join cdstock as stock on stock.trno = head.trno
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join transnum as num on num.trno=head.trno
              left join trxstatus as stat on stat.line=num.statid
              where head.doc= 'cd' and num.center= $center and num.statid=45 
              order by info.ctrlno, head.docno";
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        $returndata = [];

        foreach ($row  as $key2 => $value) {
            $config['params']['row']['appid'] = $row[$key2]['appid'];
            $config['params']['row']['deptid'] = $row[$key2]['clientid'];
            $config['params']['row']['department'] = $row[$key2]['clientname'];
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            }
        }
        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
    } // end function
    public function lookuplogs($config)
    {
        $doc = strtoupper($config['params']['lookupclass']);
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        // $trno = $config['params']['tableid'];
        $trno = strtoupper($config['params']['row']['line']);

        $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where  trno = $trno
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where trno = $trno ";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
