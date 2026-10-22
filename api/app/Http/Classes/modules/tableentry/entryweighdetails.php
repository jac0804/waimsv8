<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;
use App\Http\Classes\sbcscript\sbcscript;

class entryweighdetails
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'Weight details';
    public $gridname = 'inventory';

    public $tablelogs = 'table_log';

    public $style = 'width:500px;max-width:500px;';
    public $issearchshow = true;
    public $showclosebtn = true;

    private $AllowItem = false;

    private $sbcscript;

    public function __construct($params = null)
    {
        $this->fieldClass = new txtfieldClass($params);
        $this->tabClass = new tabClass($params);;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 82
        );
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        return $this->getheaddata($trno, $config['params']['doc']);
    }

    public function getheaddata($trno, $doc)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        $this->AllowItem =  $this->coreFunctions->datareader("select item.iscatchwt as value from lastock as s left join item on item.itemid=s.itemid where s.trno=" . $trno . " and s.line=" . $line, [], '', true);

        $action = 0;
        $weight = 1;

        $column = ['action', 'weight'];
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = $this->AllowItem ? ['delete'] : [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$action]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
        $obj[0]['inventory']['columns'][$weight]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';

        $obj[0]['inventory']['columns'][$weight]['readonly'] = false;

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = $this->AllowItem ? ['addrecord', 'saveallentry'] : [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function add($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $data = [];
        $data['sline'] = 0;
        $data['trno'] =  $trno;
        $data['line']  =  $line;
        $data['weight'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $data = $this->coreFunctions->opentable("select trno, line, sline, weight, '' as bgcolor from serialin where trno=? and line=?", [$trno, $line]);
        return $data;
    }


    public function saveallentry($config)
    {
        $row = $config['params']['data'];

        foreach ($config['params']['data'] as $key => $row) {
            if ($row['bgcolor'] == 'bg-blue-2') {
                if (['trno'] == 0 || ['line'] == 0) {
                    continue;
                }

                unset($row['bgcolor']);
                $data = [
                    'trno' => $row['trno'],
                    'line' => $row['line'],
                    'weight' => $row['weight']
                ];
                if ($row['sline'] == 0) {
                    $this->coreFunctions->sbcinsert("serialin", $data);
                } else {
                    $this->coreFunctions->sbcupdate("serialin", $data, ['sline' => $row['sline']]);
                }
            }
        }
        $returnrow = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadhead' => true];
    }

    public function sbcscript($config)
    {
        return $this->sbcscript->entryweighdetails($config);
    }
}
