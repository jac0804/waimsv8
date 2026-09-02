<?php

namespace App\Http\Classes\modules\pos;

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
use App\Http\Classes\sbcscript\sbcscript;
use Exception;

class posregistration
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'POS Registration';
    public $gridname = 'entrygrid';
    public $head = '';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $fields = [];
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;
    private $scbscript;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->scbscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5940
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $columns = ['station', 'othcode', 'serial', 'remarks', 'others', 'createby', 'createdate'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['label'] = 'DETAILS';
        $obj[0][$this->gridname]['descriptionrow'] = [];

        $obj[0][$this->gridname]['columns'][$station]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$serial]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$others]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$createby]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$othcode]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$othcode]['label'] = "Access Limit";
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = "Reg. Code / Access Key";
        $obj[0][$this->gridname]['columns'][$others]['label'] = "Notes";

        $obj[0][$this->gridname]['columns'][$station]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
        $obj[0][$this->gridname]['columns'][$serial]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
        $obj[0][$this->gridname]['columns'][$othcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        return $obj;
    }

    public function createHeadbutton($config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {

        $fields = ['divname', 'docno', 'yourref', 'create'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'yourref.type', 'input');
        data_set($col1, 'yourref.label', 'PO #');
        data_set($col1, 'create.label', 'SHOW REGISTRATIONS');

        data_set($col1, 'docno.addedparams', ['divname']);
        data_set($col1, 'docno.type', 'input');
        data_set($col1, 'docno.readonly', true);
        data_set($col1, 'docno.lookupclass', 'lookupsjpo');
        data_set($col1, 'docno.action', 'lookupsjpo');

        data_set($col1, 'divname.label', 'Provider');
        data_set($col1, 'divname.type', 'qselect');

        data_set($col1, 'create.action', 'search');

        $centers = [];
        $center = $this->coreFunctions->opentable("select code, name from center order by name");
        foreach ($center as $key => $value) {
            array_push($centers, ['label' => $value->name, 'value' => $value->code]);
        }
        data_set($col1, 'divname.options', $centers);

        $fields = [['company', 'regcode'], ['crtype', 'type'], 'specs', ['branch', 'station'], ['serialno', 'licenseno'], 'rem', 'refresh'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'regcode.label', 'Registration Key');
        data_set($col2, 'licenseno.label', 'Access Key');
        data_set($col2, 'type.label', 'Serial Type');
        data_set($col2, 'crtype.label', 'Access Limit');
        data_set($col2, 'specs.label', 'Product');
        data_set($col2, 'refresh.label', 'REGISTER');

        data_set($col2, 'company.type', 'input');
        data_set($col2, 'serialno.type', 'input');
        data_set($col2, 'station.type', 'input');
        data_set($col2, 'type.type', 'qselect');
        data_set($col2, 'crtype.type', 'qselect');

        data_set($col2, 'specs.type', 'qselect');

        data_set($col2, 'branch.required', true);
        data_set($col2, 'station.required', true);

        $types = [
            ['label' => 'Harddisk', 'value' => 'Harddisk'],
            ['label' => 'Volume', 'value' => 'Volume']
        ];
        data_set($col2, 'type.options', $types);

        $crtypes = [
            ['label' => 'DEMO', 'value' => 'DEMO'],
            ['label' => 'LICENSED', 'value' => 'LICENSED']
        ];
        data_set($col2, 'crtype.options', $crtypes);

        $items = [];
        data_set($col2, 'specs.options', $items);

        data_set($col2, 'branch.readonly', false);
        data_set($col2, 'station.readonly', false);
        data_set($col2, 'rem.readonly', false);
        data_set($col2, 'licenseno.readonly', true);

        data_set($col2, 'refresh.style', 'width:100%');
        data_set($col2, 'refresh.action', 'register');

        return array('col1' => $col1, 'col2' => $col2);
    }


    public function data($config)
    {
        return $this->paramsdata($config);
    }



    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("
      select
      0 as trno, 
      '' as docno,
      '' as yourref,
      '' as divname,
      '' as company,
      '' as regcode,
      '' as specs,
      '' as branch,
      '' as station,
      '' as serialno,
      '' as licenseno,
      '' as crtype,
      '' as type,
      '' as rem,
      '' as itemharddisk,
      '' as itemvolume
    ");
        if (!empty($data)) {

            $items = [];
            for ($i = 1; $i <= 41; $i++) {
                $itemName = $this->GetProduct($i, true, true);
                array_push($items, ['label' => $itemName['name'], 'value' => $itemName['id']]);
            }
            $data[0]->itemharddisk = $items;


            $items2 = [];
            for ($i = 1; $i <= 14; $i++) {
                $itemName = $this->GetOldProduct($i, true, true);
                array_push($items2, ['label' => $itemName['name'], 'value' => $itemName['id']]);
            }
            $data[0]->itemvolume = $items2;

            return $data[0];
        } else {
            return [];
        }
    }


    public function headtablestatus($config)
    {
        // should return action
        $action = $config['params']["action2"];


        switch ($action) {
            case "register":
                return $this->register($config);
                break;

            case 'search':
                return $this->loaddetails($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }


    //===================================
    // start of registration
    //===================================
    public function register($config)
    {

        try {
            $regcode = $config['params']["dataparams"]["regcode"];
            $company = $config['params']["dataparams"]["company"];
            $itemname = $config['params']["dataparams"]["specs"];
            $license = $config['params']["dataparams"]["crtype"];
            $divname = $config['params']["dataparams"]["divname"];
            $serialType = $config['params']["dataparams"]["type"];
            $trno = $config['params']["dataparams"]["trno"];
            $branch = $config['params']["dataparams"]["branch"];
            $station = $config['params']["dataparams"]["station"];
            $remarks = $config['params']["dataparams"]["rem"];
            $licenseno = $config['params']["dataparams"]["licenseno"];

            if ($regcode == '') return ['status' => false,  'msg' => 'Invalid registration code'];
            if ($company == '') return ['status' => false,  'msg' => 'Invalid company'];

            if (!isset($license['value'])) return ['status' => false,  'msg' => 'Invalid access limit'];
            if (!isset($serialType['value'])) return ['status' => false,  'msg' => 'Invalid serial type'];
            if (!isset($divname['value'])) return ['status' => false,  'msg' => 'Select valid System provider'];

            if ($serialType['value'] == '') return ['status' => false,  'msg' => 'Invalid serial type'];
            if ($license['value'] == '') return ['status' => false,  'msg' => 'Invalid access limit'];

            if ($license['value'] == 'LICENSED' && $trno == 0) return ['status' => false,  'msg' => 'Please select valid SJ# to assign this registration.'];

            $strReg = $regcode; // or wherever the text value comes from, e.g. $_POST['reg']
            $arr = explode("~", $regcode);
            if (count($arr) > 1) {
                $strReg = $arr[1];
            }
            $productID = $itemname['value'];

            $pYear = 0;
            $pMonth = 1;

            if ($license['value'] == 'LICENSED') {
                $pYear = 4;
                $pMonth = 0;
            }

            $isdemo = ($license['value'] == 'DEMO');
            $ishd = ($serialType['value'] == 'Harddisk');

            $serialNo = $this->GetHDSerialFromEncrypted($strReg, 37, $company);

            $accessKey = $this->CreatePass($company, $pYear, $pMonth, $productID, $strReg, 37);

            $config['params']['dataparams']['licenseno'] = $accessKey;
            $config['params']['dataparams']['serialno'] = $serialNo;

            $data = [
                'trno' => $trno,
                'station' => $station,
                'branch' => $branch,
                'regcode' => $company . ":" . $regcode,
                'serialno' => $serialNo,
                'isdemo' => $isdemo,
                'accesskey' => $accessKey,
                'ishd' => $ishd,
                'rem' => $remarks,
                'productid' => $productID,
                'center' => $divname['value'],
                'createby' => $config['params']["user"],
                'createdate' => $this->othersClass->getCurrentTimeStamp()
            ];

            $this->coreFunctions->sbcinsert("posreg", $data);

            $datagrid = $this->getSerialRegistration($config);

            return ['status' => true, 'action' => 'load', 'msg' => 'Access key generated', 'data' => $config['params']['dataparams'], 'griddata' => ['entrygrid' => $datagrid]];
        } catch (Exception $ex) {
            return ['status' => true, 'msg' => $ex->getMessage()];
        }
    }

    function BaseXDec($BaseX, $BaseNumber = 32)
    {
        static $LegalNumbers = null;

        if ($LegalNumbers === null || $LegalNumbers === "") {
            $LegalNumbers = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>";
        }

        if ($BaseNumber > 64) {
            $arrTemp = [32, 126, 161, 255];
            for ($j = 0; $j <= 1; $j++) {
                $nLow  = $arrTemp[$j * 2];
                $nHigh = $arrTemp[$j * 2 + 1];
                for ($i = $nLow; $i <= $nHigh; $i++) {
                    $ch = chr($i);
                    if (strpos($LegalNumbers, $ch) === false) {
                        $LegalNumbers .= $ch;
                    }
                }
            }
        }

        $NumLength = strlen($BaseX);
        $Number = 0;

        for ($i = $NumLength; $i >= 1; $i--) {
            $char = substr($BaseX, $NumLength - $i, 1);
            $pos = strpos($LegalNumbers, $char);
            // InStr returns 1-based index, 0 if not found -> DecValue = InStr - 1
            $DecValue = ($pos === false) ? -1 : $pos; // strpos is already 0-based, equivalent to (1-based - 1)
            $Number = $Number + ($DecValue * ($BaseNumber ** ($i - 1)));
        }

        // if ($Number == 0) $Number = 1; // left commented, same as original

        return $Number;
    }

    function StringCut($ItemToTranspose, $StartPos = 1)
    {
        // Transpose a portion of string from left to right
        // eg. StringCut("The quick brown fox", 5)
        //     returns "uick brown foxThe "
        $LeftSide  = substr($ItemToTranspose, 0, $StartPos);
        $RightSide = substr($ItemToTranspose, $StartPos);

        return $RightSide . $LeftSide;
    }


    function BaseX($Base10, $BaseNumber = 32)
    {
        static $LegalNumbers = null;

        if ($LegalNumbers === null || $LegalNumbers === "") {
            $LegalNumbers = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>";
        }

        if ($BaseNumber > 64) {
            $arrTemp = [32, 126, 161, 255];
            for ($j = 0; $j <= 1; $j++) {
                $nLow  = $arrTemp[$j * 2];
                $nHigh = $arrTemp[$j * 2 + 1];
                for ($i = $nLow; $i <= $nHigh; $i++) {
                    $ch = chr($i);
                    if (strpos($LegalNumbers, $ch) === false) {
                        $LegalNumbers .= $ch;
                    }
                }
            }
        }

        // If TypeName(Base10) = "String" Then Base10 = Val(Base10)
        if (is_string($Base10)) {
            // Val() reads a leading numeric portion and returns 0 if none found
            $Base10 = is_numeric(trim($Base10)) ? (float) $Base10 : 0;
        }

        $i = 0;
        do {
            $i++;
            $High = $BaseNumber ** $i;
        } while (!($High > $Base10));
        $i--;

        $Number = "";
        for ($j = $i; $j >= 0; $j--) {
            $Quotient = (int) ($Base10 / ($BaseNumber ** $j)); // Fix() truncates toward zero, same as PHP int cast for non-negatives
            $Number .= substr($LegalNumbers, $Quotient, 1);    // Mid(LegalNumbers, Quotient + 1, 1) -> 0-based: Quotient
            $Base10 = $Base10 - ($Quotient * ($BaseNumber ** $j));
        }

        return $Number;
    }

    function GetHDSerialFromEncrypted($VolSerial, $BaseNo, $Company)
    {
        $RandomNum = substr($VolSerial, 0, 1);
        $Offset = $this->BaseXDec($RandomNum, $BaseNo);
        $VolSerialNum = substr($VolSerial, 1);

        // $qe = QuikEncrypt($VolSerialNum, $Company, $Offset);
        // return substr($qe, 1);

        return $this->QuikEncrypt($VolSerialNum, $Company, $Offset);
    }

    function QuikEncrypt(
        $ToChange,
        $Seed = "SolutionBase Corp.",
        $Offset = 0,
        $LegalChr = "0A1B2C3D4E5F6G7H8I9JKLMNOPQRSTUVWXYZ",
        $Direction = 0,
        $Polarity = 0
    ) {
        // QuikEncrypt() --> cEncrypted/cDecrypted
        // to encrypt/decrypt a password.
        // Where : ToChange  - string to be decrypted
        //         Seed      - string that decrypts
        //         Offset    - number from 0 to 62
        //         LegalChr  - 64 unique typeable characters
        //         Direction - 0 (from left to right of LegalChr)
        //                     1 (from right to left of     "   )
        //         Polarity  - 0 (Encrypt Mode)
        //                     1 (Decrypt Mode)

        $Crypted = "";

        try {
            $LegalChr = $this->StringCut($LegalChr, $Offset);
            $Length   = strlen($ToChange);
            $LenSeed  = strlen($Seed);
            $LenLegal = strlen($LegalChr);

            // j = IIf(Direction = 0, 1, LenSeed) -- 1-based index into Seed
            $j = ($Direction == 0) ? 1 : $LenSeed;

            for ($i = 1; $i <= $Length; $i++) {
                $seedChar = substr($Seed, $j - 1, 1); // Mid(Seed, j, 1) -> 0-based
                $pos1based = strpos($LegalChr, $seedChar);
                $instrSeed = ($pos1based === false) ? 0 : $pos1based + 1; // emulate InStr (1-based, 0 = not found)

                $bUpDown = ($instrSeed > ($LenLegal / 2));

                $changeChar = substr($ToChange, $i - 1, 1); // Mid(ToChange, i, 1)
                $posFound = strpos($LegalChr, $changeChar);
                $Pos = ($posFound === false) ? 0 : $posFound + 1; // InStr, 1-based, 0 = not found

                if ($bUpDown) {
                    $SeedOffset = $Pos;
                } else {
                    $SeedOffset = $LenLegal - $Pos;
                }

                $Crypted .= $this->BaseX($SeedOffset, $LenLegal + 1);

                if ($Direction == 0) {
                    $j++;
                    if ($j > $LenSeed) {
                        $j = 1;
                    }
                } else {
                    $j--;
                    if ($j < 1) {
                        $j = $LenSeed;
                    }
                }
            }

            return $Crypted;
        } catch (\Throwable $e) {
            // CryptError: (VB's On Error GoTo)
            return $Crypted;
        }
    }

    function CreatePass($Client, $Years, $Months, $Product, $VolSerial, $BaseNo)
    {
        //  Offset = GetRandom(0, 63)
        //  RandomNum = Trim(BaseX(Offset, 64))
        $RandomNum = substr($VolSerial, 0, 1);
        $Offset = $this->BaseXDec($RandomNum, $BaseNo);

        $VolSerialNum = substr($VolSerial, -8); // Right(VolSerial, 8)

        $ToChange = $VolSerialNum;
        $ToChange = $ToChange
            . $this->BaseX($this->Val($Years), $BaseNo)
            . $this->BaseX($this->Val($Months), $BaseNo)
            . $this->BaseX($Product, $BaseNo);

        $ToReturn = $this->QuikEncrypt($ToChange, $Client, $Offset);

        return $ToReturn;
    }

    function Val($str)
    {
        if (is_numeric($str)) {
            return $str + 0;
        }
        // Extract leading numeric portion (matches VB's Val behavior: "123abc" -> 123)
        if (preg_match('/^\s*[-+]?(\d+\.?\d*|\.\d+)/', (string) $str, $matches)) {
            return $matches[0] + 0;
        }
        return 0;
    }

    function GetProduct($product, $blnName = false, $blnBoth = false)
    {
        static $map = [
            "1" => "RETAIL",
            "2" => "MIS LITE",
            "3" => "MIS FULL",
            "4" => "QSR",
            "5" => "FINEDINE",
            "6" => "PRICE INQUIRY",

            "7" => "AIMS",
            "8" => "FRONT OFFICE",

            "9"  => "RETAIL + LP",
            "10" => "RETAIL + LP (RFID)",
            "11" => "RETAIL + LOAD WALLET (RFID)",
            "12" => "RETAIL + LOAD WALLET + CASHLESS (RFID)",
            "13" => "RETAIL + LP + LOAD WALLET (RFID)",
            "14" => "RETAIL + LP + LOAD WALLET + CASHLESS (RFID)",
            "15" => "RETAIL - OT",

            "16" => "QSR + LP",
            "17" => "QSR + LP (RFID)",
            "18" => "QSR + LOAD WALLET (RFID)",
            "19" => "QSR + LOAD WALLET + CASHLESS (RFID)",
            "20" => "QSR + LP + LOAD WALLET (RFID)",
            "21" => "QSR + LP + LOAD WALLET + CASHLESS (RFID)",

            "22" => "FINEDINE + LP",
            "23" => "FINEDINE + LP (RFID)",
            "24" => "FINEDINE + LOAD WALLET (RFID)",
            "25" => "FINEDINE + LOAD WALLET + CASHLESS (RFID)",
            "26" => "FINEDINE + LP + LOAD WALLET (RFID)",
            "27" => "FINEDINE + LP + LOAD WALLET + CASHLESS (RFID)",
            "28" => "FINEDINE - OT",

            "29" => "LOADING RFID",

            "30" => "QUEUING",

            "31" => "PARKING",

            "32" => "LMS",
            "33" => "DTS",

            "34" => "HMS - FO",
            "35" => "HMS - HK",
            "36" => "HMS - BO",

            "37" => "RETAIL - AIMS",
            "38" => "QSR - AIMS",
            "39" => "FINEDINE - AIMS",
            "40" => "HMS - AIMS",
            "41" => "LOAD INQUIRY",
        ];

        $productUpper = strtoupper($product);

        // Each VB "Case N, NAME" matches on either the numeric id OR the name.
        // Build a reverse lookup (NAME -> id) so we can match both directions.
        static $nameToId = null;
        if ($nameToId === null) {
            $nameToId = array_flip($map); // NAME => id
        }

        if (isset($map[$productUpper])) {
            // matched by numeric id (e.g. "1")
            $id = $productUpper;
        } elseif (isset($nameToId[$productUpper])) {
            // matched by name (e.g. "RETAIL")
            $id = $nameToId[$productUpper];
        } else {
            // Case Else : "PIRATED" / "0"
            if ($blnBoth) {
                return ["id" => "0", "name" => "PIRATED"];
            }
            return $blnName ? "PIRATED" : "0";
        }

        if ($blnBoth) {
            return ["id" => $id, "name" => $map[$id]];
        }

        return $blnName ? $map[$id] : $id;
    }

    function GetOldProduct($pProduct, $blnName = false, $blnBoth = false)
    {
        static $map = [
            "1"  => "POS",
            "2"  => "MISLITE",
            "3"  => "MISFULL",
            "4"  => "HOTEL",
            "5"  => "AIMS",
            "6"  => "MALL",
            "7"  => "PRICE INQUIRY",
            "8"  => "POS LP",
            "9"  => "POS ONLINE",
            "10" => "POS LP ONLINE",
            "11" => "PARKING",
            "12" => "PAYROLL",
            "13" => "LOBBY",
            "14" => "BARANGAY",
        ];

        $productUpper = strtoupper($pProduct);

        // Each VB "Case N, NAME" matches on either the numeric id OR the name.
        // Build a reverse lookup (NAME -> id) so we can match both directions.
        static $nameToId = null;
        if ($nameToId === null) {
            $nameToId = array_flip($map); // NAME => id
        }

        if (isset($map[$productUpper])) {
            // matched by numeric id (e.g. "1")
            $id = $productUpper;
        } elseif (isset($nameToId[$productUpper])) {
            // matched by name (e.g. "RETAIL")
            $id = $nameToId[$productUpper];
        } else {
            // Case Else : "PIRATED" / "0"
            if ($blnBoth) {
                return ["id" => "0", "name" => "PIRATED"];
            }
            return $blnName ? "PIRATED" : "0";
        }

        if ($blnBoth) {
            return ["id" => $id, "name" => $map[$id]];
        }

        return $blnName ? $map[$id] : $id;
    }

    // ==========================
    // End of registrations
    // ==========================


    public function sbcscript($config)
    {
        return $this->scbscript->posregistration($config);
    }

    private function loaddetails($config)
    {
        $data = $this->getSerialRegistration($config);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }

    private function getSerialRegistration($config)
    {
        $trno = $config['params']['dataparams']['trno'];
        $serialno = $config['params']['dataparams']['serialno'];

        $addsql = "";
        if ($serialno != "") {
            if ($trno == 0) {
                $addsql = "union all select trno, line, createby, createdate, station, serialno as serila, concat('RegKey: ', regcode,' | AccessKey: ',accesskey) as remarks, rem as others, if(isdemo=1,'DEMO','LICENSED') as othcode 
                from posreg where serialno='" . $serialno . "' and trno=0";
            }
        }

        $data = $this->coreFunctions->opentable("select trno, line, createby, createdate, station, serial, remarks, others, '' as othcode from particulars where trno=" . $trno . "
                                                union all
                                                select trno, line, createby, createdate, station, serial, remarks, others, '' as othcode from hparticulars where trno=" . $trno . " 
                                                union all
                                                select trno, line, createby, createdate, station, serialno as serila, concat('RegKey: ', regcode,' | AccessKey: ',accesskey) as remarks, rem as others, if(isdemo=1,'DEMO','LICENSED') as othcode 
                                                from posreg where trno<>0 and trno=" . $trno . "
                                                $addsql
                                                order by createdate desc");
        return $data;
    }
}
