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

class streetsetup
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STREET LIST SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'street';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['code', 'street'];
    public $showclosebtn = true;
    private $reporter;
    private $logger;

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
            'load' => 5278
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $columns = ['action', 'code', 'street'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$street]['style'] = "width:750px;whiteSpace: normal;min-width:750px;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'print', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['code'] = '';
        $data['street'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if (trim($row['code'] == '')) {
            return ['status' => false, 'msg' => 'Street code is empty.'];
        }

        if (trim($row['street'] == '')) {
            return ['status' => false, 'msg' => 'Street is empty.'];
        }

        if ($row['line'] == 0) {
            $qry = "select code as value from " . $this->table . " where code = '" . $data['code'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if (!empty($checking)) {
                return ['status' => false, 'msg' => 'Street code already exists. - ' . $data['code']];
            }
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE' . ' - ' . $data['code'] . ' - ' . $data['street']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $qry = "select code as value from " . $this->table . " where code = '" . $data['code'] . "' and line = '" . $row['line'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if (!empty($checking)) {
                unset($data["code"]);
            } else {
                $qry = "select code as value from " . $this->table . " where code = '" . $data['code'] . "'";
                $checking1 = $this->coreFunctions->datareader($qry);

                if (!empty($checking1)) {
                    $returndata = $this->loaddata($config);
                    return ['status' => false, 'msg' => 'Street code already exists. - ' . $data['code'], 'data' => $data];
                }
            }
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if (trim($data[$key]['code'] == '')) {
                    return ['status' => false, 'msg' => 'Street code is empty.'];
                }

                if (trim($data[$key]['street'] == '')) {
                    return ['status' => false, 'msg' => 'Street is empty.'];
                }

                if ($data[$key]['line'] == 0) {
                    $qry = "select code as value from " . $this->table . " where code = '" . $data[$key]['code'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    if (!empty($checking)) {
                        return ['status' => false, 'msg' => 'Street code already exists. - ' . $data[$key]['code'], 'data' => $data];
                    }

                    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['createby'] = $config['params']['user'];

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'CREATE' . ' - ' . $data[$key]['code'] . ' : ' . $data[$key]['street']
                    );
                } else {
                    $qry = "select code as value from " . $this->table . " where code = '" . $data[$key]['code'] . "' and line = '" . $data[$key]['line'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    if (!empty($checking)) {
                        unset($data2[$key]["code"]);
                    } else {
                        $qry = "select code as value from " . $this->table . " where code = '" . $data[$key]['code'] . "'";
                        $checking1 = $this->coreFunctions->datareader($qry);

                        if (!empty($checking1)) {
                            $returndata = $this->loaddata($config);
                            return ['status' => false, 'msg' => 'Street code already exists. - ' . $data[$key]['code'], 'data' => $data];
                        }
                    }
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['code']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }



    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];

        $cols = [
            ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']

        ];

        $qry = "select trno, doc, task, dateid, user
                from " . $this->tablelogs . "
                where doc = ?
                union all 
                select trno, doc, task, dateid, user
                from " . $this->tablelogs_del . "
                where doc = ?
                order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

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
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
        );
    }

    private function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $query = "select line, code, street from street order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportdata($params)
    {
        $data = $this->report_default_query($params);
        $str = $this->default_layout($params, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }


    private function default_layout_header($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        //$width = 800; $height = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(160, 0, "CODE", 'B', 'L', false, 0);
        PDF::MultiCell(560, 0, "STREET", 'B', 'L', false, 1);
        PDF::MultiCell(0, 0, "\n");
    }

    private function default_layout($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_layout_header($params, $data);

        for ($i = 0; $i < count($data); $i++) {
            $code_height = PDF::GetStringHeight(160, $data[$i]['code']);
            $street_height = PDF::GetStringHeight(560, $data[$i]['street']);
            $max_height = max($code_height, $street_height);

            if ($max_height > 25) {
                $max_height = $max_height + 15;
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(160, 0, $data[$i]['code'], '', 'L', 0, 0, '', '');
            PDF::MultiCell(560, $max_height, $data[$i]['street'], '', 'L', 0, 1, '', '');
            if (intVal($i) + 1 == $page) {
                $this->default_layout_header($params, $data);
                $page += $count;
            }
        }

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
} //end class
