<?php

namespace App\Http\Classes\modules\hrisentry;

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

class generationmaster
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'GENERATION MASTER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'generation';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['generation', 'startyear', 'endyear'];
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
            'load' => 5225
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $columns = ['action', 'generation', 'startyear', 'endyear'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$generation]['style'] = "width:400px;whiteSpace: normal;min-width:500px;";
        $obj[0][$this->gridname]['columns'][$startyear]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$endyear]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

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
        $data['generation'] = '';
        $data['startyear'] = '';
        $data['endyear'] = '';
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

        if ($row['line'] == 0) {
            $qry = "select generation as value from " . $this->table . " where generation = '" . $data['generation'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if (!empty($checking)) {
                return ['status' => false, 'msg' => 'Generation already exist. - ' . $data['generation']];
            }

            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE' . ' - ' . $data['generation'] . ' : ' . $data['startyear'] . ' - ' . $data['endyear']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $qry = "select generation as value from " . $this->table . " where generation = '" . $data['generation'] . "' and line = '" . $row['line'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if (!empty($checking)) {
                unset($data["generation"]);
            } else {
                $qry = "select generation as value from " . $this->table . " where generation = '" . $data['generation'] . "'";
                $checking1 = $this->coreFunctions->datareader($qry);

                if (!empty($checking1)) {
                    $returndata = $this->loaddata($config);
                    return ['status' => false, 'msg' => 'Generation already exist. - ' . $data['generation'], 'data' => $data];
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

                if ($data[$key]['line'] == 0) {
                    $qry = "select generation as value from " . $this->table . " where generation = '" . $data[$key]['generation'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    if (!empty($checking)) {
                        // $returndata = $this->loaddata($config);
                        return ['status' => false, 'msg' => 'Generation already exist. - ' . $data[$key]['generation'], 'data' => $data];
                    }

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'CREATE' . ' - ' . $data[$key]['generation'] . ' : ' . $data[$key]['startyear'] . ' - ' . $data[$key]['endyear']
                    );
                } else {
                    $qry = "select generation as value from " . $this->table . " where generation = '" . $data[$key]['generation'] . "' and line = '" . $data[$key]['line'] . "'";
                    $checking = $this->coreFunctions->datareader($qry);

                    if (!empty($checking)) {
                        unset($data2[$key]["generation"]);
                    } else {
                        $qry = "select generation as value from " . $this->table . " where generation = '" . $data[$key]['generation'] . "'";
                        $checking1 = $this->coreFunctions->datareader($qry);

                        if (!empty($checking1)) {
                            $returndata = $this->loaddata($config);
                            return ['status' => false, 'msg' => 'Generation already exist. - ' . $data[$key]['generation'], 'data' => $data];
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
        $row = $config['params']['row'];

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['generation']);
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

        $qry = "
      select trno, doc, task, dateid, user
      from " . $this->tablelogs . "
      where doc = ?
      union all 
      select trno, doc, task, dateid, user
      from " . $this->tablelogs_del . "
      where doc = ?
      order by dateid desc
    ";

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
        $query = "select line, generation, startyear,endyear from generation ";
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
        PDF::MultiCell(200, 0, "GENERATION", 'B', 'L', false, 0);
        PDF::MultiCell(100, 0, "START YEAR", 'B', 'C', false, 0);
        PDF::MultiCell(100, 0, "END YEAR", 'B', 'C', false, 1);
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
            $gen_height = PDF::GetStringHeight(200, $data[$i]['generation']);
            $syr_height = PDF::GetStringHeight(100, $data[$i]['startyear']);
            $eyr_height = PDF::GetStringHeight(100, $data[$i]['endyear']);
            $max_height = max($gen_height, $syr_height, $eyr_height);

            if ($max_height > 25) {
                $max_height = $max_height + 15;
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(200, 0, $data[$i]['generation'], '', 'L', 0, 0, '', '');
            PDF::MultiCell(100, $max_height, $data[$i]['startyear'], '', 'C', 0, 0, '', '');
            PDF::MultiCell(100, $max_height, $data[$i]['endyear'], '', 'C', 0, 1, '', '');
            if (intVal($i) + 1 == $page) {
                $this->default_layout_header($params, $data);
                $page += $count;
            }
        }

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 10);

        PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(200, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], '', 'L');


        return PDF::Output($this->modulename . '.pdf', 'S');
    }
} //end class
