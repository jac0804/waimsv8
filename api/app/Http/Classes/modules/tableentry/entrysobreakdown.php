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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrysobreakdown
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'SO Breakdown';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    public $tablenum = 'transnum';
    private $table = 'omso';
    private $htable = 'homso';
    private $othersClass;
    private $logger;
    public $style = 'width:100%;';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $fields = ['trno', 'line', 'soline',  'sono', 'rtno', 'qty',];
    public $showclosebtn = true;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 4187
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");

        $action = 0;
        $sono = 1;
        $rtno = 2;
        $qty = 3;

        $tab = [
            $this->gridname => [
                'gridcolumns' => [
                    'action',
                    'sono',
                    'rtno',
                    'qty'
                ]
            ]
        ];

        switch ($config['params']['doc']) {
            case 'PO':
            case 'RR':
                $stockbuttons = [];
                break;
            default:
                $stockbuttons = ['delete'];
                break;
        }


        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$sono]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";


        switch ($config['params']['doc']) {
            case 'RR':
            case 'PO':
                unset($obj[0][$this->gridname]['columns'][$action]);
                $obj[0][$this->gridname]['columns'][$sono]['type'] = "label";
                $obj[0][$this->gridname]['columns'][$rtno]['type'] = "label";
                $obj[0][$this->gridname]['columns'][$qty]['type'] = "label";
                break;
        }


        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");

        switch ($config['params']['doc']) {
            case 'PO':
            case 'RR':
                $tbuttons = [];
                break;
            default:
                $tbuttons = ['addrecord', 'saveallentry'];

                if ($isposted) {
                    $tbuttons = [];
                } else {
                }
                break;
        }



        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = 'SAVE';
        return $obj;
    }

    public function add($config)
    {

        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $soqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from omso where trno=? and line=? and rtno =''", [$trno, $line], '', true);
        $bal = $config['params']['row']['rrqty'] -  $soqty;
        if ($bal < 0) {
            $bal = 0;
        }

        $data = [];
        $data['trno'] = $trno;
        $data['line'] = $line;
        $data['soline'] = 0;
        $data['sono'] = '';
        $data['rtno'] = '';
        $data['qty'] =  $bal;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];


        switch ($config['params']['doc']) {
            case 'PO':
            case 'RR':
                if ($config['params']['doc'] == 'PO') {
                    $table = "postock";
                } else {
                    $table = "lastock";
                }
                $qry = "select so.trno, so.line, so.soline, so.sono, so.rtno, so.qty, '' as bgcolor
                        from omso as so
                        left join omstock as stock on stock.trno=so.trno and so.line=stock.line
                        left join (select trno,line,reqtrno,reqline from " . $table . " as rs ) as rr on rr.reqtrno=stock.reqtrno and rr.reqline=stock.reqline
                                    where rr.trno= " . $trno . " and rr.line = " . $line . "
                                order by soline";
                break;
            default:
                $qry = "select so.trno, so.line, so.soline, so.sono, so.rtno, so.qty,stock.reqtrno,stock.reqline, '' as bgcolor 
                        from omso as so 
                        left join omstock as stock on stock.trno=so.trno and so.line=stock.line
                        where so.trno = " . $trno . " and so.line=" . $line . "
                        order by soline";
                break;
        }

        $data = $this->coreFunctions->opentable($qry);

        return $data;
    }


    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $msg = '';

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $omqty = 0;
                if ($data[$key]['qty'] == 0) {
                    continue;
                } else {
                    $omqty = $this->coreFunctions->datareader("select rrqty as value from omstock where trno=? and line=?", [$trno, $line], '', true);
                    $soqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from omso where trno=? and line =? and soline <> ? and rtno = ''", [$trno, $line, $data[$key]['soline']], '', true);

                    if (($soqty + $data[$key]['qty']) > $omqty) {
                        $msg .= 'Unable to add SO#, qty exceed.';
                        continue;
                    }
                }

                if ($data[$key]['soline'] == 0) {
                    $qry = "select soline as value from omso where trno=? and line=? order by soline desc limit 1";
                    $line = $this->coreFunctions->datareader($qry, [$trno, $line]);
                    if ($line == '') {
                        $line = 0;
                    }
                    $line = $line + 1;
                    $data2['soline'] = $line;
                    $this->coreFunctions->sbcinsert($this->table, $data2);

                    $reqdata = $this->coreFunctions->opentable('select reqtrno,reqline from omstock where trno=?', [$trno]);

                    foreach ($reqdata as $key => $value) {
                        $osiref = $this->coreFunctions->datareader(
                            "select group_concat(docno,'\r (',sono,')') as value
                        from (select concat(h.docno,' - Draft') as docno, ifnull(group_concat(so.sono),'') as sono
                              from omstock as s 
                              left join omso as so on so.trno=s.trno and so.line=s.line 
                              left join omhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno
                              union all
                              select concat(h.docno,' - Posted') as docno, ifnull(group_concat(so.sono),'') as sono 
                              from homstock as s 
                              left join homso as so on so.trno=s.trno and so.line=s.line 
                              left join homhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno) as so",
                            [$reqdata[$key]->reqtrno, $reqdata[$key]->reqline, $reqdata[$key]->reqtrno, $reqdata[$key]->reqline]
                        );
                        $this->coreFunctions->execqry("update hstockinfotrans set osiref2='" . $osiref . "'  where trno=" . $reqdata[$key]->reqtrno . " and line=" . $reqdata[$key]->reqline);
                    }

                    if (isset($data[$key]['sono'])) $this->logger->sbcwritelog($trno, $config, 'ADD STOCK', "SO # => " . $data[$key]['sono']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line'], 'soline' => $data[$key]['soline']]);

                    $item = $this->coreFunctions->opentable("select info.itemdesc
                                    from omstock as s
                                    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                                    where s.trno=? and s.line=?", [$data[$key]['trno'], $data[$key]['line']]);
                    $item2 = json_decode(json_encode($item), true);

                    if ($data[$key]['rtno'] != '') {
                        $this->logger->sbcwritelog($trno, $config, "UPDATE STOCK", $item2[0]['itemdesc'] . " SO # => " . $data[$key]['sono'] . " ; RT # => " . $data[$key]['rtno']);
                    } else {
                        $this->logger->sbcwritelog($trno, $config, "UPDATE STOCK", $item2[0]['itemdesc'] . " SO # => " . $data[$key]['sono']);
                    }
                }
            } // end if
        } // foreach

        $returndata = $this->loaddata($config);
        if ($msg == '') {
            $msg == 'All saved successfully.';
        }

        return ['status' => true, 'msg' => $msg, 'data' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from omso where trno=? and line=? and soline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['soline']]);

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
}
