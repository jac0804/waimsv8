<?php

namespace App\Http\Classes\modules\announcemententry;

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
use App\Http\Classes\builder\lookupclass;
use Illuminate\Support\Facades\Storage;

class payrollattachments
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ATTACHMENTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'leave_picture';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['title'];
    public $showclosebtn = true;
    private $logger;
    private $lookupclass;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->lookupclass = new lookupclass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 1730
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $columns = ['action', 'ext', 'title'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];


        $stockbuttons = ['view', 'download', 'delete'];

        foreach ($stockbuttons as $key2 => $value2) {
            $$value2 = $key2;
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
        $obj[0][$this->gridname]['columns'][$action]['btns']['view']['action'] = 'viewfile';
        $obj[0][$this->gridname]['columns'][$title]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$ext]['label'] = 'FileType';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $addattachment = $this->othersClass->checkAccess($config['params']['user'], 5471);
        $tbuttons = ['adddocument'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = 'adddocument';
        $obj[0]['lookupclass'] = ['table' => 'leave_picture', 'field' => 'picture', 'fieldid' => 'line', 'folder' => 'payroll_attachments', 'trno' => $config['params']['row']['trno'], 'tmline' => $config['params']['row']['line']];


        $obj[0]['label'] = 'Add Attachment';
        return $obj;
    }

    public function add($config)
    {
        $line = 0;
        if ($line == 0) {
            $line = $config['params']['sourcerow']['line'];
        }
        $id = $config['params']['tableid'];

        $data = [];
        $data['trno'] = $id;
        $data['line'] = 0;
        $data['ltline'] = $line;
        $data['ext'] = '';
        $data['title'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        return [];
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $mainfolder = '/images/';
        $qry = "select picture as value from " . $this->table . " where ltline=? and line=? order by line desc limit 1";
        $filename = $this->coreFunctions->datareader($qry, [$row['trno'], $row['line'], $config['params']['doc']]);
        if ($filename !== '') {
            $filename = str_replace($mainfolder, '', $filename);
            if (Storage::disk('sbcpath')->exists($filename)) {
                Storage::disk('sbcpath')->delete($filename);
            }
        }
        $qry = "delete from " . $this->table . " where ltline=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $config['params']['doc']]);
        $this->logger->sbcwritelog($row['trno'], $config, 'ATTACHMENT', 'DELETE TITLE - ' . $row['title']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($trno, $line, $doc)
    {
        return [];
    }

    public function loaddata($config)
    {

        $doc = strtolower($config['params']['doc']);
        $line = isset($config['params']['row']) ? $config['params']['row']['line'] : $config['params']['addedparams']['tmline'];
        $tableid = $config['params']['tableid'];
        $addf = " and ltline = " . $line;

        $qry = "select '$doc' as type , md5(ltline) as trno2, md5(line) as line2, ltline as trno, line, title, picture as picture, substring_index(picture,'.',-1) as ext,'' as bgcolor from " . $this->table . " where trno=?  " . $addf . " order by line";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        $data = $this->getFileTypes($data);
        return $data;
    }

    public function getFileTypes($data)
    {
        foreach ($data as $d) {
            switch ($d->ext) {
                case 'JPG':
                case 'JPEG':
                case 'PNG':
                case 'GIF':
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    $d->filetype = 'image';
                    break;
                case 'pdf':
                case 'PDF':
                    $d->filetype = 'pdf';
                    break;
                default:
                    $d->filetype = 'others';
                    break;
            }
        }
        return $data;
    }

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
                    $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($data[$key]['ltline'], $config, ' CREATE - ' . $data[$key]['name']);
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['ltline' => $data[$key]['ltline']]);
                    $this->logger->sbcmasterlog($data[$key]['ltline'], $config, ' UPDATE - ' . $data[$key]['name']);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function
} //end class
